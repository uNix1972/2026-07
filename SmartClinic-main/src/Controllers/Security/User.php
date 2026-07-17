<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Views\Renderer;
use Dao\Security\Users as DaoUsers;
use Dao\Security\Security as DaoSecurity;
use Utilities\Site;
use Utilities\Validators;

// CRUD de usuario del sistema
class User extends PrivateController
{
    private $viewData = [];
    private $mode = "DSP";

    private $modeDescriptions = [
        "DSP" => "Detalle del Usuario %s",
        "INS" => "Nuevo Usuario",
        "UPD" => "Editar Usuario %s",
        "DEL" => "Eliminar Usuario %s"
    ];

    private $readonly = "";
    private $showCommitBtn = true;

    private $user = [
        "usercod" => 0,
        "username" => "",
        "useremail" => "",
        "userpswd" => "",
        "userfching" => "",
        "userpswdest" => "ACT",
        "userpswdexp" => "",
        "userest" => "ACT",
        "useractcod" => "",
        "userpswdchg" => "",
        "usertipo" => "NOR"
    ];

    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        // Orquesta carga, validaciaIn y guardado del formulario de usuario
        try {
            $this->getData();
            if ($this->isPostBack() && $this->validateData()) {
                $this->handlePost();
            }
            $this->setViewData();
            Renderer::render("security/user", $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                "index.php?page=Security_Users",
                $ex->getMessage()
            );
        }
    }

    // =============================
    // GETDATA
    // =============================
    private function getData()
    {
        // Carga datos iniciales segaUn modo y usuario objetivo
        $this->mode = \Utilities\Validators::sanitizeAlphaNum($_GET["mode"] ?? "NOF");

        if (!isset($this->modeDescriptions[$this->mode])) {
            throw new \Exception("Modo inválido");
        }

        $this->readonly = ($this->mode === "DEL" || $this->mode === "DSP") ? "readonly" : "";
        $this->showCommitBtn = $this->mode !== "DSP";

        if ($this->mode === "INS") {
            $this->user = [
                "usercod" => 0,
                "username" => "",
                "useremail" => "",
                "userpswd" => "",
                "userfching" => "",
                "userpswdest" => "ACT",
                "userpswdexp" => "",
                "userest" => "ACT",
                "useractcod" => "",
                "userpswdchg" => "",
                "usertipo" => "NOR"
            ];
        } else {
            $id = \Utilities\Validators::sanitizeId($_GET["id"] ?? 0);
            if ($id === null) {
                throw new \Exception("ID de usuario inválido");
            }
            $userData = DaoUsers::getUserById($id);
            if (!$userData) {
                throw new \Exception("Usuario no encontrado");
            }
            $this->user = array_merge($this->user, $userData);
        }
    }

    /**
     * Devuelve true si el usuario logueado esta editando su propio perfil
     * Usa $_SESSION["login"]["userId"] guardado por Utilities\Security::login()
     */
    // =============================
    // ISEDITINGSELF
    // =============================
    private function isEditingSelf(): bool
    {
        $loggedUserId = \Utilities\Security::getUserId();
        return intval($this->user["usercod"]) === intval($loggedUserId);
    }

    // =============================
    // VALIDATEDATA
    // =============================
    private function validateData(): bool
    {
        // Valida entradas y aplica restricciones de autoediciaIn
        $errors = [];
        if (!\Utilities\Security::validateCsrfPost()) {
            $errors["username_error"] = "Solicitud inválida o expirada. Recargue la página e intente nuevamente.";
        }

        $this->user["usercod"] = \Utilities\Validators::sanitizeInt($_POST["usercod"] ?? 0);
        $this->user["username"] = \Utilities\Validators::sanitizeString($_POST["username"] ?? "");
        $this->user["useractcod"] = "admin";
        $this->user["userfching"] = date("Y-m-d H:i:s");
        $this->user["userpswdexp"] = date("Y-m-d H:i:s", strtotime("+90 days"));

        // Email: solo editable en INS, en UPD/DEL se conserva el de la BD
        if ($this->mode === "INS") {
            $this->user["useremail"] = \Utilities\Validators::sanitizeEmail($_POST["useremail"] ?? "");
        }

        // Estado y Tipo: si se edita a si mismo, conservar los de la BD
        if ($this->isEditingSelf() && $this->mode === "UPD") {
            $currentData = DaoUsers::getUserById($this->user["usercod"]);
            $this->user["userest"] = $currentData["userest"];
            $this->user["usertipo"] = $currentData["usertipo"];
        } else {
            $this->user["userest"] = \Utilities\Validators::sanitizeAlphaNum($_POST["userest"] ?? "ACT");
            $this->user["usertipo"] = \Utilities\Validators::sanitizeAlphaNum($_POST["usertipo"] ?? "NOR");
        }

        if ($this->mode === "INS") {
            $this->user["userpswd"] = trim($_POST["userpswd"] ?? "");
        }

        if (\Utilities\Validators::IsEmpty($this->user["username"]))
            $errors["username_error"] = "Nombre requerido";
        if ($this->mode === "INS" && ($this->user["useremail"] === null || \Utilities\Validators::IsEmpty($this->user["useremail"])))
            $errors["useremail_error"] = "Email requerido";
        if ($this->mode === "INS" && \Utilities\Validators::IsEmpty($this->user["userpswd"]))
            $errors["userpswd_error"] = "Password requerido";
        if (!in_array($this->user["userest"], ["ACT", "INA"]))
            $errors["userest_error"] = "Estado inválido";
        if (!in_array($this->user["usertipo"], ["NOR", "ADM", "CON"]))
            $errors["usertipo_error"] = "Tipo inválido";

        if (count($errors) > 0) {
            foreach ($errors as $k => $v) {
                $this->user[$k] = $v;
            }
            return false;
        }
        return true;
    }

    // =============================
    // HANDLEPOST
    // =============================
    private function handlePost()
    {
        // Ejecuta accion INS/UPD/DEL segaUn modo
        switch ($this->mode) {
            case "INS":
                $hashedPswd = \Dao\Security\Security::hashPasswordPublic($this->user["userpswd"]);
                $now = date("Y-m-d H:i:s");
                $newId = DaoUsers::insertUser(
                    $this->user["username"],
                    $this->user["useremail"],
                    $hashedPswd,
                    $this->user["userfching"],
                    $this->user["userpswdest"],
                    $this->user["userpswdexp"],
                    $this->user["userest"],
                    $this->user["useractcod"],
                    $now,  // userpswdchg should be datetime, not password hash
                    $this->user["usertipo"]
                );
                $this->assignRole(intval($newId), $this->user["usertipo"]);
                Site::redirectToWithMsg("index.php?page=Security_Users", "Usuario creado correctamente");
                break;

            case "UPD":
                DaoUsers::updateUser(
                    $this->user["usercod"],
                    $this->user["username"],
                    $this->user["useremail"],
                    "",
                    $this->user["userfching"],
                    $this->user["userpswdest"] ?? "ACT",
                    $this->user["userpswdexp"],
                    $this->user["userest"],
                    $this->user["useractcod"],
                    "",
                    $this->user["usertipo"]
                );
                if (!$this->isEditingSelf()) {
                    $this->assignRole($this->user["usercod"], $this->user["usertipo"]);
                }
                Site::redirectToWithMsg("index.php?page=Security_Users", "Usuario actualizado correctamente");
                break;

            case "DEL":
                \Dao\Security\Security::removeAllRolesFromUser($this->user["usercod"]);
                DaoUsers::deleteUser($this->user["usercod"]);
                Site::redirectToWithMsg("index.php?page=Security_Users", "Usuario eliminado correctamente");
                break;
        }
    }

    /**
     * Elimina todos los roles del usuario y asigna el que corresponde
     * segaUn el tipo (usertipo) seleccionado en el formulario
     *



     * Mapa de tipos -> IDs de rol en la tabla roles:
     *   ADM => 1  (Administrador)
     *   NOR => 2  (Normal)
     *   CON => 3  (Consulta)
     */
    // =============================
    // ASSIGNROLE
    // =============================
    private function assignRole(int $usercod, string $usertipo): void
    {
        \Dao\Security\Security::removeAllRolesFromUser($usercod);

        $rolMap = [
            "ADM" => 1,
            "NOR" => 2,
            "CON" => 3
        ];

        if (isset($rolMap[$usertipo])) {
            \Dao\Security\Security::assignRolToUser($usercod, $rolMap[$usertipo]);
        }
    }

    // =============================
    // SETVIEWDATA
    // =============================
    private function setViewData(): void
    {
        $this->viewData["FormTitle"] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->user["username"] ?? ""
        );
        $this->viewData["mode"] = $this->mode;

        $isSelf       = $this->isEditingSelf() && $this->mode === "UPD";
        $isReadonly   = ($this->mode === "DEL" || $this->mode === "DSP");
        $selectsLocked = ($this->mode === "DEL" || $this->mode === "DSP" || $isSelf);

        // Valores de los campos
        $this->viewData["val_username"]  = htmlspecialchars($this->user["username"] ?? "", ENT_QUOTES);
        $this->viewData["val_useremail"] = htmlspecialchars($this->user["useremail"] ?? "", ENT_QUOTES);
        $this->viewData["val_userest"]   = $this->user["userest"] ?? "ACT";
        $this->viewData["val_usertipo"]  = $this->user["usertipo"] ?? "NOR";

        // Flags de estado para la vista
        $this->viewData["field_readonly"]      = $isReadonly;
        $this->viewData["email_readonly"]      = ($this->mode !== "INS");
        $this->viewData["selects_locked"]      = $selectsLocked;
        $this->viewData["is_insert"]           = ($this->mode === "INS");
        $this->viewData["is_delete"]           = ($this->mode === "DEL");
        $this->viewData["show_commit"]         = ($this->mode !== "DSP");
        $this->viewData["warn_self"]           = $isSelf;

        // Flags de opciones seleccionadas en selects
        $this->viewData["est_ACT"] = ($this->user["userest"] ?? "ACT") === "ACT";
        $this->viewData["est_INA"] = ($this->user["userest"] ?? "ACT") === "INA";
        $this->viewData["tipo_NOR"] = ($this->user["usertipo"] ?? "NOR") === "NOR";
        $this->viewData["tipo_ADM"] = ($this->user["usertipo"] ?? "NOR") === "ADM";
        $this->viewData["tipo_CON"] = ($this->user["usertipo"] ?? "NOR") === "CON";

        // Mensajes de error
        $this->viewData["errorNombre"]  = $this->user["username_error"] ?? "";
        $this->viewData["errorEmail"]   = $this->user["useremail_error"] ?? "";
        $this->viewData["errorPswd"]    = $this->user["userpswd_error"] ?? "";
        $this->viewData["errorEstado"]  = $this->user["userest_error"] ?? "";
        $this->viewData["errorTipo"]    = $this->user["usertipo_error"] ?? "";

        // usercod para la action del form
        $this->viewData["u_usercod"] = $this->user["usercod"] ?? 0;
    }
}