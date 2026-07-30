<?php

namespace Utilities;

/**
 * PDF clínico con identidad visual SmartClinic, sin dependencias externas.
 */
class ClinicalPdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 42;

    private array $pages = [];
    private array $pageBottoms = [];
    private array $commands = [];
    private array $cita = [];
    private float $y = 0;

    public static function download(
        string $filename,
        array $cita,
        array $recetas = []
    ): void {
        $generator = new self();
        $pdf = $generator->build($cita, $recetas);

        header('Content-Type: application/pdf');
        header(
            'Content-Disposition: attachment; filename="'
            . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename)
            . '"'
        );
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    public function build(array $cita, array $recetas = []): string
    {
        $this->pages = [];
        $this->pageBottoms = [];
        $this->commands = [];
        $this->cita = $cita;
        $this->newPage($cita);
        $this->patientCard($cita);
        $this->vitals($cita);
        $this->clinicalNotes($cita);
        $this->prescriptions($recetas);
        $this->closePage();

        return $this->compile();
    }

    private function newPage(array $cita): void
    {
        if ($this->commands) {
            $this->closePage();
        }
        $this->commands = [];

        $this->fillRect(0, 742, self::PAGE_WIDTH, 100, [0.012, 0.231, 0.624]);
        $this->fillRect(0, 736, self::PAGE_WIDTH, 6, [0.004, 0.553, 0.925]);
        $this->text(42, 805, 'SMARTCLINIC', 18, true, [1, 1, 1]);
        $this->text(
            42,
            785,
            'Expediente clínico por cita',
            11,
            false,
            [0.80, 0.93, 1]
        );
        $this->text(
            553,
            806,
            'CITA #' . ($cita['id'] ?? 'N/D'),
            10,
            true,
            [1, 1, 1],
            'right'
        );
        $this->text(
            553,
            787,
            self::formatDate((string)($cita['fecha_hora'] ?? '')),
            9,
            false,
            [0.80, 0.93, 1],
            'right'
        );
        $this->y = 710;
    }

    /**
     * Cierra el documento como una sola página que crece hacia abajo
     * según el contenido real, en vez de partirlo en varias páginas de
     * tamaño fijo. El pie de página se ancla justo debajo del último
     * bloque de contenido (no en una coordenada fija), y esa posición
     * define el límite inferior real del PDF (ver compile()).
     */
    private function closePage(): void
    {
        if (!$this->commands) {
            return;
        }
        $footerLineY = $this->y - 20;
        $footerTextY = $this->y - 35;
        $this->line(42, $footerLineY, 553, $footerLineY, [0.82, 0.87, 0.93]);
        $this->text(
            42,
            $footerTextY,
            'Documento clínico generado por SmartClinic',
            7.5,
            false,
            [0.38, 0.45, 0.55]
        );
        $this->text(
            553,
            $footerTextY,
            'Página 1',
            7.5,
            true,
            [0.38, 0.45, 0.55],
            'right'
        );

        // Alto mínimo de 500pt (bottom <= 342) para que una cita con poco
        // contenido no se vea como una tira diminuta; si hay más
        // contenido, el límite baja (se vuelve más negativo) y la página
        // crece para siempre caber en una sola hoja.
        $this->pageBottoms[] = min(342, $footerTextY - 15);
        $this->pages[] = implode("\n", $this->commands);
        $this->commands = [];
    }

    /**
     * El expediente de una cita siempre se genera como una sola página
     * (ver closePage()/compile()), así que aquí ya no hace falta romper
     * en una página nueva cuando el contenido es largo.
     */
    private function ensureSpace(float $height): void
    {
    }

    private function patientCard(array $cita): void
    {
        $height = 112;
        $this->fillRect(42, $this->y - $height, 511, $height, [0.965, 0.982, 1]);
        $this->strokeRect(42, $this->y - $height, 511, $height, [0.80, 0.88, 0.96]);
        $this->text(58, $this->y - 23, 'DATOS DE LA ATENCIÓN', 9, true, [0.012, 0.231, 0.624]);

        $patient = trim(
            (string)($cita['paciente_nombres'] ?? '')
            . ' '
            . (string)($cita['paciente_apellidos'] ?? '')
        );
        $doctor = trim(
            (string)($cita['medico_nombres'] ?? '')
            . ' '
            . (string)($cita['medico_apellidos'] ?? '')
        );
        $this->labelValue(58, $this->y - 46, 'Paciente', $patient ?: 'N/D', 238);
        $this->labelValue(
            306,
            $this->y - 46,
            'Identidad',
            (string)($cita['identidad'] ?? 'N/D'),
            230
        );
        $this->labelValue(58, $this->y - 75, 'Médico', $doctor ?: 'N/D', 238);
        $this->labelValue(
            306,
            $this->y - 75,
            'Especialidad',
            (string)($cita['nombre_especialidad'] ?? 'N/D'),
            230
        );
        $this->labelValue(
            58,
            $this->y - 102,
            'Centro de salud',
            (string)($cita['centro_nombre'] ?? 'N/D'),
            300
        );
        $this->labelValue(
            380,
            $this->y - 102,
            'Estado',
            (string)($cita['nombre_estado'] ?? 'N/D'),
            155
        );
        $this->y -= $height + 20;
    }

    private function vitals(array $cita): void
    {
        $this->sectionTitle('SIGNOS VITALES');
        $cards = [
            ['Temperatura', self::value($cita, 'temperatura', ' °C')],
            [
                'Presión arterial',
                self::pair($cita, 'presion_sistolica', 'presion_diastolica', ' mmHg')
            ],
            ['Frecuencia cardiaca', self::value($cita, 'frecuencia_cardiaca', ' lpm')],
            ['Frecuencia respiratoria', self::value($cita, 'frecuencia_respiratoria', ' rpm')],
            ['Saturación de oxígeno', self::value($cita, 'saturacion_oxigeno', ' %')],
            [
                'Peso / talla',
                self::value($cita, 'peso', ' kg') . ' / '
                . self::value($cita, 'talla', ' cm')
            ],
        ];

        $cardWidth = 163;
        $cardHeight = 55;
        foreach ($cards as $index => $card) {
            $column = $index % 3;
            $row = intdiv($index, 3);
            $x = 42 + ($column * 174);
            $top = $this->y - ($row * 65);
            $this->fillRect($x, $top - $cardHeight, $cardWidth, $cardHeight, [0.975, 0.985, 0.997]);
            $this->strokeRect($x, $top - $cardHeight, $cardWidth, $cardHeight, [0.84, 0.90, 0.96]);
            $this->text($x + 11, $top - 17, $card[0], 7.2, true, [0.38, 0.45, 0.55]);
            $this->text($x + 11, $top - 38, $card[1], 11, true, [0.012, 0.231, 0.624]);
        }
        $this->y -= 132;

        $notes = trim((string)($cita['signos_notas'] ?? ''));
        if ($notes !== '') {
            $this->textBlock('Observaciones de signos vitales', $notes, [0.94, 0.97, 1]);
        }
    }

    private function clinicalNotes(array $cita): void
    {
        $this->sectionTitle('NOTA CLÍNICA');
        $items = [
            ['Motivo de consulta', $cita['motivo_consulta'] ?? 'Pendiente'],
            ['Diagnóstico', $cita['diagnostico'] ?? 'Pendiente'],
            ['Tratamiento', $cita['tratamiento'] ?? 'No registrado'],
            ['Observaciones', $cita['observaciones'] ?? 'No registradas'],
        ];
        foreach ($items as $item) {
            $this->textBlock($item[0], (string)$item[1], [0.97, 0.98, 0.99]);
        }
    }

    private function prescriptions(array $recetas): void
    {
        $this->sectionTitle('RECETAS Y ÓRDENES');
        if (!$recetas) {
            $this->textBlock('Indicaciones', 'Sin recetas u ordenes registradas.', [0.97, 0.98, 0.99]);
            return;
        }
        foreach ($recetas as $index => $receta) {
            $title = ($index + 1) . '. ' . (string)($receta['medicamento'] ?? 'Indicacion');
            $text = (string)($receta['indicaciones'] ?? 'Sin indicaciones adicionales.');
            $this->textBlock($title, $text, [0.97, 0.98, 0.99]);
        }
    }

    private function sectionTitle(string $title): void
    {
        // Reserva también el primer bloque para evitar encabezados huérfanos.
        $this->ensureSpace(110);
        $this->fillRect(42, $this->y - 26, 511, 26, [0.012, 0.231, 0.624]);
        $this->fillRect(42, $this->y - 26, 6, 26, [0.004, 0.553, 0.925]);
        $this->text(58, $this->y - 18, $title, 9, true, [1, 1, 1]);
        $this->y -= 38;
    }

    private function textBlock(
        string $label,
        string $value,
        array $background
    ): void {
        $lines = self::wrap($value, 82);
        $height = 33 + (count($lines) * 12);
        $this->ensureSpace($height + 8);
        $this->fillRect(42, $this->y - $height, 511, $height, $background);
        $this->strokeRect(42, $this->y - $height, 511, $height, [0.86, 0.90, 0.94]);
        $this->text(56, $this->y - 17, $label, 8, true, [0.012, 0.231, 0.624]);
        $lineY = $this->y - 34;
        foreach ($lines as $line) {
            $this->text(56, $lineY, $line, 9.2, false, [0.10, 0.14, 0.22]);
            $lineY -= 12;
        }
        $this->y -= $height + 8;
    }

    private function labelValue(
        float $x,
        float $y,
        string $label,
        string $value,
        float $width
    ): void {
        $this->text($x, $y, $label, 7, true, [0.38, 0.45, 0.55]);
        $clean = self::fit($value, $width, 9);
        $this->text($x, $y - 12, $clean, 9, true, [0.10, 0.14, 0.22]);
    }

    private function text(
        float $x,
        float $y,
        string $text,
        float $size,
        bool $bold,
        array $color,
        string $align = 'left'
    ): void {
        if ($align === 'right') {
            $x -= self::estimateWidth($text, $size, $bold);
        }
        $font = $bold ? 'F2' : 'F1';
        $this->commands[] = sprintf(
            'BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET',
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            self::pdfText($text)
        );
    }

    private function fillRect(
        float $x,
        float $y,
        float $width,
        float $height,
        array $color
    ): void {
        $this->commands[] = sprintf(
            'q %.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f Q',
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $width,
            $height
        );
    }

    private function strokeRect(
        float $x,
        float $y,
        float $width,
        float $height,
        array $color
    ): void {
        $this->commands[] = sprintf(
            'q %.3F %.3F %.3F RG 0.8 w %.2F %.2F %.2F %.2F re S Q',
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $width,
            $height
        );
    }

    private function line(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $color
    ): void {
        $this->commands[] = sprintf(
            'q %.3F %.3F %.3F RG 0.7 w %.2F %.2F m %.2F %.2F l S Q',
            $color[0],
            $color[1],
            $color[2],
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    private function compile(): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        $nextId = 5;
        foreach ($this->pages as $index => $stream) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $bottom = sprintf('%.2F', $this->pageBottoms[$index] ?? 0);
            $objects[$pageId] =
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 {$bottom} 595 842] "
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> '
                . "/Contents {$contentId} 0 R >>";
            $objects[$contentId] =
                '<< /Length ' . strlen($stream) . " >>\nstream\n"
                . $stream . "\nendstream";
        }
        $kids = implode(' ', array_map(
            static fn (int $id): string => $id . ' 0 R',
            $pageIds
        ));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count '
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
        $pdf .= 'trailer << /Size ' . ($objectCount + 1)
            . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private static function wrap(string $text, int $width): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?: 'No registrado';
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private static function value(array $data, string $key, string $suffix): string
    {
        $value = $data[$key] ?? null;
        return $value === null || $value === '' ? 'N/D' : $value . $suffix;
    }

    private static function pair(
        array $data,
        string $first,
        string $second,
        string $suffix
    ): string {
        if (
            !isset($data[$first], $data[$second])
            || $data[$first] === ''
            || $data[$second] === ''
        ) {
            return 'N/D';
        }
        return $data[$first] . '/' . $data[$second] . $suffix;
    }

    private static function formatDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp === false
            ? ($value ?: 'Fecha no disponible')
            : date('d/m/Y H:i', $timestamp);
    }

    private static function fit(string $text, float $width, float $size): string
    {
        $truncated = false;
        while (
            strlen($text) > 3
            && self::estimateWidth($text, $size, true) > $width
        ) {
            $text = substr($text, 0, -1);
            $truncated = true;
        }
        return $text === ''
            ? 'N/D'
            : rtrim($text) . ($truncated ? '...' : '');
    }

    private static function estimateWidth(
        string $text,
        float $size,
        bool $bold
    ): float {
        return strlen($text) * $size * ($bold ? 0.57 : 0.52);
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
