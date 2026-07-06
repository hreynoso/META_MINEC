<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
                $table->foreignId('presidential_goal_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('planificado'); // planificado|en_ejecucion|en_riesgo|completado
                $table->string('risk_level')->default('bajo');     // bajo|medio|alto
                $table->decimal('budget', 15, 2)->default(0);
                $table->decimal('executed', 15, 2)->default(0);
                $table->unsignedTinyInteger('physical_progress')->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('source')->nullable();       // fuente de financiamiento
                $table->string('responsible')->nullable();
                $table->unsignedBigInteger('beneficiaries')->default(0);
                $table->string('location')->nullable();
                $table->json('deliverables')->nullable();
                $table->text('expected_impact')->nullable();
                $table->text('benefits')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
