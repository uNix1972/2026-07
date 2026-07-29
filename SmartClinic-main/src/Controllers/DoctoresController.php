<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Utilities\AuditLogger;
use Utilities\ClinicalPdf;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
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
            case 'preclinica':
                $this->preclinica();
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
        $returnTo = ($_POST['return_to'] ?? '') === 'preclinica'
            ? 'preclinica'
            : '';

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
                    'Revise los rangos de los signos vitales.',
                    $returnTo,
                    $citaId
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
        $this->redirectWithMessage(
            'Signos vitales guardados correctamente.',
            $returnTo,
            $citaId
        );
    }

    private function preclinica(): void
    {
        Site::addLink('public/css/clinical-record.css');
        $medico = $this->getMedicoActual();
        if (!$medico) {
            http_response_code(403);
            exit('La cuenta no está vinculada con un médico.');
        }

        $agenda = Clinica::getAgendaDoctor(intval($medico['id']));
        $citaId = intval($_GET['cita_id'] ?? ($agenda[0]['id'] ?? 0));
        $cita = $citaId > 0 ? $this->requireCitaPropia($citaId, false) : null;

        foreach ($agenda as &$item) {
            $item['selected'] = intval($item['id']) === $citaId
                ? 'selected'
                : '';
            $item['signos_estado'] = $item['temperatura'] !== null
                ? 'Registrados'
                : 'Pendientes';
        }
        unset($item);

        Renderer::render('preclinica', [
            'medico_nombres' => $medico['nombres'] ?? '',
            'medico_apellidos' => $medico['apellidos'] ?? '',
            'agenda' => $agenda,
            'hay_cita' => (bool)$cita,
            'cita_id' => $cita['id'] ?? '',
            'fecha_hora' => $cita['fecha_hora'] ?? '',
            'paciente_nombres' => $cita['paciente_nombres'] ?? '',
            'paciente_apellidos' => $cita['paciente_apellidos'] ?? '',
            'nombre_estado' => $cita['nombre_estado'] ?? '',
            'temperatura' => $cita['temperatura'] ?? '',
            'presion_sistolica' => $cita['presion_sistolica'] ?? '',
            'presion_diastolica' => $cita['presion_diastolica'] ?? '',
            'frecuencia_cardiaca' => $cita['frecuencia_cardiaca'] ?? '',
            'frecuencia_respiratoria' => $cita['frecuencia_respiratoria'] ?? '',
            'saturacion_oxigeno' => $cita['saturacion_oxigeno'] ?? '',
            'peso' => $cita['peso'] ?? '',
            'talla' => $cita['talla'] ?? '',
            'signos_notas' => $cita['signos_notas'] ?? '',
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    private function expediente(): void
    {
        Site::addLink('public/css/clinical-record.css');
        $medico = $this->getMedicoActual();
        $pacienteId = intval($_GET['paciente_id'] ?? 0);
        [$fechaDesde, $fechaHasta] = $this->getDateRange();
        $citas = $medico
            ? Clinica::getCitasExpedientePaciente(
                $pacienteId,
                intval($medico['id']),
                $fechaDesde,
                $fechaHasta
            )
            : [];

        Renderer::render('expediente_clinico', [
            'citas' => $citas,
            'volver' => 'index.php?page=DoctoresController',
            'paciente_id' => $pacienteId,
            'fecha_desde' => $fechaDesde ?? '',
            'fecha_hasta' => $fechaHasta ?? '',
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

    private function getDateRange(): array
    {
        $desde = Validators::sanitizeDate((string)($_GET['fecha_desde'] ?? ''));
        $hasta = Validators::sanitizeDate((string)($_GET['fecha_hasta'] ?? ''));
        if ($desde && $hasta && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return [$desde, $hasta];
    }

    private function redirectWithMessage(
        string $message,
        string $action = '',
        int $citaId = 0
    ): void
    {
        $url = 'index.php?page=DoctoresController';
        if ($action !== '') {
            $url .= '&action=' . rawurlencode($action);
        }
        if ($citaId > 0) {
            $url .= '&cita_id=' . $citaId;
        }
        Site::redirectTo($url . '&msg=' . urlencode($message));
    }

}
