<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Views\Renderer;
use Dao\Security\Roles as DaoRoles;
use Utilities\Site;
use Utilities\Paging;

// Listado y filtros de roles
class Roles extends PrivateController
{
    private $viewData = [];

    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        // Lista roles con filtros, orden y paginacion
        try {
            // Obtener parametros de filtro y paginacion
            $partialName = \Utilities\Validators::sanitizeString($_GET["partialName"] ?? "");
            $status = \Utilities\Validators::sanitizeAlphaNum($_GET["status"] ?? "");
            if (!in_array($status, ["ACT", "INA"], true)) {
                $status = "";
            }
            $orderBy = $this->getOrderBy();
            $orderDescending = $this->getOrderDescending();
            $pageNum = \Utilities\Validators::sanitizeInt($_GET["pageNum"] ?? 1, 1) ?? 1;
            $page = $pageNum - 1;
            $itemsPerPage = 10;

            // Obtener datos del DAO
            $result = DaoRoles::getRoles(
                $partialName,
                $status,
                $orderBy,
                $orderDescending,
                $page,
                $itemsPerPage
            );

            // Prepare safe role and permission values for the template.
            $this->viewData["roles"] = array_map(
                static function (array $role): array {
                    $role["role_id_url"] = rawurlencode($role["rolescod"]);
                    $role["is_active"] = $role["rolesest"] === "ACT";
                    $role["can_deactivate"] =
                        $role["is_active"] && (int) $role["rolId"] !== 1;
                    $role["rolescod"] = htmlspecialchars($role["rolescod"], ENT_QUOTES);
                    $role["rolesdsc"] = htmlspecialchars($role["rolesdsc"], ENT_QUOTES);
                    $role["permissions"] = array_map(
                        static fn(array $permission): array => [
                            "funcionNombre" => htmlspecialchars(
                                $permission["funcionNombre"],
                                ENT_QUOTES
                            ),
                            "funcionDescripcion" => htmlspecialchars(
                                $permission["funcionDescripcion"],
                                ENT_QUOTES
                            ),
                            "accessType" => htmlspecialchars(
                                $permission["accessType"],
                                ENT_QUOTES
                            ),
                            "accessClass" => $permission["accessClass"],
                        ],
                        $role["permissions"] ?? []
                    );
                    return $role;
                },
                $result["roles"]
            );
            $this->viewData["total"] = $result["total"];
            $this->viewData["page"] = $result["page"];
            $this->viewData["itemsPerPage"] = $result["itemsPerPage"];

            // Variables para mantener los filtros en el formulario
            $this->viewData["partialName"] = $partialName;
            $this->viewData["status"] = $status;
            $this->viewData["status_EMP"] = $status === "" ? "selected" : "";
            $this->viewData["status_ACT"] = $status === "ACT" ? "selected" : "";
            $this->viewData["status_INA"] = $status === "INA" ? "selected" : "";

            // Variables para ordenamiento (similar a productos)
            $this->setOrderVariables();

            // PaginaciaIn
            $this->viewData["pagination"] = Paging::getPagination(
                $result["total"],
                $itemsPerPage,
                $pageNum,
                "index.php?page=Security_Roles&partialName=" . urlencode($partialName) . "&status=" . urlencode($status),
                "Security_Roles"
            );

            Renderer::render("security/roles", $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                "index.php?page=Security_Roles",
                "Error: " . $ex->getMessage()
            );
        }
    }

    // =============================
    // GETORDERBY
    // =============================
    private function getOrderBy(): string
    {
        // Normaliza campo de orden valido
        $allowed = ["rolescod", "rolesdsc", "rolesest"];
        $orderBy = $_GET["orderBy"] ?? "";
        return in_array($orderBy, $allowed) ? $orderBy : "";
    }

    // =============================
    // GETORDERDESCENDING
    // =============================
    private function getOrderDescending(): bool
    {
        // Interpreta bandera descendente desde querystring
        return isset($_GET["orderDescending"]) && $_GET["orderDescending"] === "1";
    }

    // =============================
    // SETORDERVARIABLES
    // =============================
    private function setOrderVariables(): void
    {
        // Marca estado de orden para cada columna en la vista
        // Para cada columna, definimos variables que indican si esta ordenada
        $orderBy = $this->getOrderBy();
        $desc = $this->getOrderDescending();

        $this->viewData["OrderByRolescod"] = $orderBy === "rolescod" && !$desc;
        $this->viewData["OrderByRolescodDesc"] = $orderBy === "rolescod" && $desc;
        $this->viewData["OrderByRolesdsc"] = $orderBy === "rolesdsc" && !$desc;
        $this->viewData["OrderByRolesdscDesc"] = $orderBy === "rolesdsc" && $desc;
        $this->viewData["OrderByRolesest"] = $orderBy === "rolesest" && !$desc;
        $this->viewData["OrderByRolesestDesc"] = $orderBy === "rolesest" && $desc;
    }

}
