<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Acceso temporal de demostración (correo + contraseña).
 * Gated por config('security.demo_login') / DEMO_LOGIN_ENABLED.
 * En producción con SSO institucional debe permanecer apagado.
 *
 * Protección anti-fuerza bruta (A.8.5): límite de intentos por correo+IP con
 * bloqueo temporal. Los eventos de acceso los registra LogAuthenticationEvents.
 */
class DemoLoginController extends Controller
{
    /** Intentos fallidos permitidos antes del bloqueo temporal. */
    private const MAX_ATTEMPTS = 5;

    /** Ventana de decaimiento del contador, en segundos. */
    private const DECAY_SECONDS = 60;

    public function store(Request $request): RedirectResponse
    {
        // SSO solo Google: sin login local mientras sso_only esté activo.
        abort_if((bool) config('security.sso_only'), 404);
        abort_unless((bool) config('security.demo_login'), 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        // Auth::attempt emite los eventos Failed/Login que quedan en la bitácora.
        if (! Auth::attempt($credentials, true)) {
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('messages.auth.invalid_credentials'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Un solo dispositivo: si ya hay otra sesión activa, pide confirmación.
        if ($redirect = \App\Support\DeviceSession::resolveLogin($request, Auth::user())) {
            return $redirect;
        }

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
            'email' => __('messages.auth.too_many_attempts', ['seconds' => $seconds]),
        ]);
    }

    /** Clave del limitador: por correo e IP, para no penalizar a otros usuarios. */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
