<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Dao\Security\Users as DaoUsers;
use Utilities\Site;
use Views\Renderer;

/**
 * Creates, displays, updates, and deletes users with one or more roles.
 */
class User extends PrivateController
{
    private array $viewData = [];
    private string $mode = "DSP";
    private array $availableRoles = [];
    private array $selectedRoleIds = [];

    private array $modeDescriptions = [
        "DSP" => "Detalle del Usuario %s",
        "INS" => "Nuevo Usuario",
        "UPD" => "Editar Usuario %s",
        "DEL" => "Eliminar Usuario %s",
    ];

    private array $user = [
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
        "usertipo" => "NOR",
    ];

    public function run(): void
    {
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

    /**
     * Loads the role catalog and the target user for the requested form mode.
     */
    private function getData(): void
    {
        $this->mode = \Utilities\Validators::sanitizeAlphaNum($_GET["mode"] ?? "NOF");
        if (!isset($this->modeDescriptions[$this->mode])) {
            throw new \Exception("Modo invalido");
        }

        $this->availableRoles = DaoUsers::getActiveRoles();

        if ($this->mode === "INS") {
            // Reception is the least privileged operational role and is the
            // safest initial suggestion; the administrator can change it.
            $activeRoleIds = array_map("intval", array_column($this->availableRoles, "rolId"));
            $this->selectedRoleIds = in_array(2, $activeRoleIds, true) ? [2] : [];
            return;
        }

        $id = \Utilities\Validators::sanitizeId($_GET["id"] ?? 0);
        if ($id === null) {
            throw new \Exception("ID de usuario invalido");
        }

        $userData = DaoUsers::getUserById($id);
        if (!$userData) {
            throw new \Exception("Usuario no encontrado");
        }

        $this->user = array_merge($this->user, $userData);
        $this->selectedRoleIds = $this->parseRoleIds($userData["role_ids"] ?? "");
    }

    /**
     * Checks whether the authenticated user is editing their own account.
     */
    private function isEditingSelf(): bool
    {
        return (int) $this->user["usercod"] === (int) \Utilities\Security::getUserId();
    }

    /**
     * Validates account fields and the complete role selection.
     *
     * Administrators cannot alter their own status or roles from this screen,
     * which prevents accidental loss of administrative access.
     */
    private function validateData(): bool
    {
        $errors = [];
        if (!\Utilities\Security::validateCsrfPost()) {
            $errors["username_error"] = "Solicitud invalida o expirada. Recargue la pagina e intente nuevamente.";
        }

        $this->user["usercod"] = \Utilities\Validators::sanitizeInt($_POST["usercod"] ?? 0);
        $this->user["username"] = \Utilities\Validators::sanitizeString($_POST["username"] ?? "");
        $this->user["useractcod"] = "admin";
        $this->user["userfching"] = date("Y-m-d H:i:s");
        $this->user["userpswdexp"] = date("Y-m-d H:i:s", strtotime("+90 days"));

        if ($this->mode === "INS") {
            $this->user["useremail"] = \Utilities\Validators::sanitizeEmail($_POST["useremail"] ?? "");
            $this->user["userpswd"] = trim($_POST["userpswd"] ?? "");
        }

        $mustPreserveAccess = $this->isEditingSelf() && $this->mode === "UPD";
        if ($mustPreserveAccess) {
            $currentData = DaoUsers::getUserById((int) $this->user["usercod"]);
            if (!$currentData) {
                throw new \Exception("Usuario no encontrado");
            }
            $this->user["userest"] = $currentData["userest"];
            $this->selectedRoleIds = $this->parseRoleIds($currentData["role_ids"] ?? "");
        } elseif (in_array($this->mode, ["INS", "UPD"], true)) {
            $this->user["userest"] = \Utilities\Validators::sanitizeAlphaNum($_POST["userest"] ?? "ACT");
            $postedRoles = $_POST["role_ids"] ?? [];
            $this->selectedRoleIds = is_array($postedRoles)
                ? array_values(array_unique(array_filter(
                    array_map("intval", $postedRoles),
                    static fn(int $roleId): bool => $roleId > 0
                )))
                : [];
        }

        if (\Utilities\Validators::IsEmpty($this->user["username"])) {
            $errors["username_error"] = "Nombre requerido";
        }
        if (
            $this->mode === "INS"
            && ($this->user["useremail"] === null
                || \Utilities\Validators::IsEmpty($this->user["useremail"]))
        ) {
            $errors["useremail_error"] = "Email requerido";
        }
        if ($this->mode === "INS" && \Utilities\Validators::IsEmpty($this->user["userpswd"])) {
            $errors["userpswd_error"] = "Password requerido";
        }
        if (!in_array($this->user["userest"], ["ACT", "INA"], true)) {
            $errors["userest_error"] = "Estado invalido";
        }

        if (in_array($this->mode, ["INS", "UPD"], true)) {
            $activeRoleIds = array_map("intval", array_column($this->availableRoles, "rolId"));
            if ($this->selectedRoleIds === []) {
                $errors["roles_error"] = "Seleccione al menos un rol.";
            } elseif (array_diff($this->selectedRoleIds, $activeRoleIds) !== []) {
                $errors["roles_error"] = "Uno o mas roles seleccionados no estan activos.";
            }
        }

        if ($errors !== []) {
            foreach ($errors as $key => $message) {
                $this->user[$key] = $message;
            }
            return false;
        }

        return true;
    }

    /**
     * Delegates atomic user and role writes to the DAO.
     */
    private function handlePost(): void
    {
        switch ($this->mode) {
            case "INS":
                $this->user["userpswd"] = \Dao\Security\Security::hashPasswordPublic(
                    $this->user["userpswd"]
                );
                $this->user["userpswdchg"] = date("Y-m-d H:i:s");
                DaoUsers::createUserWithRoles($this->user, $this->selectedRoleIds);
                Site::redirectToWithMsg(
                    "index.php?page=Security_Users",
                    "Usuario creado correctamente"
                );
                break;

            case "UPD":
                DaoUsers::updateUserWithRoles($this->user, $this->selectedRoleIds);
                Site::redirectToWithMsg(
                    "index.php?page=Security_Users",
                    "Usuario actualizado correctamente"
                );
                break;

            case "DEL":
                DaoUsers::deleteUser((int) $this->user["usercod"]);
                Site::redirectToWithMsg(
                    "index.php?page=Security_Users",
                    "Usuario eliminado correctamente"
                );
                break;
        }
    }

    /**
     * Builds the role checklist and field state consumed by the template.
     */
    private function setViewData(): void
    {
        $this->viewData["FormTitle"] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->user["username"] ?? ""
        );
        $this->viewData["mode"] = $this->mode;

        $isSelf = $this->isEditingSelf() && $this->mode === "UPD";
        $isReadonly = in_array($this->mode, ["DEL", "DSP"], true);
        $accessLocked = $isReadonly || $isSelf;

        $this->viewData["val_username"] = htmlspecialchars(
            $this->user["username"] ?? "",
            ENT_QUOTES
        );
        $this->viewData["val_useremail"] = htmlspecialchars(
            $this->user["useremail"] ?? "",
            ENT_QUOTES
        );

        $this->viewData["field_readonly"] = $isReadonly;
        $this->viewData["email_readonly"] = $this->mode !== "INS";
        $this->viewData["selects_locked"] = $accessLocked;
        $this->viewData["is_insert"] = $this->mode === "INS";
        $this->viewData["is_delete"] = $this->mode === "DEL";
        $this->viewData["show_commit"] = $this->mode !== "DSP";
        $this->viewData["warn_self"] = $isSelf;

        $this->viewData["est_ACT"] = ($this->user["userest"] ?? "ACT") === "ACT";
        $this->viewData["est_INA"] = ($this->user["userest"] ?? "ACT") === "INA";
        $this->viewData["roles"] = array_map(
            function (array $role) use ($accessLocked): array {
                $permissions = array_map(
                    static fn(array $permission): array => [
                        "funcionNombre" => htmlspecialchars(
                            $permission["funcionNombre"] ?? "",
                            ENT_QUOTES
                        ),
                        "funcionDescripcion" => htmlspecialchars(
                            $permission["funcionDescripcion"] ?? "",
                            ENT_QUOTES
                        ),
                        "accessType" => htmlspecialchars(
                            $permission["accessType"] ?? "Acceso",
                            ENT_QUOTES
                        ),
                        "accessClass" => $permission["accessClass"] ?? "action",
                    ],
                    $role["permissions"] ?? []
                );

                return [
                    "rolId" => (int) $role["rolId"],
                    "rolNombre" => htmlspecialchars($role["rolNombre"], ENT_QUOTES),
                    "rolDescripcion" => htmlspecialchars($role["rolDescripcion"], ENT_QUOTES),
                    "selected" => in_array((int) $role["rolId"], $this->selectedRoleIds, true),
                    "locked" => $accessLocked,
                    "permissions" => $permissions,
                    "permission_count" => count($permissions),
                    "has_permissions" => $permissions !== [],
                    "automatic_access" => (bool) ($role["automatic_access"] ?? false),
                ];
            },
            $this->availableRoles
        );

        $this->viewData["errorNombre"] = $this->user["username_error"] ?? "";
        $this->viewData["errorEmail"] = $this->user["useremail_error"] ?? "";
        $this->viewData["errorPswd"] = $this->user["userpswd_error"] ?? "";
        $this->viewData["errorEstado"] = $this->user["userest_error"] ?? "";
        $this->viewData["errorRoles"] = $this->user["roles_error"] ?? "";
        $this->viewData["u_usercod"] = $this->user["usercod"] ?? 0;
    }

    /**
     * Converts a GROUP_CONCAT role value into distinct integer IDs.
     */
    private function parseRoleIds(string $roleIds): array
    {
        if (trim($roleIds) === "") {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map("intval", explode(",", $roleIds)),
            static fn(int $roleId): bool => $roleId > 0
        )));
    }
}
