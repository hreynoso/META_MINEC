<?php

namespace App\Rules;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * Impide reutilizar una de las últimas N contraseñas del usuario (A.5.17).
 * En creación (sin usuario aún) no aplica.
 */
class NotInPasswordHistory implements ValidationRule
{
    public function __construct(private readonly ?User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user || ! PasswordPolicy::historyEnabled()) {
            return;
        }

        $recent = PasswordHistory::where('user_id', $this->user->id)
            ->orderByDesc('id')
            ->take(PasswordPolicy::remember())
            ->pluck('password');

        foreach ($recent as $hash) {
            if (Hash::check((string) $value, (string) $hash)) {
                $fail(__('messages.user.password_reused'));

                return;
            }
        }
    }
}
