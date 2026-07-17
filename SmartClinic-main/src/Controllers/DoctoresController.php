<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\ClinicaAvanzada as Clinica;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class DoctoresController extends PrivateController
{
    public function run(): void
    {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'confirmarLlegada':
                $this->confirmarLlegada();
                break;
            case 'iniciarAtencion':
                $this->iniciarAtencion();
                break;
            case 'guardarHistorial':
                $this->guardarHistorial();
                break;
            case 'finalizar':
                $this->finalizar();
                break;
            default:
                $this->index();
        }
    }

    private function index(): void
    {
        $userId = intval(Security::getUserId());
        $medico = Clinica::getMedicoByUsuario($userId);
        $medicoId = intval($_GET['medico_id'] ?? ($medico['id'] ?? 0));
        if ($medicoId <= 0) {
            $medicoId = 1;
        }

        $agenda = Clinica::getAgendaDoctor($medicoId);
        $sala = Clinica::getSalaEspera($medicoId);
        Renderer::render('doctor_portal', [
            'medico' => $medico ?: ['nombres' => 'Doctor', 'apellidos' => 'Demo', 'nombre_especialidad' => 'Medicina General'],
            'medico_nombres' => $medico['nombres'] ?? 'Doctor',
            'medico_apellidos' => $medico['apellidos'] ?? 'Demo',
            'medico_especialidad' => $medico['nombre_especialidad'] ?? 'Medicina General',
            'medico_id' => $medicoId,
            'agenda' => $agenda,
            'sala' => $sala,
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    private function confirmarLlegada(): void
    {
        $this->postEstado(6, 'Paciente marcado en sala de espera.');
    }

    private function iniciarAtencion(): void
    {
        $this->postEstado(7, 'Consulta iniciada.');
    }

    private function finalizar(): void
    {
        $this->postEstado(3, 'Consulta finalizada.');
    }

    private function postEstado(int $estadoId, string $mensaje): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=DoctoresController&msg=Token CSRF inválido');
        }
        $citaId = intval($_POST['cita_id'] ?? 0);
        if ($citaId > 0) {
            Clinica::actualizarEstadoCita($citaId, $estadoId);
            Clinica::crearNotificacion('Estado de cita', $mensaje . ' Cita #' . $citaId);
            \Utilities\AuditLogger::log('CITA_ESTADO', 'Doctores', $mensaje . ' Cita #' . $citaId, ['cita_id' => $citaId, 'estado_id' => $estadoId]);
        }
        Site::redirectTo('index.php?page=DoctoresController&msg=' . urlencode($mensaje));
    }

    private function guardarHistorial(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=DoctoresController&msg=Token CSRF inválido');
        }
        $citaId = intval($_POST['cita_id'] ?? 0);
        $motivo = trim((string)($_POST['motivo_consulta'] ?? ''));
        $diagnostico = trim((string)($_POST['diagnostico'] ?? ''));
        $tratamiento = trim((string)($_POST['tratamiento'] ?? ''));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $medicamento = trim((string)($_POST['medicamento'] ?? ''));
        $indicaciones = trim((string)($_POST['indicaciones'] ?? ''));

        if ($citaId > 0 && $motivo !== '' && $diagnostico !== '') {
            $historialId = Clinica::guardarHistorial($citaId, $motivo, $diagnostico, $tratamiento, $observaciones);
            if ($medicamento !== '' || $indicaciones !== '') {
                Clinica::guardarReceta($historialId, $medicamento ?: 'Indicaciones generales', $indicaciones ?: 'Según criterio médico');
            }
            Clinica::crearNotificacion('Historial clínico', 'Se registró historial clínico para la cita #' . $citaId);
            \Utilities\AuditLogger::log('HISTORIAL_GUARDADO', 'Doctores', 'Historial médico guardado', ['cita_id' => $citaId]);
        }
        Site::redirectTo('index.php?page=DoctoresController&msg=' . urlencode('Historial clínico guardado.'));
    }
}
