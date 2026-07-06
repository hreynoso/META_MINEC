<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpis')) {
            Schema::create('kpis', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->decimal('value', 15, 2)->default(0);
                $table->string('unit')->nullable();
                $table->decimal('target', 15, 2)->default(0);
                $table->string('trend')->default('flat'); // up|down|flat
                $table->boolean('strategic')->default(false); // se muestra en Dashboard
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
