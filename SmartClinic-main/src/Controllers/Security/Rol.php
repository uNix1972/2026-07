<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Dao\Security\Roles as DaoRoles;
use Utilities\Site;
use Views\Renderer;

/**
 * Maintains role metadata and its complete set of function permissions.
 */
class Rol extends PrivateController
{
    private array $viewData = [];
    private string $mode = "DSP";
    private string $originalRoleName = "";
    private array $availableFunctions = [];
    private array $selectedFunctionIds = [];

    private array $modeDescriptions = [
        "DSP" => "Detalle del Rol %s",
        "INS" => "Nuevo Rol",
        "UPD" => "Editar Rol %s",
        "DEL" => "Eliminar Rol %s",
    ];

    private array $role = [
        "rolId" => 0,
        "rolescod" => "",
        "rolesdsc" => "",
        "rolesest" => "ACT",
    ];

    public function run(): void
    {
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

    /**
     * Loads the role and active function catalog for the requested mode.
     */
    private function getData(): void
    {
        $this->mode = \Utilities\Validators::sanitizeAlphaNum(
            $_GET["mode"] ?? "NOF"
        );
        if (!isset($this->modeDescriptions[$this->mode])) {
            throw new \Exception("Modo inválido");
        }

        $this->availableFunctions = DaoRoles::getActiveFunctions();
        if ($this->mode === "INS") {
            return;
        }

        $roleName = trim((string) \Utilities\Validators::sanitizeString(
            $_GET["id"] ?? ""
        ));
        if ($roleName === "") {
            throw new \Exception("ID de rol inválido");
        }

        $storedRole = DaoRoles::getRoleById($roleName);
        if (!$storedRole) {
            throw new \Exception("Rol no encontrado");
        }

        $this->role = array_merge($this->role, $storedRole);
        $this->originalRoleName = (string) $this->role["rolescod"];
        $this->selectedFunctionIds = DaoRoles::getRolePermissionIds(
            (int) $this->role["rolId"]
        );
    }

    /**
     * Validates role metadata and the complete permission selection.
     */
    private function validateData(): bool
    {
        $errors = [];
        if (!\Utilities\Security::validateCsrfPost()) {
            $errors["rolescod_error"] =
                "Solicitud inválida o expirada. Recargue la página e intente nuevamente.";
        }

        if ($this->mode === "DEL") {
            if ($errors !== []) {
                $this->role = array_merge($this->role, $errors);
                return false;
            }
            return true;
        }

        $this->originalRoleName = trim((string) \Utilities\Validators::sanitizeString(
            $_POST["rolescod_original"] ?? $this->originalRoleName
        ));
        $isAdministrator = (int) $this->role["rolId"] === 1;

        if ($isAdministrator && $this->mode === "UPD") {
            $storedRole = DaoRoles::getRoleById($this->originalRoleName);
            if (!$storedRole) {
                throw new \Exception("Rol no encontrado");
            }
            $this->role["rolescod"] = $storedRole["rolescod"];
            $this->role["rolesest"] = "ACT";
            $this->selectedFunctionIds = DaoRoles::getRolePermissionIds(1);
        } else {
            $this->role["rolescod"] = trim((string) \Utilities\Validators::sanitizeString(
                $_POST["rolescod"] ?? ""
            ));
            $this->role["rolesest"] = \Utilities\Validators::sanitizeAlphaNum(
                $_POST["rolesest"] ?? "ACT"
            );
            $postedFunctions = $_POST["permission_ids"] ?? [];
            $this->selectedFunctionIds = is_array($postedFunctions)
                ? array_values(array_unique(array_filter(
                    array_map("intval", $postedFunctions),
                    static fn(int $functionId): bool => $functionId > 0
                )))
                : [];
        }

        $this->role["rolesdsc"] = trim((string) \Utilities\Validators::sanitizeString(
            $_POST["rolesdsc"] ?? ""
        ));

        if ($this->role["rolescod"] === "") {
            $errors["rolescod_error"] = "Nombre de rol requerido";
        } elseif (mb_strlen($this->role["rolescod"]) > 50) {
            $errors["rolescod_error"] = "El nombre no puede exceder 50 caracteres";
        }
        if ($this->role["rolesdsc"] === "") {
            $errors["rolesdsc_error"] = "Descripción requerida";
        } elseif (mb_strlen($this->role["rolesdsc"]) > 150) {
            $errors["rolesdsc_error"] = "La descripción no puede exceder 150 caracteres";
        }
        if (!in_array($this->role["rolesest"], ["ACT", "INA"], true)) {
            $errors["rolesest_error"] = "Estado inválido";
        }

        $activeFunctionIds = array_map(
            "intval",
            array_column($this->availableFunctions, "funcionId")
        );
        if (array_diff($this->selectedFunctionIds, $activeFunctionIds) !== []) {
            $errors["permissions_error"] =
                "Uno o más accesos seleccionados no están activos.";
        } elseif (
            !$isAdministrator
            && $this->role["rolesest"] === "ACT"
            && $this->selectedFunctionIds === []
        ) {
            $errors["permissions_error"] =
                "Un rol activo debe tener al menos un acceso.";
        }

        if ($errors !== []) {
            $this->role = array_merge($this->role, $errors);
            return false;
        }

        return true;
    }

    /**
     * Delegates atomic role and permission writes to the role DAO.
     */
    private function handlePost(): void
    {
        switch ($this->mode) {
            case "INS":
                DaoRoles::createRoleWithPermissions(
                    $this->role,
                    $this->selectedFunctionIds
                );
                Site::redirectToWithMsg(
                    "index.php?page=Security_Roles",
                    "Rol creado correctamente"
                );
                break;

            case "UPD":
                DaoRoles::updateRoleWithPermissions(
                    $this->role,
                    $this->originalRoleName,
                    $this->selectedFunctionIds
                );
                Site::redirectToWithMsg(
                    "index.php?page=Security_Roles",
                    "Rol y accesos actualizados correctamente"
                );
                break;

            case "DEL":
                if ((int) $this->role["rolId"] === 1) {
                    throw new \Exception(
                        "El rol Administrador es obligatorio y no puede eliminarse."
                    );
                }
                DaoRoles::deleteRole($this->role["rolescod"]);
                Site::redirectToWithMsg(
                    "index.php?page=Security_Roles",
                    "Rol desactivado correctamente"
                );
                break;
        }
    }

    /**
     * Groups permission checkboxes for a compact, scannable editor.
     */
    private function setViewData(): void
    {
        $isReadonly = in_array($this->mode, ["DSP", "DEL"], true);
        $isAdministrator = (int) $this->role["rolId"] === 1;
        $permissionsLocked = $isReadonly || $isAdministrator;

        $groups = [
            "menu" => ["groupName" => "Menús", "functions" => []],
            "module" => ["groupName" => "Módulos", "functions" => []],
            "action" => ["groupName" => "Acciones", "functions" => []],
        ];
        foreach ($this->availableFunctions as $function) {
            $groupKey = $function["accessClass"] ?? "action";
            $groups[$groupKey]["functions"][] = [
                "funcionId" => (int) $function["funcionId"],
                "funcionNombre" => htmlspecialchars(
                    $function["funcionNombre"],
                    ENT_QUOTES
                ),
                "funcionDescripcion" => htmlspecialchars(
                    $function["funcionDescripcion"],
                    ENT_QUOTES
                ),
                "selected" => in_array(
                    (int) $function["funcionId"],
                    $this->selectedFunctionIds,
                    true
                ),
                "locked" => $permissionsLocked,
            ];
        }

        $this->viewData = array_merge($this->viewData, $this->role);
        $this->viewData["FormTitle"] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->role["rolescod"]
        );
        $this->viewData["mode"] = $this->mode;
        $this->viewData["role_id_url"] = rawurlencode($this->originalRoleName);
        $this->viewData["rolescod_original"] = $this->originalRoleName;
        $this->viewData["name_readonly"] = $isReadonly || $isAdministrator;
        $this->viewData["description_readonly"] = $isReadonly;
        $this->viewData["status_locked"] = $isReadonly || $isAdministrator;
        $this->viewData["permissions_locked"] = $permissionsLocked;
        $this->viewData["automatic_access"] = $isAdministrator;
        $this->viewData["show_commit"] = $this->mode !== "DSP";
        $this->viewData["is_delete"] = $this->mode === "DEL";
        $this->viewData["rolesest_act"] =
            $this->role["rolesest"] === "ACT" ? "selected" : "";
        $this->viewData["rolesest_ina"] =
            $this->role["rolesest"] === "INA" ? "selected" : "";
        $this->viewData["functionGroups"] = array_values($groups);
        $this->viewData["selected_permission_count"] =
            count($this->selectedFunctionIds);
        $this->viewData["permissions_error"] =
            $this->role["permissions_error"] ?? "";
    }
}
