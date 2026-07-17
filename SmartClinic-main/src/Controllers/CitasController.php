<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\Medicos as DaoMedicos;
use Dao\Pacientes as DaoPacientes;
use Utilities\Security;
use Utilities\Site;
use Utilities\AuditLogger;
use Utilities\MessageNotifier;
use Views\Renderer;

class CitasController extends PublicController
{
    private array $viewData = [];

    public function run(): void
    {
        // Requiere autenticación pero no controlador específico
        if (!Security::isLogged()) {
            Site::redirectTo('index.php?page=Sec_Login');
            exit;
        }

        $action = $_GET['action'] ?? 'index';
        $action = trim(strval($action));

        switch ($action) {
            case 'index':
                $this->index();
                break;

            case 'agendar':
                $this->agendar();
                break;

            case 'edit':
                $this->edit();
                break;

            case 'availableTimes':
                $this->availableTimes();
                break;

            case 'delete':
                $this->delete();
                break;

            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        $this->autoCancelPendingAppointments();

        $userId = Security::getUserId();
        $canManageCitas = Security::isAuthorized($userId, 'CitasController', 'CTR');
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);
        $showCrudActions = $canManageCitas || $isAdmin;

        $search = trim(strval($_GET['search'] ?? ''));
        $estadoFilter = trim(strval($_GET['estado'] ?? ''));
        $citas = DaoCitas::getAllCitas();
        if ($search !== '' || $estadoFilter !== '') {
            $searchLower = strtolower($search);
            $estadoLower = strtolower($estadoFilter);
            $citas = array_filter($citas, function ($item) use ($searchLower, $estadoLower) {
                $matchSearch = $searchLower === ''
                    || strpos(strtolower($item['paciente_nombres'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($item['paciente_apellidos'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($item['medico_nombres'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($item['medico_apellidos'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($item['nombre_especialidad'] ?? ''), $searchLower) !== false
                    || strpos(strtolower($item['fecha_hora'] ?? ''), $searchLower) !== false;
                $matchEstado = $estadoLower === ''
                    || strpos(strtolower($item['nombre_estado'] ?? ''), $estadoLower) !== false;

                return $matchSearch && $matchEstado;
            });
        }

        // Ordenar citas por fecha y hora ascendente (más antiguas primero)
        usort($citas, function ($a, $b) {
            return strtotime($a['fecha_hora'] ?? '') - strtotime($b['fecha_hora'] ?? '');
        });

        $this->viewData['citas'] = array_values($citas);
        $this->viewData['canManageCitas'] = $canManageCitas;
        $this->viewData['showCrudActions'] = $showCrudActions;
        $this->viewData['searchValue'] = $search;
        $this->viewData['estadoFilter'] = $estadoFilter;
        $this->viewData['estadoOptions'] = [
            ['value' => '', 'label' => 'Todos', 'selected' => $estadoFilter === ''],
            ['value' => 'Pendiente', 'label' => 'Pendiente', 'selected' => strtolower($estadoFilter) === 'pendiente'],
            ['value' => 'Confirmada', 'label' => 'Confirmada', 'selected' => strtolower($estadoFilter) === 'confirmada'],
            ['value' => 'Cancelada', 'label' => 'Cancelada', 'selected' => strtolower($estadoFilter) === 'cancelada'],
            ['value' => 'Completada', 'label' => 'Completada', 'selected' => strtolower($estadoFilter) === 'completada'],
            ['value' => 'No Asistió', 'label' => 'No Asistió', 'selected' => strtolower($estadoFilter) === 'no asistió' || strtolower($estadoFilter) === 'no asistio'],
        ];

        Renderer::render('citas', $this->viewData);
    }

    private function agendar(): void
    {
        $medicos = DaoMedicos::getAllMedicos();
        $pacientes = DaoPacientes::getAllPacientes();
        $defaults = [
            'paciente_id' => 0,
            'medico_id' => 0,
            'fecha' => '',
            'hora' => '',
            'error' => '',
            'timeOptions' => $this->getTimeOptions(),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrfPost()) {
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'medicos' => $medicos,
                    'pacientes' => $pacientes,
                    'error' => 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.',
                ]));
                return;
            }

            $pacienteId = \Utilities\Validators::sanitizeId($_POST['paciente_id'] ?? 0);
            $medicoId = \Utilities\Validators::sanitizeId($_POST['medico_id'] ?? 0);
            $fecha = \Utilities\Validators::sanitizeDate($_POST['fecha'] ?? '');
            $hora = \Utilities\Validators::sanitizeTime($_POST['hora'] ?? '');

            if ($pacienteId === null || $medicoId === null || $fecha === null || $hora === null) {
                $errorMessage = 'Datos inválidos. Verifique los campos.';
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'medicos' => $medicos,
                    'pacientes' => $pacientes,
                    'error' => $errorMessage,
                ]));
                return;
            } else {
                $fecha = $this->forceYmdFormat($fecha);
                $fechaHora = $fecha && $hora ? $fecha . 'T' . $hora : '';

                $defaults['paciente_id'] = $pacienteId;
                $defaults['medico_id'] = $medicoId;
                $defaults['fecha'] = $fecha;
                $defaults['hora'] = $hora;
                $defaults['timeOptions'] = $this->getTimeOptions($hora, $medicoId, $fecha);

                $errorMessage = $this->validateCita($pacienteId, $medicoId, $fechaHora);
                if ($errorMessage !== null) {
                    foreach ($medicos as &$medicoItem) {
                        $medicoItem['selected'] = intval($medicoItem['id']) === $medicoId;
                    }
                    unset($medicoItem);

                    foreach ($pacientes as &$pacienteItem) {
                        $pacienteItem['selected'] = intval($pacienteItem['id']) === $pacienteId;
                    }
                    unset($pacienteItem);

                    Renderer::render('cita_agendar', array_merge($defaults, [
                        'medicos' => $medicos,
                        'pacientes' => $pacientes,
                        'error' => $errorMessage,
                    ]));

                    return;
                }

                $newId = DaoCitas::insertCita($pacienteId, $medicoId, 1, $fechaHora);
                AuditLogger::log('crear', 'Citas', 'Cita agendada para ' . $fechaHora, ['cita_id' => $newId, 'paciente_id' => $pacienteId, 'medico_id' => $medicoId]);
                $paciente = DaoPacientes::getPacienteById($pacienteId) ?: [];
                $medico = DaoMedicos::getMedicoById($medicoId) ?: [];
                MessageNotifier::sendAppointmentSaved($paciente, $medico, $fechaHora, $newId);
                Site::redirectTo('index.php?page=CitasController&action=index&success=1');
                exit;
            }
        }

        Renderer::render('cita_agendar', array_merge($defaults, [
            'medicos' => $medicos,
            'pacientes' => $pacientes,
        ]));
    }

    private const ALLOWED_TRANSITIONS = [
        1 => [2, 4],
        2 => [3, 4, 5],
    ];

    private function autoCancelPendingAppointments(): void
    {
        $citas = DaoCitas::getAllCitas();
        $now = new \DateTime();
        $oneHourAgo = (clone $now)->modify('-1 hour');

        foreach ($citas as $cita) {
            if (!empty($cita['fecha_hora']) && ($cita['estado_id'] ?? 1) == 1) {
                $citaDateTime = new \DateTime($cita['fecha_hora']);
                if ($citaDateTime <= $oneHourAgo) {
                    DaoCitas::updateCita($cita['id'], $cita['paciente_id'], $cita['medico_id'], 4, $cita['fecha_hora']);
                    AuditLogger::log('auto-cancelar', 'Citas', 'Cita vencida cancelada automáticamente', ['cita_id' => $cita['id']]);
                }
            }
        }
    }

    private function edit(): void
    {
        $this->authorizeCitas();
        $this->autoCancelPendingAppointments();

        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            Site::redirectTo('index.php?page=CitasController&action=index');
            exit;
        }

        $cita = DaoCitas::getCitaById($id);
        if (!$cita) {
            Site::redirectTo('index.php?page=CitasController&action=index');
            exit;
        }

        $currentDateTime = new \DateTime();
        $citaDateTime = new \DateTime($cita['fecha_hora']);
        $esCitaPasada = $citaDateTime <= $currentDateTime;

        $estadosFinales = [3, 4, 5];
        $esEstadoFinal = in_array(intval($cita['estado_id'] ?? 1), $estadosFinales);
        $modoLectura = $esCitaPasada || $esEstadoFinal;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrfPost()) {
                Site::redirectTo('index.php?page=CitasController&action=edit&id=' . $id . '&csrf=1');
                exit;
            }

            $errorMessage = null;
            $estadoActual = intval($cita['estado_id'] ?? 1);
            $nuevoEstadoId = \Utilities\Validators::sanitizeInt($_POST['estado_id'] ?? 1, 1, 5);

            if ($nuevoEstadoId !== null && $estadoActual !== $nuevoEstadoId) {
                $transicionesPermitidas = self::ALLOWED_TRANSITIONS[$estadoActual] ?? [];
                if (!in_array($nuevoEstadoId, $transicionesPermitidas)) {
                    $nombresEstados = [
                        1 => 'Pendiente',
                        2 => 'Confirmada',
                        3 => 'Completada',
                        4 => 'Cancelada',
                        5 => 'No Asistió'
                    ];
                    $errorMessage = "Transición de estado no permitida: de {$nombresEstados[$estadoActual]} a {$nombresEstados[$nuevoEstadoId]}";
                }
            }

            $pacienteId = \Utilities\Validators::sanitizeId($_POST['paciente_id'] ?? 0);
            $medicoId = \Utilities\Validators::sanitizeId($_POST['medico_id'] ?? 0);
            $estadoId = \Utilities\Validators::sanitizeInt($_POST['estado_id'] ?? 1, 1, 5);
            $fecha = \Utilities\Validators::sanitizeDate($_POST['fecha'] ?? '');
            $hora = \Utilities\Validators::sanitizeTime($_POST['hora'] ?? '');

            if ($pacienteId === null || $medicoId === null || $estadoId === null || $fecha === null || $hora === null) {
                Site::redirectTo('index.php?page=CitasController&action=edit&id=' . $id . '&error=invalid');
                exit;
            } else {
                $fecha = $this->forceYmdFormat($fecha);
                $fechaHora = $fecha && $hora ? $fecha . 'T' . $hora : '';

                if ($errorMessage === null) {
                    $errorMessage = $this->validateCita($pacienteId, $medicoId, $fechaHora, $id);
                }
                if ($errorMessage !== null) {
                    $medicos = DaoMedicos::getAllMedicos();
                    foreach ($medicos as &$medicoItem) {
                        $medicoItem['selected'] = intval($medicoItem['id']) === $medicoId;
                    }
                    unset($medicoItem);

                    $pacientes = DaoPacientes::getAllPacientes();
                    foreach ($pacientes as &$pacienteItem) {
                        $pacienteItem['selected'] = intval($pacienteItem['id']) === $pacienteId;
                    }
                    unset($pacienteItem);

                    $estados = [
                        ['id' => 1, 'label' => 'Pendiente', 'selected' => $estadoId === 1],
                        ['id' => 2, 'label' => 'Confirmada', 'selected' => $estadoId === 2],
                        ['id' => 3, 'label' => 'Completada', 'selected' => $estadoId === 3],
                        ['id' => 4, 'label' => 'Cancelada', 'selected' => $estadoId === 4],
                        ['id' => 5, 'label' => 'No Asistió', 'selected' => $estadoId === 5],
                    ];

                    Renderer::render('cita_edit', [
                        'cita_id' => $id,
                        'fecha' => $fecha,
                        'hora' => $hora,
                        'medicos' => $medicos,
                        'pacientes' => $pacientes,
                        'estados' => $estados,
                        'timeOptions' => $this->getTimeOptions($hora, $medicoId, $fecha, $id),
                        'minDate' => $this->getMinDate(),
                        'maxDate' => $this->getMaxDate(),
                        'modo_lectura' => $modoLectura,
                        'error' => $errorMessage,
                    ]);

                    return;
                }

                DaoCitas::updateCita($id, $pacienteId, $medicoId, $estadoId, $fechaHora);
                AuditLogger::log('editar', 'Citas', 'Cita actualizada para ' . $fechaHora, ['cita_id' => $id, 'estado_id' => $estadoId]);
                Site::redirectTo('index.php?page=CitasController&action=index');
                exit;
            }
        }

        $fecha = '';
        $hora = '';
        if ($cita && isset($cita['fecha_hora'])) {
            $rawFechaHora = $cita['fecha_hora'];
            $dt = null;

            $formatos = [
                'Y-m-d H:i:s',
                'Y-m-d\TH:i:s',
                'Y-m-d\TH:i',
                'Y-m-d H:i',
            ];

            foreach ($formatos as $formato) {
                $dt = \DateTime::createFromFormat($formato, $rawFechaHora);
                if ($dt !== false) {
                    break;
                }
            }

            if ($dt) {
                $fecha = $dt->format('Y-m-d');
                $hora = $dt->format('H:i');
            } else {
                if (preg_match('/(\d{2}:\d{2}):?\d*/', $rawFechaHora, $matches)) {
                    $hora = $matches[1];
                    $fecha = substr($rawFechaHora, 0, 10);
                } else {
                    $fecha = substr($rawFechaHora, 0, 10);
                    $hora = substr($rawFechaHora, 11, 5);
                }
            }
            if (strlen($hora) === 5 && strpos($hora, ':') === 2) {
            } elseif (strlen($hora) > 5) {
                $hora = substr($hora, 0, 5);
            } else {
                $hora = '00:00';
            }
        }

        $medicos = DaoMedicos::getAllMedicos();
        foreach ($medicos as &$medicoItem) {
            $medicoItem['selected'] = intval($medicoItem['id']) === intval($cita['medico_id']);
        }
        unset($medicoItem);

        $pacientes = DaoPacientes::getAllPacientes();
        foreach ($pacientes as &$pacienteItem) {
            $pacienteItem['selected'] = intval($pacienteItem['id']) === intval($cita['paciente_id']);
        }
        unset($pacienteItem);

        $estados = [
            ['id' => 1, 'label' => 'Pendiente', 'selected' => intval($cita['estado_id']) === 1],
            ['id' => 2, 'label' => 'Confirmada', 'selected' => intval($cita['estado_id']) === 2],
            ['id' => 3, 'label' => 'Completada', 'selected' => intval($cita['estado_id']) === 3],
            ['id' => 4, 'label' => 'Cancelada', 'selected' => intval($cita['estado_id']) === 4],
            ['id' => 5, 'label' => 'No Asistió', 'selected' => intval($cita['estado_id']) === 5],
        ];

        Renderer::render('cita_edit', [
            'cita_id' => intval($cita['id']),
            'fecha' => $fecha,
            'hora' => $hora,
            'medicos' => $medicos,
            'pacientes' => $pacientes,
            'estados' => $estados,
            'timeOptions' => $this->getTimeOptions($hora, intval($cita['medico_id']), $fecha, intval($cita['id'])),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
            'modo_lectura' => $modoLectura,
            'error' => isset($_GET['csrf'])
                ? 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.'
                : (isset($_GET['error']) ? 'Datos inválidos. Verifique los campos.' : ''),
        ]);
    }

    private function availableTimes(): void
    {
        $medicoId = \Utilities\Validators::sanitizeId($_GET['medico_id'] ?? 0);
        $fecha = \Utilities\Validators::sanitizeDate($_GET['fecha'] ?? '');
        // Permite excluir la cita actual al editar (para que su hora no aparezca como ocupada)
        $excludeId = \Utilities\Validators::sanitizeId($_GET['exclude_id'] ?? 0) ?? 0;

        if ($medicoId === null || $fecha === null) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }

        $timeOptions = $this->getTimeOptions('', $medicoId, $fecha, $excludeId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_values(array_map(function ($option) {
            return ['value' => $option['value'], 'label' => $option['label']];
        }, $timeOptions)));
        exit;
    }

    private function delete(): void
    {
        $this->authorizeCitas();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=CitasController&action=index');
            exit;
        }

        $id = \Utilities\Validators::sanitizeId($_POST['id'] ?? 0);

        if ($id > 0) {
            $cita = DaoCitas::getCitaById($id);
            if ($cita) {
                $citaDateTime = new \DateTime($cita['fecha_hora']);
                $now = new \DateTime();
                if ($citaDateTime > $now && intval($cita['estado_id']) !== 3) {
                    DaoCitas::deleteCita($id);
                    AuditLogger::log('cancelar', 'Citas', 'Cita cancelada desde agenda', ['cita_id' => $id]);
                }
            }
        }

        Site::redirectTo('index.php?page=CitasController&action=index');
        exit;
    }

    private function validateCita(int $pacienteId, int $medicoId, string $fechaHoraRaw, int $excludeId = 0): ?string
    {
        if ($pacienteId <= 0 || $medicoId <= 0 || $fechaHoraRaw === '') {
            return 'Por favor completa todos los campos.';
        }

        $paciente = DaoPacientes::getPacienteById($pacienteId);
        if (!$paciente) {
            return 'El paciente seleccionado no existe.';
        }

        $medico = DaoMedicos::getMedicoById($medicoId);
        if (!$medico) {
            return 'El médico seleccionado no existe.';
        }

        $fechaHora = $this->parseFechaHora($fechaHoraRaw);
        if (!$fechaHora) {
            error_log("CitasController::validateCita - fechaHoraRaw recibido: '" . $fechaHoraRaw . "'");
            return 'Fecha y hora inválidas. Recibido: ' . htmlspecialchars($fechaHoraRaw) . ' — Formato esperado: YYYY-MM-DDTHH:MM';
        }

        $now = new \DateTime();
        $minBooking = (clone $now)->add(new \DateInterval('PT30M'));
        $maxBooking = (clone $now)->add(new \DateInterval('P3M'));

        if ($fechaHora < $now) {
            return 'No se puede agendar una cita en una fecha u hora pasada.';
        }

        if ($fechaHora < $minBooking) {
            return 'La cita debe reservarse al menos 30 minutos antes.';
        }

        if ($fechaHora > $maxBooking) {
            return 'No se pueden agendar citas con más de 3 meses de anticipación.';
        }

        $today = $now->format('Y-m-d');
        if ($fechaHora->format('Y-m-d') === $today && $fechaHora <= $now) {
            return 'Si la cita es hoy, la hora debe ser futura.';
        }

        $hour = intval($fechaHora->format('H'));
        $minute = intval($fechaHora->format('i'));
        if ($minute % 30 !== 0) {
            return 'La hora debe ser un intervalo de 30 minutos.';
        }

        $minutesOfDay = $hour * 60 + $minute;
        if ($minutesOfDay < 420 || $minutesOfDay >= 1020) {
            return 'Las citas solo pueden agendarse entre 7:00 y 17:00.';
        }

        if ($minutesOfDay >= 720 && $minutesOfDay < 780) {
            return 'No se pueden agendar citas en el horario de almuerzo (12:00 a 13:00).';
        }

        if (DaoCitas::countCitasMedicoDia($medicoId, $fechaHora->format('Y-m-d'), $excludeId) >= 18) {
            return 'El médico ya tiene el límite de 18 citas para ese día.';
        }

        if (!DaoCitas::checkDisponibilidad($medicoId, $pacienteId, $fechaHoraRaw, $excludeId)) {
            return 'El médico o paciente ya tiene una cita en un horario cercano de 30 minutos.';
        }

        return null;
    }

    private function getMinDateTime(): string
    {
        $minDateTime = (new \DateTime())->add(new \DateInterval('PT30M'));

        return $minDateTime->format('Y-m-d\\TH:i');
    }

    private function getMaxDateTime(): string
    {
        $maxDateTime = (new \DateTime())->add(new \DateInterval('P3M'));

        return $maxDateTime->format('Y-m-d\\TH:i');
    }

    private function getMinDate(): string
    {
        $minDate = (new \DateTime())->add(new \DateInterval('PT30M'));
        return $minDate->format('Y-m-d');
    }

    private function getMaxDate(): string
    {
        $maxDate = (new \DateTime())->add(new \DateInterval('P3M'));
        return $maxDate->format('Y-m-d');
    }

    private function getTimeOptions(string $selectedTime = '', int $medicoId = 0, string $date = '', int $excludeId = 0): array
    {
        $selectedTime = $this->normalizeTime($selectedTime);

        $times = [];

        for ($hour = 7; $hour <= 11; $hour++) {
            $times[] = sprintf('%02d:00', $hour);
            $times[] = sprintf('%02d:30', $hour);
        }

        for ($hour = 13; $hour <= 16; $hour++) {
            $times[] = sprintf('%02d:00', $hour);
            $times[] = sprintf('%02d:30', $hour);
        }

        $blockedTimes = [];
        if ($medicoId > 0 && $date !== '') {
            $blockedTimes = DaoCitas::getBookedTimeSlots($medicoId, $date, $excludeId);
        }

        // Normalizar horarios bloqueados a formato HH:MM para comparación consistente
        $blockedTimes = array_map(fn($t) => $this->normalizeTime($t), $blockedTimes);

        $options = [];
        foreach ($times as $time) {
            if (in_array($time, $blockedTimes, true) && $time !== $selectedTime) {
                continue;
            }

            $options[] = [
                'value' => $time,
                'label' => $time,
                'selected' => $time === $selectedTime,
            ];
        }

        return $options;
    }

    private function normalizeTime(string $time): string
    {
        if (empty($time)) {
            return '';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
            return $matches[1];
        }
        $dt = \DateTime::createFromFormat('H:i:s', $time);
        if ($dt) {
            return $dt->format('H:i');
        }
        $dt = \DateTime::createFromFormat('H:i', $time);
        if ($dt) {
            return $dt->format('H:i');
        }
        return '';
    }

    private function authorizeCitas(): void
    {
        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);

        if (!Security::isAuthorized($userId, 'CitasController', 'CTR') && !$isAdmin) {
            Site::redirectTo('index.php?page=CitasController&action=index');
            exit;
        }
    }

    private function parseFechaHora(string $fechaHoraRaw): ?\DateTime
    {
        $fechaHoraRaw = trim($fechaHoraRaw);
        if ($fechaHoraRaw === '') {
            return null;
        }

        try {
            $dt = new \DateTime($fechaHoraRaw);
            if ($dt->format('Y') > 1970) {
                return $dt;
            }
        } catch (\Exception $e) {
        }

        // Formatos manuales para casos no estándar (d/m/Y, etc.)
        $formatos = [
            'Y-m-d\TH:i',    // 2026-06-08T07:30
            'Y-m-d H:i',     // 2026-06-08 07:30
            'Y-m-d\TH:i:s',  // 2026-06-08T07:30:00
            'Y-m-d H:i:s',   // 2026-06-08 07:30:00
            'd/m/Y\TH:i',    // 08/06/2026T07:30
            'd/m/Y H:i',     // 08/06/2026 07:30
            'd/m/Y\TH:i:s',  // 08/06/2026T07:30:00
            'd/m/Y H:i:s',   // 08/06/2026 07:30:00
            'd-m-Y\TH:i',    // 08-06-2026T07:30
            'd-m-Y H:i',     // 08-06-2026 07:30
            'd-m-Y\TH:i:s',  // 08-06-2026T07:30:00
            'd-m-Y H:i:s',   // 08-06-2026 07:30:00
            'j/n/Y\TH:i',    // 8/6/2026T07:30
            'j/n/Y H:i',     // 8/6/2026 07:30
            'j-n-Y\TH:i',    // 8-6-2026T07:30
            'j-n-Y H:i',     // 8-6-2026 07:30
        ];

        foreach ($formatos as $formato) {
            \DateTime::getLastErrors(); // reset
            $fechaHora = \DateTime::createFromFormat($formato, $fechaHoraRaw);
            $errors = \DateTime::getLastErrors();
            if ($fechaHora && is_array($errors) && $errors['error_count'] === 0) {
                return $fechaHora;
            }
        }

        if (strpos($fechaHoraRaw, 'T') !== false || strpos($fechaHoraRaw, ' ') !== false) {
            $partes = preg_split('/[T\s]/', $fechaHoraRaw, 2);
            if (count($partes) === 2) {
                $fechaParte = trim($partes[0]);
                $horaParte = trim($partes[1]);
                $formatosFecha = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'j/n/Y', 'j-n-Y'];
                foreach ($formatosFecha as $fmt) {
                    \DateTime::getLastErrors();
                    $dt = \DateTime::createFromFormat($fmt, $fechaParte);
                    $errors = \DateTime::getLastErrors();
                    if ($dt && is_array($errors) && $errors['error_count'] === 0) {
                        $horaLimpia = substr($horaParte, 0, 5); // HH:MM
                        $combinada = $dt->format('Y-m-d') . 'T' . $horaLimpia;
                        \DateTime::getLastErrors();
                        $dt2 = \DateTime::createFromFormat('Y-m-d\TH:i', $combinada);
                        $errors2 = \DateTime::getLastErrors();
                        if ($dt2 && is_array($errors2) && $errors2['error_count'] === 0) {
                            return $dt2;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function normalizeDate(string $fecha): string
    {
        if ($fecha === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        // Intentar parsear formatos d/m/Y o d-m-Y
        $formatos = ['d/m/Y', 'd-m-Y'];
        foreach ($formatos as $formato) {
            $dt = \DateTime::createFromFormat($formato, $fecha);
            $errors = \DateTime::getLastErrors();
            if ($dt && is_array($errors) && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                return $dt->format('Y-m-d');
            }
        }
        return $fecha;
    }

    private function forceYmdFormat(string $fecha): string
    {
        if ($fecha === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        $formatos = ['d/m/Y', 'd-m-Y'];
        foreach ($formatos as $formato) {
            $dt = \DateTime::createFromFormat($formato, $fecha);
            $errors = \DateTime::getLastErrors();
            if ($dt && is_array($errors) && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                return $dt->format('Y-m-d');
            }
        }
        return $fecha;
    }
}
