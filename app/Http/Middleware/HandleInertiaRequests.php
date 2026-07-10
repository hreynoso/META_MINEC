<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // Token CSRF para envíos de formularios nativos (descargas por POST).
            'csrf' => csrf_token(),
            // Idioma del sistema (Configuración → Idioma) para sincronizar vue-i18n.
            'locale' => \App\Support\Locale::current(),
            // A.8.9 — parámetros del cierre de sesión por inactividad (frontend).
            'security' => [
                'idle' => [
                    'enabled' => (bool) config('security.session_idle.enabled', true),
                    'minutes' => (int) config('security.session_idle.minutes', 30),
                    'warnSeconds' => (int) config('security.session_idle.warn_seconds', 60),
                ],
            ],
            'auth' => [
                'user' => $request->user()
                    ? [
                        ...$request->user()->only(['id', 'name', 'email']),
                        'avatar' => $request->user()->avatarUrl(),
                    ]
                    : null,
                'roles' => $request->user()?->getRoleNames() ?? [],
                'permissions' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
                // Usuarios activos en los últimos 5 minutos (tolerante si falta la columna).
                'online' => fn () => rescue(fn () => \App\Models\User::where('last_seen_at', '>=', now()->subMinutes(5))->count(), 1, false),
            ],
            'branding' => [
                'app_name' => config('branding.app_name'),
                'institution' => config('branding.institution'),
                'institution_short' => config('branding.institution_short'),
                'assets' => \App\Support\Branding::urls(),
                'colors' => \App\Support\Branding::colors(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'report' => fn () => $request->session()->get('report'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
