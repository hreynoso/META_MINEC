<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use App\Rules\NotInPasswordHistory;
use App\Support\ExportName;
use App\Support\LocalTime;
use App\Support\PasswordPolicy;
use App\Support\SheetExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /** A.5.18 — atestación de la última revisión de accesos. */
    private const REVIEW_KEY = 'security.access_review';

    /** Días sin acceder tras los cuales una cuenta se considera inactiva (A.5.18). */
    private const DORMANT_DAYS = 90;

    public function index(): Response
    {
        $threshold = now()->subDays(self::DORMANT_DAYS);

        $users = User::with(['roles:id,name', 'institution:id,short_name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($threshold) {
                $roles = $u->roles->pluck('name');
                $never = $u->last_login_at === null;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'institution_id' => $u->institution_id,
                    'institution' => $u->institution?->short_name,
                    'roles' => $roles,
                    'blocked' => $u->blocked_at !== null,
                    'last_login_at' => LocalTime::format($u->last_login_at),
                    // Origen del acceso: SSO (tiene google_id) o cuenta local.
                    'origin' => filled($u->google_id) ? 'sso' : 'local',
                    // Revisión de accesos (A.5.18): privilegio e inactividad.
                    'privileged' => (bool) $roles->intersect(['Super Admin', 'Administrador'])->count(),
                    'never' => $never,
                    'dormant' => $never || $u->last_login_at->lt($threshold),
                ];
            });

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'institutions' => Institution::active()->orderBy('name')->get(['id', 'short_name', 'name']),
            'currentUserId' => auth()->id(),
            'dormantDays' => self::DORMANT_DAYS,
            'lastReview' => $this->lastReview(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $threshold = now()->subDays(self::DORMANT_DAYS);

        $rows = User::with(['roles:id,name', 'institution:id,short_name'])->orderBy('name')->get()
            ->map(function (User $u) use ($threshold) {
                $never = $u->last_login_at === null;
                $privileged = (bool) $u->roles->pluck('name')->intersect(['Super Admin', 'Administrador'])->count();

                return [
                    $u->name,
                    $u->email,
                    $u->institution?->short_name ?? '',
                    $u->roles->pluck('name')->implode(', '),
                    $u->blocked_at !== null ? 'Bloqueado' : 'Activo',
                    filled($u->google_id) ? 'SSO' : 'Local',
                    LocalTime::format($u->last_login_at) ?? __('messages.user.never_logged_in'),
                    $privileged ? 'Sí' : 'No',
                    ($never || $u->last_login_at->lt($threshold)) ? 'Sí' : 'No',
                ];
            })->all();

        return SheetExport::stream(
            ExportName::make('Usuarios', 'xlsx'),
            ['Nombres y Apellidos', 'Correo', 'Institución', 'Roles', 'Estado', 'Origen', 'Último acceso', 'Privilegiado', 'Inactivo '.self::DORMANT_DAYS.'+ días'],
            $rows,
        );
    }

    /** Bloquea o desbloquea una cuenta (A.5.18). Alerta en ambos casos (A.8.16). */
    public function toggleBlock(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', __('messages.user.cannot_block_self'));
        }

        if ($user->blocked_at !== null) {
            $user->update(['blocked_at' => null]);

            $this->alertSecurity(
                'META · Alerta de seguridad: cuenta desbloqueada',
                "Se desbloqueó la cuenta {$user->email}.",
            );

            return back()->with('success', __('messages.user.unblocked'));
        }

        $user->update(['blocked_at' => now()]);

        $this->alertSecurity(
            'META · Alerta de seguridad: cuenta bloqueada',
            "Se bloqueó la cuenta {$user->email}.",
        );

        return back()->with('success', __('messages.user.blocked'));
    }

    /** Registra una atestación de revisión de accesos (evidencia A.5.18). */
    public function recordReview(Request $request): RedirectResponse
    {
        $by = $request->user()?->name ?: ($request->user()?->email ?? 'Sistema');

        Setting::put(self::REVIEW_KEY, json_encode(['at' => now()->toIso8601String(), 'by' => $by]));

        rescue(fn () => activity('security')
            ->causedBy($request->user())
            ->log('Revisión de accesos registrada'), null, false);

        return back()->with('success', __('messages.security.review_recorded'));
    }

    /** Última revisión de accesos registrada (o null). */
    private function lastReview(): ?array
    {
        $raw = Setting::value(self::REVIEW_KEY);

        if (! $raw) {
            return null;
        }

        $data = json_decode((string) $raw, true);

        if (! is_array($data) || empty($data['at'])) {
            return null;
        }

        return [
            'at' => LocalTime::format(Carbon::parse($data['at'])),
            'by' => $data['by'] ?? '—',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        if ($this->grantsAdminRole($request, $data, null)) {
            return back()->with('error', __('messages.user.only_admin_can_grant_admin'));
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution_id' => $data['institution_id'] ?? null,
            'password' => Hash::make($data['password']),
            'blocked_at' => ($data['blocked'] ?? false) ? now() : null,
        ]);

        PasswordPolicy::record($user, $user->password);
        $user->syncRoles($data['roles'] ?? []);

        if ($this->hasAdminRole($data['roles'] ?? [])) {
            $this->alertSecurity(
                'META · Alerta de seguridad: rol administrativo asignado',
                "Se creó el usuario {$user->email} con un rol administrativo.",
            );
        }

        return back()->with('success', __('messages.user.created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

        if ($this->grantsAdminRole($request, $data, $user)) {
            return back()->with('error', __('messages.user.only_admin_can_grant_admin'));
        }

        // Estado previo para detectar cambios sensibles (bloqueo / privilegios).
        $wasBlocked = $user->blocked_at !== null;
        $hadAdmin = $user->hasAnyRole(['Super Admin', 'Administrador']);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution_id' => $data['institution_id'] ?? null,
            'blocked_at' => ($data['blocked'] ?? false) ? ($user->blocked_at ?? now()) : null,
        ]);

        $passwordChanged = filled($data['password'] ?? null);
        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if ($passwordChanged) {
            PasswordPolicy::record($user, $user->password);
        }

        $user->syncRoles($data['roles'] ?? []);

        if ($user->blocked_at !== null && ! $wasBlocked) {
            $this->alertSecurity(
                'META · Alerta de seguridad: cuenta bloqueada',
                "Se bloqueó la cuenta {$user->email}.",
            );
        }

        if ($this->hasAdminRole($data['roles'] ?? []) && ! $hadAdmin) {
            $this->alertSecurity(
                'META · Alerta de seguridad: rol administrativo asignado',
                "Se asignó un rol administrativo a {$user->email}.",
            );
        }

        return back()->with('success', __('messages.user.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', __('messages.user.cannot_delete_self'));
        }

        $email = $user->email;
        $user->delete();

        $this->alertSecurity(
            'META · Alerta de seguridad: cuenta eliminada',
            "Se eliminó la cuenta {$email}.",
        );

        return back()->with('success', __('messages.user.deleted'));
    }

    /** @param array<int, string> $roles ¿Incluye algún rol administrativo? */
    private function hasAdminRole(array $roles): bool
    {
        return count(array_intersect($roles, ['Super Admin', 'Administrador'])) > 0;
    }

    /** Envía una alerta de seguridad al personal TIC (A.8.16), con actor e IP. */
    private function alertSecurity(string $subject, string $detail): void
    {
        $actor = request()->user()?->name ?: (request()->user()?->email ?? 'Sistema');

        \App\Support\SecurityAlert::notify(
            $subject,
            $detail."\n\nRealizado por: {$actor}\nIP: ".(request()->ip() ?: '—')."\nFecha (UTC): ".now()->toDateTimeString(),
        );
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
            'password' => [$user ? 'nullable' : 'required', 'string', PasswordPolicy::rule(), new NotInPasswordHistory($user)],
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
