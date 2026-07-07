<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gestores institucionales (uno por institución adscrita) para la Red de Gestores.
 * Son datos de demostración, consistentes con el resto del dataset semilla.
 * El acceso real es por SSO; la contraseña aquí es aleatoria.
 */
class GestorSeeder extends Seeder
{
    public function run(): void
    {
        $gestores = [
            ['name' => 'María E. Rodríguez', 'email' => 'demo@minec.gob.sv', 'code' => 'MINEC'],
            ['name' => 'Carlos A. Menjívar', 'email' => 'cmenjivar@conamype.gob.sv', 'code' => 'CONAMYPE'],
            ['name' => 'José R. Hernández', 'email' => 'jhernandez@cnr.gob.sv', 'code' => 'CNR'],
            ['name' => 'Ana S. Portillo', 'email' => 'aportillo@proesa.gob.sv', 'code' => 'PROESA'],
            ['name' => 'Ricardo A. Cruz', 'email' => 'rcruz@bandesal.gob.sv', 'code' => 'BANDESAL'],
            ['name' => 'Lucía B. Aguilar', 'email' => 'laguilar@insaforp.gob.sv', 'code' => 'INSAFORP'],
            ['name' => 'Verónica Alfaro', 'email' => 'valfaro@dc.gob.sv', 'code' => 'DC'],
            ['name' => 'Julio Ramírez', 'email' => 'jramirez@conacyt.gob.sv', 'code' => 'CONACYT'],
        ];

        $institutions = Institution::pluck('id', 'code');

        foreach ($gestores as $g) {
            $user = User::firstOrNew(['email' => $g['email']]);
            $user->name = $g['name'];
            $user->institution_id = $institutions[$g['code']] ?? null;

            // Solo se asigna contraseña a gestores nuevos; no se toca la de usuarios
            // existentes (p. ej. la cuenta demo).
            if (! $user->exists) {
                $user->password = Hash::make(Str::random(40));
            }

            $user->save();
        }
    }
}
