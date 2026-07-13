<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mantenimiento de catálogos administrables (Configuración → Catálogos).
 * Hoy: tipo, sector y dependencia administrativa de las instituciones.
 */
class CatalogController extends Controller
{
    public function index(): Response
    {
        $groups = collect(CatalogOption::groups())->mapWithKeys(function (string $group) {
            $model = CatalogOption::modelFor($group);
            $column = CatalogOption::columnFor($group);

            $options = CatalogOption::where('group', $group)
                ->orderBy('sort')->orderBy('label')
                ->get()
                ->map(fn (CatalogOption $o) => [
                    'id' => $o->id,
                    'label' => $o->label,
                    'active' => $o->active,
                    'sort' => $o->sort,
                    'in_use' => $model::where($column, $o->label)->count(),
                ])
                ->all();

            return [$group => $options];
        })->all();

        return Inertia::render('Admin/Catalogs', [
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group' => ['required', Rule::in(CatalogOption::groups())],
            'label' => ['required', 'string', 'max:120'],
        ], [], ['label' => 'valor']);

        $exists = CatalogOption::where('group', $data['group'])->where('label', $data['label'])->exists();
        if ($exists) {
            return back()->with('error', __('messages.catalog.duplicate'));
        }

        $sort = (int) CatalogOption::where('group', $data['group'])->max('sort') + 1;

        CatalogOption::create([
            'group' => $data['group'],
            'label' => $data['label'],
            'sort' => $sort,
            'active' => true,
        ]);

        return back()->with('success', __('messages.catalog.created'));
    }

    public function update(Request $request, CatalogOption $catalog): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'active' => ['boolean'],
        ], [], ['label' => 'valor']);

        // Etiqueta única dentro del grupo (ignorando la propia).
        $dup = CatalogOption::where('group', $catalog->group)
            ->where('label', $data['label'])
            ->where('id', '!=', $catalog->id)
            ->exists();
        if ($dup) {
            return back()->with('error', __('messages.catalog.duplicate'));
        }

        $oldLabel = $catalog->label;
        $newLabel = $data['label'];

        $catalog->update([
            'label' => $newLabel,
            'active' => $request->boolean('active'),
        ]);

        // Renombrado en cascada: actualiza los registros que usaban el valor.
        if ($oldLabel !== $newLabel) {
            $model = CatalogOption::modelFor($catalog->group);
            $column = CatalogOption::columnFor($catalog->group);
            $model::where($column, $oldLabel)->update([$column => $newLabel]);
        }

        return back()->with('success', __('messages.catalog.updated'));
    }

    public function destroy(CatalogOption $catalog): RedirectResponse
    {
        $model = CatalogOption::modelFor($catalog->group);
        $column = CatalogOption::columnFor($catalog->group);

        if ($model::where($column, $catalog->label)->exists()) {
            return back()->with('error', __('messages.catalog.in_use'));
        }

        $catalog->delete();

        return back()->with('success', __('messages.catalog.deleted'));
    }
}
