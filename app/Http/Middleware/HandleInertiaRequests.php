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
            'auth' => [
                'user' => $request->user()
                    ? $request->user()->only(['id', 'name', 'email'])
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
