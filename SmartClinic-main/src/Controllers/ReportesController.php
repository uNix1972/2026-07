<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\Medicos as DaoMedicos;
use Dao\Pacientes as DaoPacientes;
use Utilities\Security;
use Views\Renderer;

class ReportesController extends PrivateController
{
    public function run(): void
    {
        $citas = DaoCitas::getAllCitas();
        $pacientes = DaoPacientes::getAllPacientes();
        $medicos = DaoMedicos::getAllMedicosReport();

        $desde = \Utilities\Validators::sanitizeDate($_GET['desde'] ?? '') ?? '';
        $hasta = \Utilities\Validators::sanitizeDate($_GET['hasta'] ?? '') ?? '';

        if ($desde !== '' || $hasta !== '') {
            $citas = array_values(array_filter($citas, function ($cita) use ($desde, $hasta) {
                $fecha = substr(strval($cita['fecha_hora'] ?? ''), 0, 10);
                if ($desde !== '' && $fecha < $desde) {
                    return false;
                }
                if ($hasta !== '' && $fecha > $hasta) {
                    return false;
                }
                return true;
            }));
        }

        $estadoMap = [];
        $citasHoy = 0;
        $hoy = date('Y-m-d');
        foreach ($citas as $cita) {
            $estado = strval($cita['nombre_estado'] ?? 'Sin estado');
            if (!isset($estadoMap[$estado])) {
                $estadoMap[$estado] = 0;
            }
            $estadoMap[$estado]++;
            if (substr(strval($cita['fecha_hora'] ?? ''), 0, 10) === $hoy) {
                $citasHoy++;
            }
        }

        $estadoRows = [];
        foreach ($estadoMap as $estado => $total) {
            $estadoRows[] = [
                'estado' => $estado,
                'total' => $total,
            ];
        }

        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($citas);
            return;
        }

        $ultimasCitas = array_slice($citas, 0, 10);

        Renderer::render('reportes', [
            'desde' => $desde,
            'hasta' => $hasta,
            'totalPacientes' => count($pacientes),
            'totalMedicos' => count($medicos),
            'totalCitas' => count($citas),
            'citasHoy' => $citasHoy,
            'estadoRows' => $estadoRows,
            'ultimasCitas' => $ultimasCitas,
            'userName' => Security::getUser()['userName'] ?? 'Usuario',
        ]);
    }

    private function exportCsv(array $citas): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=smartclinic_reporte_citas.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Fecha', 'Paciente', 'Medico', 'Especialidad', 'Estado']);
        foreach ($citas as $cita) {
            fputcsv($out, [
                $cita['id'] ?? '',
                $cita['fecha_hora'] ?? '',
                trim(($cita['paciente_nombres'] ?? '') . ' ' . ($cita['paciente_apellidos'] ?? '')),
                trim(($cita['medico_nombres'] ?? '') . ' ' . ($cita['medico_apellidos'] ?? '')),
                $cita['nombre_especialidad'] ?? '',
                $cita['nombre_estado'] ?? '',
            ]);
        }
        fclose($out);
    }
}
