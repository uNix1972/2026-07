<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Dao\FacturaVenta as DaoFacturaVenta;
use Dao\InventarioCentro as DaoInventarioCentro;
use Dao\MedicoCentroSalud as DaoMedicoCentroSalud;
use Dao\Producto as DaoProducto;
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
        // "confirmarLlegada" (poner en espera) y "preclinica"/"guardarSignos"
        // (signos vitales) ya no viven en el portal del doctor: ahora los
        // maneja la enfermera/recepción (poner en espera se hace desde el
        // módulo de Citas; la preclínica pasará al futuro portal de
        // enfermería). El médico solo inicia la consulta, registra el
        // historial y finaliza.
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'iniciarAtencion':
                $this->transicionar(7, 'Consulta iniciada.');
                break;
            case 'noAsistio':
                $this->transicionar(5, 'Paciente marcado como no asistió.');
                break;
            case 'guardarHistorial':
                $this->guardarHistorial();
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
        Site::addLink('public/css/clinical-record.css?v=20260730-1');
        Site::addEndScript('public/js/kardex-autocomplete.js');
        $medico = $this->getMedicoActual();
        if (!$medico) {
            http_response_code(403);
            exit('La cuenta no está vinculada con un médico.');
        }

        Clinica::autoCancelarPendientesVencidas();

        $medicoId = intval($medico['id']);
        $hoy = date('Y-m-d');
        // El médico necesita ver primero lo que requiere su atención ahora
        // mismo (En Atención, luego En Espera), después lo confirmado y lo
        // pendiente de confirmar, y al final lo que ya no requiere acción
        // (completadas, no asistió, canceladas). Dentro de cada grupo se
        // mantiene el orden cronológico, así que si un día solo tiene una
        // cita, esa sigue siendo la única/primera de ese día.
        $agendaCompleta = $this->ordenarAgendaPorPrioridad(Clinica::getAgendaDoctor($medicoId));
        foreach ($agendaCompleta as &$item) {
            $estadoId = intval($item['estado_id']);
            $item['puedeFinalizar'] = $estadoId === 7;
            // "No asistió" solo tiene sentido mientras la cita sigue
            // Confirmada (el paciente todavía no ha llegado). En cuanto se
            // marca "En espera" es porque ya llegó y está físicamente en
            // el centro, así que decir que "no asistió" ya no es correcto.
            $item['puedeNoAsistio'] = $estadoId === 2;
            // El PDF es el resumen de la consulta (diagnóstico, tratamiento,
            // receta); no existe nada que mostrar hasta que la cita esté
            // Completada, así que antes de eso no tiene sentido ofrecerlo.
            $item['puedeVerPdf'] = $estadoId === 3;
        }
        unset($item);

        // La tabla "Agenda del doctor" se puede filtrar por Día/Semana/Mes
        // y por centro de salud (un médico puede atender en varios centros),
        // pero el combo de "Registrar historial" (más abajo) siempre debe
        // poder ver cualquier cita "En Atención" sin importar esos filtros,
        // por eso se guarda $agendaCompleta aparte.
        $centrosMedico = DaoMedicoCentroSalud::getActivosByMedico($medicoId);
        $agendaFiltro = $this->sanitizeAgendaFiltro($_GET['agenda_filtro'] ?? 'dia');
        $centroFiltro = $this->sanitizeCentroFiltro(
            (string)($_GET['centro_filtro'] ?? 'todos'),
            $centrosMedico
        );
        $agenda = $this->filtrarAgendaPorPeriodo($agendaCompleta, $agendaFiltro);
        $agenda = $this->filtrarAgendaPorCentro($agenda, $centroFiltro);

        // Poner "En Espera" y tomar signos vitales ya no son tareas del
        // médico (los hace la enfermera/recepción), así que "Iniciar
        // consulta" ya no depende de que existan signos vitales guardados.
        $sala = Clinica::getSalaEspera($medicoId, date('Y-m-d'));
        foreach ($sala as &$item) {
            $estadoId = intval($item['estado_id']);
            $item['puedeIniciarAtencion'] = $estadoId === 6;
            // Una vez en atención, la Sala de espera también debe poder
            // finalizar la consulta directamente, sin obligar al doctor a
            // subir hasta la tabla de Agenda para hacerlo.
            $item['puedeFinalizar'] = $estadoId === 7;
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
        // Texto del botón del dropdown: el nombre del centro activo, o
        // "Todos los centros" si no hay ninguno filtrado en particular.
        $centroFiltroLabel = 'Todos los centros';
        foreach ($centrosFiltro as $centro) {
            if ($centro['activo']) {
                $centroFiltroLabel = $centro['nombre'];
                break;
            }
        }

        // Catálogo para el buscador de producto de "Registrar historial y
        // receta". El precio viaja solo para mostrar una vista previa; al
        // guardar, el servidor vuelve a leerlo del catálogo y no confía en
        // el valor enviado por el navegador.
        $stockPorProductoCentro = DaoInventarioCentro::getStockMap();
        $productosParaReceta = array_map(static function (array $p) use ($stockPorProductoCentro): array {
            $productoId = (int) $p['id'];
            return [
                'id' => $productoId,
                'nombre' => (string) $p['nombre'],
                'precio_unitario' => (float) $p['precio_unitario'],
                'unidad_medida' => (string) $p['unidad_medida'],
                'stock_por_centro' =>
                    $stockPorProductoCentro[$productoId] ?? [],
            ];
        }, DaoProducto::getActivos());
        $productosRecetaJsonAttr = htmlspecialchars(
            json_encode($productosParaReceta, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
        $feedbackMessage = trim((string)($_GET['msg'] ?? ''));
        $feedbackIsError = ($_GET['msg_type'] ?? '') === 'error';

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
            'centroFiltroLabel' => $centroFiltroLabel,
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
            'productosRecetaJsonAttr' => $productosRecetaJsonAttr,
            'msg' => htmlspecialchars(
                $feedbackMessage,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
            'msgSuccess' => $feedbackMessage !== '' && !$feedbackIsError,
            'msgError' => $feedbackMessage !== '' && $feedbackIsError,
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
     * (o nada, p. ej. al volver de guardar un historial o al entrar de
     * nuevo al portal) cae de vuelta a "dia", para que el doctor siempre
     * empiece viendo la agenda de hoy en vez de la lista completa.
     */
    private function sanitizeAgendaFiltro(string $filtro): string
    {
        return in_array($filtro, ['dia', 'semana', 'mes', 'todos'], true)
            ? $filtro
            : 'dia';
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
     * El médico necesita ver primero lo que requiere su atención ahora
     * mismo (En Atención, luego En Espera), después lo confirmado y lo
     * pendiente de confirmar, y al final lo que ya no requiere acción
     * (completadas, no asistió, canceladas). Dentro de cada grupo se
     * mantiene el orden cronológico. Se aplica siempre, sin importar el
     * período (Día/Semana/Mes/Todos) que esté activo, así que no hace
     * falta ningún clic ni filtro aparte para verla ordenada así.
     */
    private function ordenarAgendaPorPrioridad(array $agenda): array
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
        usort($agenda, static function (array $a, array $b) use ($prioridadPorEstado): int {
            $prioridadA = $prioridadPorEstado[intval($a['estado_id'] ?? 0)] ?? 99;
            $prioridadB = $prioridadPorEstado[intval($b['estado_id'] ?? 0)] ?? 99;
            if ($prioridadA !== $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            return strcmp((string)($a['fecha_hora'] ?? ''), (string)($b['fecha_hora'] ?? ''));
        });
        return $agenda;
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
        // El cambio de estado en sí se hace con un candado atómico (ver
        // ClinicaAvanzada) en vez de "leer estado -> validar en PHP ->
        // escribir": eso dejaba una ventana donde dos solicitudes casi
        // simultáneas (doble clic, dos pestañas) podían las dos leer el
        // mismo estado "viejo", pasar la validación de arriba, y las dos
        // escribir. Si la cita ya no está en el estado esperado cuando el
        // candado se libera, es que alguien más ganó la carrera primero.
        if ($estadoId === 7) {
            $medicoId = intval($this->getMedicoActual()['id'] ?? 0);
            $resultado = Clinica::iniciarAtencionSiPosible($citaId, $medicoId);
            if (!$resultado['ok']) {
                if ($resultado['motivo'] === 'ocupado') {
                    $enAtencion = $resultado['ocupadaCon'];
                    $this->redirectWithMessage(
                        'Ya tiene una consulta en curso con '
                        . trim(
                            $enAtencion['paciente_nombres'] . ' '
                            . $enAtencion['paciente_apellidos']
                        )
                        . ' (cita #' . $enAtencion['id'] . '). Finalícela antes '
                        . 'de iniciar otra.',
                        '',
                        0,
                        true
                    );
                }
                $this->redirectWithMessage(
                    'Esta cita ya fue actualizada por otra solicitud. Recargue la página e intente de nuevo.'
                );
            }
        } elseif (!Clinica::actualizarEstadoCitaSiEstaba($citaId, $estadoActual, $estadoId)) {
            $this->redirectWithMessage(
                'Esta cita ya fue actualizada por otra solicitud. Recargue la página e intente de nuevo.'
            );
        }

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
                'Debe guardar el historial clínico antes de finalizar la consulta.',
                '',
                0,
                true
            );
        }

        // Candado atómico: si dos clics de "Finalizar" llegan casi juntos,
        // solo el primero encuentra la cita todavía "En Atención" y
        // escribe; el segundo ve que ya cambió y no duplica notificación
        // ni auditoría (ver actualizarEstadoCitaSiEstaba en ClinicaAvanzada).
        if (!Clinica::actualizarEstadoCitaSiEstaba($citaId, $estadoActual, 3)) {
            $this->redirectWithMessage(
                'Esta consulta ya fue finalizada por otra solicitud.'
            );
        }
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

        if ($motivo === '' || $diagnostico === '') {
            $this->redirectWithMessage(
                'El motivo de consulta y el diagnóstico son obligatorios.'
            );
        }

        // La receta ahora es una lista de líneas (un medicamento por fila,
        // "+ Agregar medicamento" en el formulario) en vez de un solo campo.
        // Las líneas de compra se validan antes de guardar el historial: si
        // la suma solicitada de un producto supera el saldo del centro, no se
        // persiste todavía ni el historial ni la receta.
        $medicamentos = is_array($_POST['medicamento'] ?? null) ? $_POST['medicamento'] : [];
        $indicacionesLineas = is_array($_POST['indicaciones'] ?? null) ? $_POST['indicaciones'] : [];
        $productoIds = is_array($_POST['producto_id'] ?? null) ? $_POST['producto_id'] : [];
        $cantidades = is_array($_POST['cantidad'] ?? null) ? $_POST['cantidad'] : [];
        $comprasAqui = is_array($_POST['comprar_aqui'] ?? null) ? $_POST['comprar_aqui'] : [];

        $lineasVenta = [];
        $totalesSolicitados = [];
        $productosVenta = [];
        foreach ($comprasAqui as $index => $comprarAqui) {
            if ((int)$comprarAqui !== 1) {
                continue;
            }

            $productoId = Validators::sanitizeId($productoIds[$index] ?? 0);
            if ($productoId === null) {
                $this->redirectWithMessage(
                    'Seleccione un producto válido del inventario para cada '
                    . 'medicamento marcado como compra en la clínica.',
                    '',
                    0,
                    true
                );
            }
            $cantidad = Validators::sanitizeInt($cantidades[$index] ?? 0, 1);
            if ($cantidad === null) {
                $this->redirectWithMessage(
                    'La cantidad de cada producto comprado en la clínica '
                    . 'debe ser mayor que cero.',
                    '',
                    0,
                    true
                );
            }
            $producto = DaoProducto::getById($productoId);
            if (!$producto || ($producto['estado'] ?? '') !== 'ACT') {
                $this->redirectWithMessage(
                    'Uno de los productos seleccionados ya no está disponible.',
                    '',
                    0,
                    true
                );
            }

            $lineasVenta[] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio_unitario' => (float) $producto['precio_unitario'],
            ];
            $totalesSolicitados[$productoId] =
                ($totalesSolicitados[$productoId] ?? 0) + $cantidad;
            $productosVenta[$productoId] = $producto;
        }

        foreach ($totalesSolicitados as $productoId => $cantidadSolicitada) {
            $disponible = DaoInventarioCentro::getStock(
                (int)$productoId,
                (int)$cita['centro_salud_id']
            );
            if ($cantidadSolicitada > $disponible) {
                $producto = $productosVenta[$productoId];
                $this->redirectWithMessage(
                    'No se guardó el historial: "' . $producto['nombre']
                    . '" solicita ' . $cantidadSolicitada
                    . ' y solo hay ' . $disponible
                    . ' disponibles en el centro de la cita.',
                    '',
                    0,
                    true
                );
            }
        }

        $historialId = Clinica::guardarHistorial(
            $citaId,
            $motivo,
            $diagnostico,
            $tratamiento,
            $observaciones
        );

        foreach ($medicamentos as $index => $rawMedicamento) {
            $medicamentoLinea = trim((string) $rawMedicamento);
            $indicacionesLinea = trim((string) ($indicacionesLineas[$index] ?? ''));
            if ($medicamentoLinea === '' && $indicacionesLinea === '') {
                continue;
            }

            Clinica::guardarReceta(
                $historialId,
                $medicamentoLinea !== '' ? $medicamentoLinea : 'Indicaciones generales',
                $indicacionesLinea !== '' ? $indicacionesLinea : 'Según criterio médico'
            );
        }

        $mensajeFinal = 'Historial clínico guardado.';
        if (!empty($lineasVenta)) {
            try {
                $venta = DaoFacturaVenta::insertConDetalle(
                    $historialId,
                    (int) $cita['paciente_id'],
                    (int) $cita['centro_salud_id'],
                    (int) Security::getUserId(),
                    $lineasVenta
                );
                $mensajeFinal .= ' Factura de venta ' . $venta['numero_factura'] . ' generada.';
                AuditLogger::log(
                    'VENTA_MEDICAMENTO',
                    'Doctores',
                    'Factura de venta ' . $venta['numero_factura'] . ' generada para la cita #' . $citaId,
                    ['cita_id' => $citaId, 'historial_id' => $historialId, 'factura_venta_id' => $venta['id']]
                );
            } catch (\DomainException | \RuntimeException $e) {
                // El historial y la receta ya quedaron guardados arriba;
                // solo falló la venta (p. ej. no había suficiente stock).
                // Se avisa puntualmente en vez de perder todo lo demás.
                $this->redirectWithMessage(
                    'Historial clínico guardado, pero no se pudo generar la '
                    . 'venta del medicamento: ' . $e->getMessage(),
                    '',
                    0,
                    true
                );
            }
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
        $this->redirectWithMessage($mensajeFinal);
    }

    private function expediente(): void
    {
        Site::addLink('public/css/clinical-record.css?v=20260730-1');
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
        int $citaId = 0,
        bool $isError = false
    ): void
    {
        $url = 'index.php?page=DoctoresController';
        if ($action !== '') {
            $url .= '&action=' . rawurlencode($action);
        }
        if ($citaId > 0) {
            $url .= '&cita_id=' . $citaId;
        }
        if ($isError) {
            $url .= '&msg_type=error';
        }
        Site::redirectTo($url . '&msg=' . urlencode($message));
        exit;
    }

}
