<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierre de sesión por inactividad (ISO 27001 A.8.5 / A.8.9). Refuerzo del lado
 * del servidor del temporizador del frontend: si pasó más de N minutos desde la
 * última actividad registrada en la sesión, se cierra. La marca se actualiza en
 * cada petición del usuario (incluida la de keep-alive, que el frontend solo
 * envía cuando hay actividad reciente).
 */
class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.session_idle.enabled', true) || ! $request->user()) {
            return $next($request);
        }

        $minutes = max(1, (int) config('security.session_idle.minutes', 30));
        $session = $request->session();
        $last = (int) $session->get('last_activity_at', 0);
        $now = now()->timestamp;

        if ($last > 0 && ($now - $last) > $minutes * 60) {
            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')
                ->with('error', __('messages.auth.idle_timeout'));
        }

        $session->put('last_activity_at', $now);

        return $next($request);
    }
}
