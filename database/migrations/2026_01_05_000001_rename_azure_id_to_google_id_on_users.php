<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cambio de SSO Office 365/Azure → Google Workspace: renombra la columna
 * users.azure_id a google_id. Guardado para ser idempotente tanto en BD ya
 * desplegadas (tienen azure_id) como en instalaciones nuevas (crean google_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'azure_id') && ! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('azure_id', 'google_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'google_id') && ! Schema::hasColumn('users', 'azure_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('google_id', 'azure_id');
            });
        }
    }
};
