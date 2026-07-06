<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Project;
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
                'goal' => $p->presidentialGoal?->name ?? '',
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
            ->get(['code', 'short_name', 'name']);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'institutions' => $institutions,
        ]);
    }
}
