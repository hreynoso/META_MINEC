<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Rol exclusivo "Ministra": destinatario de los informes de su tab (informe
 * presidencial y envíos automáticos). Se le conceden los permisos de lectura de
 * su despacho más los tableros que consulta.
 */
return new class extends Migration
{
    private const PERMS = [
        'ministra.ver', 'ministra.informe',
        'proyectos.ver', 'kpis.ver', 'reportes.ver', 'ia.ver',
    ];

    public function up(): void
    {
        Artisan::call('permission:cache-reset');

        foreach (self::PERMS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $role = Role::findOrCreate('Ministra', 'web');
        $role->syncPermissions(self::PERMS);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // No se elimina el rol en el rollback para no romper asignaciones.
    }
};
