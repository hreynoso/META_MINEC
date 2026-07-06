<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $names = config('permission.table_names');
        $cols = config('permission.column_names');
        $pivotRole = $cols['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $cols['permission_pivot_key'] ?? 'permission_id';

        if (! Schema::hasTable($names['permissions'])) {
            Schema::create($names['permissions'], function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable($names['roles'])) {
            Schema::create($names['roles'], function (Blueprint $table) use ($teams, $cols) {
                $table->bigIncrements('id');
                if ($teams || config('permission.testing')) {
                    $table->unsignedBigInteger($cols['team_foreign_key'])->nullable();
                    $table->index($cols['team_foreign_key'], 'roles_team_foreign_key_index');
                }
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                if ($teams || config('permission.testing')) {
                    $table->unique([$cols['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        if (! Schema::hasTable($names['model_has_permissions'])) {
            Schema::create($names['model_has_permissions'], function (Blueprint $table) use ($names, $cols, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($cols['model_morph_key']);
                $table->index([$cols['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign($pivotPermission)->references('id')->on($names['permissions'])->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($cols['team_foreign_key']);
                    $table->index($cols['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                    $table->primary([$cols['team_foreign_key'], $pivotPermission, $cols['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([$pivotPermission, $cols['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($names['model_has_roles'])) {
            Schema::create($names['model_has_roles'], function (Blueprint $table) use ($names, $cols, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($cols['model_morph_key']);
                $table->index([$cols['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign($pivotRole)->references('id')->on($names['roles'])->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($cols['team_foreign_key']);
                    $table->index($cols['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                    $table->primary([$cols['team_foreign_key'], $pivotRole, $cols['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([$pivotRole, $cols['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($names['role_has_permissions'])) {
            Schema::create($names['role_has_permissions'], function (Blueprint $table) use ($names, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->foreign($pivotPermission)->references('id')->on($names['permissions'])->cascadeOnDelete();
                $table->foreign($pivotRole)->references('id')->on($names['roles'])->cascadeOnDelete();
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        app('cache')->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $names = config('permission.table_names');
        Schema::dropIfExists($names['role_has_permissions']);
        Schema::dropIfExists($names['model_has_roles']);
        Schema::dropIfExists($names['model_has_permissions']);
        Schema::dropIfExists($names['roles']);
        Schema::dropIfExists($names['permissions']);
    }
};
