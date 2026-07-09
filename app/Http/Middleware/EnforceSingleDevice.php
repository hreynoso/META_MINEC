<?php

namespace App\Http\Middleware;

use App\Support\DeviceSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricción "un solo dispositivo" (ver App\Support\DeviceSession).
 *
 * - Fase pendiente: el usuario recién autenticó pero hay otra sesión activa;
 *   se le retiene en el aviso Sí/No hasta que decida.
 * - Sesión dueña: renueva su marca de actividad.
 * - Sesión no-dueña: si la dueña sigue activa, se cierra esta (se inició sesión
 *   en otro dispositivo). Si la dueña quedó inactiva (p. ej. reingreso por
 *   "recordarme" en el mismo equipo), se reclama en silencio sin expulsar.
 */
class EnforceSingleDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! DeviceSession::enabled()) {
            return $next($request);
        }

        $session = $request->session();

        // Fase de confirmación: solo se permiten las rutas del aviso y el logout.
        if ($session->get(DeviceSession::PENDING_KEY)) {
            if ($request->routeIs('device.conflict', 'device.conflict.continue', 'device.conflict.cancel', 'logout')) {
                return $next($request);
            }

            return redirect()->route('device.conflict');
        }

        $stored = $user->current_session_id;
        $sid = $session->getId();

        // Sesión dueña (o columna vacía → reclama esta como dueña).
        if (empty($stored)) {
            DeviceSession::claim($user, $request);

            return $next($request);
        }

        if ($stored === $sid) {
            DeviceSession::refresh($user);

            return $next($request);
        }

        // No es la dueña: la otra sigue activa → se cierra esta sesión.
        if (DeviceSession::otherSessionActive($user, $sid)) {
            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')
                ->with('error', __('messages.auth.session_closed_other_device'));
        }

        // La dueña quedó inactiva → se toma el control en silencio.
        DeviceSession::claim($user, $request);

        return $next($request);
    }
}
