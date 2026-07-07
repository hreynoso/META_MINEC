<?php

use App\Models\Institution;
use Database\Seeders\GestorSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puebla el directorio de gestores en el deploy sin depender de
     * AUTORUN_LARAVEL_SEED. Solo actúa si ya hay instituciones (evita crear
     * gestores sin institución en una base recién migrada y aún sin sembrar;
     * en ese caso el GestorSeeder correrá dentro del db:seed completo).
     */
    public function up(): void
    {
        if (Schema::hasTable('institutions') && Institution::exists()) {
            Artisan::call('db:seed', ['--class' => GestorSeeder::class, '--force' => true]);
        }
    }

    public function down(): void
    {
        // Datos de demostración; no se revierten automáticamente.
    }
};
