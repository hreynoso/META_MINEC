<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Crea o actualiza la cuenta local "Super Admin" (break-glass) a partir de las
 * variables de entorno SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD. Idempotente: se
 * puede ejecutar en cada despliegue. Si faltan las variables, no hace nada.
 */
class SyncSuperAdminCommand extends Command
{
    protected $signature = 'superadmin:sync';

    protected $description = 'Sincroniza la cuenta local Super Admin desde variables de entorno.';

    public function handle(): int
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->warn('SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD no definidos; se omite la sincronización.');

            return self::SUCCESS;
        }

        Role::findOrCreate('Super Admin', 'web');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPERADMIN_NAME', 'Super Administrador'),
                'password' => Hash::make($password),
                'blocked_at' => null,
            ],
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $this->info("Super Admin sincronizado: {$email}");

        return self::SUCCESS;
    }
}
