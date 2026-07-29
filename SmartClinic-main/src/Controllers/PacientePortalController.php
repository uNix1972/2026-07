<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\ClinicaAvanzada as Clinica;
use Dao\Especialidad as DaoEspecialidad;
use Dao\MedicoCentroSalud as DaoMedicoCentroSalud;
use Dao\Medicos as DaoMedicos;
use Utilities\ClinicalPdf;
use Utilities\MessageNotifier;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class PacientePortalController extends PrivateController
{
    public function run(): void
    {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'agendar':
                $this->agendar();
                break;
            case 'pagar':
                $this->pagar();
                break;
            case 'pdf':
                $this->pdf();
                break;
            default:
                $this->index();
        }
    }

    private function getPaciente(): array
    {
        $paciente = Clinica::getPacienteByUsuario(intval(Security::getUserId()));
        if (!$paciente) {
            http_response_code(403);
            exit('La cuenta no está vinculada con un paciente.');
        }
        return $paciente;
    }

    private function index(): void
    {
        Site::addLink('public/css/clinical-record.css');
        $paciente = $this->getPaciente();
        $citas = DaoCitas::getCitasByPaciente(intval($paciente['id']));
        $fechaDesde = Validators::sanitizeDate(
            (string)($_GET['fecha_desde'] ?? '')
        );
        $fechaHasta = Validators::sanitizeDate(
            (string)($_GET['fecha_hasta'] ?? '')
        );
        if ($fechaDesde && $fechaHasta && $fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }
        Renderer::render('paciente_portal', [
            'paciente' => $paciente,
            'paciente_nombres' => $paciente['nombres'] ?? 'Paciente',
            'paciente_apellidos' => $paciente['apellidos'] ?? 'Demo',
            'paciente_id' => intval($paciente['id']),
            'paciente_telefono' => $paciente['telefono'] ?? '',
            'paciente_direccion' => $paciente['direccion'] ?? '',
            'citas' => $citas,
            'expedientes' => Clinica::getCitasExpedientePaciente(
                intval($paciente['id']),
                null,
                $fechaDesde,
                $fechaHasta
            ),
            'fecha_desde' => $fechaDesde ?? '',
            'fecha_hasta' => $fechaHasta ?? '',
            'historial' => Clinica::getHistorialPaciente(intval($paciente['id'])),
            'recetas' => Clinica::getRecetasPaciente(intval($paciente['id'])),
            'medicos' => DaoMedicos::getAllMedicos(),
            'csrf_token' => Security::getCsrfToken(),
            'minDate' => date('Y-m-d'),
            'maxDate' => (new \DateTime())->add(new \DateInterval('P3M'))->format('Y-m-d'),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    private function agendar(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PacientePortalController&msg=Token CSRF inválido');
        }

        $paciente = $this->getPaciente();
        $pacienteId = intval($paciente['id']);
        $medicoId = \Utilities\Validators::sanitizeId($_POST['medico_id'] ?? 0);
        $centroSaludId =
            \Utilities\Validators::sanitizeId($_POST['centro_salud_id'] ?? 0);
        $fecha = \Utilities\Validators::sanitizeDate($_POST['fecha'] ?? '');
        $hora = \Utilities\Validators::sanitizeTime($_POST['hora'] ?? '');

        if (
            $medicoId === null
            || $centroSaludId === null
            || $fecha === null
            || $hora === null
        ) {
            Site::redirectTo(
                'index.php?page=PacientePortalController&msg='
                . urlencode('No se pudo agendar. Complete todos los datos.')
            );
        }

        $fechaHora = $fecha . ' ' . $hora . ':00';
        $ubicacion = DaoMedicoCentroSalud::getActivoByMedicoCentro(
            $medicoId,
            $centroSaludId
        );
        $fechaCita = new \DateTime($fechaHora);
        $minBooking = (new \DateTime())->add(new \DateInterval('PT30M'));

        if (!$ubicacion) {
            Site::redirectTo(
                'index.php?page=PacientePortalController&msg='
                . urlencode('El centro seleccionado no está asignado activamente al médico.')
            );
        }
        if ($fechaCita < $minBooking) {
            Site::redirectTo(
                'index.php?page=PacientePortalController&msg='
                . urlencode('La cita debe reservarse al menos 30 minutos antes.')
            );
        }

        $conflicts = DaoCitas::getAvailabilityConflicts(
            $medicoId,
            $pacienteId,
            $fechaHora
        );
        if (count($conflicts) > 0) {
            $conflict = $conflicts[0];
            $timestamp = strtotime(strval($conflict['fecha_hora'] ?? ''));
            $conflictTime = $timestamp !== false
                ? date('H:i', $timestamp)
                : 'la hora seleccionada';
            $isPatientConflict =
                intval($conflict['paciente_id'] ?? 0) === $pacienteId;
            $doctorName = trim(
                strval($conflict['medico_nombres'] ?? '')
                . ' '
                . strval($conflict['medico_apellidos'] ?? '')
            );
            $message = $isPatientConflict
                ? 'Ya tiene otra cita a las '
                    . $conflictTime
                    . ' con el médico '
                    . ($doctorName !== '' ? $doctorName : 'indicado')
                    . '.'
                : 'El médico seleccionado ya tiene otra cita a las '
                    . $conflictTime
                    . '.';
            Site::redirectTo(
                'index.php?page=PacientePortalController&msg='
                . urlencode($message)
            );
        }

        $citaId = DaoCitas::insertCita(
            $pacienteId,
            $medicoId,
            $centroSaludId,
            1,
            $fechaHora
        );
        Clinica::crearNotificacion(
            'Nueva cita web',
            'Paciente agendó una cita desde el portal. Cita #' . $citaId
        );
        \Utilities\AuditLogger::log(
            'CITA_WEB',
            'Paciente',
            'Paciente agendó cita web',
            [
                'cita_id' => $citaId,
                'medico_id' => $medicoId,
                'centro_salud_id' => $centroSaludId,
            ]
        );
        $medico = DaoMedicos::getMedicoById($medicoId) ?: [];
        MessageNotifier::sendAppointmentSaved(
            $paciente,
            $medico,
            $ubicacion,
            $fechaHora,
            $citaId
        );
        Site::redirectTo(
            'index.php?page=PacientePortalController&msg='
            . urlencode(
                'Cita solicitada. Puede simular el pago para confirmarla.'
            )
        );
    }

    private function pagar(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PacientePortalController&msg=Token CSRF inválido');
        }
        $citaId = intval($_POST['cita_id'] ?? 0);
        $total = floatval($_POST['total'] ?? 750.00);
        $paciente = $this->getPaciente();
        $cita = Clinica::getCitaExpediente($citaId);
        if (
            $citaId > 0
            && $cita
            && intval($cita['paciente_id']) === intval($paciente['id'])
        ) {
            $transaccion = 'SIM-' . date('YmdHis') . '-' . random_int(100, 999);
            Clinica::crearPago($citaId, $total, 'Tarjeta demo', $transaccion);
            Clinica::actualizarEstadoCita($citaId, 2);
            Clinica::crearNotificacion('Pago confirmado', 'Pago simulado aprobado para la cita #' . $citaId . '. Recibo generado.');
            \Utilities\AuditLogger::log('PAGO_SIMULADO', 'Paciente', 'Pago simulado aprobado', ['cita_id' => $citaId, 'total' => $total]);
        }
        Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('Pago simulado aprobado y recibo generado.'));
    }

    private function pdf(): void
    {
        $paciente = $this->getPaciente();
        $cita = Clinica::getCitaExpediente(intval($_GET['cita_id'] ?? 0));
        if (
            !$cita
            || intval($cita['paciente_id']) !== intval($paciente['id'])
        ) {
            http_response_code(403);
            exit('Acceso denegado.');
        }

        $recetas = empty($cita['historial_id'])
            ? []
            : Clinica::getRecetasHistorial(intval($cita['historial_id']));
        ClinicalPdf::download(
            'expediente-cita-' . $cita['id'] . '.pdf',
            $cita,
            $recetas
        );
    }
}
