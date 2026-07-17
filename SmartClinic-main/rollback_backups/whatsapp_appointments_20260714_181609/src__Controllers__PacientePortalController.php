<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\ClinicaAvanzada as Clinica;
use Dao\Especialidad as DaoEspecialidad;
use Dao\Medicos as DaoMedicos;
use Utilities\Security;
use Utilities\Site;
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
            default:
                $this->index();
        }
    }

    private function getPaciente(): array
    {
        $paciente = Clinica::getPacienteByUsuario(intval(Security::getUserId()));
        if (!$paciente) {
            $paciente = ['id' => 1, 'nombres' => 'Paciente', 'apellidos' => 'Demo', 'telefono' => '', 'direccion' => ''];
        }
        return $paciente;
    }

    private function index(): void
    {
        $paciente = $this->getPaciente();
        $citas = DaoCitas::getCitasByPaciente(intval($paciente['id']));
        Renderer::render('paciente_portal', [
            'paciente' => $paciente,
            'paciente_nombres' => $paciente['nombres'] ?? 'Paciente',
            'paciente_apellidos' => $paciente['apellidos'] ?? 'Demo',
            'paciente_telefono' => $paciente['telefono'] ?? '',
            'paciente_direccion' => $paciente['direccion'] ?? '',
            'citas' => $citas,
            'historial' => Clinica::getHistorialPaciente(intval($paciente['id'])),
            'recetas' => Clinica::getRecetasPaciente(intval($paciente['id'])),
            'medicos' => DaoMedicos::getAllMedicos(),
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    private function agendar(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PacientePortalController&msg=Token CSRF inválido');
        }
        $paciente = $this->getPaciente();
        $medicoId = intval($_POST['medico_id'] ?? 0);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $hora = trim((string)($_POST['hora'] ?? ''));
        $fechaHora = $fecha . ' ' . $hora . ':00';
        if ($medicoId > 0 && $fecha !== '' && $hora !== '' && DaoCitas::checkDisponibilidad($medicoId, intval($paciente['id']), $fechaHora)) {
            $citaId = DaoCitas::insertCita(intval($paciente['id']), $medicoId, 1, $fechaHora);
            Clinica::crearNotificacion('Nueva cita web', 'Paciente agendó una cita desde el portal. Cita #' . $citaId);
            \Utilities\AuditLogger::log('CITA_WEB', 'Paciente', 'Paciente agendó cita web', ['cita_id' => $citaId]);
            Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('Cita solicitada. Puede simular el pago para confirmarla.'));
        }
        Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('No se pudo agendar. Revise disponibilidad y datos.'));
    }

    private function pagar(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PacientePortalController&msg=Token CSRF inválido');
        }
        $citaId = intval($_POST['cita_id'] ?? 0);
        $total = floatval($_POST['total'] ?? 750.00);
        if ($citaId > 0) {
            $transaccion = 'SIM-' . date('YmdHis') . '-' . random_int(100, 999);
            Clinica::crearPago($citaId, $total, 'Tarjeta demo', $transaccion);
            Clinica::actualizarEstadoCita($citaId, 2);
            Clinica::crearNotificacion('Pago confirmado', 'Pago simulado aprobado para la cita #' . $citaId . '. Recibo generado.');
            \Utilities\AuditLogger::log('PAGO_SIMULADO', 'Paciente', 'Pago simulado aprobado', ['cita_id' => $citaId, 'total' => $total]);
        }
        Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('Pago simulado aprobado y recibo generado.'));
    }
}
