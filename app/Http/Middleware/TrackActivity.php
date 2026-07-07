<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actualiza la marca de última actividad del usuario autenticado (con throttle
 * de 1 minuto) para alimentar el indicador de usuarios conectados. Es tolerante
 * a que la columna aún no exista (antes de migrar).
 */
class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (is_null($user->last_seen_at) || $user->last_seen_at->lt(now()->subMinute()))) {
            rescue(fn () => $user->forceFill(['last_seen_at' => now()])->saveQuietly(), null, false);
        }

        return $next($request);
    }
}
