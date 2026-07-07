<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\PresidentialGoal;
use App\Services\Ai\AiReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'message' => 'El API de IA no está configurado. Ve a Configuración → Inteligencia Artificial.',
            ]);
        }

        $institution = Institution::with(['projects' => fn ($q) => $q->orderBy('code')])->findOrFail($data['institution_id']);

        try {
            $draft = trim($ai->generate($this->prompt($institution, $data['periodo'])));

            return response()->json(['ai' => true, 'draft' => $draft]);
        } catch (Throwable $e) {
            return response()->json([
                'ai' => false,
                'draft' => '',
                'message' => 'No se pudo generar la memoria: '.$e->getMessage(),
            ]);
        }
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

        Estructura la memoria en secciones con encabezados: (1) Presentación institucional,
        (2) Gestión de proyectos, (3) Ejecución presupuestaria, (4) Impacto y beneficiarios,
        (5) Alineación con las metas presidenciales, y (6) Conclusiones y perspectivas.
        Usa un tono formal, institucional y en español.

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
