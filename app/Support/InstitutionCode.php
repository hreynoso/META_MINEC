<?php

namespace App\Support;

use App\Models\Institution;
use App\Models\Setting;

/**
 * Genera el código de una institución según la nomenclatura configurable en
 * Configuración → Catálogos. Plantilla con tokens:
 *   {SIGLAS}  siglas de la institución (mayúsculas, alfanumérico)
 *   {AÑO} / {ANIO} / {ANO} / {YEAR}  año actual (4 dígitos)
 *   {AÑO2}    año actual (2 dígitos)
 *   {SEC}     secuencia numérica con relleno de ceros (longitud configurable)
 * Por defecto: {SIGLAS}-{AÑO}-{SEC} con 4 dígitos → p. ej. MINEC-2026-0001.
 */
class InstitutionCode
{
    public const PATTERN_KEY = 'institution.code_pattern';

    public const SEQ_KEY = 'institution.code_seq_length';

    public const DEFAULT_PATTERN = '{SIGLAS}-{AÑO}-{SEC}';

    public const DEFAULT_SEQ = 4;

    public static function pattern(): string
    {
        $p = trim((string) Setting::value(self::PATTERN_KEY, self::DEFAULT_PATTERN));

        return $p !== '' ? $p : self::DEFAULT_PATTERN;
    }

    public static function seqLength(): int
    {
        $n = (int) Setting::value(self::SEQ_KEY, self::DEFAULT_SEQ);

        return ($n >= 1 && $n <= 10) ? $n : self::DEFAULT_SEQ;
    }

    /** Sustituye los tokens de siglas/año (deja {SEC} para la secuencia). */
    public static function resolveTokens(string $pattern, string $shortName): string
    {
        $siglas = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $shortName));
        $siglas = $siglas !== '' ? $siglas : 'INST';
        $year = (string) now()->year;

        return str_replace(
            ['{SIGLAS}', '{AÑO}', '{ANIO}', '{ANO}', '{YEAR}', '{AÑO2}', '{ANIO2}'],
            [$siglas, $year, $year, $year, $year, substr($year, -2), substr($year, -2)],
            $pattern,
        );
    }

    /** Genera un código único para las siglas dadas según la plantilla vigente. */
    public static function generate(string $shortName): string
    {
        $resolved = self::resolveTokens(self::pattern(), $shortName);
        $len = self::seqLength();

        if (str_contains($resolved, '{SEC}')) {
            $seq = 1;
            do {
                $code = str_replace('{SEC}', str_pad((string) $seq, $len, '0', STR_PAD_LEFT), $resolved);
                $seq++;
            } while (Institution::where('code', $code)->exists() && $seq < 1000000);

            return $code;
        }

        // Plantilla sin secuencia: garantiza unicidad añadiendo un sufijo.
        $code = $resolved;
        $n = 1;
        while (Institution::where('code', $code)->exists()) {
            $code = $resolved.'-'.(++$n);
        }

        return $code;
    }

    /** Ejemplo de código para una plantilla (para la vista previa del ajuste). */
    public static function preview(string $pattern, int $seqLength): string
    {
        $resolved = self::resolveTokens($pattern, 'MINEC');

        return str_replace('{SEC}', str_pad('1', max(1, min($seqLength, 10)), '0', STR_PAD_LEFT), $resolved);
    }
}
