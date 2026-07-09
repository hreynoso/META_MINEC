<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Acceso local exclusivo de la cuenta "Super Admin" (break-glass). Es
 * independiente del SSO: el resto de usuarios entra solo por Google Workspace.
 *
 * Protección anti-fuerza bruta (A.8.5): límite de intentos por correo+IP con
 * bloqueo temporal. Los eventos de acceso quedan en la bitácora (A.8.15/A.8.16).
 */
class LocalAdminController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function store(Request $request): RedirectResponse
    {
        abort_unless((bool) config('security.local_admin_login'), 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $credentials['email'])->first();

        // Pueden usar el acceso local: la cuenta con rol "Super Admin" y los
        // correos incluidos en la lista de autorizados (cuentas de demostración).
        $allowlist = (array) config('security.local_login_emails', []);
        $emailAllowed = in_array(Str::lower((string) $credentials['email']), $allowlist, true);

        if (! $user
            || ! $user->password
            || ! Hash::check($credentials['password'], $user->password)
            || (! $user->hasRole('Super Admin') && ! $emailAllowed)) {
            event(new Failed('web', $user, $credentials));
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Credenciales no válidas para el acceso local.',
            ]);
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'email' => 'La cuenta está bloqueada. Contacte al administrador.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Lanza un error de validación (y registra el bloqueo) si se excede el límite. */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos fallidos. Intente de nuevo en {$seconds} segundos.",
        ]);
    }

    /** Clave del limitador: por correo e IP, para no penalizar a otros usuarios. */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
