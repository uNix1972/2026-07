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
            case 'pdfTodo':
                $this->pdfTodo();
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
        Site::addEndScript('public/js/kardex-autocomplete.js');
        Clinica::autoCancelarPendientesVencidas();
        $paciente = $this->getPaciente();

        // El paciente puede haberse atendido en más de un centro; se
        // arma la lista de centros a partir de sus propias citas (no de
        // todos los centros del sistema) para que el filtro solo muestre
        // opciones que de verdad le aplican.
        // Mismo orden de prioridad que la Agenda del portal del doctor:
        // primero lo que requiere atención inmediata (En Atención, luego
        // En Espera), después lo confirmado y lo pendiente de confirmar,
        // y al final lo que ya no requiere acción. Dentro de cada grupo
        // se mantiene el orden cronológico.
        $citasTodas = $this->ordenarCitasPorPrioridad(
            DaoCitas::getCitasByPaciente(intval($paciente['id']))
        );
        $centrosPaciente = $this->extraerCentrosUnicos($citasTodas);
        $centroFiltro = $this->sanitizeCentroFiltroPaciente(
            (string)($_GET['centro_filtro'] ?? 'todos'),
            $centrosPaciente
        );
        $citas = $this->filtrarCitasPorCentro($citasTodas, $centroFiltro);
        // "Pagar demo" solo tiene sentido mientras la cita sigue Pendiente
        // (estado_id 1); una vez pagada pasa a Confirmada y el botón debe
        // desaparecer, si no el paciente puede pensar que el pago nunca
        // se registró aunque sí haya funcionado.
        foreach ($citas as &$citaItem) {
            $citaItem['puedePagar'] = intval($citaItem['estado_id'] ?? 0) === 1;
        }
        unset($citaItem);

        $centrosFiltro = [[
            'id' => 'todos',
            'nombre' => 'Todos los centros',
            'activo' => $centroFiltro === 'todos',
            'url' => $this->buildPacienteUrl(['centro_filtro' => 'todos']),
        ]];
        foreach ($centrosPaciente as $centro) {
            $centroId = (string)$centro['centro_salud_id'];
            $centrosFiltro[] = [
                'id' => $centroId,
                'nombre' => $centro['centro_nombre'],
                'activo' => $centroFiltro === $centroId,
                'url' => $this->buildPacienteUrl(['centro_filtro' => $centroId]),
            ];
        }

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
            'centrosFiltro' => $centrosFiltro,
            'mostrarFiltroCentros' => count($centrosPaciente) > 1,
            'expedientes' => Clinica::getCitasExpedientePaciente(
                intval($paciente['id']),
                null,
                $fechaDesde,
                $fechaHasta
            ),
            'fecha_desde' => $fechaDesde ?? '',
            'fecha_hasta' => $fechaHasta ?? '',
            'medicosJsonAttr' => $this->jsonAttrParaAutocompletar(
                $this->mapearMedicosParaBuscador(DaoMedicos::getAllMedicos())
            ),
            'csrf_token' => Security::getCsrfToken(),
            'minDate' => date('Y-m-d'),
            'maxDate' => (new \DateTime())->add(new \DateInterval('P3M'))->format('Y-m-d'),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    /**
     * Convierte médicos al formato {id, nombre} que necesita la barra de
     * búsqueda con autocompletar (kardex-autocomplete.js), el mismo
     * componente que ya se usa en Inventario/Kárdex y en el módulo de
     * Citas del admin.
     */
    private function mapearMedicosParaBuscador(array $medicos): array
    {
        return array_map(function ($m) {
            return [
                'id' => (int) $m['id'],
                'nombre' => 'Dr/a ' . trim($m['nombres'] . ' ' . $m['apellidos'])
                    . ' - ' . $m['nombre_especialidad'],
            ];
        }, $medicos);
    }

    /**
     * Convierte una lista ya mapeada a un texto JSON listo para meterse
     * como atributo HTML (data-options), escapado porque el motor de
     * plantillas de este proyecto no escapa lo que imprime automáticamente.
     */
    private function jsonAttrParaAutocompletar(array $opciones): string
    {
        return htmlspecialchars(json_encode($opciones, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Mismo criterio que DoctoresController::ordenarAgendaPorPrioridad():
     * En Atención y En Espera primero (lo que requiere acción ahora),
     * después Confirmada/Pendiente, y al final lo que ya no requiere
     * acción (Completada, No Asistió, Cancelada). Dentro de cada grupo
     * se mantiene el orden cronológico.
     */
    private function ordenarCitasPorPrioridad(array $citas): array
    {
        $prioridadPorEstado = [
            7 => 0, // En Atención
            6 => 1, // En Espera
            2 => 2, // Confirmada
            1 => 3, // Pendiente
            3 => 4, // Completada
            5 => 5, // No Asistió
            4 => 6, // Cancelada
        ];
        usort($citas, static function (array $a, array $b) use ($prioridadPorEstado): int {
            $prioridadA = $prioridadPorEstado[intval($a['estado_id'] ?? 0)] ?? 99;
            $prioridadB = $prioridadPorEstado[intval($b['estado_id'] ?? 0)] ?? 99;
            if ($prioridadA !== $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            return strcmp((string)($a['fecha_hora'] ?? ''), (string)($b['fecha_hora'] ?? ''));
        });
        return $citas;
    }

    /**
     * Lista de centros distintos donde el paciente tiene citas, en el
     * orden en que aparecen (sin repetir), para armar el filtro.
     */
    private function extraerCentrosUnicos(array $citas): array
    {
        $vistos = [];
        $centros = [];
        foreach ($citas as $cita) {
            $id = intval($cita['centro_salud_id'] ?? 0);
            if ($id <= 0 || isset($vistos[$id])) {
                continue;
            }
            $vistos[$id] = true;
            $centros[] = [
                'centro_salud_id' => $id,
                'centro_nombre' => (string)($cita['centro_nombre'] ?? 'Centro'),
            ];
        }
        return $centros;
    }

    /**
     * "todos" o el id de un centro donde el paciente realmente tiene
     * citas; cualquier otra cosa cae de vuelta a "todos".
     */
    private function sanitizeCentroFiltroPaciente(string $filtro, array $centros): string
    {
        if ($filtro === 'todos') {
            return 'todos';
        }
        foreach ($centros as $centro) {
            if ((string)$centro['centro_salud_id'] === $filtro) {
                return $filtro;
            }
        }
        return 'todos';
    }

    private function filtrarCitasPorCentro(array $citas, string $centroFiltro): array
    {
        if ($centroFiltro === 'todos') {
            return $citas;
        }
        return array_values(array_filter(
            $citas,
            static function (array $item) use ($centroFiltro): bool {
                return (string)intval($item['centro_salud_id'] ?? 0) === $centroFiltro;
            }
        ));
    }

    /**
     * Reusa los filtros actuales de la URL y solo pisa los que vengan en
     * $overrides, para que este filtro y el de fechas de "Mi expediente"
     * puedan combinarse sin que uno borre al otro.
     */
    private function buildPacienteUrl(array $overrides): string
    {
        $params = array_merge($_GET, ['page' => 'PacientePortalController'], $overrides);
        unset($params['msg']);
        return 'index.php?' . http_build_query($params);
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
            strval($ubicacion['consultorio'] ?? ''),
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
            && intval($cita['estado_id']) === 1
        ) {
            $transaccion = 'SIM-' . date('YmdHis') . '-' . random_int(100, 999);
            Clinica::crearPago($citaId, $total, 'Tarjeta demo', $transaccion);
            Clinica::actualizarEstadoCita($citaId, 2);
            Clinica::crearNotificacion('Pago confirmado', 'Pago simulado aprobado para la cita #' . $citaId . '. Recibo generado.');
            \Utilities\AuditLogger::log('PAGO_SIMULADO', 'Paciente', 'Pago simulado aprobado', ['cita_id' => $citaId, 'total' => $total]);
            Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('Pago simulado aprobado y recibo generado.'));
            exit;
        }
        Site::redirectTo('index.php?page=PacientePortalController&msg=' . urlencode('Esa cita ya no está pendiente de pago.'));
        exit;
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

    /**
     * "Descargar todo": un solo PDF con una página por cada cita que
     * cumpla el mismo rango de fechas que el paciente tenga filtrado en
     * "Mi expediente por cita" (mismos $_GET['fecha_desde']/['fecha_hasta']
     * que usa index()), en vez de tener que bajar cita por cita.
     */
    private function pdfTodo(): void
    {
        $paciente = $this->getPaciente();
        $fechaDesde = Validators::sanitizeDate(
            (string)($_GET['fecha_desde'] ?? '')
        );
        $fechaHasta = Validators::sanitizeDate(
            (string)($_GET['fecha_hasta'] ?? '')
        );
        if ($fechaDesde && $fechaHasta && $fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $citas = Clinica::getCitasExpedientePaciente(
            intval($paciente['id']),
            null,
            $fechaDesde,
            $fechaHasta
        );
        if (!$citas) {
            $params = ['msg' => 'No hay citas en ese rango de fechas para descargar.'];
            if ($fechaDesde) {
                $params['fecha_desde'] = $fechaDesde;
            }
            if ($fechaHasta) {
                $params['fecha_hasta'] = $fechaHasta;
            }
            Site::redirectTo(
                'index.php?page=PacientePortalController&' . http_build_query($params)
            );
        }

        // getCitasExpedientePaciente() no trae los datos del paciente (ya
        // viene filtrado a un solo paciente), así que se completan aquí
        // con los que ya tenemos, para que cada página del PDF muestre
        // nombre e identidad igual que el PDF de una sola cita.
        $items = [];
        foreach ($citas as $cita) {
            $cita['paciente_nombres'] = $paciente['nombres'] ?? '';
            $cita['paciente_apellidos'] = $paciente['apellidos'] ?? '';
            $cita['identidad'] = $paciente['identidad'] ?? '';
            $recetas = empty($cita['historial_id'])
                ? []
                : Clinica::getRecetasHistorial(intval($cita['historial_id']));
            $items[] = ['cita' => $cita, 'recetas' => $recetas];
        }

        ClinicalPdf::downloadMultiple(
            'expedientes-paciente-' . intval($paciente['id']) . '.pdf',
            $items
        );
    }
}
