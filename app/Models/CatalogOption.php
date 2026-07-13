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

    /**
     * Registro de catálogos: grupo → modelo y columna que lo usan. Añadir un
     * catálogo nuevo es agregar una entrada aquí (y su título i18n en el front).
     * Solo deben registrarse columnas de solo presentación (no claves de lógica
     * como estado/riesgo de proyecto o tendencia de KPI).
     */
    public const REGISTRY = [
        'institution_type' => ['model' => Institution::class, 'column' => 'type'],
        'institution_sector' => ['model' => Institution::class, 'column' => 'sector'],
        'institution_dependency' => ['model' => Institution::class, 'column' => 'admin_dependency'],
        'institution_province' => ['model' => Institution::class, 'column' => 'province'],
        'kpi_unit' => ['model' => Kpi::class, 'column' => 'unit'],
    ];

    /** @return string[] */
    public static function groups(): array
    {
        return array_keys(self::REGISTRY);
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    public static function modelFor(string $group): string
    {
        return self::REGISTRY[$group]['model'];
    }

    public static function columnFor(string $group): string
    {
        return self::REGISTRY[$group]['column'];
    }

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
