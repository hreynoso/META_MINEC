<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Institution;
use App\Models\PresidentialGoal;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with(['institution', 'presidentialGoal'])
            ->orderBy('code')
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'institution' => $p->institution?->code ?? '',
                'institution_id' => $p->institution_id,
                'goal' => $p->presidentialGoal?->name ?? '',
                'presidential_goal_id' => $p->presidential_goal_id,
                'status' => $p->status,
                'risk_level' => $p->risk_level,
                'budget' => $p->budget,
                'executed' => $p->executed,
                'financial_progress' => $p->financialProgress(),
                'physical_progress' => $p->physical_progress,
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'source' => $p->source,
                'responsible' => $p->responsible,
                'beneficiaries' => $p->beneficiaries,
                'location' => $p->location,
                'deliverables' => $p->deliverables,
                'expected_impact' => $p->expected_impact,
                'benefits' => $p->benefits,
            ]);

        $institutions = Institution::orderBy('name')
            ->get(['id', 'code', 'short_name', 'name']);

        $goals = PresidentialGoal::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'institutions' => $institutions,
            'goals' => $goals,
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return to_route('proyectos.index')->with('success', 'Proyecto creado correctamente.');
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return to_route('proyectos.index')->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return to_route('proyectos.index')->with('success', 'Proyecto eliminado correctamente.');
    }
}
