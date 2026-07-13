<?php

use Database\Seeders\InstitutionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Mantenimiento de instituciones: amplía la tabla con los campos del formulario
 * (identificación, gobierno, contacto, dirección, responsable y auditoría) y
 * rellena las instituciones existentes con datos de demostración vía el seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('institutions', 'type')) {
            Schema::table('institutions', function (Blueprint $table) {
                // Identificación
                $table->string('type')->nullable()->after('short_name');
                $table->string('sector')->nullable()->after('type');
                $table->string('rnc')->nullable()->after('sector');
                $table->string('status')->default('activa')->after('rnc');
                $table->string('logo_path')->nullable()->after('status');

                // Gobierno. parent_id sin FK a nivel de BD (compatibilidad SQLite/MySQL
                // al alterar la tabla); la referencia se valida en la aplicación.
                $table->unsignedBigInteger('parent_id')->nullable()->after('logo_path')->index();
                $table->string('admin_dependency')->nullable()->after('parent_id');

                // Contacto
                $table->string('phone_main')->nullable()->after('admin_dependency');
                $table->string('phone_alt')->nullable()->after('phone_main');
                $table->string('email')->nullable()->after('phone_alt');
                $table->string('website')->nullable()->after('email');

                // Dirección
                $table->string('province')->nullable()->after('website');
                $table->string('addr_sector')->nullable()->after('province');
                $table->string('addr_street')->nullable()->after('addr_sector');
                $table->string('addr_number')->nullable()->after('addr_street');
                $table->string('addr_reference')->nullable()->after('addr_number');
                $table->string('postal_code')->nullable()->after('addr_reference');

                // Responsable (máxima autoridad)
                $table->string('authority_name')->nullable()->after('postal_code');
                $table->string('authority_position')->nullable()->after('authority_name');
                $table->string('authority_email')->nullable()->after('authority_position');
                $table->string('authority_phone')->nullable()->after('authority_email');

                // Auditoría (fechas = created_at/updated_at existentes)
                $table->string('created_by')->nullable()->after('authority_phone');
                $table->string('updated_by')->nullable()->after('created_by');
            });
        }

        // Rellena/actualiza las instituciones con datos de demostración.
        rescue(fn () => Artisan::call('db:seed', ['--class' => InstitutionSeeder::class, '--force' => true]), null, false);
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            foreach ([
                'type', 'sector', 'rnc', 'status', 'logo_path', 'parent_id', 'admin_dependency',
                'phone_main', 'phone_alt', 'email', 'website',
                'province', 'addr_sector', 'addr_street', 'addr_number', 'addr_reference', 'postal_code',
                'authority_name', 'authority_position', 'authority_email', 'authority_phone',
                'created_by', 'updated_by',
            ] as $col) {
                if (Schema::hasColumn('institutions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
