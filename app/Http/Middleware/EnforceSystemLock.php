<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSystemLock
{
    /**
     * Bloqueo global del sistema ("modo mantenimiento") controlado por Setting.
     * Los administradores conservan acceso para poder desbloquear.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locked = (bool) Setting::value(config('security.system_lock_setting_key'), false);

        if ($locked && ! $request->user()?->hasAnyRole(['Super Admin', 'Administrador'])) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                abort(503, 'Sistema en mantenimiento.');
            }

            return response()->view('locked', [], 503);
        }

        return $next($request);
    }
}
