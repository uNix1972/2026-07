<?php

namespace Controllers;

use Dao\CentroSalud as DaoCentroSalud;
use Utilities\AuditLogger;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class CentrosSaludController extends PrivateController
{
    private const TIPOS = [
        "Centro de Salud",
        "Clínica",
        "Hospital",
        "Consultorio"
    ];

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
            case "status":
                $this->status();
                break;
            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        $search = Validators::sanitizeString($_GET["search"] ?? "", 100);
        $status = $this->sanitizeStatusFilter(
            strval($_GET["status"] ?? "")
        );
        $editId = Validators::sanitizeId($_GET["edit_id"] ?? 0);
        $formData = $this->emptyForm();

        if ($editId !== null) {
            $centro = DaoCentroSalud::getById($editId);
            if ($centro) {
                $formData = $centro;
            } else {
                $editId = null;
            }
        }

        $this->renderWorkspace(
            $formData,
            null,
            $editId ?? 0,
            $search,
            $status
        );
    }

    private function create(): void
    {
        $data = $this->emptyForm();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Site::redirectTo($this->buildIndexUrl());
            exit;
        }

        $search = Validators::sanitizeString(
            $_POST["return_search"] ?? "",
            100
        );
        $status = $this->sanitizeStatusFilter(
            strval($_POST["return_status"] ?? "")
        );

        if (!Security::validateCsrfPost()) {
            $this->renderWorkspace(
                $data,
                "Solicitud inválida o expirada. Recargue la página e intente nuevamente.",
                0,
                $search,
                $status
            );
            return;
        }

        $data = $this->readForm();
        $error = $this->validateForm($data);

        if ($error !== null) {
            $this->renderWorkspace(
                $data,
                $error,
                0,
                $search,
                $status
            );
            return;
        }

        $newId = DaoCentroSalud::insert(
            $data["codigo"],
            $data["nombre"],
            $data["tipo"],
            $data["direccion"],
            $data["ciudad"],
            $data["telefono"],
            $data["email"]
        );

        AuditLogger::log(
            "crear",
            "Centros de Salud",
            "Centro de salud creado: "
                . $data["codigo"]
                . " - "
                . $data["nombre"],
            ["centro_salud_id" => $newId]
        );

        $_SESSION["centros_salud_success"] =
            "Centro de salud creado correctamente.";
        Site::redirectTo(
            $this->buildIndexUrl($search, $status, $newId)
        );
        exit;
    }

    private function edit(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $centro = $id !== null ? DaoCentroSalud::getById($id) : false;

        if (!$centro) {
            Site::redirectTo($this->buildIndexUrl());
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Site::redirectTo($this->buildIndexUrl("", "", $id));
            exit;
        }

        $search = Validators::sanitizeString(
            $_POST["return_search"] ?? "",
            100
        );
        $status = $this->sanitizeStatusFilter(
            strval($_POST["return_status"] ?? "")
        );

        if (!Security::validateCsrfPost()) {
            $this->renderWorkspace(
                $centro,
                "Solicitud inválida o expirada. Recargue la página e intente nuevamente.",
                $id,
                $search,
                $status
            );
            return;
        }

        $data = $this->readForm();
        $data["id"] = $id;
        $error = $this->validateForm($data, $id);

        if ($error !== null) {
            $this->renderWorkspace(
                $data,
                $error,
                $id,
                $search,
                $status
            );
            return;
        }

        DaoCentroSalud::update(
            $id,
            $data["codigo"],
            $data["nombre"],
            $data["tipo"],
            $data["direccion"],
            $data["ciudad"],
            $data["telefono"],
            $data["email"]
        );

        AuditLogger::log(
            "editar",
            "Centros de Salud",
            "Centro de salud actualizado: "
                . $data["codigo"]
                . " - "
                . $data["nombre"],
            ["centro_salud_id" => $id]
        );

        $_SESSION["centros_salud_success"] =
            "Los cambios del centro se guardaron correctamente.";
        Site::redirectTo(
            $this->buildIndexUrl($search, $status, $id)
        );
        exit;
    }

    private function status(): void
    {
        $returnSearch = Validators::sanitizeString(
            $_POST["return_search"] ?? "",
            100
        );
        $returnStatus = $this->sanitizeStatusFilter(
            strval($_POST["return_status"] ?? "")
        );
        $returnEditId = Validators::sanitizeId(
            $_POST["return_edit_id"] ?? 0
        );
        $returnUrl = $this->buildIndexUrl(
            $returnSearch,
            $returnStatus,
            $returnEditId ?? 0
        );

        if (
            $_SERVER["REQUEST_METHOD"] !== "POST"
            || !Security::validateCsrfPost()
        ) {
            Site::redirectTo($returnUrl);
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        $estado = ($_POST["estado"] ?? "") === "ACT" ? "ACT" : "INA";
        $centro = $id !== null ? DaoCentroSalud::getById($id) : false;

        if ($centro) {
            if ($estado === "INA" && $centro["estado"] === "ACT") {
                $appointmentSummary =
                    DaoCentroSalud::getFutureActiveAppointmentSummary($id);
                $futureAppointments =
                    (int) ($appointmentSummary["total"] ?? 0);

                if ($futureAppointments > 0) {
                    $nextAppointment = strval(
                        $appointmentSummary["proxima_fecha"] ?? ""
                    );
                    $nextAppointmentText = $nextAppointment !== ""
                        ? date(
                            "d/m/Y H:i",
                            strtotime($nextAppointment)
                        )
                        : "fecha no disponible";

                    $_SESSION["centros_salud_status_error"] =
                        "No se puede desactivar "
                        . $centro["nombre"]
                        . ": tiene "
                        . $futureAppointments
                        . ($futureAppointments === 1
                            ? " cita futura activa"
                            : " citas futuras activas")
                        . ". La próxima está programada para "
                        . $nextAppointmentText
                        . ". Reasigne o cancele estas citas antes de "
                        . "desactivar el centro.";

                    AuditLogger::log(
                        "bloqueado",
                        "Centros de Salud",
                        "Desactivación bloqueada por citas futuras: "
                            . $centro["codigo"]
                            . " - "
                            . $centro["nombre"],
                        [
                            "centro_salud_id" => $id,
                            "citas_futuras_activas" =>
                                $futureAppointments,
                            "proxima_cita" => $nextAppointment
                        ]
                    );

                    Site::redirectTo($returnUrl);
                    exit;
                }
            }

            DaoCentroSalud::setStatus($id, $estado);
            $accion = $estado === "ACT" ? "activar" : "desactivar";
            $descripcion = $estado === "ACT"
                ? "Centro de salud activado: "
                : "Centro de salud desactivado: ";

            AuditLogger::log(
                $accion,
                "Centros de Salud",
                $descripcion
                    . $centro["codigo"]
                    . " - "
                    . $centro["nombre"],
                ["centro_salud_id" => $id]
            );
        }

        Site::redirectTo($returnUrl);
        exit;
    }

    private function emptyForm(): array
    {
        return [
            "codigo" => "",
            "nombre" => "",
            "tipo" => self::TIPOS[0],
            "direccion" => "",
            "ciudad" => "",
            "telefono" => "",
            "email" => "",
            "email_invalido" => false
        ];
    }

    private function readForm(): array
    {
        $emailRaw = trim(strval($_POST["email"] ?? ""));
        $email = $emailRaw === ""
            ? ""
            : Validators::sanitizeEmail($emailRaw);

        return [
            "codigo" => strtoupper(
                Validators::sanitizeAlphaNum(
                    $_POST["codigo"] ?? "",
                    30
                )
            ),
            "nombre" => Validators::sanitizeString(
                $_POST["nombre"] ?? "",
                150
            ),
            "tipo" => Validators::sanitizeString(
                $_POST["tipo"] ?? "",
                50
            ),
            "direccion" => Validators::sanitizeString(
                $_POST["direccion"] ?? "",
                255
            ),
            "ciudad" => Validators::sanitizeString(
                $_POST["ciudad"] ?? "",
                100
            ),
            "telefono" => Validators::sanitizeString(
                $_POST["telefono"] ?? "",
                20
            ),
            "email" => $email ?? "",
            "email_invalido" => $emailRaw !== "" && $email === null
        ];
    }

    private function validateForm(
        array $data,
        int $excludeId = 0
    ): ?string {
        if (strlen($data["codigo"]) < 2) {
            return "El código debe contener al menos dos caracteres alfanuméricos.";
        }
        if (
            $data["nombre"] === ""
            || $data["direccion"] === ""
            || $data["ciudad"] === ""
        ) {
            return "Nombre, dirección y ciudad son obligatorios.";
        }
        if (!in_array($data["tipo"], self::TIPOS, true)) {
            $currentCenter = $excludeId > 0
                ? DaoCentroSalud::getById($excludeId)
                : false;
            $keepsExistingType = $currentCenter
                && strval($currentCenter["tipo"] ?? "")
                    === $data["tipo"];

            if (!$keepsExistingType) {
                return "Seleccione un tipo de centro válido.";
            }
        }
        if ($data["email_invalido"]) {
            return "El correo electrónico no tiene un formato válido.";
        }
        if (
            DaoCentroSalud::existsCodigo(
                $data["codigo"],
                $excludeId
            )
        ) {
            return "Ya existe un centro de salud con ese código.";
        }

        return null;
    }

    private function buildTipos(string $selected): array
    {
        $types = self::TIPOS;
        if ($selected !== "" && !in_array($selected, $types, true)) {
            $types[] = $selected;
        }

        return array_map(
            function (string $tipo) use ($selected) {
                return [
                    "valor" => $tipo,
                    "etiqueta" => $this->formatTypeLabel($tipo),
                    "selected" => $tipo === $selected
                ];
            },
            $types
        );
    }

    /**
     * Corrects display-only labels from legacy seed values without rewriting
     * the stored value when an administrator edits another field.
     */
    private function formatTypeLabel(string $type): string
    {
        $labels = [
            "ClÃ­nica" => "Clínica",
            "Clinica Ambulatoria" => "Clínica Ambulatoria"
        ];

        return $labels[$type] ?? $type;
    }

    private function sanitizeStatusFilter(string $status): string
    {
        return in_array($status, ["ACT", "INA"], true)
            ? $status
            : "";
    }

    private function buildIndexUrl(
        string $search = "",
        string $status = "",
        int $editId = 0
    ): string {
        $params = [
            "page" => "CentrosSaludController",
            "action" => "index"
        ];

        if ($search !== "") {
            $params["search"] = $search;
        }
        if ($status !== "") {
            $params["status"] = $status;
        }
        if ($editId > 0) {
            $params["edit_id"] = $editId;
        }

        return "index.php?" . http_build_query($params);
    }

    /**
     * Renders creation, editing, search and status management in one page.
     */
    private function renderWorkspace(
        array $formData,
        ?string $error = null,
        int $editId = 0,
        string $search = "",
        string $status = ""
    ): void {
        $centros = DaoCentroSalud::getAll($search, $status);
        $numeroFila = 1;

        foreach ($centros as &$centro) {
            $centroId = (int) $centro["id"];
            $centro["numero_fila"] = $numeroFila++;
            $centro["activo"] = $centro["estado"] === "ACT";
            $centro["inactivo"] = !$centro["activo"];
            $centro["estado_texto"] =
                $centro["activo"] ? "Activo" : "Inactivo";
            $centro["tipo_texto"] = $this->formatTypeLabel(
                strval($centro["tipo"] ?? "")
            );
            $centro["selected"] = $editId === $centroId;
            $centro["telefono_texto"] =
                trim(strval($centro["telefono"] ?? "")) !== ""
                    ? $centro["telefono"]
                    : "Sin teléfono";
            $centro["edit_url"] =
                $this->buildIndexUrl(
                    $search,
                    $status,
                    $centroId
                )
                . "#centro-form";
        }
        unset($centro);

        $summary = DaoCentroSalud::getWorkspaceSummary();
        $formData["tipos"] = $this->buildTipos(
            strval($formData["tipo"] ?? self::TIPOS[0])
        );
        $formData["error"] = $error;

        $statusError = strval(
            $_SESSION["centros_salud_status_error"] ?? ""
        );
        $success = strval(
            $_SESSION["centros_salud_success"] ?? ""
        );
        unset(
            $_SESSION["centros_salud_status_error"],
            $_SESSION["centros_salud_success"]
        );

        Renderer::render(
            "centros_salud",
            array_merge(
                $formData,
                [
                    "centros" => $centros,
                    "searchValue" => $search,
                    "statusValue" => $status,
                    "statusAll" => $status === "",
                    "statusActive" => $status === "ACT",
                    "statusInactive" => $status === "INA",
                    "statusError" => $statusError,
                    "success" => $success,
                    "editing" => $editId > 0,
                    "creating" => $editId <= 0,
                    "formTitle" =>
                        $editId > 0
                            ? "Editar centro"
                            : "Nuevo centro",
                    "formSubtitle" =>
                        $editId > 0
                            ? "Actualice la información de la sede seleccionada."
                            : "Registre una nueva sede sin salir del listado.",
                    "submitLabel" =>
                        $editId > 0
                            ? "Guardar cambios"
                            : "Guardar centro",
                    "selectedId" => $editId,
                    "newUrl" =>
                        $this->buildIndexUrl($search, $status)
                        . "#centro-form",
                    "totalResultados" => count($centros),
                    "centrosActivos" =>
                        (int) (
                            $summary["centros_activos"] ?? 0
                        ),
                    "medicosAsignados" =>
                        (int) (
                            $summary["medicos_asignados"] ?? 0
                        ),
                    "citasHoy" =>
                        (int) ($summary["citas_hoy"] ?? 0)
                ]
            )
        );
    }
}
