<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Support\SheetExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['roles:id,name', 'institution:id,short_name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'institution_id' => $u->institution_id,
                'institution' => $u->institution?->short_name,
                'roles' => $u->roles->pluck('name'),
                'blocked' => $u->blocked_at !== null,
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'institutions' => Institution::orderBy('name')->get(['id', 'short_name', 'name']),
        ]);
    }

    public function export(): StreamedResponse
    {
        $rows = User::with(['roles:id,name', 'institution:id,short_name'])->orderBy('name')->get()
            ->map(fn (User $u) => [
                $u->name,
                $u->email,
                $u->institution?->short_name ?? '',
                $u->roles->pluck('name')->implode(', '),
                $u->blocked_at !== null ? 'Bloqueado' : 'Activo',
            ])->all();

        return SheetExport::stream('usuarios', ['Nombres y Apellidos', 'Correo', 'Institución', 'Roles', 'Estado'], $rows);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        if ($this->grantsAdminRole($request, $data, null)) {
            return back()->with('error', 'Solo un usuario con el rol Administrador puede asignar el rol Administrador.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution_id' => $data['institution_id'] ?? null,
            'password' => Hash::make($data['password']),
            'blocked_at' => ($data['blocked'] ?? false) ? now() : null,
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

        if ($this->grantsAdminRole($request, $data, $user)) {
            return back()->with('error', 'Solo un usuario con el rol Administrador puede asignar el rol Administrador.');
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution_id' => $data['institution_id'] ?? null,
            'blocked_at' => ($data['blocked'] ?? false) ? ($user->blocked_at ?? now()) : null,
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles($data['roles'] ?? []);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * ¿La operación intenta OTORGAR el rol Administrador sin que el usuario que
     * la ejecuta lo tenga? Solo un Administrador puede conceder ese rol.
     *
     * @param  array<string, mixed>  $data
     */
    private function grantsAdminRole(Request $request, array $data, ?User $user): bool
    {
        $wantsAdmin = in_array('Administrador', $data['roles'] ?? [], true);
        $alreadyAdmin = $user?->hasRole('Administrador') ?? false;
        $canGrant = $request->user()->hasAnyRole(['Super Admin', 'Administrador']);

        return $wantsAdmin && ! $alreadyAdmin && ! $canGrant;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'blocked' => ['boolean'],
        ], [], [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
            'institution_id' => 'institución',
            'roles' => 'roles',
        ]);
    }
}
