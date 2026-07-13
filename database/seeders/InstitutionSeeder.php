<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        // Datos de demostración; se completarán/ajustarán desde el mantenimiento.
        $items = [
            ['code' => 'MINEC', 'name' => 'Ministerio de Economía', 'short_name' => 'MINEC', 'type' => 'Ministerio', 'sector' => 'Economía', 'admin_dependency' => 'Gobierno Central', 'parent' => null, 'email' => 'info@minec.gob.sv', 'website' => 'https://www.economia.gob.sv', 'phone_main' => '(503) 2590-5600', 'authority_name' => 'Titular del MINEC', 'authority_position' => 'Ministro/a de Economía'],
            ['code' => 'CONAMYPE', 'name' => 'Comisión Nacional de la Micro y Pequeña Empresa', 'short_name' => 'CONAMYPE', 'type' => 'Comisión', 'sector' => 'Economía', 'admin_dependency' => 'Institución Autónoma', 'parent' => 'MINEC', 'website' => 'https://www.conamype.gob.sv'],
            ['code' => 'PROESA', 'name' => 'Organismo Promotor de Exportaciones e Inversiones', 'short_name' => 'PROESA', 'type' => 'Institución Autónoma', 'sector' => 'Comercio', 'admin_dependency' => 'Institución Autónoma', 'parent' => 'MINEC'],
            ['code' => 'DC', 'name' => 'Defensoría del Consumidor', 'short_name' => 'DC', 'type' => 'Institución Autónoma', 'sector' => 'Social', 'admin_dependency' => 'Institución Autónoma', 'parent' => null],
            ['code' => 'CNR', 'name' => 'Centro Nacional de Registros', 'short_name' => 'CNR', 'type' => 'Institución Autónoma', 'sector' => 'Economía', 'admin_dependency' => 'Institución Autónoma', 'parent' => null],
            ['code' => 'INSAFORP', 'name' => 'Instituto Salvadoreño de Formación Profesional', 'short_name' => 'INSAFORP', 'type' => 'Instituto', 'sector' => 'Trabajo', 'admin_dependency' => 'Institución Autónoma', 'parent' => null],
            ['code' => 'BANDESAL', 'name' => 'Banco de Desarrollo de El Salvador', 'short_name' => 'BANDESAL', 'type' => 'Banco', 'sector' => 'Financiero', 'admin_dependency' => 'Institución Autónoma', 'parent' => null],
            ['code' => 'CONACYT', 'name' => 'Consejo Nacional de Ciencia y Tecnología', 'short_name' => 'CONACYT', 'type' => 'Consejo', 'sector' => 'Tecnología', 'admin_dependency' => 'Institución Autónoma', 'parent' => null],
        ];

        // Primera pasada: crea/actualiza sin la jerarquía (para resolver los padres).
        foreach ($items as $i) {
            $data = collect($i)->except('parent')->all();
            $data['status'] = 'activa';
            $data['created_by'] ??= 'Sistema (demo)';
            $data['updated_by'] = 'Sistema (demo)';
            Institution::updateOrCreate(['code' => $i['code']], $data);
        }

        // Segunda pasada: asigna la institución superior por código.
        foreach ($items as $i) {
            if (! empty($i['parent'])) {
                $parent = Institution::where('code', $i['parent'])->first();
                if ($parent) {
                    Institution::where('code', $i['code'])->update(['parent_id' => $parent->id]);
                }
            }
        }
    }
}
