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
    protected $signature = 'superadmin:sync {--email=} {--password=} {--name=}';

    protected $description = 'Crea o actualiza la cuenta local Super Admin (por opciones o variables de entorno).';

    public function handle(): int
    {
        // Las opciones tienen prioridad sobre las variables de entorno; así se
        // puede provisionar a mano en la terminal aunque la config esté cacheada.
        $email = $this->option('email') ?: env('SUPERADMIN_EMAIL');
        $password = $this->option('password') ?: env('SUPERADMIN_PASSWORD');
        $name = $this->option('name') ?: env('SUPERADMIN_NAME', 'Super Administrador');

        if (blank($email) || blank($password)) {
            $this->warn('Falta el correo o la contraseña (opciones --email/--password o SUPERADMIN_EMAIL/SUPERADMIN_PASSWORD); se omite.');

            return self::SUCCESS;
        }

        $email = strtolower(trim((string) $email));

        Role::findOrCreate('Super Admin', 'web');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'blocked_at' => null,
            ],
        );

        $user->syncRoles(['Super Admin']);

        $this->info("Super Admin sincronizado: {$email} (rol Super Admin: ".($user->fresh()->hasRole('Super Admin') ? 'sí' : 'NO').')');

        return self::SUCCESS;
    }
}
