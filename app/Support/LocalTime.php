<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Formatea fechas/horas en la zona horaria del equipo de cada usuario.
 *
 * Los registros se guardan en UTC; el navegador envía su zona horaria en la
 * cookie `tz` (p. ej. "America/Santo_Domingo"), que el middleware de cookies
 * deja sin cifrar. Así cada quien ve la hora de su propio dispositivo, tanto en
 * pantalla como en los PDF y las exportaciones (la cookie viaja en cada
 * petición). Si no hay cookie válida, se usa la zona por defecto (El Salvador).
 */
class LocalTime
{
    public const FALLBACK_TZ = 'America/El_Salvador';

    /** Zona horaria a usar para mostrar (cookie del navegador o la de respaldo). */
    public static function timezone(): string
    {
        $tz = request()?->cookie('tz');

        if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        return self::FALLBACK_TZ;
    }

    /**
     * Formatea un instante en la zona del usuario. Devuelve null si la fecha es
     * null (útil para encadenar con `?? '—'`).
     */
    public static function format(?DateTimeInterface $dt, string $format = 'd/m/Y h:i A'): ?string
    {
        if ($dt === null) {
            return null;
        }

        return Carbon::instance($dt)->setTimezone(self::timezone())->format($format);
    }
}
