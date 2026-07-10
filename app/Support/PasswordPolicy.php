<?php

namespace App\Support;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * Política de contraseñas (ISO 27001 A.5.17) para cuentas locales
 * (Super Admin / demo). Con SSO puro rara vez aplica, pero endurece cualquier
 * credencial local. Incluye el historial para evitar reutilización.
 */
class PasswordPolicy
{
    /** Regla de complejidad configurable en config/security.php. */
    public static function rule(): Password
    {
        $c = (array) config('security.password_policy');
        $rule = Password::min((int) ($c['min'] ?? 12));

        if ($c['mixed_case'] ?? true) {
            $rule->mixedCase();
        }
        if ($c['numbers'] ?? true) {
            $rule->numbers();
        }
        if ($c['symbols'] ?? true) {
            $rule->symbols();
        }

        return $rule;
    }

    public static function remember(): int
    {
        return (int) config('security.password_history.remember', 5);
    }

    public static function historyEnabled(): bool
    {
        return (bool) config('security.password_history.enabled', true);
    }

    /** Registra el hash en el historial y poda los que exceden el límite. */
    public static function record(User $user, string $hashedPassword): void
    {
        if (! self::historyEnabled()) {
            return;
        }

        rescue(function () use ($user, $hashedPassword) {
            PasswordHistory::create(['user_id' => $user->id, 'password' => $hashedPassword]);

            $stale = PasswordHistory::where('user_id', $user->id)
                ->orderByDesc('id')
                ->skip(self::remember())
                ->take(1000)
                ->pluck('id');

            if ($stale->isNotEmpty()) {
                PasswordHistory::whereIn('id', $stale)->delete();
            }
        }, null, false);
    }
}
