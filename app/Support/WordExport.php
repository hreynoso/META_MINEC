<?php

namespace App\Support;

use Illuminate\Http\Response;
use ZipArchive;

/**
 * Genera documentos Word (.docx / OOXML) sin dependencias externas, armando el
 * paquete ZIP mínimo válido (título + párrafos). Suficiente para memorias e
 * informes en texto que se editan luego en Word/Google Docs.
 */
class WordExport
{
    private const CONTENT_TYPES = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        .'</Types>';

    private const RELS = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        .'</Relationships>';

    /**
     * @param  string[]  $subtitle  Líneas bajo el título (institución, período, etc.).
     */
    public static function download(string $filename, string $title, array $subtitle, string $body): Response
    {
        $document = static::buildDocument($title, $subtitle, $body);

        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::CONTENT_TYPES);
        $zip->addFromString('_rels/.rels', self::RELS);
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @param  string[]  $subtitle */
    private static function buildDocument(string $title, array $subtitle, string $body): string
    {
        $parts = [static::heading($title)];

        foreach ($subtitle as $line) {
            $parts[] = static::paragraph($line, center: true, muted: true);
        }

        $parts[] = static::paragraph('');

        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            $parts[] = static::paragraph($line);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.implode('', $parts)
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private static function heading(string $text): string
    {
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="120"/></w:pPr>'
            .'<w:r><w:rPr><w:b/><w:sz w:val="36"/></w:rPr>'
            .'<w:t xml:space="preserve">'.static::esc($text).'</w:t></w:r></w:p>';
    }

    private static function paragraph(string $text, bool $center = false, bool $muted = false): string
    {
        $pPr = '';
        if ($center) {
            $pPr = '<w:pPr><w:jc w:val="center"/></w:pPr>';
        }

        $rPr = $muted ? '<w:rPr><w:color w:val="64748B"/><w:sz w:val="18"/></w:rPr>' : '<w:rPr><w:sz w:val="22"/></w:rPr>';

        return '<w:p>'.$pPr.'<w:r>'.$rPr.'<w:t xml:space="preserve">'.static::esc($text).'</w:t></w:r></w:p>';
    }

    private static function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
