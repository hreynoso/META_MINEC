<?php

use App\Models\CatalogOption;
use App\Models\Institution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos administrables (opciones de selectores). Empieza con los del
 * mantenimiento de instituciones: tipo, sector y dependencia administrativa.
 * Se siembran con los valores fijos que existían como constantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalog_options')) {
            Schema::create('catalog_options', function (Blueprint $table) {
                $table->id();
                $table->string('group')->index();   // institution_type | institution_sector | institution_dependency
                $table->string('label');
                $table->unsignedInteger('sort')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['group', 'label']);
            });
        }

        $seed = [
            'institution_type' => Institution::TYPES,
            'institution_sector' => Institution::SECTORS,
            'institution_dependency' => Institution::DEPENDENCIES,
        ];

        foreach ($seed as $group => $labels) {
            foreach (array_values($labels) as $i => $label) {
                rescue(fn () => CatalogOption::updateOrCreate(
                    ['group' => $group, 'label' => $label],
                    ['sort' => $i, 'active' => true],
                ), null, false);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_options');
    }
};
