<?php

use App\Http\Middleware\EnforceSingleDevice;
use App\Http\Middleware\EnforceSystemLock;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Las rutas de autenticación deben ir en el grupo `web` para tener
            // sesión, cookies y CSRF (StartSession). Sin esto, session() falla.
            Route::middleware('web')->group(__DIR__.'/../routes/auth.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Traefik va delante del contenedor
        $middleware->trustProxies(at: '*');

        // La cookie `tz` (zona horaria del navegador) la escribe el frontend en
        // texto plano; se excluye del cifrado para poder leerla server-side.
        $middleware->encryptCookies(except: ['tz']);

        $middleware->web(append: [
            SetLocale::class,              // idioma del sistema (Configuración → Idioma)
            HandleInertiaRequests::class,
            EnsureUserIsNotBlocked::class,
            EnforceSingleDevice::class,    // un solo dispositivo activo por usuario
            EnforceSystemLock::class,      // "modo mantenimiento" controlado por Setting
            TrackActivity::class,          // marca de última actividad (usuarios conectados)
            SecurityHeaders::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // El webhook de Mailgun llega sin token CSRF.
        $middleware->validateCsrfTokens(except: ['webhooks/mailgun']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
