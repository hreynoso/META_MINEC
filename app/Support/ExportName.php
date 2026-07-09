<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nomenclatura única de documentos exportables: AAAAMMDD-Nombre_del_Documento.ext
 */
class ExportName
{
    /** Construye el nombre de archivo con la fecha de hoy y el nombre normalizado. */
    public static function make(string $name, string $ext): string
    {
        return now()->format('Ymd').'-'.static::normalize($name).'.'.ltrim($ext, '.');
    }

    /** Convierte un nombre legible en un segmento con guiones bajos y sin acentos. */
    public static function normalize(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]+/', '_', Str::ascii($name)) ?? '';

        return trim($clean, '_') ?: 'Documento';
    }
}
