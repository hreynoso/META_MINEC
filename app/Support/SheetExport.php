<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación de grids a XLSX nativo vía OpenSpout (streaming a php://output).
 */
class SheetExport
{
    /**
     * @param  string[]  $headers
     * @param  array<int, array<int|string, mixed>>  $rows
     */
    public static function stream(string $filename, array $headers, array $rows): StreamedResponse
    {
        $name = str_ends_with($filename, '.xlsx') ? $filename : $filename.'.xlsx';

        return response()->streamDownload(function () use ($headers, $rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
