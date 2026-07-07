<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce por petición para el CSP. Se genera ANTES de renderizar la vista
        // para que @routes (Ziggy) y @vite lo incrusten en sus etiquetas <script>.
        $nonce = null;
        if (config('security.csp.enabled')) {
            $nonce = Vite::useCspNonce();
        }

        $response = $next($request);

        foreach (config('security.headers', []) as $header => $value) {
            $response->headers->set($header, $value);
        }

        // HSTS solo sobre HTTPS (detrás de Traefik con proxies de confianza).
        if (config('security.hsts.enabled') && $request->isSecure()) {
            $hsts = 'max-age='.config('security.hsts.max_age');
            if (config('security.hsts.include_subdomains')) {
                $hsts .= '; includeSubDomains';
            }
            if (config('security.hsts.preload')) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        if ($nonce !== null) {
            $header = config('security.csp.report_only')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';
            $response->headers->set($header, sprintf(config('security.csp.policy'), $nonce));
        }

        return $response;
    }
}
