<?php

namespace App\Providers;

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
    }
}
