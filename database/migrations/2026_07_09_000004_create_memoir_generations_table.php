<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de memorias institucionales generadas con IA: guarda institución,
 * período, autor, fecha y el contenido para poder consultarlas y exportarlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('memoir_generations')) {
            return;
        }

        Schema::create('memoir_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('periodo');
            $table->longText('content');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoir_generations');
    }
};
