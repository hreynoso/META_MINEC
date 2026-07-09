<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica el idioma del sistema (Configuración → Idioma) a cada petición, para
 * que las validaciones, mensajes y documentos server-side salgan en el idioma
 * elegido. El idioma se comparte al frontend vía HandleInertiaRequests.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(Locale::current());

        return $next($request);
    }
}
