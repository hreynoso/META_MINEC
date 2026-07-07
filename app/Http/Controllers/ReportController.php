<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Kpi;
use App\Models\Project;
use App\Support\Branding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    private const STATUS_LABEL = [
        'planificado' => 'Planificado',
        'en_ejecucion' => 'En ejecución',
        'en_riesgo' => 'En riesgo',
        'retrasado' => 'Retrasado',
        'completado' => 'Completado',
    ];

    private const RISK_LABEL = ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];

    private const TREND_LABEL = ['up' => 'Al alza', 'down' => 'A la baja', 'flat' => 'Estable'];

    /** Catálogo de reportes disponibles. */
    private function catalog(): array
    {
        return [
            ['slug' => 'ejecucion-presupuestaria', 'title' => 'Ejecución presupuestaria consolidada', 'description' => 'Comparativo por institución del monto presupuestado vs. ejecutado.', 'format' => 'pdf'],
            ['slug' => 'proyectos-por-meta', 'title' => 'Proyectos por meta presidencial', 'description' => 'Matriz de alineación estratégica de la cartera.', 'format' => 'xlsx'],
            ['slug' => 'riesgos-alertas', 'title' => 'Reporte de riesgos y alertas', 'description' => 'Proyectos con desvíos de cronograma o riesgo alto.', 'format' => 'pdf'],
            ['slug' => 'impacto-beneficiarios', 'title' => 'Impacto y beneficiarios', 'description' => 'Población beneficiaria por región e institución.', 'format' => 'xlsx'],
            ['slug' => 'avance-fisico-financiero', 'title' => 'Avance físico y financiero', 'description' => 'Curva S por proyecto y agregado institucional.', 'format' => 'pdf'],
            ['slug' => 'cumplimiento-kpis', 'title' => 'Cumplimiento de KPIs', 'description' => 'Semáforo trimestral de indicadores estratégicos.', 'format' => 'xlsx'],
        ];
    }

    public function index(): Response
    {
        $projects = Project::all();
        $budget = (float) $projects->sum('budget');
        $executed = (float) $projects->sum('executed');

        $summary = [
            'budget' => $budget,
            'executed' => $executed,
            'executed_pct' => $budget > 0 ? (int) round($executed / $budget * 100) : 0,
            'institutions' => Institution::has('projects')->count(),
            'beneficiaries' => (int) $projects->sum('beneficiaries'),
        ];

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
            'byInstitution' => $this->institutionExecution(),
            'reports' => $this->catalog(),
        ]);
    }

    public function preview(string $report): HttpResponse|StreamedResponse
    {
        $def = $this->find($report);
        $data = $this->build($report);

        if ($def['format'] === 'pdf') {
            return Pdf::loadView('reports.table', $data)->setPaper('a4', 'landscape')->stream($report.'.pdf');
        }

        // XLSX: la vista previa se muestra como página HTML.
        return response()->view('reports.table', $data);
    }

    public function download(string $report): HttpResponse|StreamedResponse
    {
        $def = $this->find($report);
        $data = $this->build($report);

        if ($def['format'] === 'pdf') {
            return Pdf::loadView('reports.table', $data)->setPaper('a4', 'landscape')->download($report.'.pdf');
        }

        return $this->xlsx($report, $data);
    }

    /** Exporta la tabla "Ejecución por institución" a XLSX. */
    public function institutionExport(): StreamedResponse
    {
        $rows = collect($this->institutionExecution())->map(fn (array $i) => [
            $i['short_name'].' — '.$i['name'],
            $i['projects_count'],
            $i['budget'],
            $i['executed'],
            $i['pct'].'%',
        ])->all();

        return \App\Support\SheetExport::stream(
            'ejecucion-por-institucion',
            ['Institución', 'Proyectos', 'Presupuesto', 'Ejecutado', '%'],
            $rows,
        );
    }

    /** Definición del reporte o 404 si no existe. */
    private function find(string $report): array
    {
        $def = collect($this->catalog())->firstWhere('slug', $report);
        abort_unless($def, 404);

        return $def;
    }

    /** @return array{code:string,name:string,short_name:string,projects_count:int,budget:float,executed:float,pct:int}[] */
    private function institutionExecution(): array
    {
        return Institution::with('projects:id,institution_id,budget,executed')
            ->orderBy('name')
            ->get()
            ->map(function (Institution $i) {
                $budget = (float) $i->projects->sum('budget');
                $executed = (float) $i->projects->sum('executed');

                return [
                    'code' => $i->code,
                    'name' => $i->name,
                    'short_name' => $i->short_name,
                    'projects_count' => $i->projects->count(),
                    'budget' => $budget,
                    'executed' => $executed,
                    'pct' => $budget > 0 ? (int) round($executed / $budget * 100) : 0,
                ];
            })
            ->filter(fn (array $i) => $i['projects_count'] > 0)
            ->values()
            ->all();
    }

    /** Construye título + columnas + filas del reporte. */
    private function build(string $report): array
    {
        $def = $this->find($report);
        $body = match ($report) {
            'ejecucion-presupuestaria' => $this->rEjecucion(),
            'proyectos-por-meta' => $this->rProyectosPorMeta(),
            'riesgos-alertas' => $this->rRiesgos(),
            'impacto-beneficiarios' => $this->rImpacto(),
            'avance-fisico-financiero' => $this->rAvance(),
            'cumplimiento-kpis' => $this->rKpis(),
        };

        return [
            'title' => $def['title'],
            'subtitle' => $def['description'],
            'generated_at' => now()->format('d/m/Y H:i'),
            'logo' => $this->logoDataUri(),
            'institution' => config('branding.institution'),
            'columns' => $body['columns'],
            'rows' => $body['rows'],
        ];
    }

    /** Logo del login como data URI (para incrustarlo en el PDF). Null si no hay. */
    private function logoDataUri(): ?string
    {
        $path = Branding::path('logo_login');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function money(float|int|null $v): string
    {
        return '$'.number_format((float) $v, 0);
    }

    private function rEjecucion(): array
    {
        $rows = collect($this->institutionExecution())->map(fn (array $i) => [
            $i['short_name'].' — '.$i['name'],
            (string) $i['projects_count'],
            $this->money($i['budget']),
            $this->money($i['executed']),
            $i['pct'].'%',
        ])->all();

        return ['columns' => ['Institución', 'Proyectos', 'Presupuesto', 'Ejecutado', '% Ejec.'], 'rows' => $rows];
    }

    private function rProyectosPorMeta(): array
    {
        $rows = Project::with(['institution', 'presidentialGoal'])
            ->get()
            ->sortBy(fn (Project $p) => ($p->presidentialGoal?->name ?? 'zzz').$p->code)
            ->map(fn (Project $p) => [
                $p->presidentialGoal?->name ?? 'Sin meta asignada',
                $p->code,
                $p->name,
                $p->institution?->short_name ?? '',
                self::STATUS_LABEL[$p->status] ?? $p->status,
                $this->money($p->budget),
                $p->physical_progress.'%',
            ])->values()->all();

        return ['columns' => ['Meta presidencial', 'Código', 'Proyecto', 'Institución', 'Estado', 'Presupuesto', 'Avance físico'], 'rows' => $rows];
    }

    private function rRiesgos(): array
    {
        $rows = Project::with('institution')
            ->where(fn ($q) => $q->where('risk_level', 'alto')->orWhereIn('status', ['en_riesgo', 'retrasado']))
            ->orderBy('code')
            ->get()
            ->map(fn (Project $p) => [
                $p->code,
                $p->name,
                $p->institution?->short_name ?? '',
                self::STATUS_LABEL[$p->status] ?? $p->status,
                self::RISK_LABEL[$p->risk_level] ?? $p->risk_level,
                $p->physical_progress.'%',
                $p->responsible ?? '—',
            ])->all();

        return ['columns' => ['Código', 'Proyecto', 'Institución', 'Estado', 'Riesgo', 'Avance físico', 'Responsable'], 'rows' => $rows];
    }

    private function rImpacto(): array
    {
        $rows = Project::with('institution')
            ->orderBy('institution_id')
            ->orderBy('code')
            ->get()
            ->map(fn (Project $p) => [
                $p->institution?->short_name ?? '',
                $p->location ?? 'Nacional',
                $p->name,
                number_format((int) $p->beneficiaries),
            ])->all();

        return ['columns' => ['Institución', 'Ubicación', 'Proyecto', 'Beneficiarios'], 'rows' => $rows];
    }

    private function rAvance(): array
    {
        $rows = Project::with('institution')
            ->orderBy('code')
            ->get()
            ->map(function (Project $p) {
                $fin = $p->budget > 0 ? (int) round($p->executed / $p->budget * 100) : 0;

                return [
                    $p->code,
                    $p->name,
                    $p->institution?->short_name ?? '',
                    $p->physical_progress.'%',
                    $this->money($p->budget),
                    $this->money($p->executed),
                    $fin.'%',
                ];
            })->all();

        return ['columns' => ['Código', 'Proyecto', 'Institución', 'Avance físico', 'Presupuesto', 'Ejecutado', 'Avance financiero'], 'rows' => $rows];
    }

    private function rKpis(): array
    {
        $rows = Kpi::orderBy('sort')->orderBy('label')->get()->map(function (Kpi $k) {
            $ach = $k->target > 0 ? (int) round($k->value / $k->target * 100) : 0;

            return [
                $k->label,
                number_format((float) $k->value, 2),
                $k->unit ?? '',
                number_format((float) $k->target, 2),
                $ach.'%',
                self::TREND_LABEL[$k->trend] ?? $k->trend,
                $k->strategic ? 'Sí' : 'No',
            ];
        })->all();

        return ['columns' => ['Indicador', 'Valor', 'Unidad', 'Meta', 'Logro', 'Tendencia', 'Estratégico'], 'rows' => $rows];
    }

    private function xlsx(string $report, array $data): StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Encabezado institucional (OpenSpout no incrusta imágenes; va como texto).
            $writer->addRow(Row::fromValues([(string) config('branding.institution').' · Sistema META']));
            $writer->addRow(Row::fromValues([$data['title']]));
            $writer->addRow(Row::fromValues(['Generado el '.$data['generated_at']]));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues($data['columns']));

            foreach ($data['rows'] as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, $report.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
