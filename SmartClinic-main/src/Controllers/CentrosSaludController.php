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
        $centros = DaoCentroSalud::getAll($search);
        $statusError =
            strval($_SESSION["centros_salud_status_error"] ?? "");
        unset($_SESSION["centros_salud_status_error"]);
        $numeroFila = 1;

        foreach ($centros as &$centro) {
            $centro["numero_fila"] = $numeroFila++;
            $centro["activo"] = $centro["estado"] === "ACT";
            $centro["inactivo"] = $centro["estado"] !== "ACT";
            $centro["estado_texto"] = $centro["activo"] ? "Activo" : "Inactivo";
        }
        unset($centro);

        Renderer::render("centros_salud", [
            "centros" => $centros,
            "searchValue" => $search,
            "statusError" => $statusError
        ]);
    }

    private function create(): void
    {
        $data = $this->emptyForm();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                $this->renderCreate($data, "Solicitud inválida o expirada. Recargue la página e intente nuevamente.");
                return;
            }

            $data = $this->readForm();
            $error = $this->validateForm($data);

            if ($error !== null) {
                $this->renderCreate($data, $error);
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
                "Centro de salud creado: " . $data["codigo"] . " - " . $data["nombre"],
                ["centro_salud_id" => $newId]
            );

            Site::redirectTo("index.php?page=CentrosSaludController&action=index");
            exit;
        }

        $this->renderCreate($data);
    }

    private function edit(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $centro = $id !== null ? DaoCentroSalud::getById($id) : false;

        if (!$centro) {
            Site::redirectTo("index.php?page=CentrosSaludController&action=index");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                $this->renderEdit($centro, "Solicitud inválida o expirada. Recargue la página e intente nuevamente.");
                return;
            }

            $data = $this->readForm();
            $data["id"] = $id;
            $error = $this->validateForm($data, $id);

            if ($error !== null) {
                $this->renderEdit($data, $error);
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
                "Centro de salud actualizado: " . $data["codigo"] . " - " . $data["nombre"],
                ["centro_salud_id" => $id]
            );

            Site::redirectTo("index.php?page=CentrosSaludController&action=index");
            exit;
        }

        $this->renderEdit($centro);
    }

    private function status(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=CentrosSaludController&action=index");
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

                    Site::redirectTo(
                        "index.php?page=CentrosSaludController&action=index"
                    );
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
                $descripcion . $centro["codigo"] . " - " . $centro["nombre"],
                ["centro_salud_id" => $id]
            );
        }

        Site::redirectTo("index.php?page=CentrosSaludController&action=index");
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
        $email = $emailRaw === "" ? "" : Validators::sanitizeEmail($emailRaw);

        return [
            "codigo" => strtoupper(Validators::sanitizeAlphaNum($_POST["codigo"] ?? "", 30)),
            "nombre" => Validators::sanitizeString($_POST["nombre"] ?? "", 150),
            "tipo" => Validators::sanitizeString($_POST["tipo"] ?? "", 50),
            "direccion" => Validators::sanitizeString($_POST["direccion"] ?? "", 255),
            "ciudad" => Validators::sanitizeString($_POST["ciudad"] ?? "", 100),
            "telefono" => Validators::sanitizeString($_POST["telefono"] ?? "", 20),
            "email" => $email ?? "",
            "email_invalido" => $emailRaw !== "" && $email === null
        ];
    }

    private function validateForm(array $data, int $excludeId = 0): ?string
    {
        if (strlen($data["codigo"]) < 2) {
            return "El código debe contener al menos dos caracteres alfanuméricos.";
        }
        if ($data["nombre"] === "" || $data["direccion"] === "" || $data["ciudad"] === "") {
            return "Nombre, dirección y ciudad son obligatorios.";
        }
        if (!in_array($data["tipo"], self::TIPOS, true)) {
            return "Seleccione un tipo de centro válido.";
        }
        if ($data["email_invalido"]) {
            return "El correo electrónico no tiene un formato válido.";
        }
        if (DaoCentroSalud::existsCodigo($data["codigo"], $excludeId)) {
            return "Ya existe un centro de salud con ese código.";
        }

        return null;
    }

    private function buildTipos(string $selected): array
    {
        return array_map(function (string $tipo) use ($selected) {
            return [
                "valor" => $tipo,
                "selected" => $tipo === $selected
            ];
        }, self::TIPOS);
    }

    private function renderCreate(array $data, ?string $error = null): void
    {
        $data["tipos"] = $this->buildTipos($data["tipo"]);
        $data["error"] = $error;
        Renderer::render("centro_salud_create", $data);
    }

    private function renderEdit(array $data, ?string $error = null): void
    {
        $data["tipos"] = $this->buildTipos($data["tipo"]);
        $data["error"] = $error;
        Renderer::render("centro_salud_edit", $data);
    }
}
