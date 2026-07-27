<?php

namespace Utilities;

/**
 * Generador PDF de texto sin dependencias externas.
 *
 * Los reportes clínicos se dividen automáticamente en varias páginas y usan
 * WinAnsi para conservar los caracteres españoles en los lectores PDF.
 */
class SimplePdf
{
    public static function download(string $filename, string $title, array $lines): void
    {
        $wrapped = [$title, str_repeat('-', 82)];
        foreach ($lines as $line) {
            $wrapped = array_merge($wrapped, self::wrap((string)$line, 88));
        }

        $pages = array_chunk($wrapped, 52);
        if (!$pages) {
            $pages = [['']];
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        $nextId = 4;

        foreach ($pages as $pageNumber => $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $stream = "BT /F1 10 Tf 45 800 Td 14 TL\n";
            foreach ($pageLines as $line) {
                $stream .= '(' . self::pdfText($line) . ") Tj T*\n";
            }
            $stream .= "ET";
            $objects[$pageId] =
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
                . "/Resources << /Font << /F1 3 0 R >> >> "
                . "/Contents {$contentId} 0 R >>";
            $objects[$contentId] =
                '<< /Length ' . strlen($stream) . " >>\nstream\n"
                . $stream . "\nendstream";
        }

        $kids = implode(' ', array_map(
            function (int $id): string {
                return $id . ' 0 R';
            },
            $pageIds
        ));
        $objects[2] =
            '<< /Type /Pages /Kids [' . $kids . '] /Count '
            . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $objectCount = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($objectCount + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $objectCount; $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n";
        }
        $pdf .= "trailer << /Size " . ($objectCount + 1)
            . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        header('Content-Type: application/pdf');
        header(
            'Content-Disposition: attachment; filename="'
            . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename)
            . '"'
        );
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private static function wrap(string $line, int $width): array
    {
        $line = preg_replace('/\s+/', ' ', trim($line));
        if ($line === '') {
            return [''];
        }
        return explode("\n", wordwrap($line, $width, "\n", true));
    }

    private static function pdfText(string $text): string
    {
        $encoded = function_exists('iconv')
            ? iconv('UTF-8', 'Windows-1252//TRANSLIT', $text)
            : $text;
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            (string)$encoded
        );
    }
}
