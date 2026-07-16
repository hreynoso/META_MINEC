<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Gestión de períodos del sistema.
 *
 * El sistema trabaja con dos períodos anuales:
 *  - Ejecución: el año en curso, sobre el que se ejecuta y monitorea la cartera
 *    de proyectos, KPIs y metas.
 *  - Planificación: el año próximo (ejecución + 1), que se habilita para cargar
 *    la planificación a partir de una fecha de activación configurable.
 *
 * Los valores se guardan en la tabla `settings` (clave/valor). El año de
 * ejecución es editable (por defecto, el año del calendario); el de
 * planificación se deriva siempre como ejecución + 1. El estado del período de
 * planificación (Programada / Activa) es informativo por ahora.
 */
class Period
{
    /** Clave del ajuste: año de ejecución. */
    public const EXECUTION_YEAR = 'periods.execution_year';

    /** Clave del ajuste: fecha de activación del período de planificación. */
    public const PLANNING_ACTIVATION = 'periods.planning_activation_date';

    /** Año de ejecución (por defecto, el año en curso). */
    public static function executionYear(): int
    {
        $stored = (int) Setting::value(self::EXECUTION_YEAR, 0);

        return $stored > 0 ? $stored : (int) self::today()->year;
    }

    /** Año de planificación: siempre el año siguiente al de ejecución. */
    public static function planningYear(): int
    {
        return self::executionYear() + 1;
    }

    /** Fecha de activación del período de planificación (Y-m-d) o null. */
    public static function planningActivationDate(): ?string
    {
        $raw = trim((string) Setting::value(self::PLANNING_ACTIVATION, ''));

        return $raw !== '' ? $raw : null;
    }

    /**
     * ¿El período de planificación está activo? Lo está cuando hay una fecha de
     * activación definida y esa fecha ya llegó (en la zona horaria de negocio).
     */
    public static function planningIsActive(): bool
    {
        $date = self::planningActivationDate();

        if ($date === null) {
            return false;
        }

        return self::today()->startOfDay()->gte(
            Carbon::parse($date, LocalTime::FALLBACK_TZ)->startOfDay()
        );
    }

    /** "Hoy" en la zona horaria de negocio (El Salvador), para comparar fechas. */
    private static function today(): Carbon
    {
        return Carbon::now(LocalTime::FALLBACK_TZ);
    }
}
