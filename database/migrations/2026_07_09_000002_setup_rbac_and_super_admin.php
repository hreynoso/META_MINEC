<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC (ISO 27001 A.5.15 / A.8.2 / A.8.3): crea el catálogo de permisos, el rol
 * "Super Admin" (máximo privilegio, cuenta local) y asigna conjuntos de permisos
 * por defecto a cada rol. Idempotente: usa findOrCreate + syncPermissions.
 */
return new class extends Migration
{
    /** Catálogo de permisos del sistema (debe coincidir con RoleController::CATALOG). */
    private const PERMISSIONS = [
        'proyectos.ver', 'proyectos.gestionar',
        'kpis.ver', 'kpis.gestionar',
        'reportes.ver', 'reportes.generar',
        'ministra.ver', 'ministra.informe',
        'ia.ver', 'ia.recomendar',
        'memorias.generar',
        'gestores.participar', 'gestores.notificar',
        'logs.ver',
        'config.gestionar', 'usuarios.gestionar', 'roles.gestionar',
    ];

    /** Permisos por defecto por rol ('*' = todos). */
    private const ROLE_PERMISSIONS = [
        'Super Admin' => '*',
        'Administrador' => '*',
        'Directivo' => [
            'proyectos.ver', 'kpis.ver', 'reportes.ver', 'reportes.generar',
            'ministra.ver', 'ministra.informe', 'ia.ver', 'logs.ver',
            'gestores.participar', 'memorias.generar',
        ],
        'Gestor de Proyectos' => [
            'proyectos.ver', 'proyectos.gestionar', 'kpis.ver', 'reportes.ver',
            'gestores.participar', 'gestores.notificar', 'memorias.generar', 'ia.ver',
        ],
        'Analista' => [
            'proyectos.ver', 'kpis.ver', 'kpis.gestionar', 'reportes.ver',
            'reportes.generar', 'ia.ver', 'ia.recomendar', 'logs.ver',
        ],
        'Consultor' => [
            'proyectos.ver', 'kpis.ver', 'reportes.ver', 'ia.ver',
        ],
    ];

    public function up(): void
    {
        Artisan::call('permission:cache-reset');

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms === '*' ? self::PERMISSIONS : $perms);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // No se eliminan roles ni permisos en el rollback para no romper
        // asignaciones existentes de usuarios.
    }
};
