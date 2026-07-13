<?php

namespace App\Services\Reports;

use App\Models\AiRecommendation;
use App\Models\Institution;
use App\Models\Kpi;
use App\Models\Project;
use App\Services\PredictionService;
use App\Support\LocalTime;

/**
 * Datos del tablero e informe presidencial (Ministra). Extraído de
 * MinisterController para poder reutilizarlo en los envíos programados por
 * correo (informe recurrente en PDF) sin duplicar la lógica.
 */
class MinisterReportData
{
    public function __construct(private readonly PredictionService $pred) {}

    public const STATUS_LABEL = [
        'planificado' => 'Planificado',
        'en_ejecucion' => 'En ejecución',
        'en_riesgo' => 'En riesgo',
        'retrasado' => 'Retrasado',
        'completado' => 'Completado',
    ];

    public const RISK_LABEL = ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];

    /** Cifras agregadas de la cartera para el tablero ejecutivo. */
    public function summaryData(): array
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
    public function kpisData(): array
    {
        return Kpi::orderBy('sort')->take(6)->get()->map(fn (Kpi $k) => [
            'label' => $k->label,
            'value' => $k->value,
            'unit' => $k->unit,
            'target' => $k->target,
            'achievement' => $k->target > 0 ? (int) round($k->value / $k->target * 100) : 0,
        ])->all();
    }

    /**
     * Clasifica cada institución en un semáforo según la salud de sus proyectos.
     * Incluye, por color, la lista de proyectos en ese estado (para poder
     * verlos al hacer clic en el tab de la Ministra).
     */
    public function semaforo(): array
    {
        return Institution::with('projects')
            ->orderBy('name')
            ->get()
            ->map(function (Institution $i) {
                $buckets = ['green' => [], 'amber' => [], 'red' => []];

                foreach ($i->projects as $p) {
                    $state = ($p->risk_level === 'alto' || in_array($p->status, ['en_riesgo', 'retrasado'], true))
                        ? 'red'
                        : ($p->risk_level === 'medio' ? 'amber' : 'green');

                    $buckets[$state][] = [
                        'name' => $p->name,
                        'code' => $p->code,
                        'physical_progress' => $p->physical_progress,
                        'status' => self::STATUS_LABEL[$p->status] ?? $p->status,
                    ];
                }

                $green = count($buckets['green']);
                $amber = count($buckets['amber']);
                $red = count($buckets['red']);

                return [
                    'code' => $i->code,
                    'name' => $i->name,
                    'short_name' => $i->short_name,
                    'green' => $green,
                    'amber' => $amber,
                    'red' => $red,
                    'items' => $buckets,
                    'status' => $red > 0 ? 'critico' : ($amber > 0 ? 'observacion' : 'optimo'),
                ];
            })
            ->filter(fn (array $i) => ($i['green'] + $i['amber'] + $i['red']) > 0)
            ->values()
            ->all();
    }

    /**
     * Proyectos con riesgo de fracaso (probabilidad de éxito < 60%), del más
     * crítico al menos. Cada uno trae su última recomendación de acción: la más
     * reciente generada con IA para ese proyecto, o la del modelo si no hay.
     */
    public function alerts(): array
    {
        $atRisk = Project::with('institution')
            ->get()
            ->map(fn (Project $p) => ['p' => $p, 'success' => $this->pred->score($p)])
            ->filter(fn (array $a) => $a['success'] < 60)
            ->sortBy('success')
            ->values();

        $ids = $atRisk->map(fn (array $a) => $a['p']->id)->all();

        $latestRecs = AiRecommendation::with('user')
            ->whereIn('project_id', $ids)
            ->latest()
            ->get()
            ->unique('project_id')
            ->keyBy('project_id');

        return $atRisk->map(function (array $a) use ($latestRecs) {
            /** @var Project $p */
            $p = $a['p'];
            $rec = $latestRecs->get($p->id);

            return [
                'name' => $p->name,
                'institution' => $p->institution?->short_name ?? '',
                'physical_progress' => $p->physical_progress,
                'risk' => self::RISK_LABEL[$p->risk_level] ?? $p->risk_level,
                'success' => $a['success'],
                'recommendation' => $rec?->recommendation ?: $this->pred->recommendation($p),
                'recommendation_source' => $rec ? 'ia' : 'modelo',
                'recommendation_by' => $rec?->user?->name,
                'recommendation_at' => $rec ? LocalTime::format($rec->created_at) : null,
            ];
        })->all();
    }

    /** Recomendaciones de intervención para los proyectos más críticos. */
    public function recommendations(): array
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
    public function buildPrompt(array $institutionIds, string $from, string $to): string
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
