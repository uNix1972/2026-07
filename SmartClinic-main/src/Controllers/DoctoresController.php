<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Utilities\AuditLogger;
use Utilities\ClinicalPdf;
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
                $this->postEstado(6, 'Paciente marcado en sala de espera.');
                break;
            case 'iniciarAtencion':
                $this->postEstado(7, 'Consulta iniciada.');
                break;
            case 'guardarHistorial':
                $this->guardarHistorial();
                break;
            case 'guardarSignos':
                $this->guardarSignos();
                break;
            case 'expediente':
                $this->expediente();
                break;
            case 'pdf':
                $this->pdf();
                break;
            case 'finalizar':
                $this->postEstado(3, 'Consulta finalizada.');
                break;
            default:
                $this->index();
        }
    }

    private function index(): void
    {
        Site::addLink('public/css/clinical-record.css');
        $medico = $this->getMedicoActual();
        if (!$medico) {
            http_response_code(403);
            exit('La cuenta no está vinculada con un médico.');
        }

        $medicoId = intval($medico['id']);
        Renderer::render('doctor_portal', [
            'medico' => $medico,
            'medico_nombres' => $medico['nombres'],
            'medico_apellidos' => $medico['apellidos'],
            'medico_especialidad' =>
                $medico['nombre_especialidad'] ?? 'Medicina General',
            'medico_id' => $medicoId,
            'agenda' => Clinica::getAgendaDoctor($medicoId),
            'sala' => Clinica::getSalaEspera($medicoId),
            'pacientes' => Clinica::getPacientesAtendidosDoctor($medicoId),
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    private function postEstado(int $estadoId, string $mensaje): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $this->requireCitaPropia($citaId);

        Clinica::actualizarEstadoCita($citaId, $estadoId);
        Clinica::crearNotificacion(
            'Estado de cita',
            $mensaje . ' Cita #' . $citaId
        );
        AuditLogger::log(
            'CITA_ESTADO',
            'Doctores',
            $mensaje . ' Cita #' . $citaId,
            ['cita_id' => $citaId, 'estado_id' => $estadoId]
        );
        $this->redirectWithMessage($mensaje);
    }

    private function guardarHistorial(): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $this->requireCitaPropia($citaId);

        $motivo = trim((string)($_POST['motivo_consulta'] ?? ''));
        $diagnostico = trim((string)($_POST['diagnostico'] ?? ''));
        $tratamiento = trim((string)($_POST['tratamiento'] ?? ''));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $medicamento = trim((string)($_POST['medicamento'] ?? ''));
        $indicaciones = trim((string)($_POST['indicaciones'] ?? ''));

        if ($motivo === '' || $diagnostico === '') {
            $this->redirectWithMessage(
                'El motivo de consulta y el diagnóstico son obligatorios.'
            );
        }

        $historialId = Clinica::guardarHistorial(
            $citaId,
            $motivo,
            $diagnostico,
            $tratamiento,
            $observaciones
        );
        if ($medicamento !== '' || $indicaciones !== '') {
            Clinica::guardarReceta(
                $historialId,
                $medicamento ?: 'Indicaciones generales',
                $indicaciones ?: 'Según criterio médico'
            );
        }
        Clinica::crearNotificacion(
            'Historial clínico',
            'Se registró historial clínico para la cita #' . $citaId
        );
        AuditLogger::log(
            'HISTORIAL_GUARDADO',
            'Doctores',
            'Historial médico guardado',
            ['cita_id' => $citaId]
        );
        $this->redirectWithMessage('Historial clínico guardado.');
    }

    private function guardarSignos(): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $this->requireCitaPropia($citaId);

        $ranges = [
            'temperatura' => [30, 45],
            'presion_sistolica' => [50, 260],
            'presion_diastolica' => [30, 180],
            'frecuencia_cardiaca' => [20, 250],
            'frecuencia_respiratoria' => [5, 80],
            'saturacion_oxigeno' => [50, 100],
            'peso' => [1, 500],
            'talla' => [30, 250],
        ];
        $datos = [];
        foreach ($ranges as $field => $range) {
            $raw = trim((string)($_POST[$field] ?? ''));
            $datos[$field] = $raw === '' ? null : floatval($raw);
            if (
                $datos[$field] !== null
                && (
                    $datos[$field] < $range[0]
                    || $datos[$field] > $range[1]
                )
            ) {
                $this->redirectWithMessage(
                    'Revise los rangos de los signos vitales.'
                );
            }
        }
        $datos['notas'] = substr(
            trim((string)($_POST['notas'] ?? '')),
            0,
            500
        );

        Clinica::guardarSignosVitales($citaId, $datos);
        AuditLogger::log(
            'SIGNOS_VITALES',
            'Doctores',
            'Signos vitales actualizados',
            ['cita_id' => $citaId]
        );
        $this->redirectWithMessage('Signos vitales guardados correctamente.');
    }

    private function expediente(): void
    {
        Site::addLink('public/css/clinical-record.css');
        $medico = $this->getMedicoActual();
        $pacienteId = intval($_GET['paciente_id'] ?? 0);
        $citas = $medico
            ? Clinica::getCitasExpedientePaciente(
                $pacienteId,
                intval($medico['id'])
            )
            : [];

        Renderer::render('expediente_clinico', [
            'citas' => $citas,
            'volver' => 'index.php?page=DoctoresController',
        ]);
    }

    private function pdf(): void
    {
        $cita = $this->requireCitaPropia(
            intval($_GET['cita_id'] ?? 0),
            false
        );
        $recetas = empty($cita['historial_id'])
            ? []
            : Clinica::getRecetasHistorial(intval($cita['historial_id']));
        ClinicalPdf::download(
            'expediente-cita-' . $cita['id'] . '.pdf',
            $cita,
            $recetas
        );
    }

    private function getMedicoActual(): array
    {
        return Clinica::getMedicoByUsuario(
            intval(Security::getUserId())
        ) ?: [];
    }

    private function requireCitaPropia(
        int $citaId,
        bool $redirect = true
    ): array {
        $cita = Clinica::getCitaExpediente($citaId);
        $medico = $this->getMedicoActual();
        if (
            !$cita
            || !$medico
            || intval($cita['medico_id']) !== intval($medico['id'])
        ) {
            if ($redirect) {
                $this->redirectWithMessage(
                    'La cita no pertenece al médico autenticado.'
                );
            }
            http_response_code(403);
            exit('Acceso denegado.');
        }
        return $cita;
    }

    private function validateCsrf(): void
    {
        if (!Security::validateCsrfPost()) {
            $this->redirectWithMessage('Solicitud inválida o expirada.');
        }
    }

    private function redirectWithMessage(string $message): void
    {
        Site::redirectTo(
            'index.php?page=DoctoresController&msg=' . urlencode($message)
        );
    }

}
