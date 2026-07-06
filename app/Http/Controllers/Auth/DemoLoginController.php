<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Acceso temporal de demostración (correo + contraseña).
 * Gated por config('security.demo_login') / DEMO_LOGIN_ENABLED.
 * En producción con SSO institucional debe permanecer apagado.
 */
class DemoLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless((bool) config('security.demo_login'), 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
