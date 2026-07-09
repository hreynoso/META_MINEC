<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de recomendaciones generadas con IA en IA Predictiva. Cada registro
 * guarda quién la generó y cuándo, para trazabilidad (ISO 27001 A.8.15).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_recommendations')) {
            return;
        }

        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('recommendation');
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
