<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de informes presidenciales generados con IA en el Despacho de la
 * Ministra: guarda autor, período, instituciones y el contenido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('minister_reports')) {
            return;
        }

        Schema::create('minister_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->json('institutions')->nullable();
            $table->longText('content');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minister_reports');
    }
};
