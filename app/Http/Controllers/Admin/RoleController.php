<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Catálogo de permisos del sistema, agrupado por módulo. */
    private const CATALOG = [
        'Proyectos' => ['proyectos.ver' => 'Ver proyectos', 'proyectos.gestionar' => 'Crear/editar/eliminar proyectos'],
        'KPIs' => ['kpis.ver' => 'Ver indicadores', 'kpis.gestionar' => 'Gestionar indicadores'],
        'Reportes' => ['reportes.ver' => 'Ver reportes', 'reportes.generar' => 'Generar/descargar reportes'],
        'Ministra' => ['ministra.ver' => 'Ver el despacho de la Ministra', 'ministra.informe' => 'Generar informe presidencial'],
        'IA Predictiva' => ['ia.ver' => 'Ver predicciones', 'ia.recomendar' => 'Generar recomendaciones con IA'],
        'Memorias' => ['memorias.generar' => 'Generar memorias institucionales'],
        'Red de Gestores' => ['gestores.participar' => 'Participar en el chat', 'gestores.notificar' => 'Notificar riesgos'],
        'Logs' => ['logs.ver' => 'Ver logs del sistema'],
        'Configuración' => ['config.gestionar' => 'Gestionar configuración', 'usuarios.gestionar' => 'Gestionar usuarios', 'roles.gestionar' => 'Gestionar roles y permisos'],
    ];

    public function index(): Response
    {
        $this->ensurePermissions();

        $roles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions' => $r->permissions->pluck('name'),
                'users_count' => $r->users()->count(),
            ]);

        return Inertia::render('Admin/Roles', [
            'roles' => $roles,
            'catalog' => collect(self::CATALOG)->map(fn (array $perms, string $group) => [
                'group' => $group,
                'permissions' => collect($perms)->map(fn (string $label, string $name) => ['name' => $name, 'label' => $label])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        $role = Role::findOrCreate($data['name'], 'web');
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', __('messages.role.created'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validateRole($request, $role);

        $role->name = $data['name'];
        $role->save();
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', __('messages.role.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['Super Admin', 'Administrador'], true)) {
            return back()->with('error', __('messages.role.cannot_delete', ['name' => $role->name]));
        }

        $role->delete();

        return back()->with('success', __('messages.role.deleted'));
    }

    private function ensurePermissions(): void
    {
        foreach (self::CATALOG as $perms) {
            foreach (array_keys($perms) as $name) {
                Permission::findOrCreate($name, 'web');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [], ['name' => 'nombre del rol']);
    }
}
