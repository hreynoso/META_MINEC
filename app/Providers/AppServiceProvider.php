<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Todos los registros se ALMACENAN en UTC (zona canónica). La conversión
        // a la zona horaria del equipo de cada usuario se hace al mostrar, con la
        // zona que envía el navegador (cookie `tz`) — ver App\Support\LocalTime.
        // Se fija explícito porque Laravel 11/12 no publica config/app.php.
        date_default_timezone_set('UTC');
        config(['app.timezone' => 'UTC']);
    }

    public function boot(): void
    {
        // TLS lo termina Traefik; forzamos https en produccion detras del proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // El SSO usa el driver "google" nativo de laravel/socialite; no requiere
        // registrar un proveedor externo.

        // Bitácora de eventos de acceso (ISO 27001 A.8.15/A.8.16).
        Event::subscribe(LogAuthenticationEvents::class);

        // Bypass de máximo privilegio: el rol "Super Admin" concede toda
        // habilidad. Devolver null deja que las demás verificaciones sigan su
        // curso normal (no niega, solo concede al super admin).
        Gate::before(fn ($user) => $user?->hasRole('Super Admin') ? true : null);
    }
}
