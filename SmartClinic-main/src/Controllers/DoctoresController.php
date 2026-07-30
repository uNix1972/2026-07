<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Dao\MedicoCentroSalud as DaoMedicoCentroSalud;
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
                $this->transicionar(6, 'Paciente marcado en sala de espera.');
                break;
            case 'iniciarAtencion':
                $this->transicionar(7, 'Consulta iniciada.');
                break;
            case 'noAsistio':
                $this->transicionar(5, 'Paciente marcado como no asistió.');
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
                $this->finalizar();
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

        Clinica::autoCancelarPendientesVencidas();

        $medicoId = intval($medico['id']);
        $hoy = date('Y-m-d');
        $agendaCompleta = Clinica::getAgendaDoctor($medicoId);
        foreach ($agendaCompleta as &$item) {
            $estadoId = intval($item['estado_id']);
            $tieneSignos = $item['temperatura'] !== null;
            $esHoy = substr((string)($item['fecha_hora'] ?? ''), 0, 10) === $hoy;
            $item['puedeConfirmarLlegada'] = $estadoId === 2;
            $item['puedeIniciarAtencion'] = in_array($estadoId, [2, 6], true) && $tieneSignos;
            $item['faltaPreclinica'] = in_array($estadoId, [2, 6], true) && !$tieneSignos;
            $item['puedeFinalizar'] = $estadoId === 7;
            $item['puedeNoAsistio'] = in_array($estadoId, [2, 6], true);
            // El link a Preclínica en la tabla de agenda solo debe verse
            // cuando de verdad se puede tomar/editar ahí (cita de hoy,
            // todavía activa). Para el resto de filas (pasadas, futuras,
            // completadas, canceladas) no lleva a ningún lado útil.
            $item['puedeAbrirPreclinica'] = $esHoy && in_array($estadoId, [2, 6, 7], true);
        }
        unset($item);

        // La tabla "Agenda del doctor" se puede filtrar por Día/Semana/Mes
        // y por centro de salud (un médico puede atender en varios centros),
        // pero el combo de "Registrar historial" (más abajo) siempre debe
        // poder ver cualquier cita "En Atención" sin importar esos filtros,
        // por eso se guarda $agendaCompleta aparte.
        $centrosMedico = DaoMedicoCentroSalud::getActivosByMedico($medicoId);
        $agendaFiltro = $this->sanitizeAgendaFiltro($_GET['agenda_filtro'] ?? 'todos');
        $centroFiltro = $this->sanitizeCentroFiltro(
            (string)($_GET['centro_filtro'] ?? 'todos'),
            $centrosMedico
        );
        $agenda = $this->filtrarAgendaPorPeriodo($agendaCompleta, $agendaFiltro);
        $agenda = $this->filtrarAgendaPorCentro($agenda, $centroFiltro);

        // La toma de signos vitales vive únicamente en Preclínica (Paso 2).
        // Aquí solo se muestra si a la cita ya se le tomó la preclínica o
        // todavía le falta, para guiar al médico al lugar correcto.
        $sala = Clinica::getSalaEspera($medicoId, date('Y-m-d'));
        foreach ($sala as &$item) {
            $estadoId = intval($item['estado_id']);
            $tieneSignos = $item['temperatura'] !== null;
            $item['puedeIniciarAtencion'] = $estadoId === 6 && $tieneSignos;
            $item['faltaPreclinica'] = $estadoId === 6 && !$tieneSignos;
            $item['yaEnAtencion'] = $estadoId === 7;
        }
        unset($item);

        $pacientes = $this->buscarYPaginarPacientes($medicoId);

        // Cada pill (Día/Semana/Mes/Todos y cada centro) arma su URL a
        // partir de los filtros ACTUALES en $_GET, cambiando solo el suyo,
        // para que ambos filtros se puedan combinar sin pisarse.
        $centrosFiltro = [[
            'id' => 'todos',
            'nombre' => 'Todos los centros',
            'activo' => $centroFiltro === 'todos',
            'url' => $this->buildAgendaUrl(['centro_filtro' => 'todos']),
        ]];
        foreach ($centrosMedico as $centro) {
            $centroId = (string)intval($centro['centro_salud_id']);
            $centrosFiltro[] = [
                'id' => $centroId,
                'nombre' => (string)($centro['centro_nombre'] ?? 'Centro'),
                'activo' => $centroFiltro === $centroId,
                'url' => $this->buildAgendaUrl(['centro_filtro' => $centroId]),
            ];
        }

        Renderer::render('doctor_portal', [
            'medico' => $medico,
            'medico_nombres' => $medico['nombres'],
            'medico_apellidos' => $medico['apellidos'],
            'medico_especialidad' =>
                $medico['nombre_especialidad'] ?? 'Medicina General',
            'medico_id' => $medicoId,
            'agenda' => $agenda,
            'agendaTodas' => $agendaCompleta,
            'agendaFiltro' => $agendaFiltro,
            'agendaFiltroDia' => $agendaFiltro === 'dia',
            'agendaFiltroSemana' => $agendaFiltro === 'semana',
            'agendaFiltroMes' => $agendaFiltro === 'mes',
            'agendaFiltroTodos' => $agendaFiltro === 'todos',
            'urlFiltroDia' => $this->buildAgendaUrl(['agenda_filtro' => 'dia']),
            'urlFiltroSemana' => $this->buildAgendaUrl(['agenda_filtro' => 'semana']),
            'urlFiltroMes' => $this->buildAgendaUrl(['agenda_filtro' => 'mes']),
            'urlFiltroTodos' => $this->buildAgendaUrl(['agenda_filtro' => 'todos']),
            'centrosFiltro' => $centrosFiltro,
            'mostrarFiltroCentros' => count($centrosMedico) > 1,
            'sala' => $sala,
            'pacientes' => $pacientes['items'],
            'pacientesQuery' => $pacientes['query'],
            'pacientesTieneQuery' => $pacientes['query'] !== '',
            'paginaPacientes' => $pacientes['paginaActual'],
            'totalPaginasPacientes' => $pacientes['totalPaginas'],
            'urlPaginaAnteriorPacientes' => $pacientes['urlAnterior'],
            'urlPaginaSiguientePacientes' => $pacientes['urlSiguiente'],
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
        ]);
    }

    /**
     * "Mis pacientes atendidos" puede crecer mucho con el tiempo, así que
     * se filtra por nombre/apellido/identidad y se pagina de 10 en 10 en
     * vez de listar todo de una vez.
     */
    private function buscarYPaginarPacientes(int $medicoId): array
    {
        $porPagina = 10;
        $query = trim((string)($_GET['pacientes_q'] ?? ''));

        $pacientes = Clinica::getPacientesAtendidosDoctor($medicoId);
        if ($query !== '') {
            $queryLower = mb_strtolower($query);
            $pacientes = array_values(array_filter(
                $pacientes,
                static function (array $p) use ($queryLower): bool {
                    $texto = mb_strtolower(
                        ($p['nombres'] ?? '') . ' '
                        . ($p['apellidos'] ?? '') . ' '
                        . ($p['identidad'] ?? '')
                    );
                    return mb_strpos($texto, $queryLower) !== false;
                }
            ));
        }

        $total = count($pacientes);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $paginaActual = Validators::sanitizeInt(
            $_GET['pacientes_pagina'] ?? 1,
            1,
            $totalPaginas
        ) ?? 1;
        $offset = ($paginaActual - 1) * $porPagina;

        $queryString = $query !== '' ? '&pacientes_q=' . rawurlencode($query) : '';
        $baseUrl = 'index.php?page=DoctoresController' . $queryString
            . '&pacientes_pagina=';

        return [
            'items' => array_slice($pacientes, $offset, $porPagina),
            'query' => $query,
            'paginaActual' => $paginaActual,
            'totalPaginas' => $totalPaginas,
            'urlAnterior' => $paginaActual > 1
                ? $baseUrl . ($paginaActual - 1)
                : '',
            'urlSiguiente' => $paginaActual < $totalPaginas
                ? $baseUrl . ($paginaActual + 1)
                : '',
        ];
    }

    /**
     * Solo acepta los valores conocidos del filtro; cualquier otra cosa
     * (o nada) cae de vuelta a "todos" para no romper la vista.
     */
    private function sanitizeAgendaFiltro(string $filtro): string
    {
        return in_array($filtro, ['dia', 'semana', 'mes', 'todos'], true)
            ? $filtro
            : 'todos';
    }

    /**
     * "todos" o el id de uno de los centros donde el médico realmente
     * atiende; cualquier otra cosa cae de vuelta a "todos".
     */
    private function sanitizeCentroFiltro(string $filtro, array $centrosMedico): string
    {
        if ($filtro === 'todos') {
            return 'todos';
        }
        foreach ($centrosMedico as $centro) {
            if ((string)intval($centro['centro_salud_id']) === $filtro) {
                return $filtro;
            }
        }
        return 'todos';
    }

    /**
     * Arma la URL de la Agenda del doctor reusando los filtros actuales
     * de la URL y solo pisando los que se indiquen en $overrides, para
     * que el filtro de período y el de centro se puedan combinar sin
     * que uno borre al otro al hacer clic.
     */
    private function buildAgendaUrl(array $overrides): string
    {
        $params = array_merge($_GET, ['page' => 'DoctoresController'], $overrides);
        unset($params['msg']);
        return 'index.php?' . http_build_query($params);
    }

    /**
     * Filtra la agenda por centro de salud (un médico puede atender en
     * varios centros y a veces solo quiere ver la agenda de uno).
     */
    private function filtrarAgendaPorCentro(array $agenda, string $centroFiltro): array
    {
        if ($centroFiltro === 'todos') {
            return $agenda;
        }
        return array_values(array_filter(
            $agenda,
            static function (array $item) use ($centroFiltro): bool {
                return (string)intval($item['centro_salud_id'] ?? 0) === $centroFiltro;
            }
        ));
    }

    /**
     * Filtra la agenda por el período pedido, calculando los rangos con
     * PHP (no con SQL) para evitar el mismo desfase de zona horaria que
     * ya tuvimos con "Sala de espera".
     */
    private function filtrarAgendaPorPeriodo(array $agenda, string $filtro): array
    {
        if ($filtro === 'todos') {
            return $agenda;
        }

        $hoy = new \DateTime('today');

        if ($filtro === 'dia') {
            $desde = $hoy->format('Y-m-d');
            $hasta = $desde;
        } elseif ($filtro === 'semana') {
            $diaSemana = intval($hoy->format('N'));
            $desde = (clone $hoy)
                ->modify('-' . ($diaSemana - 1) . ' days')
                ->format('Y-m-d');
            $hasta = (clone $hoy)
                ->modify('+' . (7 - $diaSemana) . ' days')
                ->format('Y-m-d');
        } else {
            $desde = $hoy->format('Y-m-01');
            $hasta = (clone $hoy)
                ->modify('last day of this month')
                ->format('Y-m-d');
        }

        return array_values(array_filter(
            $agenda,
            static function (array $item) use ($desde, $hasta): bool {
                $fecha = substr((string)($item['fecha_hora'] ?? ''), 0, 10);
                return $fecha >= $desde && $fecha <= $hasta;
            }
        ));
    }

    /**
     * Cambia el estado de una cita validando que la transición tenga
     * sentido clínico (no se puede "iniciar atención" de una cita que
     * nunca llegó, ni finalizar algo que no se ha atendido, etc.).
     */
    private function transicionar(int $estadoId, string $mensaje): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $cita = $this->requireCitaPropia($citaId);
        $estadoActual = intval($cita['estado_id']);

        if (!Clinica::puedeTransicionarDoctor($estadoActual, $estadoId)) {
            $this->redirectWithMessage(
                'No se puede pasar de "' . Clinica::nombreEstado($estadoActual)
                . '" a "' . Clinica::nombreEstado($estadoId) . '".'
            );
        }
        if ($estadoId === 7 && $cita['temperatura'] === null) {
            $this->redirectWithMessage(
                'Debe completar la preclínica (signos vitales) antes de '
                . 'iniciar la atención.',
                'preclinica',
                $citaId
            );
        }
        if ($estadoId === 7) {
            $medicoId = intval($this->getMedicoActual()['id'] ?? 0);
            $enAtencion = Clinica::getCitaEnAtencion($medicoId, $citaId);
            if ($enAtencion) {
                $this->redirectWithMessage(
                    'Ya tiene una consulta en curso con '
                    . trim(
                        $enAtencion['paciente_nombres'] . ' '
                        . $enAtencion['paciente_apellidos']
                    )
                    . ' (cita #' . $enAtencion['id'] . '). Finalícela antes '
                    . 'de iniciar otra.'
                );
            }
        }

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

    /**
     * Finaliza la consulta. Exige que ya esté "En Atención" y que exista
     * un historial clínico guardado: no tiene sentido cerrar una consulta
     * sin ningún dato clínico registrado.
     */
    private function finalizar(): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $cita = $this->requireCitaPropia($citaId);
        $estadoActual = intval($cita['estado_id']);

        if (!Clinica::puedeTransicionarDoctor($estadoActual, 3)) {
            $this->redirectWithMessage(
                'Solo se puede finalizar una consulta que está "En Atención".'
            );
        }
        if (empty($cita['historial_id'])) {
            $this->redirectWithMessage(
                'Debe guardar el historial clínico antes de finalizar la consulta.'
            );
        }

        Clinica::actualizarEstadoCita($citaId, 3);
        Clinica::crearNotificacion(
            'Estado de cita',
            'Consulta finalizada. Cita #' . $citaId
        );
        AuditLogger::log(
            'CITA_ESTADO',
            'Doctores',
            'Consulta finalizada. Cita #' . $citaId,
            ['cita_id' => $citaId, 'estado_id' => 3]
        );
        $this->redirectWithMessage('Consulta finalizada.');
    }

    private function guardarHistorial(): void
    {
        $this->validateCsrf();
        $citaId = intval($_POST['cita_id'] ?? 0);
        $cita = $this->requireCitaPropia($citaId);

        if (intval($cita['estado_id']) !== 7) {
            $this->redirectWithMessage(
                'La cita debe estar "En Atención" para registrar el historial. '
                . 'Inicie la atención primero.'
            );
        }

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
        $cita = $this->requireCitaPropia($citaId);
        $returnTo = ($_POST['return_to'] ?? '') === 'preclinica'
            ? 'preclinica'
            : '';

        if (!in_array(intval($cita['estado_id']), [2, 6, 7], true)) {
            $this->redirectWithMessage(
                'Solo se pueden registrar signos vitales de citas confirmadas, '
                . 'en espera o en atención.',
                $returnTo,
                $citaId
            );
        }

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

        Clinica::autoCancelarPendientesVencidas();

        // Preclínica es para pacientes de hoy que ya confirmaron/llegaron:
        // no tiene sentido tomar signos vitales de una cita futura, de una
        // que todavía no se paga, ni de una que ya terminó.
        $hoy = date('Y-m-d');
        $agendaCompleta = Clinica::getAgendaDoctor(intval($medico['id']));
        $agenda = array_values(array_filter(
            $agendaCompleta,
            static function (array $item) use ($hoy): bool {
                $esHoy = substr((string)($item['fecha_hora'] ?? ''), 0, 10) === $hoy;
                return $esHoy && in_array(intval($item['estado_id']), [2, 6, 7], true);
            }
        ));
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

    /**
     * Site::redirectTo() solo pone el header "Location"; no detiene la
     * ejecución por sí sola. Antes esto causaba que, tras "rechazar" una
     * acción con un mensaje de error, el código siguiera corriendo y la
     * ejecutara de todas formas (los guardas de validación no protegían
     * nada en la práctica). Por eso este helper termina la petición.
     */
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
        exit;
    }

}
