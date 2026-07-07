<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            InstitutionSeeder::class,
            PresidentialGoalSeeder::class,
            KpiSeeder::class,
            ProjectSeeder::class,
            DemoUserSeeder::class,
            GestorSeeder::class,
        ]);
    }
}
