<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DeviceSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aviso "sesión activa en otro dispositivo" (restricción de un solo
 * dispositivo). El usuario ya está autenticado pero su sesión quedó
 * "pendiente": debe decidir Sí (tomar el control aquí) o No (mantener la otra).
 */
class DeviceSessionController extends Controller
{
    public function conflict(Request $request): Response|RedirectResponse
    {
        // Si ya no está pendiente (p. ej. se recargó tras decidir), sigue normal.
        if (! $request->session()->get(DeviceSession::PENDING_KEY)) {
            return redirect()->intended(route('dashboard'));
        }

        return Inertia::render('Auth/DeviceConflict', [
            'otherDevice' => DeviceSession::otherDeviceInfo($request->user()),
        ]);
    }

    /** "Sí": toma el control en este dispositivo (expulsa al anterior). */
    public function continueHere(Request $request): RedirectResponse
    {
        $user = $request->user();

        DeviceSession::claim($user, $request);
        $request->session()->forget(DeviceSession::PENDING_KEY);

        return redirect()->intended(route('dashboard'));
    }

    /** "No": no continúa aquí; la sesión del otro dispositivo permanece activa. */
    public function cancel(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', __('messages.auth.session_kept_other_device'));
    }
}
