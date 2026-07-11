<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Alertas de eventos de seguridad (ISO 27001 A.8.16). Envía un correo a los
 * administradores ante eventos sospechosos (p. ej. bloqueo por fuerza bruta).
 * Nunca interrumpe el flujo: todo va dentro de rescue().
 */
class SecurityAlert
{
    public const RECIPIENTS_KEY = 'security.alert_recipients';

    /**
     * Las alertas de seguridad están SIEMPRE activas y no pueden desactivarse
     * (requisito institucional / ISO 27001 A.8.16). No existe interruptor para
     * apagarlas; solo se configuran los destinatarios.
     */
    public static function enabled(): bool
    {
        return true;
    }

    /** Correos del personal de seguridad TIC configurados en el apartado. */
    public static function configuredRecipients(): string
    {
        return (string) Setting::value(self::RECIPIENTS_KEY, '');
    }

    /**
     * Destinatarios: los correos configurados en el apartado; si no hay, los del
     * env; y si tampoco, todos los administradores activos.
     *
     * @return string[]
     */
    public static function recipients(): array
    {
        $fromSetting = self::parse(self::configuredRecipients());
        if (! empty($fromSetting)) {
            return $fromSetting;
        }

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

    /** @return string[] */
    private static function parse(string $csv): array
    {
        return array_values(array_filter(array_map(
            fn ($e) => strtolower(trim((string) $e)),
            explode(',', $csv),
        )));
    }

    /** Alerta estándar de cambio en configuración sensible (A.8.16), con actor e IP. */
    public static function configChanged(string $area): void
    {
        $actor = request()?->user()?->name ?: (request()?->user()?->email ?? 'Sistema');

        self::notify(
            'META · Alerta de seguridad: cambio de configuración sensible',
            "Se modificó la configuración: {$area}.\n\n"
            ."Realizado por: {$actor}\n"
            ."IP: ".(request()?->ip() ?: '—')."\n"
            ."Fecha (UTC): ".now()->toDateTimeString(),
        );
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
