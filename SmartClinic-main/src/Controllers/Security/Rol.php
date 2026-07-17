<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Views\Renderer;
use Dao\Security\Roles as DaoRoles;
use Utilities\Site;
use Utilities\Validators;

// CRUD de rol de seguridad
class Rol extends PrivateController
{
    private $viewData = [];
    private $mode = "DSP";

    private $modeDescriptions = [
        "DSP" => "Detalle del Rol %s",
        "INS" => "Nuevo Rol",
        "UPD" => "Editar Rol %s",
        "DEL" => "Eliminar Rol %s"
    ];

    private $readonly = "";
    private $showCommitBtn = true;
    private $originalRolescod = "";

    private $rol = [
        "rolescod" => "",
        "rolesdsc" => "",
        "rolesest" => "ACT"
    ];

    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        // Controla ciclo CRUD de un rol
        try {
            $this->getData();
            if ($this->isPostBack() && $this->validateData()) {
                $this->handlePost();
            }
            $this->setViewData();
            Renderer::render("security/rol", $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                "index.php?page=Security_Roles",
                $ex->getMessage()
            );
        }
    }

    // =============================
    // GETDATA
    // =============================
    private function getData(): void
    {
        // Carga datos de rol segaUn modo de operacion
        $this->mode = \Utilities\Validators::sanitizeAlphaNum($_GET["mode"] ?? "NOF");

        if (!isset($this->modeDescriptions[$this->mode])) {
            throw new \Exception("Modo inválido");
        }

        $this->readonly = ($this->mode === "DEL" || $this->mode === "DSP") ? "readonly" : "";
        $this->showCommitBtn = $this->mode !== "DSP";

        if ($this->mode !== "INS") {
            $id = \Utilities\Validators::sanitizeAlphaNum($_GET["id"] ?? "");
            if ($id === "") {
                throw new \Exception("ID de rol inválido");
            }
            $rolData = DaoRoles::getRoleById($id);
            if (!$rolData) {
                throw new \Exception("Rol no encontrado");
            }
            $this->rol = array_merge($this->rol, $rolData);
            $this->originalRolescod = $this->rol["rolescod"];
        }
    }

    // =============================
    // VALIDATEDATA
    // =============================
    private function validateData(): bool
    {
        // Valida campos requeridos del rol
        $errors = [];
        if (!\Utilities\Security::validateCsrfPost()) {
            $errors['rolescod_error'] = 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.';
        }

        $this->rol["rolescod"] = \Utilities\Validators::sanitizeAlphaNum($_POST["rolescod"] ?? "");
        $this->originalRolescod = \Utilities\Validators::sanitizeAlphaNum($_POST["rolescod_original"] ?? $this->originalRolescod);
        $this->rol["rolesdsc"] = \Utilities\Validators::sanitizeString($_POST["rolesdsc"] ?? "");
        $this->rol["rolesest"] = \Utilities\Validators::sanitizeAlphaNum($_POST["rolesest"] ?? "ACT");

        if (\Utilities\Validators::IsEmpty($this->rol["rolescod"])) {
            $errors["rolescod_error"] = "Código de rol requerido";
        }
        if (\Utilities\Validators::IsEmpty($this->rol["rolesdsc"])) {
            $errors["rolesdsc_error"] = "Descripción requerida";
        }
        if (!in_array($this->rol["rolesest"], ["ACT", "INA"])) {
            $errors["rolesest_error"] = "Estado inválido";
        }

        if (count($errors) > 0) {
            foreach ($errors as $k => $v) {
                $this->rol[$k] = $v;
            }
            return false;
        }
        return true;
    }

    // =============================
    // HANDLEPOST
    // =============================
    private function handlePost(): void
    {
        // Ejecuta INS/UPD/DEL segaUn modo seleccionado
        switch ($this->mode) {
            case "INS":
                DaoRoles::insertRole(
                    $this->rol["rolescod"],
                    $this->rol["rolesdsc"],
                    $this->rol["rolesest"]
                );
                Site::redirectToWithMsg("index.php?page=Security_Roles", "Rol creado correctamente");
                break;

            case "UPD":
                DaoRoles::updateRole(
                    $this->rol["rolescod"],
                    $this->rol["rolesdsc"],
                    $this->rol["rolesest"],
                    $this->originalRolescod
                );
                Site::redirectToWithMsg("index.php?page=Security_Roles", "Rol actualizado correctamente");
                break;

            case "DEL":
                DaoRoles::deleteRole($this->rol["rolescod"]);
                Site::redirectToWithMsg("index.php?page=Security_Roles", "Rol eliminado correctamente");
                break;
        }
    }

    // =============================
    // SETVIEWDATA
    // =============================
    private function setViewData(): void
    {
        $this->viewData["FormTitle"] = sprintf($this->modeDescriptions[$this->mode], $this->rol["rolescod"]);
        $this->viewData["mode"] = $this->mode;
        $this->viewData["field_readonly"] = ($this->mode === "DEL" || $this->mode === "DSP");
        $this->viewData["show_commit"] = ($this->mode !== "DSP");
        $this->viewData["is_delete"] = ($this->mode === "DEL");

        // Prepara variables para formulario de rol
        // Variables para el select de estado
        $estadoKey = "rolesest_" . strtolower($this->rol["rolesest"]);
        $this->rol[$estadoKey] = "selected";
        $this->rol["rolescod_original"] = $this->originalRolescod;

        // Fusionar todas las variables del rol en el primer nivel de viewData
        $this->viewData = array_merge($this->viewData, $this->rol);
    }
}
