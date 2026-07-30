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
        $this->viewData["msg"] = \Utilities\Validators::sanitizeString($_GET["msg"] ?? "");
        Renderer::render("pacientes", $this->viewData);
    }

    private function create(): void
    {
        $this->authorizeCrud();
        $data = $this->emptyPatientData();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $this->readPatientData();

            if (!Security::validateCsrfPost()) {
                $this->renderCreate(
                    $data,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $error = $this->validatePatientData($data);
            if ($error !== null) {
                $this->renderCreate($data, $error);
                return;
            }

            try {
                $newId = DaoPacientes::insertPaciente(
                    $data["identidad"],
                    $data["nombres"],
                    $data["apellidos"],
                    $data["fecha_nacimiento"],
                    $data["telefono"],
                    $data["direccion"]
                );
                AuditLogger::log(
                    'crear',
                    'Pacientes',
                    'Paciente creado: ' . $data["nombres"] . ' ' . $data["apellidos"],
                    ['paciente_id' => $newId]
                );
            } catch (\Throwable $e) {
                error_log("No se pudo crear el paciente: " . $e->getMessage());
                $this->renderCreate(
                    $data,
                    "No fue posible guardar el paciente. Verifique los datos e intente nuevamente."
                );
                return;
            }

            Site::redirectTo("index.php?page=PacientesController&action=index&msg=" . urlencode('Paciente registrado correctamente.'));
            exit;
        }

        $this->renderCreate($data);
    }

    /**
     * Valores por defecto para el formulario de "Registrar paciente" en
     * su primera carga (GET, sin datos posteados todavía).
     */
    private function emptyPatientData(): array
    {
        return [
            "identidad" => "",
            "nombres" => "",
            "apellidos" => "",
            "fecha_nacimiento" => "",
            "telefono" => "",
            "direccion" => "",
        ];
    }

    /**
     * Lee y sanitiza los campos del formulario de paciente desde $_POST,
     * respetando el largo máximo de cada columna en la base de datos.
     */
    private function readPatientData(): array
    {
        $fechaRaw = trim((string) ($_POST["fecha_nacimiento"] ?? ""));
        $fechaSanitizada = \Utilities\Validators::sanitizeDate($fechaRaw);

        return [
            "identidad" => \Utilities\Validators::sanitizeAlphaNum($_POST["identidad"] ?? "", 20),
            "nombres" => \Utilities\Validators::sanitizeString($_POST["nombres"] ?? "", 100),
            "apellidos" => \Utilities\Validators::sanitizeString($_POST["apellidos"] ?? "", 100),
            // Si el formato no es válido se conserva lo que escribió el
            // usuario (para no perderlo al recargar el formulario); la
            // validación real ocurre en validatePatientData().
            "fecha_nacimiento" => $fechaSanitizada ?? $fechaRaw,
            "telefono" => \Utilities\Validators::sanitizeString($_POST["telefono"] ?? "", 20),
            "direccion" => \Utilities\Validators::sanitizeString($_POST["direccion"] ?? "", 255),
        ];
    }

    /**
     * Valida los datos de un paciente (creación o edición). $excludeId se
     * usa al editar, para no rechazar la identidad contra el propio
     * registro que se está actualizando.
     */
    private function validatePatientData(array $data, int $excludeId = 0): ?string
    {
        if ($data["identidad"] === "" || strlen($data["identidad"]) < 5) {
            return "La identidad es obligatoria y debe tener al menos 5 caracteres.";
        }
        if ($data["nombres"] === "" || $data["apellidos"] === "") {
            return "Los nombres y apellidos son obligatorios.";
        }
        if ($data["telefono"] === "") {
            return "El teléfono es obligatorio.";
        }
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $data["telefono"])) {
            return "El teléfono no tiene un formato válido.";
        }
        if ($data["direccion"] === "") {
            return "La dirección es obligatoria.";
        }

        $fechaSanitizada = \Utilities\Validators::sanitizeDate($data["fecha_nacimiento"]);
        if ($fechaSanitizada === null) {
            return "La fecha de nacimiento es obligatoria y debe ser una fecha válida.";
        }
        $fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $fechaSanitizada);
        $hoy = new \DateTime('today');
        if ($fechaNacimiento > $hoy) {
            return "La fecha de nacimiento no puede ser una fecha futura.";
        }
        $fechaMinima = (clone $hoy)->modify('-120 years');
        if ($fechaNacimiento < $fechaMinima) {
            return "La fecha de nacimiento no es válida.";
        }

        if (DaoPacientes::existsIdentidad($data["identidad"], $excludeId)) {
            return "Ya existe un paciente registrado con esa identidad.";
        }

        return null;
    }

    private function renderCreate(array $data, ?string $error = null): void
    {
        Renderer::render("paciente_create", array_merge($data, [
            "error" => $error,
            "maxFechaNacimiento" => date('Y-m-d'),
            "minFechaNacimiento" => date('Y-m-d', strtotime('-120 years')),
        ]));
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

        $pacienteExistente = DaoPacientes::getPacienteById($id);
        if (!$pacienteExistente) {
            Site::redirectTo("index.php?page=PacientesController&action=index");
            exit;
        }

        $data = $this->patientToData($pacienteExistente);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $this->readPatientData();

            if (!Security::validateCsrfPost()) {
                $this->renderEdit(
                    $id,
                    $data,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $error = $this->validatePatientData($data, $id);
            if ($error !== null) {
                $this->renderEdit($id, $data, $error);
                return;
            }

            try {
                DaoPacientes::updatePaciente(
                    $id,
                    $data["identidad"],
                    $data["nombres"],
                    $data["apellidos"],
                    $data["fecha_nacimiento"],
                    $data["telefono"],
                    $data["direccion"]
                );
                AuditLogger::log(
                    'editar',
                    'Pacientes',
                    'Paciente actualizado: ' . $data["nombres"] . ' ' . $data["apellidos"],
                    ['paciente_id' => $id]
                );
            } catch (\Throwable $e) {
                error_log("No se pudo actualizar el paciente: " . $e->getMessage());
                $this->renderEdit(
                    $id,
                    $data,
                    "No fue posible actualizar el paciente. Verifique los datos e intente nuevamente."
                );
                return;
            }

            Site::redirectTo("index.php?page=PacientesController&action=index&msg=" . urlencode('Paciente actualizado correctamente.'));
            exit;
        }

        $this->renderEdit($id, $data);
    }

    /**
     * Convierte la fila cruda de la base de datos al mismo formato
     * {identidad, nombres, apellidos, fecha_nacimiento, telefono,
     * direccion} que usa readPatientData(), para que el formulario de
     * edición se pinte igual tanto en la primera carga como al
     * recargarse después de un error.
     */
    private function patientToData(array $paciente): array
    {
        return [
            "identidad" => (string) ($paciente["identidad"] ?? ""),
            "nombres" => (string) ($paciente["nombres"] ?? ""),
            "apellidos" => (string) ($paciente["apellidos"] ?? ""),
            "fecha_nacimiento" => (string) ($paciente["fecha_nacimiento"] ?? ""),
            "telefono" => (string) ($paciente["telefono"] ?? ""),
            "direccion" => (string) ($paciente["direccion"] ?? ""),
        ];
    }

    private function renderEdit(int $id, array $data, ?string $error = null): void
    {
        Renderer::render("paciente_edit", array_merge($data, [
            "id" => $id,
            "error" => $error,
            "maxFechaNacimiento" => date('Y-m-d'),
            "minFechaNacimiento" => date('Y-m-d', strtotime('-120 years')),
        ]));
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