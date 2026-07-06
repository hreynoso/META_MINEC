<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\PresidentialGoal;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Cartera de 15 proyectos del prototipo Sistema META.
 *
 * NOTA: los encabezados (código, nombre, institución, meta, estado, avance,
 * presupuesto/ejecutado) provienen de lo observado en el prototipo. Los campos
 * de detalle (entregables, impacto, beneficios, fuente, responsable) solo se
 * capturaron completos para MINEC-2025-001; en el resto son representativos
 * y deben ajustarse con los datos oficiales cuando estén disponibles.
 */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $inst = Institution::pluck('id', 'code');
        $goal = PresidentialGoal::pluck('id', 'name');

        $projects = [
            [
                'code' => 'MINEC-2025-001', 'name' => 'Modernización del Sistema Nacional de Compras Públicas',
                'institution' => 'MINEC', 'goal' => 'Digitalización del Estado', 'status' => 'en_ejecucion', 'risk_level' => 'bajo',
                'budget' => 4500000, 'executed' => 1980000, 'physical_progress' => 44,
                'start_date' => '2025-01-14', 'end_date' => '2026-06-29', 'source' => 'Fondos GOES + BID',
                'responsible' => 'Dir. de Innovación MINEC', 'beneficiaries' => 12500, 'location' => 'San Salvador',
                'deliverables' => ['Plataforma COMPRASAL 2.0', 'Capacitación a 800 proveedores', 'API de interoperabilidad'],
                'expected_impact' => 'Reducción de 40% en tiempos de contratación pública',
                'benefits' => 'Transparencia, ahorro fiscal y acceso MIPYME a compras del Estado',
            ],
            ['code' => 'CONAMYPE-2025-014', 'name' => 'Centros Regionales de Desarrollo MIPYME (CDMYPE)', 'institution' => 'CONAMYPE', 'goal' => 'Fortalecimiento MIPYMES', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 8200000, 'executed' => 2050000, 'physical_progress' => 25, 'location' => 'Nacional', 'beneficiaries' => 6800],
            ['code' => 'PROESA-2025-007', 'name' => 'Programa Invest in El Salvador - Nearshoring', 'institution' => 'PROESA', 'goal' => 'Atracción de inversión extranjera', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 3200000, 'executed' => 1920000, 'physical_progress' => 60, 'location' => 'Nacional', 'beneficiaries' => 3200],
            ['code' => 'DC-2025-003', 'name' => 'Plataforma Digital de Denuncias del Consumidor', 'institution' => 'DC', 'goal' => 'Digitalización del Estado', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 780000, 'executed' => 585000, 'physical_progress' => 75, 'location' => 'Nacional', 'beneficiaries' => 900000],
            ['code' => 'CNR-2025-021', 'name' => 'Catastro Nacional Multifinalitario Fase III', 'institution' => 'CNR', 'goal' => 'Digitalización del Estado', 'status' => 'en_ejecucion', 'risk_level' => 'medio', 'budget' => 12500000, 'executed' => 5000000, 'physical_progress' => 40, 'location' => 'Nacional', 'beneficiaries' => 1200000],
            ['code' => 'INSAFORP-2025-045', 'name' => 'Formación Técnica en Tecnologías 4.0', 'institution' => 'INSAFORP', 'goal' => 'Generación de empleo digno', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 2100000, 'executed' => 1470000, 'physical_progress' => 70, 'location' => 'Nacional', 'beneficiaries' => 15000],
            ['code' => 'BANDESAL-2025-002', 'name' => 'Fideicomiso de Financiamiento a la MIPYME Exportadora', 'institution' => 'BANDESAL', 'goal' => 'Diversificación exportadora', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 25000000, 'executed' => 3750000, 'physical_progress' => 15, 'location' => 'Nacional', 'beneficiaries' => 4500],
            ['code' => 'CONACYT-2025-011', 'name' => 'Fondo de Innovación Tecnológica FONDESA-I+D', 'institution' => 'CONACYT', 'goal' => 'Innovación y ciencia', 'status' => 'planificado', 'risk_level' => 'alto', 'budget' => 1800000, 'executed' => 270000, 'physical_progress' => 15, 'location' => 'Nacional', 'beneficiaries' => 1200],
            ['code' => 'MINEC-2025-032', 'name' => 'Ventanilla Única de Comercio Exterior (VUCE) 2.0', 'institution' => 'MINEC', 'goal' => 'Diversificación exportadora', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 3400000, 'executed' => 2380000, 'physical_progress' => 70, 'location' => 'San Salvador / Puertos', 'beneficiaries' => 5200],
            ['code' => 'CONAMYPE-2025-028', 'name' => 'Ruta del Emprendimiento Femenino', 'institution' => 'CONAMYPE', 'goal' => 'Fortalecimiento MIPYMES', 'status' => 'en_ejecucion', 'risk_level' => 'medio', 'budget' => 6000000, 'executed' => 1200000, 'physical_progress' => 20, 'location' => 'Nacional', 'beneficiaries' => 8000, 'responsible' => 'Gerencia de Emprendimiento'],
            ['code' => 'MINEC-2024-078', 'name' => 'Zona Económica Especial Pacífico', 'institution' => 'MINEC', 'goal' => 'Crecimiento económico sostenido', 'status' => 'en_riesgo', 'risk_level' => 'alto', 'budget' => 20000000, 'executed' => 4000000, 'physical_progress' => 20, 'location' => 'Zona Pacífico', 'beneficiaries' => 250000, 'responsible' => 'Vice-Ministerio de Comercio'],
            ['code' => 'PROESA-2025-019', 'name' => 'Marca País - Estrategia Global', 'institution' => 'PROESA', 'goal' => 'Atracción de inversión extranjera', 'status' => 'completado', 'risk_level' => 'bajo', 'budget' => 2500000, 'executed' => 2500000, 'physical_progress' => 100, 'location' => 'Nacional', 'beneficiaries' => 1000000],
            ['code' => 'INSAFORP-2025-060', 'name' => 'Bilingüismo para el BPO/ITO', 'institution' => 'INSAFORP', 'goal' => 'Generación de empleo digno', 'status' => 'en_riesgo', 'risk_level' => 'alto', 'budget' => 3000000, 'executed' => 540000, 'physical_progress' => 18, 'location' => 'Nacional', 'beneficiaries' => 6000, 'responsible' => 'Gerencia Idiomas INSAFORP'],
            ['code' => 'CNR-2025-055', 'name' => 'Firma Electrónica Ciudadana', 'institution' => 'CNR', 'goal' => 'Digitalización del Estado', 'status' => 'en_ejecucion', 'risk_level' => 'medio', 'budget' => 4000000, 'executed' => 1200000, 'physical_progress' => 30, 'location' => 'Nacional', 'beneficiaries' => 800000, 'responsible' => 'Dir. Firma Electrónica CNR'],
            ['code' => 'MINEC-2025-050', 'name' => 'Interoperabilidad de Servicios Digitales del Estado', 'institution' => 'MINEC', 'goal' => 'Digitalización del Estado', 'status' => 'en_ejecucion', 'risk_level' => 'bajo', 'budget' => 18170000, 'executed' => 3600000, 'physical_progress' => 30, 'location' => 'Nacional', 'beneficiaries' => 500000],
        ];

        foreach ($projects as $p) {
            Project::updateOrCreate(
                ['code' => $p['code']],
                [
                    'name' => $p['name'],
                    'institution_id' => $inst[$p['institution']] ?? null,
                    'presidential_goal_id' => $goal[$p['goal']] ?? null,
                    'status' => $p['status'],
                    'risk_level' => $p['risk_level'],
                    'budget' => $p['budget'],
                    'executed' => $p['executed'],
                    'physical_progress' => $p['physical_progress'],
                    'start_date' => $p['start_date'] ?? null,
                    'end_date' => $p['end_date'] ?? null,
                    'source' => $p['source'] ?? null,
                    'responsible' => $p['responsible'] ?? null,
                    'beneficiaries' => $p['beneficiaries'] ?? 0,
                    'location' => $p['location'] ?? null,
                    'deliverables' => $p['deliverables'] ?? null,
                    'expected_impact' => $p['expected_impact'] ?? null,
                    'benefits' => $p['benefits'] ?? null,
                ],
            );
        }
    }
}
