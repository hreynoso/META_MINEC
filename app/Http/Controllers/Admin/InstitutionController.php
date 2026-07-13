<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogOption;
use App\Models\Institution;
use App\Support\ExportName;
use App\Support\LocalTime;
use App\Support\SheetExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mantenimiento de instituciones (Configuración → Instituciones). Es la fuente
 * de datos del desplegable "Institución" del sistema (solo las activas).
 */
class InstitutionController extends Controller
{
    public function index(): Response
    {
        $rows = Institution::with('parent:id,short_name')
            ->orderBy('name')
            ->get()
            ->map(fn (Institution $i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name' => $i->name,
                'short_name' => $i->short_name,
                'type' => $i->type,
                'sector' => $i->sector,
                'rnc' => $i->rnc,
                'status' => $i->status ?? 'activa',
                'logo_url' => $i->logoUrl(),
                'parent_id' => $i->parent_id,
                'parent' => $i->parent?->short_name,
                'admin_dependency' => $i->admin_dependency,
                'phone_main' => $i->phone_main,
                'phone_alt' => $i->phone_alt,
                'email' => $i->email,
                'website' => $i->website,
                'province' => $i->province,
                'addr_sector' => $i->addr_sector,
                'addr_street' => $i->addr_street,
                'addr_number' => $i->addr_number,
                'addr_reference' => $i->addr_reference,
                'postal_code' => $i->postal_code,
                'authority_name' => $i->authority_name,
                'authority_position' => $i->authority_position,
                'authority_email' => $i->authority_email,
                'authority_phone' => $i->authority_phone,
                'projects_count' => $i->projects()->count(),
                'created_by' => $i->created_by,
                'updated_by' => $i->updated_by,
                'created_at' => LocalTime::format($i->created_at),
                'updated_at' => LocalTime::format($i->updated_at),
            ]);

        return Inertia::render('Admin/Institutions', [
            'institutions' => $rows,
            'catalogs' => [
                'types' => CatalogOption::values('institution_type'),
                'sectors' => CatalogOption::values('institution_sector'),
                'dependencies' => CatalogOption::values('institution_dependency'),
                'statuses' => Institution::STATUSES,
            ],
            'parents' => Institution::orderBy('short_name')->get(['id', 'short_name', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateInstitution($request, null);

        $data['code'] = $this->generateCode($data['short_name']);
        $data['created_by'] = $this->actor($request);
        $data['updated_by'] = $data['created_by'];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('institutions', 'public');
        }

        Institution::create($data);

        return back()->with('success', __('messages.institution.created'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $data = $this->validateInstitution($request, $institution);

        // El código no se edita (autogenerado); se conserva el existente.
        unset($data['code']);
        $data['updated_by'] = $this->actor($request);

        if ($request->hasFile('logo')) {
            if ($institution->logo_path) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('institutions', 'public');
        }

        $institution->update($data);

        return back()->with('success', __('messages.institution.updated'));
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        if ($institution->projects()->exists()) {
            return back()->with('error', __('messages.institution.has_projects'));
        }

        if ($institution->logo_path) {
            Storage::disk('public')->delete($institution->logo_path);
        }

        $institution->delete();

        return back()->with('success', __('messages.institution.deleted'));
    }

    public function export(): StreamedResponse
    {
        $rows = Institution::with('parent:id,short_name')->orderBy('name')->get()
            ->map(fn (Institution $i) => [
                $i->code,
                $i->name,
                $i->short_name,
                $i->type ?? '',
                $i->sector ?? '',
                $i->rnc ?? '',
                ($i->status ?? 'activa') === 'activa' ? 'Activa' : 'Inactiva',
                $i->parent?->short_name ?? '',
                $i->email ?? '',
                $i->phone_main ?? '',
                $i->authority_name ?? '',
            ])->all();

        return SheetExport::stream(
            ExportName::make('Instituciones', 'xlsx'),
            ['Código', 'Nombre oficial', 'Siglas', 'Tipo', 'Sector', 'NIT', 'Estado', 'Institución superior', 'Correo', 'Teléfono', 'Máxima autoridad'],
            $rows,
        );
    }

    /** @return array<string, mixed> */
    private function validateInstitution(Request $request, ?Institution $institution): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(CatalogOption::allLabels('institution_type'))],
            'sector' => ['required', Rule::in(CatalogOption::allLabels('institution_sector'))],
            'status' => ['required', Rule::in(Institution::STATUSES)],
            'rnc' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'parent_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')->where(fn ($q) => $institution ? $q->where('id', '!=', $institution->id) : $q)],
            'admin_dependency' => ['nullable', Rule::in(CatalogOption::allLabels('institution_dependency'))],
            'phone_main' => ['nullable', 'string', 'max:40'],
            'phone_alt' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'province' => ['nullable', 'string', 'max:120'],
            'addr_sector' => ['nullable', 'string', 'max:120'],
            'addr_street' => ['nullable', 'string', 'max:120'],
            'addr_number' => ['nullable', 'string', 'max:40'],
            'addr_reference' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'authority_name' => ['nullable', 'string', 'max:255'],
            'authority_position' => ['nullable', 'string', 'max:120'],
            'authority_email' => ['nullable', 'email', 'max:255'],
            'authority_phone' => ['nullable', 'string', 'max:40'],
        ], [], [
            'name' => 'nombre oficial',
            'short_name' => 'siglas',
            'type' => 'tipo',
            'sector' => 'sector',
            'status' => 'estado',
        ]);
    }

    /** Genera un código único a partir de las siglas (autogenerado). */
    private function generateCode(string $shortName): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $shortName) ?? '');
        $base = $base !== '' ? substr($base, 0, 12) : 'INST';

        $code = $base;
        $n = 1;
        while (Institution::where('code', $code)->exists()) {
            $code = $base.'-'.(++$n);
        }

        return $code;
    }

    private function actor(Request $request): string
    {
        return $request->user()?->name ?: ($request->user()?->email ?? 'Sistema');
    }
}
