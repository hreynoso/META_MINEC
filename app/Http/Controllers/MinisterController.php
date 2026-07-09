<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendation;
use App\Models\Institution;
use App\Models\Kpi;
use App\Models\MinisterReport;
use App\Models\Project;
use App\Services\Ai\AiReportService;
use App\Services\PredictionService;
use App\Support\Branding;
use App\Support\ExportName;
use App\Support\WordExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MinisterController extends Controller
{
    public function __construct(private readonly PredictionService $pred) {}

    private const STATUS_LABEL = [
        'planificado' => 'Planificado',
        'en_ejecucion' => 'En ejecución',
        'en_riesgo' => 'En riesgo',
        'retrasado' => 'Retrasado',
        'completado' => 'Completado',
    ];

    private const RISK_LABEL = ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];

    public function index(): Response
    {
        return Inertia::render('Minister/Index', [
            'summary' => $this->summaryData(),
            'kpis' => $this->kpisData(),
            'byInstitution' => $this->semaforo(),
            'alerts' => $this->alerts(),
            'recommendations' => $this->recommendations(),
            'lastAiRecommendation' => $this->lastAiRecommendation(),
            'institutions' => Institution::has('projects')->orderBy('name')->get(['id', 'short_name']),
        ]);
    }

    /** Última recomendación generada con IA (IA Predictiva), o null si no hay. */
    private function lastAiRecommendation(): ?array
    {
        $r = AiRecommendation::with(['user', 'project'])->latest()->first();

        if (! $r) {
            return null;
        }

        return [
            'project' => $r->project?->name ?? '—',
            'user' => $r->user?->name ?? 'Sistema',
            'datetime' => \App\Support\LocalTime::format($r->created_at),
            'recommendation' => $r->recommendation,
        ];
    }

    /** Cifras agregadas de la cartera para el tablero ejecutivo. */
    private function summaryData(): array
    {
        $projects = Project::with('institution')->get();
        $budget = (float) $projects->sum('budget');
        $executed = (float) $projects->sum('executed');

        return [
            'budget' => $budget,
            'executed' => $executed,
            'executed_pct' => $budget > 0 ? round($executed / $budget * 100, 1) : 0,
            'beneficiaries' => (int) $projects->sum('beneficiaries'),
            'critical' => $projects->whereIn('status', ['en_riesgo', 'retrasado'])->count(),
            'projects_count' => $projects->count(),
            'institutions_count' => Institution::has('projects')->count(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function kpisData(): array
    {
        return Kpi::orderBy('sort')->take(6)->get()->map(fn (Kpi $k) => [
            'label' => $k->label,
            'value' => $k->value,
            'unit' => $k->unit,
            'target' => $k->target,
            'achievement' => $k->target > 0 ? (int) round($k->value / $k->target * 100) : 0,
        ])->all();
    }

    public function generateReport(Request $request, AiReportService $ai): RedirectResponse
    {
        $data = $request->validate([
            'institutions' => ['required', 'array', 'min:1'],
            'institutions.*' => ['integer', 'exists:institutions,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ], [], ['institutions' => 'instituciones']);

        try {
            $report = $ai->generate($this->buildPrompt($data['institutions'], $data['from'], $data['to']));
        } catch (Throwable $e) {
            return back()->with('error', __('messages.minister.report_failed', ['error' => $e->getMessage()]));
        }

        // Trazabilidad: se guarda el informe con autor, período e instituciones.
        MinisterReport::create([
            'user_id' => $request->user()->id,
            'from' => $data['from'],
            'to' => $data['to'],
            'institutions' => Institution::whereIn('id', $data['institutions'])->orderBy('name')->pluck('short_name')->all(),
            'content' => $report,
        ]);

        return back()->with('report', $report)->with('success', __('messages.minister.report_generated'));
    }

    /** Historial de informes presidenciales generados, del más reciente al más antiguo. */
    public function history(): JsonResponse
    {
        $items = MinisterReport::with('user')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (MinisterReport $r) => [
                'id' => $r->id,
                'user' => $r->user?->name ?? 'Sistema',
                'datetime' => \App\Support\LocalTime::format($r->created_at),
                'period' => optional($r->from)->format('d/m/Y').' – '.optional($r->to)->format('d/m/Y'),
                'institutions' => $r->institutions ?? [],
            ])
            ->all();

        return response()->json(['history' => $items]);
    }

    /** Reexporta un informe presidencial guardado a PDF. */
    public function reportStored(MinisterReport $report): HttpResponse
    {
        $pdf = Pdf::loadView('reports.minister', [
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'generated_at' => \App\Support\LocalTime::format($report->created_at),
            'from' => optional($report->from)->toDateString(),
            'to' => optional($report->to)->toDateString(),
            'selected' => $report->institutions ?? [],
            'summary' => $this->summaryData(),
            'kpis' => $this->kpisData(),
            'byInstitution' => $this->semaforo(),
            'alerts' => $this->alerts(),
            'recommendations' => $this->recommendations(),
            'narrative' => $report->content,
        ])->setPaper('a4');

        return $pdf->download(ExportName::make('Informe Presidencial', 'pdf'));
    }

    /**
     * Informe presidencial en PDF con IA: incluye toda la información del tablero
     * (resumen, KPIs, semáforo, alertas, recomendaciones) más la narrativa de IA.
     * Reutiliza el texto ya generado si se envía; si no, lo genera.
     */
    public function reportPdf(Request $request, AiReportService $ai): HttpResponse|RedirectResponse
    {
        $data = $request->validate([
            'institutions' => ['required', 'array', 'min:1'],
            'institutions.*' => ['integer', 'exists:institutions,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'report' => ['nullable', 'string'],
        ], [], ['institutions' => 'instituciones']);

        $narrative = trim((string) ($data['report'] ?? ''));

        if ($narrative === '') {
            if (! $ai->isConfigured()) {
                return back()->with('error', __('messages.ai.not_configured'));
            }

            try {
                $narrative = trim($ai->generate($this->buildPrompt($data['institutions'], $data['from'], $data['to'])));
            } catch (Throwable $e) {
                return back()->with('error', __('messages.minister.report_failed', ['error' => $e->getMessage()]));
            }
        }

        $selected = Institution::whereIn('id', $data['institutions'])->orderBy('name')->pluck('short_name')->all();

        $pdf = Pdf::loadView('reports.minister', [
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'generated_at' => \App\Support\LocalTime::format(now()),
            'from' => $data['from'],
            'to' => $data['to'],
            'selected' => $selected,
            'summary' => $this->summaryData(),
            'kpis' => $this->kpisData(),
            'byInstitution' => $this->semaforo(),
            'alerts' => $this->alerts(),
            'recommendations' => $this->recommendations(),
            'narrative' => $narrative,
        ])->setPaper('a4');

        return $pdf->download(ExportName::make('Informe Presidencial', 'pdf'));
    }

    /**
     * Informe presidencial en Word (.docx). Reutiliza la narrativa ya generada si
     * se envía; si no, la genera con la IA. Formato con títulos en negrita y
     * párrafos justificados (WordExport).
     */
    public function reportDocx(Request $request, AiReportService $ai): HttpResponse|RedirectResponse
    {
        $data = $request->validate([
            'institutions' => ['required', 'array', 'min:1'],
            'institutions.*' => ['integer', 'exists:institutions,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'report' => ['nullable', 'string'],
        ], [], ['institutions' => 'instituciones']);

        $narrative = trim((string) ($data['report'] ?? ''));

        if ($narrative === '') {
            if (! $ai->isConfigured()) {
                return back()->with('error', __('messages.ai.not_configured'));
            }

            try {
                $narrative = trim($ai->generate($this->buildPrompt($data['institutions'], $data['from'], $data['to'])));
            } catch (Throwable $e) {
                return back()->with('error', __('messages.minister.report_failed', ['error' => $e->getMessage()]));
            }
        }

        $selected = Institution::whereIn('id', $data['institutions'])->orderBy('name')->pluck('short_name')->all();

        return $this->docx($narrative, $data['from'], $data['to'], $selected);
    }

    /** Reexporta un informe presidencial guardado a Word (.docx). */
    public function reportStoredDocx(MinisterReport $report): HttpResponse
    {
        return $this->docx(
            (string) $report->content,
            optional($report->from)->toDateString() ?? '',
            optional($report->to)->toDateString() ?? '',
            $report->institutions ?? [],
        );
    }

    /**
     * Arma el .docx del informe presidencial con el encabezado institucional.
     *
     * @param  string[]  $selected
     */
    private function docx(string $narrative, string $from, string $to, array $selected): HttpResponse
    {
        $subtitle = array_values(array_filter([
            'Informe dirigido a la Presidencia de la República',
            $from && $to ? 'Período '.$from.' al '.$to : null,
            $selected ? 'Instituciones: '.implode(', ', $selected) : null,
            (string) config('branding.institution').' · Sistema META',
        ]));

        return WordExport::download(
            ExportName::make('Informe Presidencial', 'docx'),
            'Informe Presidencial',
            $subtitle,
            $narrative,
        );
    }

    /** Clasifica cada institución en un semáforo según la salud de sus proyectos. */
    private function semaforo(): array
    {
        return Institution::with('projects')
            ->orderBy('name')
            ->get()
            ->map(function (Institution $i) {
                $green = 0;
                $amber = 0;
                $red = 0;

                foreach ($i->projects as $p) {
                    if ($p->risk_level === 'alto' || in_array($p->status, ['en_riesgo', 'retrasado'], true)) {
                        $red++;
                    } elseif ($p->risk_level === 'medio') {
                        $amber++;
                    } else {
                        $green++;
                    }
                }

                return [
                    'code' => $i->code,
                    'name' => $i->name,
                    'short_name' => $i->short_name,
                    'green' => $green,
                    'amber' => $amber,
                    'red' => $red,
                    'status' => $red > 0 ? 'critico' : ($amber > 0 ? 'observacion' : 'optimo'),
                ];
            })
            ->filter(fn (array $i) => ($i['green'] + $i['amber'] + $i['red']) > 0)
            ->values()
            ->all();
    }

    /** Proyectos con riesgo de fracaso, ordenados del más crítico al menos. */
    private function alerts(): array
    {
        return Project::with('institution')
            ->get()
            ->map(fn (Project $p) => [
                'name' => $p->name,
                'institution' => $p->institution?->short_name ?? '',
                'physical_progress' => $p->physical_progress,
                'risk' => self::RISK_LABEL[$p->risk_level] ?? $p->risk_level,
                'success' => $this->pred->score($p),
            ])
            ->filter(fn (array $a) => $a['success'] < 60)
            ->sortBy('success')
            ->values()
            ->all();
    }

    /** Recomendaciones de intervención para los proyectos más críticos. */
    private function recommendations(): array
    {
        return Project::with('institution')
            ->get()
            ->map(fn (Project $p) => [
                'name' => $p->name,
                'responsible' => $p->responsible,
                'success' => $this->pred->score($p),
            ])
            ->filter(fn (array $r) => $r['success'] < 40)
            ->sortBy('success')
            ->map(fn (array $r) => [
                'title' => 'Intervención inmediata: '.$r['name'],
                'detail' => 'Convocar al responsable ('.($r['responsible'] ?: 'sin asignar').') y revisar cronograma. Probabilidad de éxito '.$r['success'].'%.',
                'priority' => 'Alta',
            ])
            ->values()
            ->all();
    }

    /** Construye el prompt para la IA con la data real de la plataforma. */
    private function buildPrompt(array $institutionIds, string $from, string $to): string
    {
        $institutions = Institution::whereIn('id', $institutionIds)
            ->with(['projects' => fn ($q) => $q->orderBy('code')])
            ->orderBy('name')
            ->get();

        $lines = [];
        foreach ($institutions as $inst) {
            $lines[] = "Institución: {$inst->short_name} — {$inst->name}";
            foreach ($inst->projects as $p) {
                $status = self::STATUS_LABEL[$p->status] ?? $p->status;
                $lines[] = sprintf(
                    '  - %s | %s | avance físico %d%% | presupuesto $%s | ejecutado $%s | riesgo %s',
                    $p->code,
                    $p->name,
                    $p->physical_progress,
                    number_format((float) $p->budget, 0),
                    number_format((float) $p->executed, 0),
                    self::RISK_LABEL[$p->risk_level] ?? $p->risk_level
                );
                $lines[] = "     Estado: {$status}";
            }
        }

        $kpis = Kpi::where('strategic', true)->orderBy('sort')->get()
            ->map(fn (Kpi $k) => "  - {$k->label}: {$k->value} {$k->unit} (meta {$k->target})")
            ->implode("\n");

        $data = implode("\n", $lines);

        return <<<PROMPT
        Eres el asesor estratégico de la Ministra de Economía de El Salvador y redactas
        para la máxima autoridad del país.

        CONTEXTO OBLIGATORIO — ESTE ES UN INFORME DE ALTO NIVEL:
        - El destinatario es la Presidencia de la República; el lector es un tomador de
          decisiones, no un equipo operativo.
        - Mantén una mirada ejecutiva y estratégica: prioriza conclusiones, implicaciones,
          riesgos país y decisiones requeridas, NO el detalle operativo ni la jerga técnica.
        - Agrega y sintetiza: habla de tendencias, magnitudes y su significado; evita listar
          proyecto por proyecto salvo para ilustrar un punto crítico.
        - Tono institucional, sobrio y directo. Cifras siempre con su interpretación
          ("qué significa"), no cifras sueltas. Español formal.
        - Respeta este marco de alto nivel en TODO el documento, de principio a fin.

        Redacta el informe ejecutivo del período del {$from} al {$to}, estructurado en
        secciones con encabezados en formato Markdown ("## "): (1) Resumen ejecutivo,
        (2) Principales logros y avances, (3) Proyectos en riesgo y acciones recomendadas,
        y (4) Cumplimiento de los indicadores estratégicos. Usa **negritas** para destacar
        las ideas y cifras clave.

        DATOS DE LA PLATAFORMA META (insumo de trabajo, NO para transcribir en crudo):
        {$data}

        INDICADORES ESTRATÉGICOS:
        {$kpis}
        PROMPT;
    }
}
