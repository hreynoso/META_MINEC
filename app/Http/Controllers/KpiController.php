<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiRequest;
use App\Models\Kpi;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function index(): Response
    {
        $kpis = Kpi::orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(fn (Kpi $k) => [
                'id' => $k->id,
                'key' => $k->key,
                'label' => $k->label,
                'value' => $k->value,
                'unit' => $k->unit,
                'target' => $k->target,
                'achievement' => $k->target > 0 ? (int) round(($k->value / $k->target) * 100) : 0,
                'trend' => $k->trend,
                'strategic' => $k->strategic,
                'sort' => $k->sort,
            ]);

        return Inertia::render('Kpis/Index', [
            'kpis' => $kpis,
        ]);
    }

    public function store(KpiRequest $request): RedirectResponse
    {
        Kpi::create($request->validated());

        return to_route('kpis.index')->with('success', 'Indicador creado correctamente.');
    }

    public function update(KpiRequest $request, Kpi $kpi): RedirectResponse
    {
        $kpi->update($request->validated());

        return to_route('kpis.index')->with('success', 'Indicador actualizado correctamente.');
    }

    public function destroy(Kpi $kpi): RedirectResponse
    {
        $kpi->delete();

        return to_route('kpis.index')->with('success', 'Indicador eliminado correctamente.');
    }
}
