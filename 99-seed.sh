<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'MINEC', 'name' => 'Ministerio de Economía', 'short_name' => 'MINEC'],
            ['code' => 'CONAMYPE', 'name' => 'Comisión Nacional de la Micro y Pequeña Empresa', 'short_name' => 'CONAMYPE'],
            ['code' => 'PROESA', 'name' => 'Organismo Promotor de Exportaciones e Inversiones', 'short_name' => 'PROESA'],
            ['code' => 'DC', 'name' => 'Defensoría del Consumidor', 'short_name' => 'DC'],
            ['code' => 'CNR', 'name' => 'Centro Nacional de Registros', 'short_name' => 'CNR'],
            ['code' => 'INSAFORP', 'name' => 'Instituto Salvadoreño de Formación Profesional', 'short_name' => 'INSAFORP'],
            ['code' => 'BANDESAL', 'name' => 'Banco de Desarrollo de El Salvador', 'short_name' => 'BANDESAL'],
            ['code' => 'CONACYT', 'name' => 'Consejo Nacional de Ciencia y Tecnología', 'short_name' => 'CONACYT'],
        ];

        foreach ($items as $i) {
            Institution::updateOrCreate(['code' => $i['code']], $i);
        }
    }
}
