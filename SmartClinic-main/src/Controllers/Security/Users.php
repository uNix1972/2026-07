<?php
namespace Controllers\Security;
use Controllers\PrivateController;
use Views\Renderer;
use Dao\Security\Users as DaoUsers;
use Utilities\Context;
use Utilities\Paging;
use Utilities\Security;

// Listado y filtros de usuarios para administraciaIn
class Users extends PrivateController
{
    private $viewData = [];
    private $partialName = "";
    private $status = "";
    private $usertipo = "";
    private $orderBy = "";
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;
    private $users = [];
    private $usersCount = 0;
    private $pages = 0;

    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        // Lista usuarios y aplica filtros guardados en contexto
        $this->getParamsFromContext();
        $this->getParams();
        $tmpUsers = DaoUsers::searchUsers($this->partialName, $this->status, $this->usertipo);
        $this->usersCount = count($tmpUsers);
        $this->pages = $this->usersCount > 0 ? ceil($this->usersCount / $this->itemsPerPage) : 1;
        $start = ($this->pageNumber - 1) * $this->itemsPerPage;
        $this->users = array_slice($tmpUsers, $start, $this->itemsPerPage);

        $loggedUserId = Security::getUserId();
        foreach ($this->users as &$user) {
            $user["is_self"] = $user["usercod"] == $loggedUserId;
        }

        $this->setParamsToContext();
        $this->setParamsToDataView();
        Renderer::render("security/users", $this->viewData);
    }

    // =============================
    // GETPARAMS
    // =============================
    private function getParams(): void
    {
        // Lee filtros y paginacion enviados por querystring
        $this->partialName = \Utilities\Validators::sanitizeString($_GET["partialName"] ?? $this->partialName);
        $this->status = \Utilities\Validators::sanitizeAlphaNum($_GET["status"] ?? $this->status);
        $this->usertipo = \Utilities\Validators::sanitizeAlphaNum($_GET["usertipo"] ?? $this->usertipo);
        $this->orderBy = \Utilities\Validators::sanitizeAlphaNum($_GET["orderBy"] ?? $this->orderBy);
        $this->orderDescending = isset($_GET["orderDescending"]) ? boolval($_GET["orderDescending"]) : $this->orderDescending;
        $this->pageNumber = \Utilities\Validators::sanitizeInt($_GET["pageNum"] ?? $this->pageNumber, 1) ?? $this->pageNumber;
        $this->itemsPerPage = \Utilities\Validators::sanitizeInt($_GET["itemsPerPage"] ?? $this->itemsPerPage, 1, 100) ?? $this->itemsPerPage;
    }

    // =============================
    // GETPARAMSFROMCONTEXT
    // =============================
    private function getParamsFromContext(): void
    {
        // Recupera filtros de la sesion de navegaciaIn
        $this->partialName = Context::getContextByKey("users_partialName");
        $this->status = Context::getContextByKey("users_status");
        $this->usertipo = Context::getContextByKey("users_usertipo");
        $this->orderBy = Context::getContextByKey("users_orderBy");
        $this->orderDescending = boolval(Context::getContextByKey("users_orderDescending"));
        $this->pageNumber = intval(Context::getContextByKey("users_page"));
        $this->itemsPerPage = intval(Context::getContextByKey("users_itemsPerPage"));
        if ($this->pageNumber < 1) $this->pageNumber = 1;
        if ($this->itemsPerPage < 1) $this->itemsPerPage = 10;
    }

    // =============================
    // SETPARAMSTOCONTEXT
    // =============================
    private function setParamsToContext(): void
    {
        // Persiste estado actual de filtros
        Context::setContext("users_partialName", $this->partialName, true);
        Context::setContext("users_status", $this->status, true);
        Context::setContext("users_usertipo", $this->usertipo, true);
        Context::setContext("users_orderBy", $this->orderBy, true);
        Context::setContext("users_orderDescending", $this->orderDescending, true);
        Context::setContext("users_page", $this->pageNumber, true);
        Context::setContext("users_itemsPerPage", $this->itemsPerPage, true);
    }

    // =============================
    // SETPARAMSTODATAVIEW
    // =============================
    private function setParamsToDataView(): void
    {
        // Prepara datos de lista y paginacion para la vista
        $this->viewData["partialName"] = $this->partialName;
        $this->viewData["status"] = $this->status;
        $this->viewData["usertipo"] = $this->usertipo;
        $this->viewData["orderBy"] = $this->orderBy;
        $this->viewData["orderDescending"] = $this->orderDescending;
        $this->viewData["pageNum"] = $this->pageNumber;
        $this->viewData["itemsPerPage"] = $this->itemsPerPage;
        $this->viewData["usersCount"] = $this->usersCount;
        $this->viewData["pages"] = $this->pages;
        $this->viewData["users"] = $this->users;
        $this->viewData["totalUsers"] = $this->usersCount;

        $statusKey = "status_" . ($this->status === "" ? "EMP" : $this->status);
        $this->viewData[$statusKey] = "selected";

        $tipoKey = "tipo_" . ($this->usertipo === "" ? "EMP" : $this->usertipo);
        $this->viewData[$tipoKey] = "selected";

        $this->viewData["pagination"] = Paging::getPagination(
            $this->usersCount,
            $this->itemsPerPage,
            $this->pageNumber,
            "index.php?page=Security_Users",
            "Security_Users"
        );
    }
}