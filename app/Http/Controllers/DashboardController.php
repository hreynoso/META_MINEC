<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\PresidentialGoal;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with('institution')->get();

        $summary = [
            'budget' => (float) $projects->sum('budget'),
            'executed' => (float) $projects->sum('executed'),
            'active' => $projects->where('status', 'en_ejecucion')->count(),
            'total' => $projects->count(),
            'completed' => $projects->where('status', 'completado')->count(),
            'beneficiaries' => (int) $projects->sum('beneficiaries'),
            'alert' => $projects->where('status', 'en_riesgo')->count(),
        ];

        $strategicKpis = Kpi::where('strategic', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (Kpi $k) => [
                'label' => $k->label,
                'value' => $k->value,
                'unit' => $k->unit,
                'target' => $k->target,
                'achievement' => $k->target > 0 ? (int) round(($k->value / $k->target) * 100) : 0,
                'trend' => $k->trend,
            ]);

        $goals = PresidentialGoal::withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (PresidentialGoal $g) => [
                'name' => $g->name,
                'count' => $g->projects_count,
            ]);

        $portfolio = $projects
            ->sortByDesc('budget')
            ->take(10)
            ->map(fn (Project $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'institution' => $p->institution?->short_name ?? $p->institution?->code ?? '—',
                'status' => $p->status,
                'progress' => $p->physical_progress,
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'strategicKpis' => $strategicKpis,
            'goals' => $goals,
            'portfolio' => $portfolio,
        ]);
    }
}
