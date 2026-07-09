<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Lógica de la restricción "un solo dispositivo".
 *
 * Se guarda en `users.current_session_id` cuál es la sesión que "posee" al
 * usuario. Cualquier otra sesión (otro navegador/equipo) queda como no-dueña:
 * el middleware EnforceSingleDevice la expulsa mientras la dueña siga activa.
 *
 * Al iniciar sesión, si ya existe otra sesión activa, no se toma el control de
 * inmediato: se marca la sesión como "pendiente de confirmación" y se muestra el
 * aviso Sí/No (pantalla Auth/DeviceConflict).
 *
 * Es tolerante a que las columnas aún no existan (antes de migrar): todas las
 * escrituras van dentro de `rescue()` y `saveQuietly()`.
 */
class DeviceSession
{
    /** Clave de sesión que marca "recién autenticado, esperando decisión Sí/No". */
    public const PENDING_KEY = 'single_device.pending';

    public static function enabled(): bool
    {
        return (bool) config('security.single_device.enabled', true);
    }

    /**
     * Minutos tras los cuales la otra sesión deja de considerarse activa. Si no
     * se configura, se usa la vigencia de la sesión (session.lifetime).
     */
    public static function windowMinutes(): int
    {
        $window = (int) config('security.single_device.window_minutes', 0);

        return $window > 0 ? $window : (int) config('session.lifetime', 120);
    }

    /**
     * ¿Hay otra sesión activa (distinta a la actual) dentro de la ventana de
     * actividad? Es la condición para pedir confirmación al entrar y para
     * expulsar a una sesión que dejó de ser la dueña.
     */
    public static function otherSessionActive(User $user, string $currentSessionId): bool
    {
        $stored = $user->current_session_id;

        if (empty($stored) || $stored === $currentSessionId) {
            return false;
        }

        $activeAt = $user->current_session_active_at;

        return $activeAt !== null
            && $activeAt->gte(now()->subMinutes(self::windowMinutes()));
    }

    /** Marca la sesión actual como dueña del usuario (toma/renueva el control). */
    public static function claim(User $user, Request $request): void
    {
        rescue(fn () => $user->forceFill([
            'current_session_id' => $request->session()->getId(),
            'current_session_ip' => $request->ip(),
            'current_session_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'current_session_active_at' => now(),
        ])->saveQuietly(), null, false);
    }

    /** Renueva la marca de actividad de la sesión dueña (con throttle de 1 min). */
    public static function refresh(User $user): void
    {
        if (! is_null($user->current_session_active_at)
            && $user->current_session_active_at->gt(now()->subMinute())) {
            return;
        }

        rescue(fn () => $user->forceFill([
            'current_session_active_at' => now(),
        ])->saveQuietly(), null, false);
    }

    /** Libera la propiedad (al cerrar sesión el dueño). */
    public static function release(User $user): void
    {
        rescue(fn () => $user->forceFill([
            'current_session_id' => null,
            'current_session_ip' => null,
            'current_session_agent' => null,
            'current_session_active_at' => null,
        ])->saveQuietly(), null, false);
    }

    /**
     * Resuelve el inicio de sesión: si hay otra sesión activa, deja la sesión
     * "pendiente" y devuelve el redirect al aviso Sí/No; si no, toma el control
     * y devuelve null para que el controlador siga con su redirección normal.
     *
     * Debe llamarse DESPUÉS de Auth::login() y de regenerar la sesión, para que
     * el id capturado sea el definitivo.
     */
    public static function resolveLogin(Request $request, User $user): ?RedirectResponse
    {
        if (! self::enabled()) {
            self::claim($user, $request);

            return null;
        }

        if (self::otherSessionActive($user, $request->session()->getId())) {
            // No se toma el control todavía: se conserva la info del otro
            // dispositivo para mostrarla en el aviso.
            $request->session()->put(self::PENDING_KEY, true);

            return redirect()->route('device.conflict');
        }

        self::claim($user, $request);

        return null;
    }

    /** Datos del otro dispositivo para mostrar en el aviso de confirmación. */
    public static function otherDeviceInfo(User $user): array
    {
        return [
            'label' => self::label($user->current_session_agent),
            'ip' => $user->current_session_ip,
            'lastActive' => $user->current_session_active_at?->diffForHumans(),
        ];
    }

    /** Etiqueta legible (navegador · sistema) a partir del user-agent. */
    public static function label(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Navegador',
        };

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS'), str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad'), str_contains($ua, 'iOS') => 'iOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'otro dispositivo',
        };

        return $browser.' · '.$os;
    }
}
