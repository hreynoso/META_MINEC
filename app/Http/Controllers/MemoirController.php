<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MemoirGeneration;
use App\Models\PresidentialGoal;
use App\Services\Ai\AiReportService;
use App\Support\Branding;
use App\Support\ExportName;
use App\Support\WordExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MemoirController extends Controller
{
    private const STATUS_LABEL = [
        'planificado' => 'Planificado',
        'en_ejecucion' => 'En ejecución',
        'en_riesgo' => 'En riesgo',
        'retrasado' => 'Retrasado',
        'completado' => 'Completado',
    ];

    public function index(AiReportService $ai): Response
    {
        return Inertia::render('Memoirs/Index', [
            'institutions' => Institution::withCount('projects')
                ->orderBy('name')
                ->get()
                ->map(fn (Institution $i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'short_name' => $i->short_name,
                    'name' => $i->name,
                    'projects_count' => $i->projects_count,
                ]),
            'provider' => $ai->providerLabel(),
        ]);
    }

    public function generate(Request $request, AiReportService $ai): JsonResponse
    {
        $data = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'periodo' => ['required', 'string', 'max:100'],
        ], [], ['institution_id' => 'institución', 'periodo' => 'período']);

        if (! $ai->isConfigured()) {
            return response()->json([
                'ai' => false,
                'draft' => '',
                'message' => __('messages.ai.not_configured'),
            ]);
        }

        $institution = Institution::with(['projects' => fn ($q) => $q->orderBy('code')])->findOrFail($data['institution_id']);

        try {
            $draft = trim($ai->generate($this->prompt($institution, $data['periodo'])));

            // Trazabilidad: se guarda la memoria generada con autor y fecha.
            $record = MemoirGeneration::create([
                'institution_id' => $institution->id,
                'user_id' => $request->user()->id,
                'periodo' => $data['periodo'],
                'content' => $draft,
            ]);

            return response()->json([
                'ai' => true,
                'draft' => $draft,
                'generation' => $this->format($record->load(['user', 'institution'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ai' => false,
                'draft' => '',
                'message' => __('messages.memoir.generate_failed', ['error' => $e->getMessage()]),
            ]);
        }
    }

    /** Historial de memorias generadas, de la más reciente a la más antigua. */
    public function history(): JsonResponse
    {
        $items = MemoirGeneration::with(['user', 'institution'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (MemoirGeneration $m) => $this->format($m))
            ->all();

        return response()->json(['history' => $items]);
    }

    /** Exporta una memoria generada a PDF (logo del login + título). */
    public function report(MemoirGeneration $memoir): HttpResponse
    {
        $memoir->loadMissing('institution');

        $pdf = Pdf::loadView('reports.memoir', [
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'generated_at' => \App\Support\LocalTime::format(now()),
            'entity' => $memoir->institution?->name ?? '',
            'entity_short' => $memoir->institution?->short_name ?? '',
            'periodo' => $memoir->periodo,
            'content' => $memoir->content,
        ])->setPaper('a4');

        return $pdf->download(ExportName::make('Memoria '.($memoir->institution?->short_name ?? '').' '.$memoir->periodo, 'pdf'));
    }

    /** Exporta una memoria generada a Word (.docx). */
    public function reportDocx(MemoirGeneration $memoir): HttpResponse
    {
        $memoir->loadMissing('institution');

        $subtitle = array_values(array_filter([
            trim(($memoir->institution?->name ?? '').($memoir->institution?->short_name ? ' ('.$memoir->institution->short_name.')' : '')),
            'Período '.$memoir->periodo,
            (string) config('branding.institution').' · Sistema META',
        ]));

        return WordExport::download(
            ExportName::make('Memoria '.($memoir->institution?->short_name ?? '').' '.$memoir->periodo, 'docx'),
            'Memoria Institucional',
            $subtitle,
            $memoir->content,
        );
    }

    /**
     * @return array{id:int, institution:string, periodo:string, user:string, datetime:string|null}
     */
    private function format(MemoirGeneration $m): array
    {
        return [
            'id' => $m->id,
            'institution' => $m->institution?->short_name ?? '—',
            'periodo' => $m->periodo,
            'user' => $m->user?->name ?? 'Sistema',
            'datetime' => \App\Support\LocalTime::format($m->created_at),
        ];
    }

    private function prompt(Institution $inst, string $periodo): string
    {
        $budget = (float) $inst->projects->sum('budget');
        $executed = (float) $inst->projects->sum('executed');
        $pct = $budget > 0 ? round($executed / $budget * 100, 1) : 0;
        $beneficiaries = (int) $inst->projects->sum('beneficiaries');

        $lines = [];
        foreach ($inst->projects as $p) {
            $status = self::STATUS_LABEL[$p->status] ?? $p->status;
            $lines[] = sprintf(
                '- %s | %s | estado: %s | avance físico %d%% | presupuesto $%s | ejecutado $%s',
                $p->code,
                $p->name,
                $status,
                $p->physical_progress,
                number_format((float) $p->budget, 0),
                number_format((float) $p->executed, 0)
            );

            if ($p->expected_impact) {
                $lines[] = '   Impacto esperado: '.$p->expected_impact;
            }
        }

        $projects = implode("\n", $lines);
        $goals = PresidentialGoal::orderBy('name')->pluck('name')->implode(', ');
        $budgetFmt = number_format($budget, 0);
        $executedFmt = number_format($executed, 0);
        $beneficiariesFmt = number_format($beneficiaries);

        return <<<PROMPT
        Eres redactor institucional del Ministerio de Economía de El Salvador. Redacta un
        borrador de MEMORIA INSTITUCIONAL para {$inst->name} ({$inst->short_name}),
        correspondiente al período {$periodo}, listo para revisión editorial.

        Estructura la memoria en secciones con encabezados en formato Markdown ("## "):
        (1) Presentación institucional, (2) Gestión de proyectos, (3) Ejecución
        presupuestaria, (4) Impacto y beneficiarios, (5) Alineación con las metas
        presidenciales, y (6) Conclusiones y perspectivas. Usa **negritas** para resaltar
        cifras e ideas clave. Redacta párrafos completos y bien hilados (no listas de datos
        sueltos). Usa un tono formal, institucional y en español.

        DATOS DE LA INSTITUCIÓN (plataforma META):
        - Proyectos ({$inst->projects->count()}):
        {$projects}
        - Presupuesto agregado: \${$budgetFmt}
        - Ejecutado: \${$executedFmt} ({$pct}%)
        - Beneficiarios directos: {$beneficiariesFmt}
        - Metas presidenciales de referencia: {$goals}
        PROMPT;
    }
}
