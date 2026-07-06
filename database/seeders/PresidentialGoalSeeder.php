<?php

namespace Database\Seeders;

use App\Models\PresidentialGoal;
use Illuminate\Database\Seeder;

class PresidentialGoalSeeder extends Seeder
{
    public function run(): void
    {
        $goals = [
            'Crecimiento económico sostenido',
            'Generación de empleo digno',
            'Atracción de inversión extranjera',
            'Digitalización del Estado',
            'Fortalecimiento MIPYMES',
            'Diversificación exportadora',
            'Innovación y ciencia',
            'Formalización empresarial',
        ];

        foreach ($goals as $name) {
            PresidentialGoal::updateOrCreate(['name' => $name]);
        }
    }
}
