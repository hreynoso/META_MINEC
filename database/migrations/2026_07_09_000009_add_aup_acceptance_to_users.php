<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.5.10 / A.5.34 — Aceptación del aviso de uso aceptable y privacidad. Se guarda
 * cuándo y qué versión aceptó cada usuario, para volver a solicitarla si el aviso
 * cambia de versión. Aplica a usuarios locales y de SSO por igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'aup_accepted_at')) {
                $table->timestamp('aup_accepted_at')->nullable()->after('avatar_path');
            }
            if (! Schema::hasColumn('users', 'aup_accepted_version')) {
                $table->string('aup_accepted_version', 20)->nullable()->after('aup_accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['aup_accepted_version', 'aup_accepted_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
