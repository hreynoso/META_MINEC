<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuario de acceso temporal para demostración.
 * Solo es útil si DEMO_LOGIN_ENABLED=true. Eliminar/rotar en producción.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@minec.gob.sv'],
            [
                'name' => 'María E. Rodríguez',
                'password' => Hash::make('MetaDemo2026*'),
            ],
        );

        if (! $user->hasRole('Administrador')) {
            $user->assignRole('Administrador');
        }
    }
}
