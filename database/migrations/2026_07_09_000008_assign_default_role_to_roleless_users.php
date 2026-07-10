<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

/**
 * A.8.3 — al activar el RBAC operativo, los usuarios SSO existentes no tienen
 * rol y quedarían sin acceso. Se les asigna el rol por defecto (solo lectura);
 * un administrador puede elevarlos luego. Los que ya tienen rol no se tocan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleName = (string) (config('security.default_role') ?: 'Consultor');

        if (! Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
            return;
        }

        User::doesntHave('roles')->get()->each(function (User $user) use ($roleName) {
            rescue(fn () => $user->assignRole($roleName), null, false);
        });

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // No se revierte: quitar roles podría dejar usuarios sin acceso.
    }
};
