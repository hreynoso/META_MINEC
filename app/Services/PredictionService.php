<?php

namespace App\Services;

use App\Models\Project;

/**
 * Modelo META-PREDICT: heurística determinística que estima la probabilidad de
 * éxito de un proyecto combinando avance físico, eficiencia financiera, nivel de
 * riesgo declarado y estado operativo. La recomendación textual puede enriquecerse
 * con IA (ver AiReportService), pero el score y los factores no dependen de la IA.
 */
class PredictionService
{
    private const RISK_FACTOR = ['bajo' => 1.0, 'medio' => 0.7, 'alto' => 0.35];

    private const STATUS_FACTOR = [
        'completado' => 1.0,
        'en_ejecucion' => 1.0,
        'planificado' => 0.9,
        'en_riesgo' => 0.7,
        'retrasado' => 0.6,
    ];

    private const STATUS_LABEL = [
        'planificado' => 'Planificado',
        'en_ejecucion' => 'En ejecución',
        'en_riesgo' => 'En riesgo',
        'retrasado' => 'Retrasado',
        'completado' => 'Completado',
    ];

    private const RISK_LABEL = ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];

    /** Probabilidad de éxito 0–100. */
    public function score(Project $p): int
    {
        if ($p->status === 'completado') {
            return 100;
        }

        $risk = self::RISK_FACTOR[$p->risk_level] ?? 0.7;
        $status = self::STATUS_FACTOR[$p->status] ?? 0.9;

        return max(0, min(100, (int) round($p->physical_progress * $risk * $status)));
    }

    /** Avance físico esperado según el cronograma (0–100), o null si no hay fechas. */
    public function expectedProgress(Project $p): ?int
    {
        if ($p->status === 'completado') {
            return 100;
        }

        $start = $p->start_date;
        $end = $p->end_date;

        if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
            return null;
        }

        $now = now();

        if ($now->lessThanOrEqualTo($start)) {
            return 0;
        }

        if ($now->greaterThanOrEqualTo($end)) {
            return 100;
        }

        return (int) round($start->diffInDays($now) / $start->diffInDays($end) * 100);
    }

    /** @return string[] Factores considerados por el modelo para este proyecto. */
    public function factors(Project $p): array
    {
        $factors = [];
        $expected = $this->expectedProgress($p);

        if ($expected !== null && $expected > $p->physical_progress) {
            $factors[] = 'Retrasado '.($expected - $p->physical_progress).' pts vs. cronograma';
        } elseif ($expected !== null) {
            $factors[] = 'En línea con el cronograma esperado';
        } else {
            $factors[] = 'Cronograma sin fechas definidas';
        }

        $factors[] = 'Ejecución financiera al '.$p->financialProgress().'%';
        $factors[] = 'Riesgo declarado: '.(self::RISK_LABEL[$p->risk_level] ?? $p->risk_level);
        $factors[] = 'Estado actual: '.(self::STATUS_LABEL[$p->status] ?? $p->status);

        return $factors;
    }

    /** Recomendación heurística de respaldo (cuando la IA no está configurada). */
    public function recommendation(Project $p): string
    {
        $score = $this->score($p);

        return match (true) {
            $score < 20 => 'Activar comité de riesgos. Reformular cronograma, verificar disponibilidad presupuestaria y ejecutar plan de contingencia.',
            $score < 40 => 'Reforzar el seguimiento a quincenal y priorizar la ejecución financiera. Evaluar ajustes al cronograma con el responsable.',
            $score < 60 => 'Mantener monitoreo mensual y atender los factores de riesgo señalados para no comprometer las metas.',
            default => 'Proyecto en trayectoria saludable. Continuar el monitoreo estándar y documentar buenas prácticas.',
        };
    }

    /** Payload completo de predicción para un proyecto. */
    public function predict(Project $p): array
    {
        $score = $this->score($p);

        return [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'institution' => $p->institution?->short_name ?? '',
            'responsible' => $p->responsible,
            'physical_progress' => $p->physical_progress,
            'financial_progress' => $p->financialProgress(),
            'risk' => $p->risk_level,
            'risk_label' => self::RISK_LABEL[$p->risk_level] ?? $p->risk_level,
            'status' => $p->status,
            'status_label' => self::STATUS_LABEL[$p->status] ?? $p->status,
            'score' => $score,
            'expected' => $this->expectedProgress($p),
            'failing' => $score < 40,
            'factors' => $this->factors($p),
            'recommendation' => $this->recommendation($p),
        ];
    }

    /** Ranking de todos los proyectos, de menor a mayor probabilidad de éxito. */
    public function ranking(): array
    {
        return Project::with('institution')
            ->get()
            ->map(fn (Project $p) => $this->predict($p))
            ->sortBy('score')
            ->values()
            ->all();
    }
}
