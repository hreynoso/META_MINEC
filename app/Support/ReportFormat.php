<?php

namespace App\Support;

/**
 * Da formato a los textos generados por IA (memorias, informes) detectando
 * encabezados y aplicando negritas/justificado. Es tolerante a las distintas
 * formas en que la IA marca los títulos: Markdown (## / **), o numerados
 * ("1. Título" / "(1) Título").
 */
class ReportFormat
{
    /** ¿La línea es un encabezado de sección? */
    public static function isHeading(string $line): bool
    {
        $line = trim($line);

        if ($line === '') {
            return false;
        }

        // Markdown: "# Título" … "###### Título"
        if (preg_match('/^#{1,6}\s+\S/', $line)) {
            return true;
        }

        // Línea completa en negrita: "**Título**" (con ":" opcional al final)
        if (preg_match('/^\*\*.+\*\*:?$/u', $line)) {
            return true;
        }

        // Numerado y corto, sin punto final: "1. Título", "(2) Título", "3) Título"
        if (preg_match('/^\(?\d+[\).]\s+\p{Lu}/u', $line)
            && mb_strlen($line) <= 90
            && ! str_ends_with($line, '.')) {
            return true;
        }

        return false;
    }

    /** Quita los marcadores de encabezado y deja el texto limpio. */
    public static function stripHeading(string $line): string
    {
        $line = trim($line);
        $line = preg_replace('/^#{1,6}\s+/', '', $line) ?? $line;
        $line = preg_replace('/^\*\*(.+?)\*\*:?$/u', '$1', $line) ?? $line;

        return trim($line);
    }

    /** ¿La línea es una viñeta? */
    public static function isBullet(string $line): bool
    {
        return (bool) preg_match('/^\s*[-*•]\s+\S/u', $line);
    }

    /** Quita el marcador de viñeta. */
    public static function stripBullet(string $line): string
    {
        return trim(preg_replace('/^\s*[-*•]\s+/u', '', trim($line)) ?? $line);
    }

    /**
     * Convierte el texto en HTML con encabezados (negrita) y párrafos justificados.
     * Escapa todo el contenido; solo interpreta **negrita** en línea.
     */
    public static function toHtml(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $html = '';
        $para = [];

        $flush = function () use (&$para, &$html): void {
            if ($para !== []) {
                $html .= '<p class="para">'.self::inlineHtml(implode(' ', $para)).'</p>';
                $para = [];
            }
        };

        foreach ($lines as $raw) {
            $line = trim($raw);

            if ($line === '') {
                $flush();

                continue;
            }

            if (self::isHeading($line)) {
                $flush();
                $html .= '<h2 class="section">'.self::inlineHtml(self::stripHeading($line)).'</h2>';

                continue;
            }

            if (self::isBullet($line)) {
                $flush();
                $html .= '<p class="bullet">'.self::inlineHtml(self::stripBullet($line)).'</p>';

                continue;
            }

            $para[] = $line;
        }

        $flush();

        return $html;
    }

    /** Escapa el texto y convierte **negrita** en <strong>. */
    private static function inlineHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
    }
}
