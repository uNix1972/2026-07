<?php

namespace Controllers;

use Dao\CentroSalud as DaoCentroSalud;
use Dao\ClinicaAvanzada as Clinica;
use Utilities\Security;
use Utilities\Validators;
use Views\Renderer;

class BIController extends PrivateController
{
    public function run(): void
    {
        $centros = DaoCentroSalud::getAll();
        $centroSaludId =
            Validators::sanitizeId($_GET['centro_salud_id'] ?? 0);
        $centroSeleccionado = null;

        foreach ($centros as $centro) {
            if ((int) $centro['id'] === $centroSaludId) {
                $centroSeleccionado = $centro;
                break;
            }
        }

        if ($centroSeleccionado === null && count($centros) > 0) {
            foreach ($centros as $centro) {
                if (
                    $centro['codigo'] === 'SMARTCLINIC'
                    && $centro['estado'] === 'ACT'
                ) {
                    $centroSeleccionado = $centro;
                    break;
                }
            }
            $centroSeleccionado = $centroSeleccionado ?? $centros[0];
            $centroSaludId = (int) $centroSeleccionado['id'];
        }

        if ($centroSeleccionado === null) {
            Renderer::render('bi_dashboard', [
                'sinCentros' => true,
                'centros' => [],
                'citasPorEstado' => [],
                'citasPorMes' => [],
                'ingresos' => [],
                'cargaMedicos' => []
            ]);
            return;
        }

        if (($_GET['action'] ?? '') === 'report') {
            $this->renderPrintableReport(
                $centroSeleccionado,
                $centroSaludId
            );
            return;
        }

        $metricas = Clinica::getMetricasBI($centroSaludId);
        $citasPorEstado = $this->addPieMetadata(
            $this->addPercentage($metricas['citasPorEstado'])
        );
        $cargaMedicos = $this->addPieMetadata(
            $this->addPercentage(
                $metricas['cargaMedicos'],
                'total_citas'
            ),
            'total_citas'
        );
        $resumen = $metricas['resumen'];

        Renderer::render('bi_dashboard', [
            'sinCentros' => false,
            'centros' => array_map(
                function (array $centro) use ($centroSaludId): array {
                    $centro['selected'] =
                        (int) $centro['id'] === $centroSaludId;
                    $centro['nombre_opcion'] =
                        $centro['nombre']
                        . ' - '
                        . $centro['ciudad']
                        . ($centro['estado'] === 'ACT'
                            ? ''
                            : ' (Inactivo)');
                    return $centro;
                },
                $centros
            ),
            'centroSaludId' => $centroSaludId,
            'centroNombre' => $centroSeleccionado['nombre'],
            'centroCodigo' => $centroSeleccionado['codigo'],
            'centroCiudad' => $centroSeleccionado['ciudad'],
            'centroEstadoTexto' =>
                $centroSeleccionado['estado'] === 'ACT'
                    ? 'Centro activo'
                    : 'Centro inactivo',
            'centroActivo' => $centroSeleccionado['estado'] === 'ACT',
            'centroInactivo' => $centroSeleccionado['estado'] !== 'ACT',
            'totalCitas' => (int) ($resumen['total_citas'] ?? 0),
            'citasMesActual' =>
                (int) ($resumen['citas_mes_actual'] ?? 0),
            'citasFuturas' => (int) ($resumen['citas_futuras'] ?? 0),
            'medicosAsignados' =>
                (int) ($resumen['medicos_asignados'] ?? 0),
            'ingresosTotal' => number_format(
                (float) ($resumen['ingresos_total'] ?? 0),
                2,
                '.',
                ','
            ),
            'ingresosMesActual' => number_format(
                (float) ($resumen['ingresos_mes_actual'] ?? 0),
                2,
                '.',
                ','
            ),
            'citasPorEstado' => $citasPorEstado,
            'citasPorMes' => $metricas['citasPorMes'],
            'ingresos' => $metricas['ingresos'],
            'cargaMedicos' => $cargaMedicos,
            'totalCargaMedicos' => array_sum(
                array_map(
                    static fn(array $row): int =>
                        (int) ($row['total_citas'] ?? 0),
                    $cargaMedicos
                )
            ),
            'reporteDesde' => date('Y-m-01'),
            'reporteHasta' => date('Y-m-d'),
        ]);
    }

    /**
     * Genera un documento BI independiente y listo para imprimir.
     *
     * El centro ya fue validado contra el catalogo disponible en run(). El
     * tipo se limita a una lista cerrada y las fechas invalidas regresan al
     * mes actual. Si el rango llega invertido se normaliza antes de consultar.
     */
    private function renderPrintableReport(
        array $centro,
        int $centroSaludId
    ): void {
        $reportTypes = [
            'ejecutivo' => 'Reporte ejecutivo',
            'citas' => 'Detalle de citas',
            'financiero' => 'Reporte financiero'
        ];
        $reportType = strtolower(trim((string) (
            $_GET['report_type'] ?? 'ejecutivo'
        )));
        if (!isset($reportTypes[$reportType])) {
            $reportType = 'ejecutivo';
        }

        $fechaDesde = Validators::sanitizeDate(
            (string) ($_GET['desde'] ?? '')
        ) ?? date('Y-m-01');
        $fechaHasta = Validators::sanitizeDate(
            (string) ($_GET['hasta'] ?? '')
        ) ?? date('Y-m-d');
        if ($fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $reporte = Clinica::getReporteBI(
            $centroSaludId,
            $fechaDesde,
            $fechaHasta
        );
        $resumen = $reporte['resumen'] ?? [];
        $citas = $this->prepareAppointmentRows(
            $reporte['citas'] ?? []
        );
        $pagos = $this->preparePaymentRows(
            $reporte['pagos'] ?? []
        );
        $citasPorEstado = $this->escapeReportRows(
            $reporte['citasPorEstado'] ?? [],
            ['estado']
        );
        $cargaMedicos = $this->escapeReportRows(
            $reporte['cargaMedicos'] ?? [],
            ['medico', 'especialidad']
        );
        $usuario = Security::getUser();

        Renderer::render('bi_report_print', [
            'reportTitle' => $reportTypes[$reportType],
            'reporteEjecutivo' => $reportType === 'ejecutivo',
            'reporteCitas' => $reportType === 'citas',
            'reporteFinanciero' => $reportType === 'financiero',
            'centroSaludId' => $centroSaludId,
            'centroNombre' => $this->escape((string) $centro['nombre']),
            'centroCodigo' => $this->escape((string) $centro['codigo']),
            'centroCiudad' => $this->escape((string) $centro['ciudad']),
            'periodoDesde' => $this->formatDate($fechaDesde),
            'periodoHasta' => $this->formatDate($fechaHasta),
            'generadoEn' => date('d/m/Y H:i'),
            'generadoPor' => $this->escape(
                is_array($usuario)
                    ? (string) ($usuario['userName'] ?? 'Usuario')
                    : 'Usuario'
            ),
            'totalCitas' => (int) ($resumen['total_citas'] ?? 0),
            'citasCompletadas' =>
                (int) ($resumen['citas_completadas'] ?? 0),
            'citasCanceladas' =>
                (int) ($resumen['citas_canceladas'] ?? 0),
            'totalPacientes' => (int) ($resumen['pacientes'] ?? 0),
            'totalMedicos' => (int) ($resumen['medicos'] ?? 0),
            'totalPagos' => (int) ($resumen['total_pagos'] ?? 0),
            'ingresos' => number_format(
                (float) ($resumen['ingresos'] ?? 0),
                2,
                '.',
                ','
            ),
            'promedioPago' => number_format(
                (float) ($resumen['promedio_pago'] ?? 0),
                2,
                '.',
                ','
            ),
            'citasPorEstado' => $citasPorEstado,
            'cargaMedicos' => $cargaMedicos,
            'citas' => $citas,
            'sinCitas' => count($citas) === 0,
            'pagos' => $pagos,
            'sinPagos' => count($pagos) === 0,
        ]);
    }

    /**
     * Formatea y escapa las filas del detalle de citas para la plantilla.
     */
    private function prepareAppointmentRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $dateTime = new \DateTimeImmutable(
                (string) $row['fecha_hora']
            );
            $row['fecha'] = $dateTime->format('d/m/Y');
            $row['hora'] = $dateTime->format('h:i A');
        }
        unset($row);

        return $this->escapeReportRows(
            $rows,
            [
                'paciente',
                'medico',
                'especialidad',
                'estado',
                'consultorio'
            ]
        );
    }

    /**
     * Formatea y escapa las filas del detalle financiero para la plantilla.
     */
    private function preparePaymentRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $dateTime = new \DateTimeImmutable(
                (string) $row['fecha_pago']
            );
            $row['fecha'] = $dateTime->format('d/m/Y H:i');
            $row['total_formateado'] = number_format(
                (float) ($row['total'] ?? 0),
                2,
                '.',
                ','
            );
        }
        unset($row);

        return $this->escapeReportRows(
            $rows,
            [
                'paciente',
                'medico',
                'metodo_pago',
                'id_transaccion_api'
            ]
        );
    }

    /**
     * Escapa campos textuales concretos antes de enviarlos al renderer.
     */
    private function escapeReportRows(
        array $rows,
        array $textKeys
    ): array {
        foreach ($rows as &$row) {
            foreach ($textKeys as $key) {
                $row[$key] = $this->escape(
                    (string) ($row[$key] ?? '')
                );
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Convierte una fecha ISO valida a la presentacion del reporte.
     */
    private function formatDate(string $date): string
    {
        return (new \DateTimeImmutable($date))->format('d/m/Y');
    }

    /**
     * Protege valores variables que se imprimen dentro del HTML.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * Anade una escala porcentual estable para las barras del tablero.
     */
    private function addPercentage(
        array $rows,
        string $valueKey = 'total'
    ): array {
        $maximum = 0;
        foreach ($rows as $row) {
            $maximum = max($maximum, (int) ($row[$valueKey] ?? 0));
        }

        foreach ($rows as &$row) {
            $value = (int) ($row[$valueKey] ?? 0);
            $row['porcentaje'] = $maximum > 0
                ? (int) round(($value / $maximum) * 100)
                : 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Prepara segmentos porcentuales para las graficas de pastel del BI.
     *
     * Usa una paleta cerrada y desplazamientos acumulados sobre una
     * circunferencia normalizada a 100. Los valores se calculan en el
     * servidor para que el tablero no dependa de una libreria JavaScript.
     */
    private function addPieMetadata(
        array $rows,
        string $valueKey = 'total'
    ): array {
        $total = array_sum(
            array_map(
                static fn(array $row): int =>
                    max(0, (int) ($row[$valueKey] ?? 0)),
                $rows
            )
        );
        $offset = 0.0;

        foreach ($rows as $index => &$row) {
            $value = max(0, (int) ($row[$valueKey] ?? 0));
            $percentage = $total > 0
                ? ($value / $total) * 100
                : 0.0;
            $row['piePercentage'] = number_format(
                $percentage,
                2,
                '.',
                ''
            );
            $row['pieRemainder'] = number_format(
                max(0, 100 - $percentage),
                2,
                '.',
                ''
            );
            $row['pieOffset'] = number_format(-$offset, 2, '.', '');
            $row['piePercentLabel'] = number_format(
                $percentage,
                1,
                '.',
                ''
            );
            $row['pieColorClass'] =
                'bi-pie-segment--' . (($index % 8) + 1);
            $offset += $percentage;
        }
        unset($row);

        return $rows;
    }
}
