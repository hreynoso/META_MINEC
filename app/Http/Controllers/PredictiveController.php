<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendation;
use App\Models\Project;
use App\Services\Ai\AiReportService;
use App\Services\PredictionService;
use App\Support\Branding;
use App\Support\ExportName;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PredictiveController extends Controller
{
    public function index(PredictionService $pred): Response
    {
        $ranking = $pred->ranking();

        // Adjunta la última generación con IA de cada proyecto (una sola consulta).
        $latest = AiRecommendation::with('user')
            ->whereIn('project_id', array_column($ranking, 'id'))
            ->latest()
            ->get()
            ->unique('project_id')
            ->keyBy('project_id');

        $ranking = array_map(function (array $item) use ($latest) {
            $rec = $latest->get($item['id']);
            $item['last_generation'] = $rec ? $this->formatGeneration($rec) : null;

            return $item;
        }, $ranking);

        return Inertia::render('Predictive/Index', [
            'ranking' => $ranking,
        ]);
    }

    /**
     * Recomendación del modelo enriquecida con la IA configurada.
     * Si la IA no está disponible, devuelve la recomendación heurística.
     */
    public function recommendation(Request $request, Project $project, PredictionService $pred, AiReportService $ai): JsonResponse
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

            if ($text === '') {
                return response()->json([
                    'ai' => true,
                    'recommendation' => $pred->recommendation($project),
                ]);
            }

            // Se guarda la generación con autor y fecha/hora para trazabilidad.
            $record = AiRecommendation::create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'recommendation' => $text,
            ]);

            return response()->json([
                'ai' => true,
                'recommendation' => $text,
                'generation' => $this->formatGeneration($record->load('user')),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ai' => false,
                'recommendation' => $pred->recommendation($project),
                'message' => 'No se pudo consultar la IA: '.$e->getMessage(),
            ]);
        }
    }

    /** Informe PDF del riesgo del proyecto con toda su trazabilidad de IA. */
    public function report(Project $project, PredictionService $pred): HttpResponse
    {
        $project->loadMissing('institution');

        $history = AiRecommendation::with('user')
            ->where('project_id', $project->id)
            ->latest()
            ->get()
            ->map(fn (AiRecommendation $r) => $this->formatGeneration($r))
            ->all();

        $pdf = Pdf::loadView('reports.prediction', [
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'generated_at' => now()->format('d/m/Y h:i A'),
            'p' => $pred->predict($project),
            'history' => $history,
        ])->setPaper('a4');

        return $pdf->download(ExportName::make('Informe de Riesgo '.$project->code, 'pdf'));
    }

    /** Historial de generaciones con IA de un proyecto, de la más reciente a la más antigua. */
    public function history(Project $project): JsonResponse
    {
        $items = AiRecommendation::with('user')
            ->where('project_id', $project->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (AiRecommendation $r) => $this->formatGeneration($r) + ['id' => $r->id])
            ->all();

        return response()->json(['history' => $items]);
    }

    /**
     * @return array{recommendation: string, user: string, datetime: string|null}
     */
    private function formatGeneration(AiRecommendation $r): array
    {
        return [
            'recommendation' => $r->recommendation,
            'user' => $r->user?->name ?? 'Sistema',
            'datetime' => $r->created_at?->format('d/m/Y h:i A'),
        ];
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
