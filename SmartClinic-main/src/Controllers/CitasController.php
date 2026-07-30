<?php

namespace Controllers;

use Dao\Citas as DaoCitas;
use Dao\ClinicaAvanzada as Clinica;
use Dao\MedicoCentroSalud as DaoMedicoCentroSalud;
use Dao\Medicos as DaoMedicos;
use Dao\Pacientes as DaoPacientes;
use Utilities\Security;
use Utilities\Site;
use Utilities\AuditLogger;
use Utilities\MessageNotifier;
use Utilities\Validators;
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

            case 'availableCenters':
                $this->availableCenters();
                break;

            case 'notify':
                $this->notify();
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

        $citaBuscadaId = Validators::sanitizeId(
            $_GET['cita_id'] ?? ''
        );
        $search = Validators::sanitizeString(
            $_GET['search'] ?? '',
            150
        );
        $estadoFilter = Validators::sanitizeString(
            $_GET['estado'] ?? '',
            50
        );
        $citas = DaoCitas::getAllCitas();

        foreach ($citas as &$cita) {
            $cita['canNotify'] = $this->canNotifyAppointment($cita);
            $cita['cannotNotify'] = !$cita['canNotify'];
            $cita['paciente_telefono_texto'] =
                trim(strval($cita['paciente_telefono'] ?? '')) !== ''
                    ? $cita['paciente_telefono']
                    : 'Sin teléfono registrado';
        }
        unset($cita);

        // Las sugerencias se construyen antes de filtrar para que el
        // autocompletado siempre pueda encontrar cualquier cita registrada.
        $citasParaBuscador = array_map(
            function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'nombre' => $this->buildAppointmentSearchLabel(
                        $item
                    ),
                    'extra' => $this->buildAppointmentSearchText(
                        $item
                    )
                ];
            },
            $citas
        );
        $this->viewData['citasJsonAttr'] = htmlspecialchars(
            json_encode(
                $citasParaBuscador,
                JSON_UNESCAPED_UNICODE
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $searchDisplayValue = $search;
        if ($citaBuscadaId !== null) {
            $selectedOption = null;
            foreach ($citasParaBuscador as $option) {
                if ((int) $option['id'] === $citaBuscadaId) {
                    $selectedOption = $option;
                    break;
                }
            }
            $searchDisplayValue = $selectedOption['nombre']
                ?? $search;

            $citas = array_values(array_filter(
                $citas,
                static function (array $item) use (
                    $citaBuscadaId
                ): bool {
                    return (int) ($item['id'] ?? 0)
                        === $citaBuscadaId;
                }
            ));
        } elseif ($search !== '') {
            $normalizedSearch = $this->normalizeAppointmentSearch(
                $search
            );
            $citas = array_values(array_filter(
                $citas,
                function (array $item) use (
                    $normalizedSearch
                ): bool {
                    return strpos(
                        $this->normalizeAppointmentSearch(
                            $this->buildAppointmentSearchText($item)
                        ),
                        $normalizedSearch
                    ) !== false;
                }
            ));
        }

        if ($estadoFilter !== '') {
            $normalizedStatus = $this->normalizeAppointmentSearch(
                $estadoFilter
            );
            $citas = array_values(array_filter(
                $citas,
                function (array $item) use (
                    $normalizedStatus
                ): bool {
                    return $this->normalizeAppointmentSearch(
                        strval($item['nombre_estado'] ?? '')
                    ) === $normalizedStatus;
                }
            ));
        }

        // La paginación se aplica al resultado ya filtrado y ordenado.
        usort(
            $citas,
            static function (array $a, array $b): int {
                return strtotime($b['fecha_hora'] ?? '') <=> strtotime($a['fecha_hora'] ?? '');
            }
        );
        $citas = array_values($citas);
        $pagination = $this->paginateAppointments(
            $citas,
            5,
            'pagina'
        );

        $this->viewData['citas'] = $pagination['items'];
        $this->viewData['paginaActual'] =
            $pagination['paginaActual'];
        $this->viewData['totalPaginas'] =
            $pagination['totalPaginas'];
        $this->viewData['totalCitas'] = count($citas);
        $this->viewData['canManageCitas'] = $canManageCitas;
        $this->viewData['showCrudActions'] = $showCrudActions;
        $this->viewData['searchValue'] = $searchDisplayValue;
        $this->viewData['citaBuscadaIdValue'] =
            $citaBuscadaId !== null
                ? (string) $citaBuscadaId
                : '';
        $this->viewData['estadoFilter'] = $estadoFilter;
        $this->viewData['hayFiltros'] =
            $citaBuscadaId !== null
            || $search !== ''
            || $estadoFilter !== '';
        $notificationStatus = trim(strval($_GET['notification'] ?? ''));
        $this->viewData['notificationSent'] = $notificationStatus === 'sent';
        $this->viewData['notificationFailed'] = $notificationStatus === 'failed';
        $this->viewData['notificationUnavailable'] =
            $notificationStatus === 'unavailable';
        $estadoOptions = [
            ['value' => '', 'label' => 'Todos', 'selected' => $estadoFilter === ''],
        ];
        foreach (Clinica::ESTADOS as $label) {
            $estadoOptions[] = [
                'value' => $label,
                'label' => $label,
                'selected' => strtolower($estadoFilter) === strtolower($label),
            ];
        }
        $this->viewData['estadoOptions'] = $estadoOptions;

        $paginationUrl =
            'index.php?page=CitasController&action=index';
        if ($citaBuscadaId !== null) {
            $paginationUrl .= '&cita_id=' . $citaBuscadaId;
        } elseif ($search !== '') {
            $paginationUrl .= '&search=' . urlencode($search);
        }
        if ($estadoFilter !== '') {
            $paginationUrl .=
                '&estado=' . urlencode($estadoFilter);
        }
        $this->viewData['urlPaginaAnterior'] =
            $pagination['paginaActual'] > 1
                ? $paginationUrl
                    . '&pagina='
                    . ($pagination['paginaActual'] - 1)
                : '';
        $this->viewData['urlPaginaSiguiente'] =
            $pagination['paginaActual']
                < $pagination['totalPaginas']
                ? $paginationUrl
                    . '&pagina='
                    . ($pagination['paginaActual'] + 1)
                : '';

        Site::addEndScript(
            'public/js/kardex-autocomplete.js'
        );
        Renderer::render('citas', $this->viewData);
    }

    private function agendar(): void
    {
        Site::addEndScript('public/js/kardex-autocomplete.js');

        $defaults = [
            'paciente_id' => 0,
            'medico_id' => 0,
            'centro_salud_id' => 0,
            'fecha' => '',
            'hora' => '',
            'notify_patient' => false,
            'error' => '',
            'timeOptions' => $this->getTimeOptions(),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pacienteId = \Utilities\Validators::sanitizeId($_POST['paciente_id'] ?? 0);
            $medicoId = \Utilities\Validators::sanitizeId($_POST['medico_id'] ?? 0);
            $centroSaludId =
                \Utilities\Validators::sanitizeId($_POST['centro_salud_id'] ?? 0);
            $fecha = \Utilities\Validators::sanitizeDate($_POST['fecha'] ?? '');
            $hora = \Utilities\Validators::sanitizeTime($_POST['hora'] ?? '');
            $notifyPatient = ($_POST['notify_patient'] ?? '') === '1';

            $defaults['paciente_id'] = $pacienteId ?? 0;
            $defaults['medico_id'] = $medicoId ?? 0;
            $defaults['centro_salud_id'] = $centroSaludId ?? 0;
            $defaults['fecha'] = $fecha ?? '';
            $defaults['hora'] = $hora ?? '';
            $defaults['notify_patient'] = $notifyPatient;
            $defaults['timeOptions'] = $this->getTimeOptions(
                $hora ?? '',
                $medicoId ?? 0,
                $fecha ?? '',
                0,
                $pacienteId ?? 0
            );

            if (!Security::validateCsrfPost()) {
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'centros' =>
                        $this->buildCentros($medicoId ?? 0, $centroSaludId ?? 0),
                    'error' => 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.',
                    ...$this->buildComboDataMedicos($medicoId ?? 0),
                    ...$this->buildComboDataPacientes($pacienteId ?? 0),
                ]));
                return;
            }

            if (
                $pacienteId === null
                || $medicoId === null
                || $centroSaludId === null
                || $fecha === null
                || $hora === null
            ) {
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'centros' =>
                        $this->buildCentros($medicoId ?? 0, $centroSaludId ?? 0),
                    'error' => 'Datos inválidos. Verifique todos los campos.',
                    ...$this->buildComboDataMedicos($medicoId ?? 0),
                    ...$this->buildComboDataPacientes($pacienteId ?? 0),
                ]));
                return;
            }

            $fecha = $this->forceYmdFormat($fecha);
            $fechaHora = $fecha . 'T' . $hora;
            $defaults['fecha'] = $fecha;

            $errorMessage = $this->validateCita(
                $pacienteId,
                $medicoId,
                $centroSaludId,
                $fechaHora
            );
            if ($errorMessage !== null) {
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'centros' => $this->buildCentros($medicoId, $centroSaludId),
                    'error' => $errorMessage,
                    ...$this->buildComboDataMedicos($medicoId),
                    ...$this->buildComboDataPacientes($pacienteId),
                ]));
                return;
            }

            $appointmentLocation =
                DaoMedicoCentroSalud::getActivoByMedicoCentro(
                    $medicoId,
                    $centroSaludId
                );
            if (!$appointmentLocation) {
                Renderer::render('cita_agendar', array_merge($defaults, [
                    'centros' =>
                        $this->buildCentros($medicoId, $centroSaludId),
                    'error' =>
                        'La asignación del médico cambió. Seleccione nuevamente el centro.',
                    ...$this->buildComboDataMedicos($medicoId),
                    ...$this->buildComboDataPacientes($pacienteId),
                ]));
                return;
            }

            $newId = DaoCitas::insertCita(
                $pacienteId,
                $medicoId,
                $centroSaludId,
                strval($appointmentLocation['consultorio'] ?? ''),
                1,
                $fechaHora
            );
            AuditLogger::log(
                'crear',
                'Citas',
                'Cita agendada para ' . $fechaHora,
                [
                    'cita_id' => $newId,
                    'paciente_id' => $pacienteId,
                    'medico_id' => $medicoId,
                    'centro_salud_id' => $centroSaludId,
                    'notificacion_solicitada' => $notifyPatient,
                ]
            );

            $notificationQuery = '';
            if ($notifyPatient) {
                $createdAppointment = DaoCitas::getCitaById($newId);
                $sent = $createdAppointment
                    ? $this->sendAppointmentNotification($createdAppointment)
                    : false;
                $notificationQuery = '&notification=' . ($sent ? 'sent' : 'failed');
                AuditLogger::log(
                    $sent ? 'notificar' : 'notificacion-fallida',
                    'Citas',
                    $sent
                        ? 'Notificación inmediata enviada al crear la cita'
                        : 'No se pudo enviar la notificación inmediata',
                    ['cita_id' => $newId]
                );
            }

            Site::redirectTo(
                'index.php?page=CitasController&action=index&success=1'
                . $notificationQuery
            );
            exit;
        }

        Renderer::render('cita_agendar', array_merge($defaults, [
            'centros' => [],
            ...$this->buildComboDataMedicos(),
            ...$this->buildComboDataPacientes(),
        ]));
    }

    /**
     * Auto-cancelar pendientes vencidas ahora vive en ClinicaAvanzada para
     * que también se pueda invocar desde el portal del doctor y del
     * paciente, no solo cuando un admin entra a este módulo.
     */
    private function autoCancelPendingAppointments(): void
    {
        $canceladas = Clinica::autoCancelarPendientesVencidas();
        foreach ($canceladas as $citaId) {
            AuditLogger::log(
                'auto-cancelar',
                'Citas',
                'Cita vencida cancelada automáticamente',
                ['cita_id' => $citaId]
            );
        }
    }

    private function edit(): void
    {
        Site::addEndScript('public/js/kardex-autocomplete.js');

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
            $nuevoEstadoId = \Utilities\Validators::sanitizeInt($_POST['estado_id'] ?? 1, 1, 7);

            if ($nuevoEstadoId !== null && $estadoActual !== $nuevoEstadoId) {
                if (!Clinica::puedeTransicionarAdmin($estadoActual, $nuevoEstadoId)) {
                    $errorMessage = 'Transición de estado no permitida: de '
                        . Clinica::nombreEstado($estadoActual) . ' a '
                        . Clinica::nombreEstado($nuevoEstadoId);
                }
            }

            $pacienteId = \Utilities\Validators::sanitizeId($_POST['paciente_id'] ?? 0);
            $medicoId = \Utilities\Validators::sanitizeId($_POST['medico_id'] ?? 0);
            $centroSaludId =
                \Utilities\Validators::sanitizeId($_POST['centro_salud_id'] ?? 0);
            $estadoId = \Utilities\Validators::sanitizeInt($_POST['estado_id'] ?? 1, 1, 7);
            $fecha = \Utilities\Validators::sanitizeDate($_POST['fecha'] ?? '');
            $hora = \Utilities\Validators::sanitizeTime($_POST['hora'] ?? '');

            if (
                $pacienteId === null
                || $medicoId === null
                || $centroSaludId === null
                || $estadoId === null
                || $fecha === null
                || $hora === null
            ) {
                Site::redirectTo('index.php?page=CitasController&action=edit&id=' . $id . '&error=invalid');
                exit;
            } else {
                $fecha = $this->forceYmdFormat($fecha);
                $fechaHora = $fecha && $hora ? $fecha . 'T' . $hora : '';

                if ($errorMessage === null) {
                    $errorMessage = $this->validateCita(
                        $pacienteId,
                        $medicoId,
                        $centroSaludId,
                        $fechaHora,
                        $id
                    );
                }
                if ($errorMessage !== null) {
                    $estados = $this->buildEstadosOptions($estadoId);

                    Renderer::render('cita_edit', [
                        'cita_id' => $id,
                        'fecha' => $fecha,
                        'hora' => $hora,
                        'centros' =>
                            $this->buildCentros($medicoId, $centroSaludId),
                        'estados' => $estados,
                        'timeOptions' => $this->getTimeOptions(
                            $hora,
                            $medicoId,
                            $fecha,
                            $id,
                            $pacienteId
                        ),
                        'minDate' => $this->getMinDate(),
                        'maxDate' => $this->getMaxDate(),
                        'modo_lectura' => $modoLectura,
                        'error' => $errorMessage,
                        ...$this->buildComboDataMedicos($medicoId),
                        ...$this->buildComboDataPacientes($pacienteId),
                    ]);

                    return;
                }

                $appointmentLocation =
                    DaoMedicoCentroSalud::getActivoByMedicoCentro(
                        $medicoId,
                        $centroSaludId
                    );
                if (!$appointmentLocation) {
                    $estados = $this->buildEstadosOptions($estadoId);

                    Renderer::render('cita_edit', [
                        'cita_id' => $id,
                        'fecha' => $fecha,
                        'hora' => $hora,
                        'centros' =>
                            $this->buildCentros($medicoId, $centroSaludId),
                        'estados' => $estados,
                        'timeOptions' => $this->getTimeOptions(
                            $hora,
                            $medicoId,
                            $fecha,
                            $id,
                            $pacienteId
                        ),
                        'minDate' => $this->getMinDate(),
                        'maxDate' => $this->getMaxDate(),
                        'modo_lectura' => $modoLectura,
                        'error' =>
                            'La asignación del médico cambió. Seleccione nuevamente el centro.',
                        ...$this->buildComboDataMedicos($medicoId),
                        ...$this->buildComboDataPacientes($pacienteId),
                    ]);
                    return;
                }

                DaoCitas::updateCita(
                    $id,
                    $pacienteId,
                    $medicoId,
                    $centroSaludId,
                    strval($appointmentLocation['consultorio'] ?? ''),
                    $estadoId,
                    $fechaHora
                );
                AuditLogger::log(
                    'editar',
                    'Citas',
                    'Cita actualizada para ' . $fechaHora,
                    [
                        'cita_id' => $id,
                        'estado_id' => $estadoId,
                        'centro_salud_id' => $centroSaludId,
                    ]
                );
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

        $estados = $this->buildEstadosOptions(intval($cita['estado_id']));

        Renderer::render('cita_edit', [
            'cita_id' => intval($cita['id']),
            'fecha' => $fecha,
            'hora' => $hora,
            'centros' => $this->buildCentros(
                intval($cita['medico_id']),
                intval($cita['centro_salud_id'])
            ),
            'estados' => $estados,
            'timeOptions' => $this->getTimeOptions(
                $hora,
                intval($cita['medico_id']),
                $fecha,
                intval($cita['id']),
                intval($cita['paciente_id'])
            ),
            'minDate' => $this->getMinDate(),
            'maxDate' => $this->getMaxDate(),
            'modo_lectura' => $modoLectura,
            'error' => isset($_GET['csrf'])
                ? 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.'
                : (isset($_GET['error']) ? 'Datos inválidos. Verifique los campos.' : ''),
            ...$this->buildComboDataMedicos(intval($cita['medico_id'])),
            ...$this->buildComboDataPacientes(intval($cita['paciente_id'])),
        ]);
    }

    private function availableTimes(): void
    {
        $medicoId = \Utilities\Validators::sanitizeId($_GET['medico_id'] ?? 0);
        $pacienteId = \Utilities\Validators::sanitizeId(
            $_GET['paciente_id'] ?? 0
        ) ?? 0;
        $fecha = \Utilities\Validators::sanitizeDate($_GET['fecha'] ?? '');
        // Permite excluir la cita actual al editar (para que su hora no aparezca como ocupada)
        $excludeId = \Utilities\Validators::sanitizeId($_GET['exclude_id'] ?? 0) ?? 0;

        if ($medicoId === null || $fecha === null) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }

        $timeOptions = $this->getTimeOptions(
            '',
            $medicoId,
            $fecha,
            $excludeId,
            $pacienteId
        );
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_values(array_map(function ($option) {
            return ['value' => $option['value'], 'label' => $option['label']];
        }, $timeOptions)));
        exit;
    }

    private function availableCenters(): void
    {
        $medicoId = \Utilities\Validators::sanitizeId($_GET['medico_id'] ?? 0);
        $centros = $medicoId === null
            ? []
            : DaoMedicoCentroSalud::getActivosByMedico($medicoId);

        $options = array_map(
            function (array $centro): array {
                return [
                    'value' => intval($centro['centro_salud_id']),
                    'label' => $centro['centro_nombre']
                        . ' - Consultorio ' . $centro['consultorio'],
                    'consultorio' => $centro['consultorio'],
                ];
            },
            $centros
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_values($options));
        exit;
    }

    private function notify(): void
    {
        $this->authorizeCitas();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::validateCsrfPost()) {
            Site::redirectTo(
                'index.php?page=CitasController&action=index&notification=unavailable'
            );
            exit;
        }

        $id = \Utilities\Validators::sanitizeId($_POST['id'] ?? 0);
        $cita = $id !== null ? DaoCitas::getCitaById($id) : null;

        if (!$cita || !$this->canNotifyAppointment($cita)) {
            AuditLogger::log(
                'notificacion-no-disponible',
                'Citas',
                'Intento de notificar una cita no elegible',
                ['cita_id' => $id ?? 0]
            );
            Site::redirectTo(
                'index.php?page=CitasController&action=index&notification=unavailable'
            );
            exit;
        }

        $sent = $this->sendAppointmentNotification($cita);
        AuditLogger::log(
            $sent ? 'notificar' : 'notificacion-fallida',
            'Citas',
            $sent
                ? 'Notificación manual enviada al paciente'
                : 'No se pudo enviar la notificación manual',
            [
                'cita_id' => $id,
                'paciente_id' => intval($cita['paciente_id']),
                'medico_id' => intval($cita['medico_id']),
                'centro_salud_id' => intval($cita['centro_salud_id']),
            ]
        );

        Site::redirectTo(
            'index.php?page=CitasController&action=index&notification='
            . ($sent ? 'sent' : 'failed')
        );
        exit;
    }

    private function canNotifyAppointment(array $cita): bool
    {
        $phone = trim(strval($cita['paciente_telefono'] ?? ''));
        $estadoId = intval($cita['estado_id'] ?? 0);
        $fechaHora = trim(strval($cita['fecha_hora'] ?? ''));

        if ($phone === '' || $fechaHora === '' || in_array($estadoId, [3, 4, 5], true)) {
            return false;
        }

        try {
            return new \DateTime($fechaHora) > new \DateTime();
        } catch (\Exception $error) {
            return false;
        }
    }

    private function sendAppointmentNotification(array $cita): bool
    {
        $paciente = [
            'nombres' => $cita['paciente_nombres'] ?? '',
            'apellidos' => $cita['paciente_apellidos'] ?? '',
            'telefono' => $cita['paciente_telefono'] ?? '',
        ];
        $medico = [
            'nombres' => $cita['medico_nombres'] ?? '',
            'apellidos' => $cita['medico_apellidos'] ?? '',
        ];
        $ubicacion = [
            'centro_nombre' => $cita['centro_nombre'] ?? '',
            'consultorio' => $cita['consultorio'] ?? '',
        ];

        return MessageNotifier::sendAppointmentSaved(
            $paciente,
            $medico,
            $ubicacion,
            strval($cita['fecha_hora'] ?? ''),
            intval($cita['id'] ?? 0)
        );
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
                $estadoActual = intval($cita['estado_id']);
                // Antes esto borraba la fila con DELETE: la cita desaparecía
                // para siempre, sin quedar en el historial del paciente ni
                // en reportes. Ahora se marca como Cancelada (estado 4) para
                // conservar el registro, igual que al cancelar desde Editar.
                if (
                    $citaDateTime > $now
                    && Clinica::puedeTransicionarAdmin($estadoActual, 4)
                ) {
                    DaoCitas::updateCita(
                        $id,
                        intval($cita['paciente_id']),
                        intval($cita['medico_id']),
                        intval($cita['centro_salud_id']),
                        strval($cita['consultorio'] ?? ''),
                        4,
                        $cita['fecha_hora']
                    );
                    AuditLogger::log('cancelar', 'Citas', 'Cita cancelada desde agenda', ['cita_id' => $id]);
                }
            }
        }

        Site::redirectTo('index.php?page=CitasController&action=index');
        exit;
    }

    private function buildEstadosOptions(int $estadoActualId): array
    {
        $estados = [];
        foreach (Clinica::ESTADOS as $id => $label) {
            $estados[] = ['id' => $id, 'label' => $label, 'selected' => $estadoActualId === $id];
        }
        return $estados;
    }

    /**
     * Convierte pacientes/médicos al formato {id, nombre} (más "telefono"
     * para pacientes) que necesita la barra de búsqueda con autocompletar
     * (kardex-autocomplete.js), reusando exactamente el mismo componente
     * que ya se usa en Inventario/Kárdex.
     */
    private function mapearPacientesParaBuscador(array $pacientes): array
    {
        return array_map(function ($p) {
            return [
                'id' => (int) $p['id'],
                'nombre' => trim($p['nombres'] . ' ' . $p['apellidos'])
                    . ' (' . $p['identidad'] . ')',
                'telefono' => (string) ($p['telefono'] ?? ''),
            ];
        }, $pacientes);
    }

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

    private function buscarOpcionPorId(array $opcionesMapeadas, int $id): ?array
    {
        foreach ($opcionesMapeadas as $opcion) {
            if ($opcion['id'] === $id) {
                return $opcion;
            }
        }
        return null;
    }

    /**
     * Junta todo lo que las plantillas necesitan para pintar el buscador
     * de Paciente: la lista completa en JSON (para filtrar en el
     * navegador) y, si ya hay uno seleccionado (al reabrir el formulario
     * tras un error, o al editar una cita existente), su nombre y
     * teléfono ya resueltos para precargar el campo de texto.
     */
    private function buildComboDataPacientes(int $selectedId = 0): array
    {
        $mapeados = $this->mapearPacientesParaBuscador(DaoPacientes::getAllPacientes());
        $seleccionado = $selectedId > 0
            ? $this->buscarOpcionPorId($mapeados, $selectedId)
            : null;

        return [
            'pacientesJsonAttr' => $this->jsonAttrParaAutocompletar($mapeados),
            'pacienteIdSeleccionadoValue' => $seleccionado ? $seleccionado['id'] : 0,
            'pacienteNombreSeleccionado' => $seleccionado ? $seleccionado['nombre'] : '',
            'pacienteTelefonoSeleccionado' => $seleccionado ? $seleccionado['telefono'] : '',
        ];
    }

    private function buildComboDataMedicos(int $selectedId = 0): array
    {
        $mapeados = $this->mapearMedicosParaBuscador(DaoMedicos::getAllMedicos());
        $seleccionado = $selectedId > 0
            ? $this->buscarOpcionPorId($mapeados, $selectedId)
            : null;

        return [
            'medicosJsonAttr' => $this->jsonAttrParaAutocompletar($mapeados),
            'medicoIdSeleccionadoValue' => $seleccionado ? $seleccionado['id'] : 0,
            'medicoNombreSeleccionado' => $seleccionado ? $seleccionado['nombre'] : '',
        ];
    }

    private function buildCentros(int $medicoId, int $selectedId = 0): array
    {
        if ($medicoId <= 0) {
            return [];
        }

        $centros = DaoMedicoCentroSalud::getActivosByMedico($medicoId);
        foreach ($centros as &$centro) {
            $centro['selected'] =
                intval($centro['centro_salud_id']) === $selectedId;
        }
        unset($centro);

        return $centros;
    }

    private function validateCita(
        int $pacienteId,
        int $medicoId,
        int $centroSaludId,
        string $fechaHoraRaw,
        int $excludeId = 0
    ): ?string
    {
        if (
            $pacienteId <= 0
            || $medicoId <= 0
            || $centroSaludId <= 0
            || $fechaHoraRaw === ''
        ) {
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

        $ubicacion = DaoMedicoCentroSalud::getActivoByMedicoCentro(
            $medicoId,
            $centroSaludId
        );
        if (!$ubicacion) {
            return 'El centro seleccionado no está asignado activamente al médico.';
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

        $conflicts = DaoCitas::getAvailabilityConflicts(
            $medicoId,
            $pacienteId,
            $fechaHoraRaw,
            $excludeId
        );
        if (count($conflicts) > 0) {
            return $this->buildAvailabilityConflictMessage(
                $conflicts,
                $medicoId,
                $pacienteId
            );
        }

        return null;
    }

    private function buildAvailabilityConflictMessage(
        array $conflicts,
        int $medicoId,
        int $pacienteId
    ): string {
        $doctorConflict = null;
        $patientConflict = null;

        foreach ($conflicts as $conflict) {
            if (
                $doctorConflict === null
                && intval($conflict['medico_id'] ?? 0) === $medicoId
            ) {
                $doctorConflict = $conflict;
            }
            if (
                $patientConflict === null
                && intval($conflict['paciente_id'] ?? 0) === $pacienteId
            ) {
                $patientConflict = $conflict;
            }
        }

        if (
            $doctorConflict !== null
            && $patientConflict !== null
            && intval($doctorConflict['id']) === intval($patientConflict['id'])
        ) {
            return 'El médico '
                . $this->conflictDoctorName($doctorConflict)
                . ' y el paciente '
                . $this->conflictPatientName($patientConflict)
                . ' ya tienen una cita a las '
                . $this->conflictTime($doctorConflict)
                . '.';
        }

        $messages = [];
        if ($doctorConflict !== null) {
            $messages[] = 'El médico '
                . $this->conflictDoctorName($doctorConflict)
                . ' ya tiene una cita a las '
                . $this->conflictTime($doctorConflict)
                . ' con el paciente '
                . $this->conflictPatientName($doctorConflict)
                . '.';
        }
        if ($patientConflict !== null) {
            $messages[] = 'El paciente '
                . $this->conflictPatientName($patientConflict)
                . ' ya tiene una cita a las '
                . $this->conflictTime($patientConflict)
                . ' con el médico '
                . $this->conflictDoctorName($patientConflict)
                . '.';
        }

        return count($messages) > 0
            ? implode(' Además, ', $messages)
            : 'El horario seleccionado ya no está disponible.';
    }

    private function conflictDoctorName(array $conflict): string
    {
        return trim(
            strval($conflict['medico_nombres'] ?? '')
            . ' '
            . strval($conflict['medico_apellidos'] ?? '')
        ) ?: 'seleccionado';
    }

    private function conflictPatientName(array $conflict): string
    {
        return trim(
            strval($conflict['paciente_nombres'] ?? '')
            . ' '
            . strval($conflict['paciente_apellidos'] ?? '')
        ) ?: 'seleccionado';
    }

    private function conflictTime(array $conflict): string
    {
        $timestamp = strtotime(strval($conflict['fecha_hora'] ?? ''));
        return $timestamp !== false ? date('H:i', $timestamp) : 'la hora indicada';
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

    private function getTimeOptions(
        string $selectedTime = '',
        int $medicoId = 0,
        string $date = '',
        int $excludeId = 0,
        int $pacienteId = 0
    ): array {
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
            $blockedTimes = DaoCitas::getBookedTimeSlots(
                $medicoId,
                $date,
                $excludeId,
                $pacienteId
            );
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

    /**
     * Builds the visible autocomplete label for one appointment.
     */
    private function buildAppointmentSearchLabel(array $appointment): string
    {
        $patient = trim(
            strval($appointment['paciente_nombres'] ?? '')
            . ' '
            . strval($appointment['paciente_apellidos'] ?? '')
        );
        if ($patient === '') {
            $patient = 'Paciente sin nombre';
        }

        return 'Cita #'
            . (int) ($appointment['id'] ?? 0)
            . ' · '
            . $patient;
    }

    /**
     * Combines every field supported by the appointment search bar.
     */
    private function buildAppointmentSearchText(array $appointment): string
    {
        return implode(' ', [
            $this->buildAppointmentSearchLabel($appointment),
            strval($appointment['paciente_nombres'] ?? ''),
            strval($appointment['paciente_apellidos'] ?? ''),
            strval($appointment['medico_nombres'] ?? ''),
            strval($appointment['medico_apellidos'] ?? ''),
            strval($appointment['nombre_especialidad'] ?? ''),
            strval($appointment['centro_codigo'] ?? ''),
            strval($appointment['centro_nombre'] ?? ''),
            strval($appointment['centro_tipo'] ?? ''),
            strval($appointment['centro_ciudad'] ?? ''),
            strval($appointment['consultorio'] ?? ''),
            strval($appointment['fecha_hora'] ?? ''),
            strval($appointment['nombre_estado'] ?? '')
        ]);
    }

    /**
     * Makes appointment searches case-insensitive and accent-insensitive.
     */
    private function normalizeAppointmentSearch(string $text): string
    {
        $withoutAccents = strtr(trim($text), [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        return function_exists('mb_strtolower')
            ? mb_strtolower($withoutAccents, 'UTF-8')
            : strtolower($withoutAccents);
    }

    /**
     * Applies the same five-row, post-filter pagination used by Médicos.
     */
    private function paginateAppointments(
        array $items,
        int $perPage,
        string $parameterName
    ): array {
        $totalPages = max(
            1,
            (int) ceil(count($items) / $perPage)
        );
        $currentPage = Validators::sanitizeInt(
            $_GET[$parameterName] ?? 1,
            1,
            $totalPages
        ) ?? 1;
        $offset = ($currentPage - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'paginaActual' => $currentPage,
            'totalPaginas' => $totalPages
        ];
    }
}
