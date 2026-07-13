<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiRequest;
use App\Models\Kpi;
use App\Support\ExportName;
use App\Support\SheetExport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'units' => \App\Models\CatalogOption::values('kpi_unit'),
        ]);
    }

    public function export(): StreamedResponse
    {
        $trend = ['up' => 'Al alza', 'down' => 'A la baja', 'flat' => 'Estable'];

        $rows = Kpi::orderBy('sort')->orderBy('label')->get()->map(function (Kpi $k) use ($trend) {
            $ach = $k->target > 0 ? (int) round($k->value / $k->target * 100) : 0;

            return [
                $k->label,
                $k->key,
                $k->value,
                $k->unit ?? '',
                $k->target,
                $ach.'%',
                $trend[$k->trend] ?? $k->trend,
                $k->strategic ? 'Sí' : 'No',
            ];
        })->all();

        return SheetExport::stream(ExportName::make('Indicadores KPIs', 'xlsx'), ['Indicador', 'Clave', 'Valor', 'Unidad', 'Meta', 'Logro', 'Tendencia', 'Estratégico'], $rows);
    }

    public function store(KpiRequest $request): RedirectResponse
    {
        Kpi::create($request->validated());

        return to_route('kpis.index')->with('success', __('messages.kpi.created'));
    }

    public function update(KpiRequest $request, Kpi $kpi): RedirectResponse
    {
        $kpi->update($request->validated());

        return to_route('kpis.index')->with('success', __('messages.kpi.updated'));
    }

    public function destroy(Kpi $kpi): RedirectResponse
    {
        $kpi->delete();

        return to_route('kpis.index')->with('success', __('messages.kpi.deleted'));
    }
}
