<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /** Roles observados en el módulo de Configuración del prototipo. */
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $roles = [
            'Administrador',
            'Directivo',
            'Gestor de Proyectos',
            'Analista',
            'Consultor',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
