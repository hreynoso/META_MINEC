<?php

namespace Database\Seeders;

use App\Models\Kpi;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        // Los 4 primeros (strategic=true) son los "Indicadores estratégicos" del Dashboard.
        $items = [
            ['key' => 'ejecucion_presupuestaria', 'label' => 'Ejecución presupuestaria global', 'value' => 34, 'unit' => '%', 'target' => 60, 'trend' => 'up', 'strategic' => true, 'sort' => 1],
            ['key' => 'proyectos_en_tiempo', 'label' => 'Proyectos en tiempo', 'value' => 68, 'unit' => '%', 'target' => 85, 'trend' => 'down', 'strategic' => true, 'sort' => 2],
            ['key' => 'inversion_extranjera', 'label' => 'Inversión extranjera captada', 'value' => 287, 'unit' => 'M USD', 'target' => 450, 'trend' => 'up', 'strategic' => true, 'sort' => 3],
            ['key' => 'mipymes_formalizadas', 'label' => 'MIPYMES formalizadas', 'value' => 4820, 'unit' => 'empresas', 'target' => 8000, 'trend' => 'up', 'strategic' => true, 'sort' => 4],
            ['key' => 'empleos_generados', 'label' => 'Empleos generados', 'value' => 12450, 'unit' => 'empleos', 'target' => 25000, 'trend' => 'up', 'strategic' => false, 'sort' => 5],
            ['key' => 'exportaciones_no_trad', 'label' => 'Exportaciones no tradicionales', 'value' => 12, 'unit' => '% crec.', 'target' => 18, 'trend' => 'flat', 'strategic' => false, 'sort' => 6],
            ['key' => 'tramites_digitalizados', 'label' => 'Trámites digitalizados', 'value' => 145, 'unit' => 'trámites', 'target' => 220, 'trend' => 'up', 'strategic' => false, 'sort' => 7],
            ['key' => 'beneficiarios_directos', 'label' => 'Beneficiarios directos', 'value' => 890000, 'unit' => 'personas', 'target' => 1500000, 'trend' => 'up', 'strategic' => false, 'sort' => 8],
        ];

        foreach ($items as $i) {
            Kpi::updateOrCreate(['key' => $i['key']], $i);
        }
    }
}
