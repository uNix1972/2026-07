<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Views\Renderer;
use Dao\Security\Funciones as DaoFunciones;
use Utilities\Site;
use Utilities\Paging;

// Listado y filtros de funciones/permiso
class Funciones extends PrivateController
{
    private $viewData = [];

    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        try {
            $partialName = \Utilities\Validators::sanitizeString($_GET["partialName"] ?? "");
            $status = \Utilities\Validators::sanitizeAlphaNum($_GET["status"] ?? "");
            // Lista funciones con filtros, orden y paginacion manual
            $type = \Utilities\Validators::sanitizeAlphaNum($_GET["type"] ?? "");
            $orderBy = $this->getOrderBy();
            $orderDescending = $this->getOrderDescending();
            $pageNum = \Utilities\Validators::sanitizeInt($_GET["pageNum"] ?? 1, 1) ?? 1;
            $page = $pageNum - 1;
            $itemsPerPage = 10;

            $result = DaoFunciones::getFunciones(
                $partialName,
                $status,
                $type,
                $orderBy,
                $orderDescending,
                $page,
                $itemsPerPage
            );

            $this->viewData["funciones"] = $result["funciones"];
            $this->viewData["total"] = $result["total"];
            $this->viewData["page"] = $result["page"];
            $this->viewData["itemsPerPage"] = $result["itemsPerPage"];

            $this->viewData["partialName"] = $partialName;
            $this->viewData["status"] = $status;
            $this->viewData["status_EMP"] = $status === "" ? "selected" : "";
            $this->viewData["status_ACT"] = $status === "ACT" ? "selected" : "";
            $this->viewData["status_INA"] = $status === "INA" ? "selected" : "";

            $this->viewData["type"] = $type;
            // Opciones para el filtro de tipo (puedes definirlas aquaA o cargarlas de la BD)
            $this->viewData["type_EMP"] = $type === "" ? "selected" : "";
            $this->viewData["type_MNU"] = $type === "MNU" ? "selected" : "";
            $this->viewData["type_FNC"] = $type === "FNC" ? "selected" : "";
            $this->viewData["type_CTL"] = $type === "CTL" ? "selected" : "";

            $this->setOrderVariables();

            $this->viewData["pagination"] = Paging::getPagination(
                $result["total"],
                $itemsPerPage,
                $pageNum,
                "index.php?page=Security_Funciones&partialName=" . urlencode($partialName) . "&status=" . urlencode($status) . "&type=" . urlencode($type),
                "Security_Funciones"
            );

            Renderer::render("security/funciones", $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                "index.php?page=Security_Funciones",
                "Error: " . $ex->getMessage()
            );
        }
    }

    // =============================
    // GETORDERBY
    // =============================
    private function getOrderBy(): string
    {
        $allowed = ["fncod", "fndsc", "fnest", "fntyp"];
        // Normaliza campo de orden permitido
        $orderBy = $_GET["orderBy"] ?? "";
        return in_array($orderBy, $allowed) ? $orderBy : "";
    }

    // =============================
    // GETORDERDESCENDING
    // =============================
    private function getOrderDescending(): bool
    {
        // Interpreta bandera de orden descendente
        return isset($_GET["orderDescending"]) && $_GET["orderDescending"] === "1";
    }

    // =============================
    // SETORDERVARIABLES
    // =============================
    private function setOrderVariables(): void
    {
        // Marca variables de vista para indicadores de orden
        $orderBy = $this->getOrderBy();
        $desc = $this->getOrderDescending();

        $this->viewData["OrderByFncod"] = $orderBy === "fncod" && !$desc;
        $this->viewData["OrderByFncodDesc"] = $orderBy === "fncod" && $desc;
        $this->viewData["OrderByFndsc"] = $orderBy === "fndsc" && !$desc;
        $this->viewData["OrderByFndscDesc"] = $orderBy === "fndsc" && $desc;
        $this->viewData["OrderByFnest"] = $orderBy === "fnest" && !$desc;
        $this->viewData["OrderByFnestDesc"] = $orderBy === "fnest" && $desc;
        $this->viewData["OrderByFntyp"] = $orderBy === "fntyp" && !$desc;
        $this->viewData["OrderByFntypDesc"] = $orderBy === "fntyp" && $desc;
    }

}
