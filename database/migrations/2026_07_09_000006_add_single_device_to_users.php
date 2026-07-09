<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restricción "un solo dispositivo": se guarda en `users` cuál es la sesión
 * activa del usuario. Un middleware (EnforceSingleDevice) expulsa a cualquier
 * sesión que ya no coincida. Funciona con cualquier driver de sesión (incluido
 * `file`, que no dispone de tabla `sessions` consultable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_session_id')) {
                $table->string('current_session_id', 100)->nullable()->index()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'current_session_ip')) {
                $table->string('current_session_ip', 45)->nullable()->after('current_session_id');
            }
            if (! Schema::hasColumn('users', 'current_session_agent')) {
                $table->string('current_session_agent', 255)->nullable()->after('current_session_ip');
            }
            if (! Schema::hasColumn('users', 'current_session_active_at')) {
                $table->timestamp('current_session_active_at')->nullable()->after('current_session_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'current_session_active_at',
                'current_session_agent',
                'current_session_ip',
                'current_session_id',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
