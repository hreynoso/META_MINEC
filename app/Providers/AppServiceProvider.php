<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
