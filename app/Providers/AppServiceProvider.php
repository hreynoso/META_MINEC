<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Zona horaria institucional (El Salvador, UTC−6, sin horario de verano). */
    private const TIMEZONE = 'America/El_Salvador';

    public function register(): void
    {
        // Laravel 11/12 no publica config/app.php, por lo que APP_TIMEZONE no se
        // aplicaba y las fechas se guardaban/mostraban en UTC (hora adelantada).
        // Se fija de forma explícita la zona de El Salvador para que created_at,
        // now() y todos los ->format(...) reflejen la hora local correcta.
        date_default_timezone_set(self::TIMEZONE);
        config(['app.timezone' => self::TIMEZONE]);
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
