<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Registro de eventos de acceso (ISO 27001 A.8.15 / A.8.16): inicio y cierre de
 * sesión, intentos fallidos y bloqueos por exceso de intentos. Se guardan en el
 * activity log con log_name "auth" y aparecen en Logs del Sistema → "Accesos".
 */
class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        $this->record('Inicio de sesión', $event->user, [
            'email' => $event->user?->getAttribute('email'),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $this->record('Cierre de sesión', $event->user, [
            'email' => $event->user?->getAttribute('email'),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        // Sin causer: el intento no llegó a autenticarse. Nunca se registra la clave.
        $this->record('Intento de acceso fallido', null, [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->record('Bloqueo por exceso de intentos', null, [
            'email' => $event->request->input('email'),
        ]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function record(string $description, $causer, array $props): void
    {
        try {
            $request = request();

            $logger = activity('auth')->withProperties(array_filter([
                'email' => $props['email'] ?? null,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ], fn ($v) => $v !== null && $v !== ''));

            if ($causer !== null) {
                $logger->causedBy($causer);
            }

            $logger->log($description);
        } catch (\Throwable $e) {
            // La bitácora nunca debe interrumpir el flujo de autenticación.
        }
    }
}
