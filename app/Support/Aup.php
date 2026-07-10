<?php

namespace App\Support;

use App\Models\User;

/**
 * Aviso de uso aceptable y privacidad (A.5.10 / A.5.34). Cada usuario debe
 * aceptarlo en su primer acceso; si la versión del aviso cambia, se vuelve a
 * solicitar. Aplica a usuarios locales y de SSO por igual.
 */
class Aup
{
    public static function enabled(): bool
    {
        return (bool) config('security.aup.enabled', true);
    }

    public static function version(): string
    {
        return (string) config('security.aup.version', '1.0');
    }

    /** ¿El usuario debe aceptar (o re-aceptar) el aviso? */
    public static function required(User $user): bool
    {
        if (! self::enabled()) {
            return false;
        }

        return rescue(
            fn () => (string) ($user->aup_accepted_version ?? '') !== self::version(),
            false,
            false,
        );
    }

    /** Registra la aceptación de la versión vigente. */
    public static function accept(User $user): void
    {
        rescue(fn () => $user->forceFill([
            'aup_accepted_at' => now(),
            'aup_accepted_version' => self::version(),
        ])->saveQuietly(), null, false);
    }
}
