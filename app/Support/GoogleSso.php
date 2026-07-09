<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resuelve la configuración del SSO de Google Workspace. Prioriza los valores
 * administrables desde Configuración (tabla settings) y cae a los de entorno
 * (config/services.php) si no se han definido en la UI.
 */
class GoogleSso
{
    public static function clientId(): ?string
    {
        return Setting::value('sso.google.client_id') ?: config('services.google.client_id');
    }

    public static function clientSecret(): ?string
    {
        return Setting::value('sso.google.client_secret') ?: config('services.google.client_secret');
    }

    public static function redirectUri(): ?string
    {
        return Setting::value('sso.google.redirect') ?: config('services.google.redirect');
    }

    public static function hostedDomain(): ?string
    {
        return Setting::value('sso.google.hosted_domain') ?: config('services.google.hosted_domain');
    }

    /** ¿El SSO tiene lo mínimo para funcionar (ID, secreto y URL de retorno)? */
    public static function isConfigured(): bool
    {
        return filled(static::clientId())
            && filled(static::clientSecret())
            && filled(static::redirectUri());
    }

    /** Inyecta la config resuelta en el driver "google" de Socialite. */
    public static function apply(): void
    {
        config(['services.google' => [
            'client_id' => static::clientId(),
            'client_secret' => static::clientSecret(),
            'redirect' => static::redirectUri(),
            'hosted_domain' => static::hostedDomain(),
        ]]);
    }
}
