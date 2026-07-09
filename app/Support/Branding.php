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

    /** Colores administrables y sus valores por defecto (variables CSS del tema). */
    public const COLORS = [
        'sidebar' => '#1b2a63',        // fondo del left sidebar (--color-shell)
        'sidebar_hover' => '#29397a',  // hover/activo del sidebar (--color-shell-hover)
        'brand' => '#0d9488',          // color primario del sistema (--color-brand)
    ];

    /** Colores actuales (settings o valor por defecto). */
    public static function colors(): array
    {
        $colors = [];

        foreach (static::COLORS as $key => $default) {
            $colors[$key] = Setting::value("branding.color.{$key}", $default);
        }

        return $colors;
    }

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

    /** Asset como data URI base64 (para incrustarlo en PDFs). Null si no existe. */
    public static function dataUri(string $key): ?string
    {
        $path = static::path($key);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
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
