<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->string('channel')->index(); // general|alertas|seguimiento|metas
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->text('body');
                $table->boolean('system')->default(false); // mensaje automático (p. ej. notificar riesgos)
                $table->timestamps();

                $table->index(['channel', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
