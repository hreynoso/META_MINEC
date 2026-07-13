<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Opción de un catálogo administrable (p. ej. tipos de institución). El `label`
 * es el valor que se guarda en el registro que lo usa (compatibilidad con las
 * columnas de texto existentes).
 */
class CatalogOption extends Model
{
    use LogsActivity;

    /** Grupos de catálogo soportados. */
    public const GROUPS = ['institution_type', 'institution_sector', 'institution_dependency'];

    /** Columna de `institutions` que usa cada grupo (para renombrar en cascada). */
    public const COLUMN = [
        'institution_type' => 'type',
        'institution_sector' => 'sector',
        'institution_dependency' => 'admin_dependency',
    ];

    protected $fillable = ['group', 'label', 'sort', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** Etiquetas activas de un grupo, ordenadas (alimentan los desplegables). */
    public static function values(string $group): array
    {
        return static::query()
            ->where('group', $group)
            ->where('active', true)
            ->orderBy('sort')
            ->orderBy('label')
            ->pluck('label')
            ->all();
    }

    /** Todas las etiquetas de un grupo (activas o no), para validar ediciones. */
    public static function allLabels(string $group): array
    {
        return static::query()->where('group', $group)->pluck('label')->all();
    }
}
