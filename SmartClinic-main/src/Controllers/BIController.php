<?php

namespace Controllers;

use Dao\CentroSalud as DaoCentroSalud;
use Dao\ClinicaAvanzada as Clinica;
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
        ]);
    }

    /**
     * Añade una escala porcentual estable para las barras del tablero.
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
