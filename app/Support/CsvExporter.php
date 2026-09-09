<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows, string $delimiter = ';'): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $delimiter) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, $delimiter);

            foreach ($rows as $row) {
                fputcsv($out, array_map(
                    fn ($value) => $value === null ? '' : (string) $value,
                    $row
                ), $delimiter);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
