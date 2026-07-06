<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Roles base. En SED eran 6; aqui se dejan como punto de partida —
     * ajustar a la estructura funcional real de MINEC.
     */
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $roles = [
            'Administrador',
            'Supervisor',
            'Analista',
            'Consulta',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
