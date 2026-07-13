<?php

use App\Models\CatalogOption;
use App\Models\Kpi;
use Illuminate\Database\Migrations\Migration;

/**
 * Siembra catálogos adicionales administrables: provincia/departamento de la
 * institución y unidades de KPI (a partir de las existentes + comunes).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Departamentos de El Salvador (editable desde el mantenimiento).
        $provinces = [
            'Ahuachapán', 'Santa Ana', 'Sonsonate', 'Chalatenango', 'La Libertad',
            'San Salvador', 'Cuscatlán', 'La Paz', 'Cabañas', 'San Vicente',
            'Usulután', 'San Miguel', 'Morazán', 'La Unión',
        ];
        foreach ($provinces as $i => $label) {
            rescue(fn () => CatalogOption::updateOrCreate(
                ['group' => 'institution_province', 'label' => $label],
                ['sort' => $i, 'active' => true],
            ), null, false);
        }

        // Unidades de KPI: las que ya existen + un conjunto común.
        $existing = rescue(fn () => Kpi::query()->whereNotNull('unit')->where('unit', '!=', '')->distinct()->pluck('unit')->all(), [], false);
        $units = array_values(array_unique(array_merge($existing, ['%', 'USD', 'Unidades', 'Puntos', 'Personas', 'Días', 'Proyectos'])));
        foreach ($units as $i => $label) {
            rescue(fn () => CatalogOption::updateOrCreate(
                ['group' => 'kpi_unit', 'label' => (string) $label],
                ['sort' => $i, 'active' => true],
            ), null, false);
        }
    }

    public function down(): void
    {
        rescue(fn () => CatalogOption::whereIn('group', ['institution_province', 'kpi_unit'])->delete(), null, false);
    }
};
