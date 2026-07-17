<?php
namespace Controllers;

use Views\Renderer;
use Dao\Pacientes as DaoPacientes;
use Utilities\Security;
use Utilities\Site;
use Utilities\AuditLogger;

class PacientesController extends PublicController
{
    private array $viewData = [];

    public function run(): void
    {
        $action = $_GET["action"] ?? "index";
        $action = trim(strval($action));

        switch ($action) {
            case "index":
                $this->index();
                break;

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
        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);
        $showCrudActions = Security::isAuthorized($userId, 'PacientesController', 'CTR') || $isAdmin;

        $search = \Utilities\Validators::sanitizeString($_GET["search"] ?? "");
        $pacientes = DaoPacientes::getAllPacientes();
        if ($search !== "") {
            $searchLower = strtolower($search);
            $pacientes = array_filter($pacientes, function ($item) use ($searchLower) {
                return strpos(strtolower($item["identidad"] ?? ""), $searchLower) !== false ||
                    strpos(strtolower($item["nombres"] ?? ""), $searchLower) !== false ||
                    strpos(strtolower($item["apellidos"] ?? ""), $searchLower) !== false ||
                    strpos(strtolower($item["telefono"] ?? ""), $searchLower) !== false ||
                    strpos(strtolower($item["direccion"] ?? ""), $searchLower) !== false;
            });
        }

        $this->viewData["pacientes"] = array_values($pacientes);
        $this->viewData["showCrudActions"] = $showCrudActions;
        $this->viewData["searchValue"] = $search;
        Renderer::render("pacientes", $this->viewData);
    }

    private function create(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("paciente_create", [
                    "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                ]);
                return;
            }

            $identidad = \Utilities\Validators::sanitizeAlphaNum($_POST["identidad"] ?? "");
            $nombres = \Utilities\Validators::sanitizeString($_POST["nombres"] ?? "");
            $apellidos = \Utilities\Validators::sanitizeString($_POST["apellidos"] ?? "");
            $fechaNacimiento = \Utilities\Validators::sanitizeDate($_POST["fecha_nacimiento"] ?? "");
            $telefono = \Utilities\Validators::sanitizeString($_POST["telefono"] ?? "");
            $direccion = \Utilities\Validators::sanitizeString($_POST["direccion"] ?? "");

            if ($identidad === "" || $nombres === "" || $apellidos === "" || $fechaNacimiento === null) {
                $this->viewData["error"] = "Todos los campos obligatorios deben ser válidos.";
                Renderer::render("paciente_create", $this->viewData);
                return;
            }

            $newId = DaoPacientes::insertPaciente(
                $identidad,
                $nombres,
                $apellidos,
                $fechaNacimiento,
                $telefono,
                $direccion
            );
            AuditLogger::log('crear', 'Pacientes', 'Paciente creado: ' . $nombres . ' ' . $apellidos, ['paciente_id' => $newId]);

            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }

        Renderer::render("paciente_create", []);
    }

    private function authorizeCrud(): void
    {
        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);

        if (!Security::isAuthorized($userId, 'PacientesController', 'CTR') && !$isAdmin) {
            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }
    }

    private function edit(): void
    {
        $this->authorizeCrud();

        $id = \Utilities\Validators::sanitizeId($_GET["id"] ?? 0);

        if ($id === null || $id <= 0) {
            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                $paciente = DaoPacientes::getPacienteById($id);
                Renderer::render("paciente_edit", [
                    "paciente" => $paciente,
                    "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                ]);
                return;
            }

            $identidad = \Utilities\Validators::sanitizeAlphaNum($_POST["identidad"] ?? "");
            $nombres = \Utilities\Validators::sanitizeString($_POST["nombres"] ?? "");
            $apellidos = \Utilities\Validators::sanitizeString($_POST["apellidos"] ?? "");
            $fechaNacimiento = \Utilities\Validators::sanitizeDate($_POST["fecha_nacimiento"] ?? "");
            $telefono = \Utilities\Validators::sanitizeString($_POST["telefono"] ?? "");
            $direccion = \Utilities\Validators::sanitizeString($_POST["direccion"] ?? "");

            if ($identidad === "" || $nombres === "" || $apellidos === "" || $fechaNacimiento === null) {
                $paciente = DaoPacientes::getPacienteById($id);
                $this->viewData["error"] = "Todos los campos obligatorios deben ser válidos.";
                Renderer::render("paciente_edit", ["paciente" => $paciente, "error" => "Datos inválidos."]);
                return;
            }

            DaoPacientes::updatePaciente(
                $id,
                $identidad,
                $nombres,
                $apellidos,
                $fechaNacimiento,
                $telefono,
                $direccion
            );
            AuditLogger::log('editar', 'Pacientes', 'Paciente actualizado: ' . $nombres . ' ' . $apellidos, ['paciente_id' => $id]);

            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }

        $paciente = DaoPacientes::getPacienteById($id);

        Renderer::render(
            "paciente_edit",
            ["paciente" => $paciente]
        );
    }

    private function delete(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }

        $id = \Utilities\Validators::sanitizeId($_POST["id"] ?? 0);

        if ($id > 0) {
            $paciente = DaoPacientes::getPacienteById($id);
            DaoPacientes::deletePaciente($id);
            AuditLogger::log('eliminar', 'Pacientes', 'Paciente eliminado: ' . (($paciente['nombres'] ?? '') . ' ' . ($paciente['apellidos'] ?? '')), ['paciente_id' => $id]);
        }

        Site::redirectTo("index.php?page=PacientesController&action=index");
        exit;
    }
}