<?php

require __DIR__ . '/../autoload.php';

use Dao\ClinicaAvanzada as Clinica;
use Utilities\ClinicalPdf;
use Utilities\Site;

$_SERVER['REQUEST_URI'] = '/';
Site::configure();

$citaId = max(1, intval($argv[1] ?? 1));
$output = $argv[2] ?? (
    __DIR__ . '/../output/pdf/expediente-cita-' . $citaId . '.pdf'
);
$cita = Clinica::getCitaExpediente($citaId);

if (!$cita) {
    fwrite(STDERR, 'No se encontró la cita #' . $citaId . PHP_EOL);
    exit(1);
}

$recetas = empty($cita['historial_id'])
    ? []
    : Clinica::getRecetasHistorial(intval($cita['historial_id']));
$directory = dirname($output);

if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
    fwrite(STDERR, 'No se pudo crear el directorio de salida.' . PHP_EOL);
    exit(1);
}

$bytes = (new ClinicalPdf())->build($cita, $recetas);
if (file_put_contents($output, $bytes) === false) {
    fwrite(STDERR, 'No se pudo escribir el PDF.' . PHP_EOL);
    exit(1);
}

echo realpath($output) . PHP_EOL;
