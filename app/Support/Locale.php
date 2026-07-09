<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Idioma del sistema (Configuración → Idioma). Se guarda en Settings con la
 * clave `system.locale`. Idiomas disponibles inicialmente: español e inglés.
 */
class Locale
{
    public const SETTING_KEY = 'system.locale';

    public const DEFAULT = 'es';

    /** @var array<string, string> Código => nombre para mostrar. */
    public const SUPPORTED = [
        'es' => 'Español',
        'en' => 'English',
    ];

    /** Idioma actual del sistema (validado contra los soportados). */
    public static function current(): string
    {
        $locale = (string) Setting::value(self::SETTING_KEY, self::DEFAULT);

        return array_key_exists($locale, self::SUPPORTED) ? $locale : self::DEFAULT;
    }

    /** ¿Es un código de idioma soportado? */
    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::SUPPORTED);
    }

    /** @return array<int, array{code: string, label: string}> Para el selector del frontend. */
    public static function options(): array
    {
        $options = [];

        foreach (self::SUPPORTED as $code => $label) {
            $options[] = ['code' => $code, 'label' => $label];
        }

        return $options;
    }
}
