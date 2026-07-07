<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Resuelve los assets de identidad visual (logo sidebar, logo login,
 * fondo de login y favicon) administrables desde Configuración.
 * Las rutas se guardan en `settings` (clave branding.<key>) y los archivos
 * viven en el disco público (storage/app/public/branding).
 */
class Branding
{
    /** Claves de assets administrables. */
    public const KEYS = ['logo_sidebar', 'logo_login', 'login_background', 'favicon'];

    /** Ruta relativa guardada en settings (o null). */
    public static function path(string $key): ?string
    {
        return Setting::value("branding.{$key}");
    }

    /** URL pública del asset (o null si no se ha cargado). */
    public static function url(string $key): ?string
    {
        $path = static::path($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** Todas las URLs para compartir con el frontend. */
    public static function urls(): array
    {
        return [
            'logo_sidebar' => static::url('logo_sidebar'),
            'logo_login' => static::url('logo_login'),
            'login_background' => static::url('login_background'),
            'favicon' => static::url('favicon'),
        ];
    }
}
