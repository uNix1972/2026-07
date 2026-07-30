<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Dao\Security\Users as DaoUsers;
use Utilities\Context;
use Utilities\Paging;
use Utilities\Security;
use Views\Renderer;

/**
 * Lists users once per account and filters by active role membership.
 */
class Users extends PrivateController
{
    private array $viewData = [];
    private string $partialName = "";
    private string $status = "";
    private int $roleId = 0;
    private int $pageNumber = 1;
    private int $itemsPerPage = 10;
    private array $users = [];
    private int $usersCount = 0;
    private int $pages = 1;

    public function run(): void
    {
        $this->getParamsFromContext();
        $this->getParams();

        $matchingUsers = DaoUsers::searchUsers(
            $this->partialName,
            $this->status,
            $this->roleId
        );
        $this->usersCount = count($matchingUsers);
        $this->pages = $this->usersCount > 0
            ? (int) ceil($this->usersCount / $this->itemsPerPage)
            : 1;
        $this->pageNumber = min($this->pageNumber, $this->pages);

        $start = ($this->pageNumber - 1) * $this->itemsPerPage;
        $this->users = array_slice($matchingUsers, $start, $this->itemsPerPage);

        $loggedUserId = Security::getUserId();
        foreach ($this->users as &$user) {
            $user["is_self"] = (int) $user["usercod"] === (int) $loggedUserId;
            $roleNames = trim((string) ($user["role_names"] ?? ""));
            $user["roles"] = $roleNames === ""
                ? [["rolNombre" => "Sin rol activo"]]
                : array_map(
                    static fn(string $name): array => ["rolNombre" => $name],
                    explode("||", $roleNames)
                );
        }
        unset($user);

        $this->setParamsToContext();
        $this->setParamsToDataView();
        Renderer::render("security/users", $this->viewData);
    }

    /**
     * Reads and sanitizes filters submitted in the query string.
     */
    private function getParams(): void
    {
        $this->partialName = \Utilities\Validators::sanitizeString(
            $_GET["partialName"] ?? $this->partialName
        );
        $this->status = \Utilities\Validators::sanitizeAlphaNum(
            $_GET["status"] ?? $this->status
        );
        $this->roleId = \Utilities\Validators::sanitizeInt(
            $_GET["role_id"] ?? $this->roleId,
            0
        ) ?? 0;
        $this->pageNumber = \Utilities\Validators::sanitizeInt(
            $_GET["pageNum"] ?? $this->pageNumber,
            1
        ) ?? $this->pageNumber;
        $this->itemsPerPage = \Utilities\Validators::sanitizeInt(
            $_GET["itemsPerPage"] ?? $this->itemsPerPage,
            1,
            100
        ) ?? $this->itemsPerPage;
    }

    /**
     * Restores the last filters used in this browser session.
     */
    private function getParamsFromContext(): void
    {
        $this->partialName = (string) Context::getContextByKey("users_partialName");
        $this->status = (string) Context::getContextByKey("users_status");
        $this->roleId = (int) Context::getContextByKey("users_role_id");
        $storedPage = (int) Context::getContextByKey("users_page");
        $storedItemsPerPage = (int) Context::getContextByKey("users_itemsPerPage");
        $this->pageNumber = $storedPage > 0 ? $storedPage : 1;
        $this->itemsPerPage = $storedItemsPerPage > 0 ? $storedItemsPerPage : 10;
    }

    /**
     * Persists filters so list/detail navigation does not lose context.
     */
    private function setParamsToContext(): void
    {
        Context::setContext("users_partialName", $this->partialName, true);
        Context::setContext("users_status", $this->status, true);
        Context::setContext("users_role_id", $this->roleId, true);
        Context::setContext("users_page", $this->pageNumber, true);
        Context::setContext("users_itemsPerPage", $this->itemsPerPage, true);
    }

    /**
     * Builds filter options, pagination, and user rows for the template.
     */
    private function setParamsToDataView(): void
    {
        $this->viewData["partialName"] = $this->partialName;
        $this->viewData["pageNum"] = $this->pageNumber;
        $this->viewData["itemsPerPage"] = $this->itemsPerPage;
        $this->viewData["usersCount"] = $this->usersCount;
        $this->viewData["pages"] = $this->pages;
        $this->viewData["users"] = $this->users;
        $this->viewData["totalUsers"] = $this->usersCount;

        $statusKey = "status_" . ($this->status === "" ? "EMP" : $this->status);
        $this->viewData[$statusKey] = "selected";

        $this->viewData["role_all_selected"] = $this->roleId === 0;
        $this->viewData["roleOptions"] = array_map(
            fn(array $role): array => [
                "rolId" => (int) $role["rolId"],
                "rolNombre" => $role["rolNombre"],
                "selected" => (int) $role["rolId"] === $this->roleId,
            ],
            DaoUsers::getActiveRoles()
        );

        $this->viewData["pagination"] = Paging::getPagination(
            $this->usersCount,
            $this->itemsPerPage,
            $this->pageNumber,
            "index.php?page=Security_Users",
            "Security_Users"
        );
    }
}
