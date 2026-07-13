<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendation;
use App\Models\Institution;
use App\Models\MinisterReport;
use App\Services\Ai\AiReportService;
use App\Services\Reports\MinisterReportData;
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
    public function __construct(private readonly MinisterReportData $data) {}

    public function index(): Response
    {
        return Inertia::render('Minister/Index', [
            'summary' => $this->data->summaryData(),
            'kpis' => $this->data->kpisData(),
            'byInstitution' => $this->data->semaforo(),
            'alerts' => $this->data->alerts(),
            'recommendations' => $this->data->recommendations(),
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

    public function generateReport(Request $request, AiReportService $ai): RedirectResponse
    {
        $data = $request->validate([
            'institutions' => ['required', 'array', 'min:1'],
            'institutions.*' => ['integer', 'exists:institutions,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ], [], ['institutions' => 'instituciones']);

        try {
            $report = $ai->generate($this->data->buildPrompt($data['institutions'], $data['from'], $data['to']));
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
            'summary' => $this->data->summaryData(),
            'kpis' => $this->data->kpisData(),
            'byInstitution' => $this->data->semaforo(),
            'alerts' => $this->data->alerts(),
            'recommendations' => $this->data->recommendations(),
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
                $narrative = trim($ai->generate($this->data->buildPrompt($data['institutions'], $data['from'], $data['to'])));
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
            'summary' => $this->data->summaryData(),
            'kpis' => $this->data->kpisData(),
            'byInstitution' => $this->data->semaforo(),
            'alerts' => $this->data->alerts(),
            'recommendations' => $this->data->recommendations(),
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
                $narrative = trim($ai->generate($this->data->buildPrompt($data['institutions'], $data['from'], $data['to'])));
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
}
