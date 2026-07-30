<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Dao\Enfermeras as DaoEnfermeras;
use Dao\Medicos as DaoMedicos;
use Dao\Pacientes as DaoPacientes;
use Dao\Security\Users as DaoUsers;
use Utilities\Site;
use Views\Renderer;

/**
 * Creates, displays, updates, and deletes users with one or more roles.
 *
 * Also lets an admin link the account to an existing médico, paciente, or
 * enfermera record — three independent optional relationships, each a
 * nullable/unique usuario_id column on those tables (no new account is
 * ever created from here, only an existing record gets linked/unlinked).
 */
class User extends PrivateController
{
    private const OWN_ACCOUNT_FEATURE = "GestionarPerfilPropio";

    private array $viewData = [];
    private string $mode = "DSP";
    private array $availableRoles = [];
    private array $selectedRoleIds = [];
    private int $medicoId = 0;
    private int $pacienteId = 0;
    private int $enfermeraId = 0;

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
            Site::addEndScript('public/js/kardex-autocomplete.js');
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
        $this->loadCurrentLinks($id);
    }

    /**
     * Finds which médico/paciente/enfermera (if any) currently has its
     * usuario_id pointing at this account, to preselect the pickers.
     */
    private function loadCurrentLinks(int $usuarioId): void
    {
        $medico = DaoMedicos::getByUsuarioId($usuarioId);
        $this->medicoId = $medico ? (int) $medico["id"] : 0;

        $paciente = DaoPacientes::getByUsuarioId($usuarioId);
        $this->pacienteId = $paciente ? (int) $paciente["id"] : 0;

        $enfermera = DaoEnfermeras::getByUsuarioId($usuarioId);
        $this->enfermeraId = $enfermera ? (int) $enfermera["id"] : 0;
    }

    /**
     * Checks whether the authenticated user is editing their own account.
     */
    private function isEditingSelf(): bool
    {
        return (int) $this->user["usercod"] === (int) \Utilities\Security::getUserId();
    }

    /**
     * Checks the explicit permission that allows editing one's own email,
     * status, and role assignments.
     */
    private function canEditOwnAccount(): bool
    {
        $userId = (int) \Utilities\Security::getUserId();
        return $this->mode === "UPD"
            && $this->isEditingSelf()
            && \Utilities\Security::isAuthorized(
                $userId,
                self::OWN_ACCOUNT_FEATURE
            );
    }

    /**
     * Validates account fields and the complete role selection.
     */
    private function validateData(): bool
    {
        $errors = [];
        if (!\Utilities\Security::validateCsrfPost()) {
            $errors["username_error"] = "Solicitud invalida o expirada. Recargue la pagina e intente nuevamente.";
        }

        $loadedUserId = (int) $this->user["usercod"];
        $postedUserId = \Utilities\Validators::sanitizeInt(
            $_POST["usercod"] ?? 0
        ) ?? 0;
        if ($this->mode !== "INS" && $postedUserId !== $loadedUserId) {
            throw new \Exception("El usuario objetivo de la solicitud no coincide.");
        }
        $this->user["usercod"] = $this->mode === "INS" ? 0 : $loadedUserId;
        $this->user["username"] = \Utilities\Validators::sanitizeString($_POST["username"] ?? "");
        $this->user["useractcod"] = "admin";
        $this->user["userfching"] = date("Y-m-d H:i:s");
        $this->user["userpswdexp"] = date("Y-m-d H:i:s", strtotime("+90 days"));

        $canEditOwnAccount = $this->canEditOwnAccount();
        if ($this->mode === "INS" || $canEditOwnAccount) {
            $this->user["useremail"] = \Utilities\Validators::sanitizeEmail($_POST["useremail"] ?? "");
        }
        if ($this->mode === "INS") {
            $this->user["userpswd"] = trim($_POST["userpswd"] ?? "");
        }

        $mustPreserveAccess =
            $this->isEditingSelf()
            && $this->mode === "UPD"
            && !$canEditOwnAccount;
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

        // A diferencia de roles/estado, el vínculo con médico/paciente/
        // enfermera no otorga permisos por sí solo, así que SÍ se puede
        // cambiar al editar la propia cuenta (no aplica el bloqueo de
        // auto-edición usado arriba).
        if (in_array($this->mode, ["INS", "UPD"], true)) {
            $this->medicoId = \Utilities\Validators::sanitizeId($_POST["medico_id"] ?? 0) ?? 0;
            $this->pacienteId = \Utilities\Validators::sanitizeId($_POST["paciente_id"] ?? 0) ?? 0;
            $this->enfermeraId = \Utilities\Validators::sanitizeId($_POST["enfermera_id"] ?? 0) ?? 0;

            $errors = array_merge($errors, $this->validateEntityLinks());
        }

        if (\Utilities\Validators::IsEmpty($this->user["username"])) {
            $errors["username_error"] = "Nombre requerido";
        }
        if (
            in_array($this->mode, ["INS", "UPD"], true)
            && ($this->mode === "INS" || $canEditOwnAccount)
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
     * Confirms each selected médico/paciente/enfermera exists and is not
     * already linked to a DIFFERENT account, before anything gets saved.
     */
    private function validateEntityLinks(): array
    {
        $errors = [];
        $currentUserId = (int) ($this->user["usercod"] ?? 0);

        if ($this->medicoId > 0) {
            $medico = DaoMedicos::getMedicoById($this->medicoId);
            if (!$medico) {
                $errors["medico_error"] = "El médico seleccionado no existe.";
            } elseif (
                (int) ($medico["usuario_id"] ?? 0) > 0
                && (int) $medico["usuario_id"] !== $currentUserId
            ) {
                $errors["medico_error"] = "Ese médico ya está vinculado a otra cuenta de usuario.";
            }
        }

        if ($this->pacienteId > 0) {
            $paciente = DaoPacientes::getPacienteById($this->pacienteId);
            if (!$paciente) {
                $errors["paciente_error"] = "El paciente seleccionado no existe.";
            } elseif (
                (int) ($paciente["usuario_id"] ?? 0) > 0
                && (int) $paciente["usuario_id"] !== $currentUserId
            ) {
                $errors["paciente_error"] = "Ese paciente ya está vinculado a otra cuenta de usuario.";
            }
        }

        if ($this->enfermeraId > 0) {
            $enfermera = DaoEnfermeras::getEnfermeraById($this->enfermeraId);
            if (!$enfermera) {
                $errors["enfermera_error"] = "La enfermera seleccionada no existe.";
            } elseif (
                (int) ($enfermera["usuario_id"] ?? 0) > 0
                && (int) $enfermera["usuario_id"] !== $currentUserId
            ) {
                $errors["enfermera_error"] = "Esa enfermera ya está vinculada a otra cuenta de usuario.";
            }
        }

        return $errors;
    }

    /**
     * Shapes the three link IDs into the array Dao\Security\Users expects
     * (null instead of 0 means "no vincular"/"quitar vínculo").
     */
    private function buildLinksPayload(): array
    {
        return [
            "medico_id" => $this->medicoId > 0 ? $this->medicoId : null,
            "paciente_id" => $this->pacienteId > 0 ? $this->pacienteId : null,
            "enfermera_id" => $this->enfermeraId > 0 ? $this->enfermeraId : null,
        ];
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
                DaoUsers::createUserWithRoles(
                    $this->user,
                    $this->selectedRoleIds,
                    $this->buildLinksPayload()
                );
                Site::redirectToWithMsg(
                    "index.php?page=Security_Users",
                    "Usuario creado correctamente"
                );
                break;

            case "UPD":
                DaoUsers::updateUserWithRoles(
                    $this->user,
                    $this->selectedRoleIds,
                    $this->buildLinksPayload()
                );
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
        $canEditOwnAccount = $this->canEditOwnAccount();
        $isRestrictedSelf = $isSelf && !$canEditOwnAccount;
        $isReadonly = in_array($this->mode, ["DEL", "DSP"], true);
        $accessLocked = $isReadonly || $isRestrictedSelf;

        $this->viewData["val_username"] = htmlspecialchars(
            $this->user["username"] ?? "",
            ENT_QUOTES
        );
        $this->viewData["val_useremail"] = htmlspecialchars(
            $this->user["useremail"] ?? "",
            ENT_QUOTES
        );

        $this->viewData["field_readonly"] = $isReadonly;
        $this->viewData["email_readonly"] =
            $this->mode !== "INS" && !$canEditOwnAccount;
        $this->viewData["selects_locked"] = $accessLocked;
        $this->viewData["is_insert"] = $this->mode === "INS";
        $this->viewData["is_delete"] = $this->mode === "DEL";
        $this->viewData["show_commit"] = $this->mode !== "DSP";
        $this->viewData["warn_self"] = $isRestrictedSelf;
        $this->viewData["self_access_enabled"] = $isSelf && $canEditOwnAccount;
        $this->viewData["confirm_self_changes"] = $isSelf && $canEditOwnAccount;

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
        $this->viewData["errorMedico"] = $this->user["medico_error"] ?? "";
        $this->viewData["errorPaciente"] = $this->user["paciente_error"] ?? "";
        $this->viewData["errorEnfermera"] = $this->user["enfermera_error"] ?? "";
        $this->viewData["u_usercod"] = $this->user["usercod"] ?? 0;

        // A diferencia de "selects_locked" (roles/estado), aquí SOLO se
        // bloquea en vista de detalle/eliminación: el vínculo con médico/
        // paciente/enfermera sí se puede tocar al editar la propia cuenta.
        $this->viewData["links_locked"] = $isReadonly;
        $this->viewData = array_merge(
            $this->viewData,
            $this->buildComboMedicos($this->medicoId),
            $this->buildComboPacientes($this->pacienteId),
            $this->buildComboEnfermeras($this->enfermeraId)
        );
    }

    /**
     * Converts a plain options array into the escaped JSON string the
     * sc-combo widget expects in its data-options attribute. Mismo patrón
     * que CitasController::jsonAttrParaAutocompletar().
     */
    private function jsonAttrParaAutocompletar(array $opciones): string
    {
        return htmlspecialchars(
            json_encode($opciones, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * Solo se ofrecen médicos/pacientes/enfermeras que no estén vinculados
     * a NINGUNA cuenta, más el que ya está vinculado a esta misma cuenta
     * (si se está editando), para que siga apareciendo seleccionado.
     */
    private function buildComboMedicos(int $selectedId): array
    {
        $currentUserId = (int) ($this->user["usercod"] ?? 0);
        $disponibles = array_values(array_filter(
            DaoMedicos::getAllMedicos(),
            static function (array $medico) use ($currentUserId): bool {
                $linkedTo = (int) ($medico["usuario_id"] ?? 0);
                return $linkedTo === 0 || $linkedTo === $currentUserId;
            }
        ));

        $items = array_map(
            static function (array $medico): array {
                return [
                    "id" => (int) $medico["id"],
                    "nombre" => "Dr/a " . trim($medico["nombres"] . " " . $medico["apellidos"])
                        . " - " . (string) ($medico["nombre_especialidad"] ?? ""),
                    "extra" => (string) ($medico["num_colegiatura"] ?? ""),
                ];
            },
            $disponibles
        );

        $seleccionado = $this->buscarOpcionPorId($items, $selectedId);

        return [
            "medicosJsonAttr" => $this->jsonAttrParaAutocompletar($items),
            "medicoIdSeleccionadoValue" => $seleccionado ? $seleccionado["id"] : 0,
            "medicoNombreSeleccionado" => $seleccionado ? $seleccionado["nombre"] : "",
        ];
    }

    private function buildComboPacientes(int $selectedId): array
    {
        $currentUserId = (int) ($this->user["usercod"] ?? 0);
        $disponibles = array_values(array_filter(
            DaoPacientes::getAllPacientes(),
            static function (array $paciente) use ($currentUserId): bool {
                $linkedTo = (int) ($paciente["usuario_id"] ?? 0);
                return $linkedTo === 0 || $linkedTo === $currentUserId;
            }
        ));

        $items = array_map(
            static function (array $paciente): array {
                return [
                    "id" => (int) $paciente["id"],
                    "nombre" => trim($paciente["nombres"] . " " . $paciente["apellidos"])
                        . " (" . $paciente["identidad"] . ")",
                    "extra" => (string) ($paciente["telefono"] ?? ""),
                ];
            },
            $disponibles
        );

        $seleccionado = $this->buscarOpcionPorId($items, $selectedId);

        return [
            "pacientesJsonAttr" => $this->jsonAttrParaAutocompletar($items),
            "pacienteIdSeleccionadoValue" => $seleccionado ? $seleccionado["id"] : 0,
            "pacienteNombreSeleccionado" => $seleccionado ? $seleccionado["nombre"] : "",
        ];
    }

    private function buildComboEnfermeras(int $selectedId): array
    {
        $currentUserId = (int) ($this->user["usercod"] ?? 0);
        $disponibles = array_values(array_filter(
            DaoEnfermeras::getAllEnfermeras(),
            static function (array $enfermera) use ($currentUserId): bool {
                $linkedTo = (int) ($enfermera["usuario_id"] ?? 0);
                return $linkedTo === 0 || $linkedTo === $currentUserId;
            }
        ));

        $items = array_map(
            static function (array $enfermera): array {
                return [
                    "id" => (int) $enfermera["id"],
                    "nombre" => trim($enfermera["nombres"] . " " . $enfermera["apellidos"]),
                    "extra" => (string) ($enfermera["num_colegiatura"] ?? ""),
                ];
            },
            $disponibles
        );

        $seleccionado = $this->buscarOpcionPorId($items, $selectedId);

        return [
            "enfermerasJsonAttr" => $this->jsonAttrParaAutocompletar($items),
            "enfermeraIdSeleccionadoValue" => $seleccionado ? $seleccionado["id"] : 0,
            "enfermeraNombreSeleccionado" => $seleccionado ? $seleccionado["nombre"] : "",
        ];
    }

    private function buscarOpcionPorId(array $opciones, int $id): ?array
    {
        foreach ($opciones as $opcion) {
            if ($opcion["id"] === $id) {
                return $opcion;
            }
        }
        return null;
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
