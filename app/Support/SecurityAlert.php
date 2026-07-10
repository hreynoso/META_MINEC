<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Alertas de eventos de seguridad (ISO 27001 A.8.16). Envía un correo a los
 * administradores ante eventos sospechosos (p. ej. bloqueo por fuerza bruta).
 * Nunca interrumpe el flujo: todo va dentro de rescue().
 */
class SecurityAlert
{
    public static function enabled(): bool
    {
        return (bool) config('security.alerts.enabled', true);
    }

    /**
     * Destinatarios: los correos configurados o, si no hay, todos los
     * administradores activos (Super Admin / Administrador).
     *
     * @return string[]
     */
    public static function recipients(): array
    {
        $configured = (array) config('security.alerts.recipients', []);

        if (! empty($configured)) {
            return $configured;
        }

        return rescue(fn () => User::role(['Super Admin', 'Administrador'])
            ->whereNull('blocked_at')
            ->pluck('email')
            ->filter()
            ->values()
            ->all(), [], false);
    }

    public static function notify(string $subject, string $body): void
    {
        if (! self::enabled()) {
            return;
        }

        rescue(function () use ($subject, $body) {
            $to = self::recipients();

            if (empty($to)) {
                return;
            }

            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        }, null, false);
    }
}
