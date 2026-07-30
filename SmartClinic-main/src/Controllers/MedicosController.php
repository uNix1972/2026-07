<?php

namespace Controllers;

use Dao\CentroSalud as DaoCentroSalud;
use Dao\Especialidad as DaoEspecialidad;
use Dao\MedicoCentroSalud as DaoMedicoCentroSalud;
use Dao\Medicos as DaoMedicos;
use Utilities\AuditLogger;
use Utilities\MessageNotifier;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class MedicosController extends PublicController
{
    private array $viewData = [];

    public function run(): void
    {
        $action = trim(strval($_GET["action"] ?? "index"));

        switch ($action) {
            case "create":
                $this->create();
                break;
            case "edit":
                $this->edit();
                break;
            case "delete":
                $this->delete();
                break;
            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        $search = Validators::sanitizeString($_GET["search"] ?? "");
        $especialidad = Validators::sanitizeString($_GET["especialidad"] ?? "");
        $medicos = DaoMedicos::getAllMedicos();

        foreach ($medicos as &$medico) {
            $centrosTexto = (string) ($medico["centros_salud"] ?? "");
            $medico["centros_salud_texto"] = $centrosTexto !== ""
                ? $centrosTexto
                : "Sin centro asignado";

            // La columna "Centros / Consultorios" viene de un GROUP_CONCAT
            // ("Centro - Consultorio X, Centro - Consultorio Y") pensado
            // para búsqueda de texto, no para mostrarlo tal cual cuando un
            // médico tiene varios centros asignados. Se separa en una
            // lista para poder pintar cada centro en su propia fila/chip.
            $medico["centros_lista"] = [];
            if ($centrosTexto !== "") {
                foreach (explode(", ", $centrosTexto) as $item) {
                    $partes = explode(" - Consultorio ", $item, 2);
                    $medico["centros_lista"][] = [
                        "centro_nombre" => $partes[0] ?? $item,
                        "consultorio" => $partes[1] ?? "",
                    ];
                }
            }
            $medico["tieneCentros"] = count($medico["centros_lista"]) > 0;
        }
        unset($medico);

        if ($search !== "" || $especialidad !== "") {
            $searchLower = strtolower($search);
            $especialidadLower = strtolower($especialidad);
            $medicos = array_filter(
                $medicos,
                function (array $item) use ($searchLower, $especialidadLower): bool {
                    $matchSearch = $searchLower === ""
                        || strpos(strtolower($item["nombres"] ?? ""), $searchLower) !== false
                        || strpos(strtolower($item["apellidos"] ?? ""), $searchLower) !== false
                        || strpos(strtolower($item["nombre_especialidad"] ?? ""), $searchLower) !== false
                        || strpos(strtolower($item["num_colegiatura"] ?? ""), $searchLower) !== false
                        || strpos(strtolower($item["centros_salud"] ?? ""), $searchLower) !== false;
                    $matchEspecialidad = $especialidadLower === ""
                        || strpos(
                            strtolower($item["nombre_especialidad"] ?? ""),
                            $especialidadLower
                        ) !== false;

                    return $matchSearch && $matchEspecialidad;
                }
            );
        }

        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);
        $this->viewData["medicos"] = array_values($medicos);
        $this->viewData["showCrudActions"] =
            Security::isAuthorized($userId, "MedicosController", "CTR") || $isAdmin;
        $this->viewData["canSchedule"] =
            Security::isLogged() && !$this->viewData["showCrudActions"];
        $this->viewData["searchValue"] = $search;
        $this->viewData["especialidadFilter"] = $especialidad;
        $this->viewData["consultorioNotice"] =
            $_SESSION["medicos_consultorio_notice"] ?? "";
        unset($_SESSION["medicos_consultorio_notice"]);
        $this->viewData["especialidades"] = array_merge(
            [[
                "id" => 0,
                "nombre_especialidad" => "Todas",
                "selected" => $especialidad === ""
            ]],
            array_map(
                function (array $item) use ($especialidad): array {
                    $item["selected"] =
                        strcasecmp($item["nombre_especialidad"], $especialidad) === 0;
                    return $item;
                },
                DaoEspecialidad::getAllEspecialidades()
            )
        );

        Renderer::render("medicos", $this->viewData);
    }

    private function create(): void
    {
        $this->authorizeCrud();
        $data = $this->emptyDoctorData();
        $assignments = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $this->readDoctorData();
            $assignmentResult = $this->readAssignments();
            $assignments = $assignmentResult["items"];

            if (!Security::validateCsrfPost()) {
                $this->renderCreate(
                    $data,
                    $assignments,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $error = $this->validateDoctorData($data);
            if ($error === null) {
                $error = $assignmentResult["error"];
            }

            if ($error !== null) {
                $this->renderCreate($data, $assignments, $error);
                return;
            }

            try {
                $newId = DaoMedicos::insertMedicoConCentros(
                    $data["especialidad_id"],
                    $data["nombres"],
                    $data["apellidos"],
                    $data["num_colegiatura"],
                    $data["telefono"],
                    $assignments
                );

                AuditLogger::log(
                    "crear",
                    "Médicos",
                    "Médico creado: " . $data["nombres"] . " " . $data["apellidos"],
                    [
                        "medico_id" => $newId,
                        "centro_salud_ids" => array_column($assignments, "centro_salud_id")
                    ]
                );
            } catch (\Throwable $error) {
                error_log("No se pudo crear el médico con sus centros: " . $error->getMessage());
                $this->renderCreate(
                    $data,
                    $assignments,
                    "No fue posible guardar el médico. Verifique los datos e intente nuevamente."
                );
                return;
            }

            Site::redirectTo("index.php?page=MedicosController&action=index");
            exit;
        }

        $this->renderCreate($data, $assignments);
    }

    private function edit(): void
    {
        $this->authorizeCrud();
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $medico = $id !== null ? DaoMedicos::getMedicoById($id) : false;

        if (!$medico) {
            Site::redirectTo("index.php?page=MedicosController&action=index");
            exit;
        }

        $currentAssignments =
            DaoMedicoCentroSalud::getActivosByMedico($id);
        $assignments = $currentAssignments;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $medico = array_merge($medico, $this->readDoctorData());
            $assignmentResult = $this->readAssignments($id);
            $assignments = $assignmentResult["items"];

            if (!Security::validateCsrfPost()) {
                $this->renderEdit(
                    $medico,
                    $assignments,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $error = $this->validateDoctorData($medico, $id);
            if ($error === null) {
                $error = $assignmentResult["error"];
            }
            if ($error === null) {
                $error =
                    $this->validateFutureAppointmentAssignmentRemovals(
                        $id,
                        $currentAssignments,
                        $assignments
                    );

                if ($error !== null) {
                    AuditLogger::log(
                        "bloqueado",
                        "Médicos",
                        "Cambio de asignación bloqueado por citas futuras: "
                            . $medico["nombres"]
                            . " "
                            . $medico["apellidos"],
                        [
                            "medico_id" => $id,
                            "motivo" => $error
                        ]
                    );
                }
            }

            if ($error !== null) {
                $this->renderEdit($medico, $assignments, $error);
                return;
            }

            try {
                $consultorioMoves = [];
                DaoMedicos::updateMedicoConCentros(
                    $id,
                    $medico["especialidad_id"],
                    $medico["nombres"],
                    $medico["apellidos"],
                    $medico["num_colegiatura"],
                    $medico["telefono"],
                    $assignments,
                    $consultorioMoves
                );

                $notificationSummary =
                    $this->notifyConsultorioMoves($consultorioMoves);
                if ($notificationSummary !== null) {
                    $_SESSION["medicos_consultorio_notice"] =
                        $notificationSummary;
                }

                AuditLogger::log(
                    "editar",
                    "Médicos",
                    "Médico actualizado: " . $medico["nombres"] . " " . $medico["apellidos"],
                    [
                        "medico_id" => $id,
                        "centro_salud_ids" => array_column($assignments, "centro_salud_id"),
                        "citas_consultorio_actualizadas" =>
                            count($consultorioMoves)
                    ]
                );
            } catch (\Throwable $error) {
                error_log("No se pudo actualizar el médico con sus centros: " . $error->getMessage());
                $this->renderEdit(
                    $medico,
                    $assignments,
                    "No fue posible actualizar el médico. Verifique los datos e intente nuevamente."
                );
                return;
            }

            Site::redirectTo("index.php?page=MedicosController&action=index");
            exit;
        }

        $this->renderEdit($medico, $assignments);
    }

    private function delete(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=MedicosController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);

        if ($id !== null) {
            $medico = DaoMedicos::getMedicoById($id);
            DaoMedicos::deleteMedico($id);
            AuditLogger::log(
                "eliminar",
                "Médicos",
                "Médico eliminado: "
                    . (($medico["nombres"] ?? "") . " " . ($medico["apellidos"] ?? "")),
                ["medico_id" => $id]
            );
        }

        Site::redirectTo("index.php?page=MedicosController&action=index");
        exit;
    }

    private function authorizeCrud(): void
    {
        if (!Security::isAuthorized(Security::getUserId(), "MedicosController", "CTR")) {
            Site::redirectTo("index.php?page=MedicosController&action=index");
            exit;
        }
    }

    private function emptyDoctorData(): array
    {
        return [
            "especialidad_id" => 0,
            "nombres" => "",
            "apellidos" => "",
            "num_colegiatura" => "",
            "telefono" => ""
        ];
    }

    private function readDoctorData(): array
    {
        return [
            "especialidad_id" => Validators::sanitizeId($_POST["especialidad_id"] ?? 0) ?? 0,
            "nombres" => Validators::sanitizeString($_POST["nombres"] ?? "", 100),
            "apellidos" => Validators::sanitizeString($_POST["apellidos"] ?? "", 100),
            "num_colegiatura" =>
                Validators::sanitizeString($_POST["num_colegiatura"] ?? "", 50),
            "telefono" => Validators::sanitizeString($_POST["telefono"] ?? "", 20)
        ];
    }

    private function validateDoctorData(array $data, int $excludeId = 0): ?string
    {
        $specialtyIds = array_map(
            "intval",
            array_column(DaoEspecialidad::getAllEspecialidades(), "id")
        );

        if (!in_array((int) $data["especialidad_id"], $specialtyIds, true)) {
            return "Seleccione una especialidad válida.";
        }
        if ($data["nombres"] === "" || $data["apellidos"] === "") {
            return "Los nombres y apellidos son obligatorios.";
        }
        if ($data["num_colegiatura"] === "" || $data["telefono"] === "") {
            return "El número de colegiatura y el teléfono son obligatorios.";
        }
        if (DaoMedicos::existsNumColegiatura($data["num_colegiatura"], $excludeId)) {
            return "Ya existe un médico con ese número de colegiatura.";
        }

        return null;
    }

    /**
     * Envía las notificaciones después de confirmar el cambio en la base.
     *
     * Una falla externa no revierte el consultorio ya guardado. Cada resultado
     * queda auditado y el resumen se muestra al administrador en el listado.
     */
    private function notifyConsultorioMoves(array $appointments): ?string
    {
        if (count($appointments) === 0) {
            return null;
        }

        $sent = 0;
        $failed = 0;
        foreach ($appointments as $appointment) {
            try {
                $wasSent =
                    MessageNotifier::sendAppointmentRoomChanged($appointment);
            } catch (\Throwable $error) {
                error_log(
                    "No se pudo notificar el cambio de consultorio de la cita "
                    . intval($appointment["id"] ?? 0)
                    . ": "
                    . $error->getMessage()
                );
                $wasSent = false;
            }

            $wasSent ? $sent++ : $failed++;
            AuditLogger::log(
                $wasSent
                    ? "notificar-cambio-consultorio"
                    : "notificacion-consultorio-fallida",
                "Citas",
                $wasSent
                    ? "Paciente notificado por cambio de consultorio"
                    : "No se pudo notificar el cambio de consultorio",
                [
                    "cita_id" => intval($appointment["id"] ?? 0),
                    "paciente_id" =>
                        intval($appointment["paciente_id"] ?? 0),
                    "medico_id" =>
                        intval($appointment["medico_id"] ?? 0),
                    "centro_salud_id" =>
                        intval($appointment["centro_salud_id"] ?? 0),
                    "consultorio_anterior" =>
                        strval($appointment["consultorio_anterior"] ?? ""),
                    "consultorio_nuevo" =>
                        strval($appointment["consultorio"] ?? "")
                ]
            );
        }

        $total = count($appointments);
        return "Consultorio actualizado en "
            . $total
            . ($total === 1 ? " cita futura." : " citas futuras.")
            . " Notificaciones enviadas: "
            . $sent
            . ". No enviadas: "
            . $failed
            . ".";
    }

    /**
     * Impide retirar una asignación usada por citas futuras.
     *
     * Cambiar únicamente el consultorio está permitido aunque existan citas
     * futuras. La relación médico-centro sí debe permanecer activa hasta que
     * esas citas sean reasignadas, canceladas o finalizadas.
     */
    private function validateFutureAppointmentAssignmentRemovals(
        int $medicoId,
        array $currentAssignments,
        array $requestedAssignments
    ): ?string {
        $requestedCenterIds = [];
        foreach ($requestedAssignments as $assignment) {
            $requestedCenterIds[(int) $assignment["centro_salud_id"]] = true;
        }

        foreach ($currentAssignments as $currentAssignment) {
            $centerId = (int) $currentAssignment["centro_salud_id"];
            $assignmentRemoved =
                !array_key_exists($centerId, $requestedCenterIds);

            if (!$assignmentRemoved) {
                continue;
            }

            $appointmentSummary =
                DaoMedicoCentroSalud::getFutureActiveAppointmentSummary(
                    $medicoId,
                    $centerId
                );
            $futureAppointments =
                (int) ($appointmentSummary["total"] ?? 0);

            if ($futureAppointments === 0) {
                continue;
            }

            $nextAppointment = strval(
                $appointmentSummary["proxima_fecha"] ?? ""
            );
            $nextAppointmentText = $nextAppointment !== ""
                ? date("d/m/Y H:i", strtotime($nextAppointment))
                : "fecha no disponible";
            $centerName = strval(
                $currentAssignment["centro_nombre"]
                    ?? "el centro seleccionado"
            );
            return "No se puede retirar la asignación de "
                . $centerName
                . ": el médico tiene "
                . $futureAppointments
                . ($futureAppointments === 1
                    ? " cita futura activa"
                    : " citas futuras activas")
                . " en ese centro. La próxima está programada para "
                . $nextAppointmentText
                . ". Reasigne o cancele estas citas antes de modificar "
                . "la asignación.";
        }

        return null;
    }

    private function readAssignments(int $excludeMedicoId = 0): array
    {
        $selectedIds = $_POST["centro_ids"] ?? [];
        $consultorios = $_POST["consultorios"] ?? [];
        $activeCenters = DaoCentroSalud::getActivos();
        $centerNames = [];
        foreach ($activeCenters as $activeCenter) {
            $centerNames[(int) $activeCenter["id"]] =
                (string) $activeCenter["nombre"];
        }
        $allowedIds = array_fill_keys(
            array_map("intval", array_column($activeCenters, "id")),
            true
        );
        $items = [];
        $seen = [];

        if (!is_array($selectedIds) || !is_array($consultorios)) {
            return [
                "items" => [],
                "error" => "La selección de centros de salud no es válida."
            ];
        }

        foreach ($selectedIds as $rawId) {
            $centerId = Validators::sanitizeId($rawId);

            if ($centerId === null || !isset($allowedIds[$centerId])) {
                return [
                    "items" => $items,
                    "error" => "Uno de los centros seleccionados no está activo o no existe."
                ];
            }
            if (isset($seen[$centerId])) {
                continue;
            }

            $consultorioRaw = trim(strval($consultorios[$centerId] ?? ""));
            $length = function_exists("mb_strlen")
                ? mb_strlen($consultorioRaw, "UTF-8")
                : strlen($consultorioRaw);

            if ($consultorioRaw === "") {
                return [
                    "items" => $items,
                    "error" => "Indique el consultorio de cada centro seleccionado."
                ];
            }
            if ($length > 30) {
                return [
                    "items" => $items,
                    "error" => "El consultorio no puede exceder 30 caracteres."
                ];
            }

            $consultorio = Validators::sanitizeString($consultorioRaw, 30);
            if ($consultorio === "") {
                return [
                    "items" => $items,
                    "error" => "El consultorio contiene un valor inválido."
                ];
            }

            $roomConflict =
                DaoMedicoCentroSalud::findActiveConsultorioConflict(
                    $centerId,
                    $consultorio,
                    $excludeMedicoId
                );
            if ($roomConflict) {
                $doctorName = trim(
                    strval($roomConflict["medico_nombres"] ?? "")
                    . " "
                    . strval($roomConflict["medico_apellidos"] ?? "")
                );
                return [
                    "items" => $items,
                    "error" => "El consultorio "
                        . $consultorio
                        . " de "
                        . ($centerNames[$centerId] ?? "ese centro")
                        . " ya está asignado al médico "
                        . ($doctorName !== "" ? $doctorName : "indicado")
                        . "."
                ];
            }

            $items[] = [
                "centro_salud_id" => $centerId,
                "consultorio" => $consultorio
            ];
            $seen[$centerId] = true;
        }

        if (count($items) === 0) {
            $message = count($activeCenters) === 0
                ? "Primero debe registrar al menos un centro de salud activo."
                : "Seleccione al menos un centro de salud e indique su consultorio.";

            return ["items" => [], "error" => $message];
        }

        return ["items" => $items, "error" => null];
    }

    private function buildEspecialidades(int $selectedId): array
    {
        return array_map(
            function (array $especialidad) use ($selectedId): array {
                $especialidad["selected"] = (int) $especialidad["id"] === $selectedId;
                return $especialidad;
            },
            DaoEspecialidad::getAllEspecialidades()
        );
    }

    private function buildCentros(array $assignments): array
    {
        $assignmentMap = [];
        foreach ($assignments as $assignment) {
            $assignmentMap[(int) $assignment["centro_salud_id"]] =
                (string) $assignment["consultorio"];
        }

        return array_map(
            function (array $centro) use ($assignmentMap): array {
                $id = (int) $centro["id"];
                $centro["selected"] = array_key_exists($id, $assignmentMap);
                $centro["consultorio"] = $assignmentMap[$id] ?? "";
                return $centro;
            },
            DaoCentroSalud::getActivos()
        );
    }

    private function renderCreate(
        array $data,
        array $assignments,
        ?string $error = null
    ): void {
        $this->renderForm("medico_create", $data, $assignments, $error);
    }

    private function renderEdit(
        array $data,
        array $assignments,
        ?string $error = null
    ): void {
        $this->renderForm("medico_edit", $data, $assignments, $error);
    }

    private function renderForm(
        string $view,
        array $data,
        array $assignments,
        ?string $error
    ): void {
        $centros = $this->buildCentros($assignments);
        $viewData = array_merge($data, [
            "especialidades" =>
                $this->buildEspecialidades((int) $data["especialidad_id"]),
            "centros" => $centros,
            "sinCentros" => count($centros) === 0,
            "puedeGuardar" => count($centros) > 0,
            "error" => $error
        ]);

        Renderer::render($view, $viewData);
    }
}
