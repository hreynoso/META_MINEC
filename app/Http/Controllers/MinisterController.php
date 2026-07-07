<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Kpi;
use App\Models\Project;
use App\Services\Ai\AiReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MinisterController extends Controller
{
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
        $projects = Project::with('institution')->get();
        $budget = (float) $projects->sum('budget');
        $executed = (float) $projects->sum('executed');
        $critical = $projects->whereIn('status', ['en_riesgo', 'retrasado'])->count();

        $summary = [
            'budget' => $budget,
            'executed' => $executed,
            'executed_pct' => $budget > 0 ? round($executed / $budget * 100, 1) : 0,
            'beneficiaries' => (int) $projects->sum('beneficiaries'),
            'critical' => $critical,
            'projects_count' => $projects->count(),
            'institutions_count' => Institution::has('projects')->count(),
        ];

        $kpis = Kpi::orderBy('sort')->take(6)->get()->map(fn (Kpi $k) => [
            'label' => $k->label,
            'value' => $k->value,
            'unit' => $k->unit,
            'target' => $k->target,
            'achievement' => $k->target > 0 ? (int) round($k->value / $k->target * 100) : 0,
        ]);

        return Inertia::render('Minister/Index', [
            'summary' => $summary,
            'kpis' => $kpis,
            'byInstitution' => $this->semaforo(),
            'alerts' => $this->alerts(),
            'recommendations' => $this->recommendations(),
            'institutions' => Institution::has('projects')->orderBy('name')->get(['id', 'short_name']),
        ]);
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
            return back()->with('error', 'No se pudo generar el informe: '.$e->getMessage());
        }

        return back()->with('report', $report)->with('success', 'Informe presidencial generado.');
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

    /** Probabilidad de éxito heurística (0–100) de un proyecto. */
    private function successScore(Project $p): int
    {
        if ($p->status === 'completado') {
            return 100;
        }

        $factor = ['bajo' => 1.0, 'medio' => 0.7, 'alto' => 0.35][$p->risk_level] ?? 0.7;

        return (int) round($p->physical_progress * $factor);
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
                'success' => $this->successScore($p),
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
                'success' => $this->successScore($p),
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
        Eres un asesor de la Ministra de Economía de El Salvador. Redacta un informe
        ejecutivo formal dirigido a la Presidencia de la República, en español, sobre
        el avance de los proyectos de inversión pública del período del {$from} al {$to}.

        El informe debe incluir: (1) un resumen ejecutivo, (2) los principales logros y
        avances, (3) los proyectos en riesgo y las acciones recomendadas, y (4) el
        cumplimiento de los indicadores estratégicos. Usa un tono institucional y conciso.

        DATOS DE LA PLATAFORMA META (proyectos por institución seleccionada):
        {$data}

        INDICADORES ESTRATÉGICOS:
        {$kpis}
        PROMPT;
    }
}
