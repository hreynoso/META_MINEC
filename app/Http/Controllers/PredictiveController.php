<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Ai\AiReportService;
use App\Services\PredictionService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PredictiveController extends Controller
{
    public function index(PredictionService $pred): Response
    {
        return Inertia::render('Predictive/Index', [
            'ranking' => $pred->ranking(),
        ]);
    }

    /**
     * Recomendación del modelo enriquecida con la IA configurada.
     * Si la IA no está disponible, devuelve la recomendación heurística.
     */
    public function recommendation(Project $project, PredictionService $pred, AiReportService $ai): JsonResponse
    {
        if (! $ai->isConfigured()) {
            return response()->json([
                'ai' => false,
                'recommendation' => $pred->recommendation($project),
                'message' => 'IA no configurada; se muestra la recomendación del modelo. Configúrala en Configuración → Inteligencia Artificial.',
            ]);
        }

        try {
            $text = trim($ai->generate($this->prompt($project, $pred)));

            return response()->json([
                'ai' => true,
                'recommendation' => $text !== '' ? $text : $pred->recommendation($project),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ai' => false,
                'recommendation' => $pred->recommendation($project),
                'message' => 'No se pudo consultar la IA: '.$e->getMessage(),
            ]);
        }
    }

    private function prompt(Project $p, PredictionService $pred): string
    {
        $factors = implode('; ', $pred->factors($p));
        $score = $pred->score($p);

        return <<<PROMPT
        Eres un analista de proyectos de inversión pública del Ministerio de Economía de El Salvador.
        Proyecto: {$p->name} ({$p->code}). Institución responsable: {$p->institution?->short_name}.
        Avance físico: {$p->physical_progress}%. Ejecución financiera: {$p->financialProgress()}%.
        Riesgo declarado: {$p->risk_level}. Estado operativo: {$p->status}.
        Probabilidad de éxito estimada por el modelo: {$score}%.
        Factores considerados: {$factors}.

        Redacta en 2 o 3 frases una recomendación de acción concreta, accionable y en español
        para mejorar la probabilidad de éxito del proyecto. Responde solo con la recomendación,
        sin encabezados ni viñetas.
        PROMPT;
    }
}
