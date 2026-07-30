<?php

namespace Dao\Security;

use Dao\Table;

/**
 * Owns the role catalog and role-to-function permission assignments.
 *
 * Active assignments in `funciones_roles` are the source of truth for normal
 * roles. Role 1 is the system Administrator and receives every active function
 * automatically because Utilities\Security has an explicit administrator
 * bypass; its displayed function list is therefore read-only and complete.
 */
class Roles extends Table
{
    private const ADMIN_ROLE_ID = 1;

    /**
     * Returns paginated roles with their effective permission details.
     */
    public static function getRoles(
        string $partialName = "",
        string $status = "",
        string $orderBy = "",
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        $sql = "SELECT
                    rolId,
                    rolNombre AS rolescod,
                    rolDescripcion AS rolesdsc,
                    rolStatus AS rolesest
                FROM roles
                WHERE 1 = 1";
        $countSql = "SELECT COUNT(*) AS total FROM roles WHERE 1 = 1";
        $params = [];

        if ($partialName !== "") {
            $sql .= " AND (rolNombre LIKE :partialName OR rolDescripcion LIKE :partialName)";
            $countSql .= " AND (rolNombre LIKE :partialName OR rolDescripcion LIKE :partialName)";
            $params["partialName"] = "%" . $partialName . "%";
        }

        if (in_array($status, ["ACT", "INA"], true)) {
            $sql .= " AND rolStatus = :status";
            $countSql .= " AND rolStatus = :status";
            $params["status"] = $status;
        }

        $columnMap = [
            "rolescod" => "rolNombre",
            "rolesdsc" => "rolDescripcion",
            "rolesest" => "rolStatus",
        ];
        if ($orderBy !== "" && isset($columnMap[$orderBy])) {
            $sql .= " ORDER BY " . $columnMap[$orderBy];
            $sql .= $orderDescending ? " DESC" : " ASC";
        } else {
            $sql .= " ORDER BY rolId ASC";
        }

        $totalResult = self::obtenerUnRegistro($countSql, $params);
        $total = (int) ($totalResult["total"] ?? 0);

        if ($itemsPerPage > 0) {
            $offset = max(0, $page) * $itemsPerPage;
            $sql .= " LIMIT $offset, $itemsPerPage";
        }

        $roles = self::obtenerRegistros($sql, $params);
        self::attachPermissions($roles);

        return [
            "roles" => $roles,
            "total" => $total,
            "page" => $page,
            "itemsPerPage" => $itemsPerPage,
        ];
    }

    /**
     * Returns an active role catalog enriched with effective permissions.
     *
     * User administration uses this method to explain exactly what each
     * selected role grants.
     */
    public static function getActiveRolesWithPermissions(&$conn = null): array
    {
        $roles = self::obtenerRegistros(
            "SELECT
                rolId,
                rolNombre,
                rolDescripcion,
                rolStatus
             FROM roles
             WHERE rolStatus = 'ACT'
             ORDER BY rolId ASC",
            [],
            $conn
        );
        self::attachPermissions($roles, $conn);
        return $roles;
    }

    /**
     * Finds a role by its current name and includes its numeric identifier.
     */
    public static function getRoleById(string $rolNombre, &$conn = null): array|false
    {
        return self::obtenerUnRegistro(
            "SELECT
                rolId,
                rolNombre AS rolescod,
                rolDescripcion AS rolesdsc,
                rolStatus AS rolesest
             FROM roles
             WHERE rolNombre = :rolNombre",
            ["rolNombre" => $rolNombre],
            $conn
        );
    }

    /**
     * Returns all active functions formatted for permission administration.
     */
    public static function getActiveFunctions(&$conn = null): array
    {
        $functions = self::obtenerRegistros(
            "SELECT funcionId, funcionNombre, funcionDescripcion, funcionStatus
             FROM funciones
             WHERE funcionStatus = 'ACT'
             ORDER BY funcionId ASC",
            [],
            $conn
        );

        $functions = array_map(
            [self::class, "decorateFunction"],
            $functions
        );
        self::sortFunctions($functions);
        return $functions;
    }

    /**
     * Returns the effective active function IDs for a role.
     *
     * Administrator receives every active function because its authorization
     * is automatic; normal roles return current funciones_roles assignments.
     */
    public static function getRolePermissionIds(int $roleId, &$conn = null): array
    {
        if ($roleId === self::ADMIN_ROLE_ID) {
            return array_map(
                "intval",
                array_column(self::getActiveFunctions($conn), "funcionId")
            );
        }

        $rows = self::obtenerRegistros(
            "SELECT fr.funcionId
             FROM funciones_roles fr
             INNER JOIN funciones f ON f.funcionId = fr.funcionId
             WHERE fr.rolId = :rolId
               AND fr.frStatus = 'ACT'
               AND fr.frFechaInicio <= CURRENT_TIMESTAMP
               AND fr.frFechaFin >= CURRENT_TIMESTAMP
               AND f.funcionStatus = 'ACT'
             ORDER BY fr.funcionId ASC",
            ["rolId" => $roleId],
            $conn
        );

        return array_map("intval", array_column($rows, "funcionId"));
    }

    /**
     * Inserts a base role row and returns its numeric ID.
     */
    public static function insertRole(
        string $rolNombre,
        string $rolDescripcion,
        string $rolStatus,
        &$conn = null
    ): int {
        $connection = $conn ?? self::getConn();
        self::executeNonQuery(
            "INSERT INTO roles (rolNombre, rolDescripcion, rolStatus)
             VALUES (:rolNombre, :rolDescripcion, :rolStatus)",
            [
                "rolNombre" => $rolNombre,
                "rolDescripcion" => $rolDescripcion,
                "rolStatus" => $rolStatus,
            ],
            $connection
        );

        return (int) $connection->lastInsertId();
    }

    /**
     * Updates the base role row while preserving its numeric identifier.
     */
    public static function updateRole(
        string $rolNombre,
        string $rolDescripcion,
        string $rolStatus,
        ?string $originalRolNombre = null,
        &$conn = null
    ): int {
        $whereName = $originalRolNombre ?? $rolNombre;
        return (int) self::executeNonQuery(
            "UPDATE roles
             SET rolNombre = :rolNombre,
                 rolDescripcion = :rolDescripcion,
                 rolStatus = :rolStatus
             WHERE rolNombre = :originalRolNombre",
            [
                "rolNombre" => $rolNombre,
                "rolDescripcion" => $rolDescripcion,
                "rolStatus" => $rolStatus,
                "originalRolNombre" => $whereName,
            ],
            $conn
        );
    }

    /**
     * Creates a role and its initial permission set in one transaction.
     */
    public static function createRoleWithPermissions(
        array $role,
        array $functionIds
    ): int {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $roleId = self::insertRole(
                $role["rolescod"],
                $role["rolesdsc"],
                $role["rolesest"],
                $conn
            );
            self::replacePermissions($roleId, $functionIds, $conn);
            $conn->commit();
            return $roleId;
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Updates role metadata and permissions atomically.
     *
     * Administrator permissions are not rewritten because its full access is
     * enforced by the authorization layer independently of assignment rows.
     */
    public static function updateRoleWithPermissions(
        array $role,
        string $originalRoleName,
        array $functionIds
    ): void {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $storedRole = self::getRoleById($originalRoleName, $conn);
            if (!$storedRole) {
                throw new \RuntimeException("Rol no encontrado.");
            }

            self::updateRole(
                $role["rolescod"],
                $role["rolesdsc"],
                $role["rolesest"],
                $originalRoleName,
                $conn
            );

            $roleId = (int) $storedRole["rolId"];
            if ($roleId !== self::ADMIN_ROLE_ID) {
                self::replacePermissions($roleId, $functionIds, $conn);
            }

            $conn->commit();
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Replaces a role's permission set without deleting assignment history.
     */
    public static function replacePermissions(
        int $roleId,
        array $functionIds,
        &$conn = null
    ): void {
        if ($roleId === self::ADMIN_ROLE_ID) {
            throw new \InvalidArgumentException(
                "El acceso total del Administrador no se puede modificar."
            );
        }

        $normalizedIds = array_values(array_unique(array_filter(
            array_map("intval", $functionIds),
            static fn(int $functionId): bool => $functionId > 0
        )));
        $activeIds = array_map(
            "intval",
            array_column(self::getActiveFunctions($conn), "funcionId")
        );
        if (array_diff($normalizedIds, $activeIds) !== []) {
            throw new \InvalidArgumentException(
                "Uno o más accesos seleccionados no están activos."
            );
        }

        self::executeNonQuery(
            "UPDATE funciones_roles
             SET frStatus = 'INA', frFechaFin = CURRENT_TIMESTAMP
             WHERE rolId = :rolId",
            ["rolId" => $roleId],
            $conn
        );

        foreach ($normalizedIds as $functionId) {
            $existing = self::obtenerUnRegistro(
                "SELECT funcionRolId
                 FROM funciones_roles
                 WHERE rolId = :rolId AND funcionId = :funcionId
                 ORDER BY funcionRolId ASC
                 LIMIT 1",
                ["rolId" => $roleId, "funcionId" => $functionId],
                $conn
            );

            if ($existing) {
                self::executeNonQuery(
                    "UPDATE funciones_roles
                     SET frStatus = 'ACT',
                         frFechaInicio = CURRENT_TIMESTAMP,
                         frFechaFin = '2099-12-31 23:59:59'
                     WHERE funcionRolId = :funcionRolId",
                    ["funcionRolId" => (int) $existing["funcionRolId"]],
                    $conn
                );
                continue;
            }

            self::executeNonQuery(
                "INSERT INTO funciones_roles
                    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
                 VALUES
                    (:funcionId, :rolId, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59')",
                ["funcionId" => $functionId, "rolId" => $roleId],
                $conn
            );
        }
    }

    /**
     * Soft-deactivates a role and all current assignments in one transaction.
     *
     * Physical deletion would discard security history and is blocked by the
     * existing foreign keys once a role has been used.
     */
    public static function deleteRole(string $rolNombre): int
    {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $role = self::getRoleById($rolNombre, $conn);
            if (!$role) {
                throw new \RuntimeException("Rol no encontrado.");
            }

            $roleId = (int) $role["rolId"];
            if ($roleId === self::ADMIN_ROLE_ID) {
                throw new \InvalidArgumentException(
                    "El rol Administrador no puede desactivarse."
                );
            }

            self::executeNonQuery(
                "UPDATE roles SET rolStatus = 'INA' WHERE rolId = :rolId",
                ["rolId" => $roleId],
                $conn
            );
            self::executeNonQuery(
                "UPDATE roles_usuarios
                 SET ruStatus = 'INA', ruFechaFin = CURRENT_TIMESTAMP
                 WHERE rolId = :rolId AND ruStatus = 'ACT'",
                ["rolId" => $roleId],
                $conn
            );
            self::executeNonQuery(
                "UPDATE funciones_roles
                 SET frStatus = 'INA', frFechaFin = CURRENT_TIMESTAMP
                 WHERE rolId = :rolId AND frStatus = 'ACT'",
                ["rolId" => $roleId],
                $conn
            );

            $conn->commit();
            return 1;
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Adds permission arrays and display metadata to role rows.
     */
    private static function attachPermissions(array &$roles, &$conn = null): void
    {
        if ($roles === []) {
            return;
        }

        $allFunctions = self::getActiveFunctions($conn);
        $assignedRows = self::obtenerRegistros(
            "SELECT
                fr.rolId,
                f.funcionId,
                f.funcionNombre,
                f.funcionDescripcion,
                f.funcionStatus
             FROM funciones_roles fr
             INNER JOIN funciones f ON f.funcionId = fr.funcionId
             WHERE fr.frStatus = 'ACT'
               AND fr.frFechaInicio <= CURRENT_TIMESTAMP
               AND fr.frFechaFin >= CURRENT_TIMESTAMP
               AND f.funcionStatus = 'ACT'
             ORDER BY fr.rolId ASC, f.funcionId ASC",
            [],
            $conn
        );

        $permissionMap = [];
        foreach ($assignedRows as $function) {
            $roleId = (int) $function["rolId"];
            $permissionMap[$roleId][] = self::decorateFunction($function);
        }
        foreach ($permissionMap as &$permissions) {
            self::sortFunctions($permissions);
        }
        unset($permissions);

        foreach ($roles as &$role) {
            $roleId = (int) $role["rolId"];
            $permissions = $roleId === self::ADMIN_ROLE_ID
                ? $allFunctions
                : ($permissionMap[$roleId] ?? []);
            $role["permissions"] = $permissions;
            $role["permission_count"] = count($permissions);
            $role["has_permissions"] = $permissions !== [];
            $role["automatic_access"] = $roleId === self::ADMIN_ROLE_ID;
        }
        unset($role);
    }

    /**
     * Adds a user-facing category to one internal function record.
     */
    private static function decorateFunction(array $function): array
    {
        $name = (string) ($function["funcionNombre"] ?? "");
        if (str_starts_with($name, "Menu_")) {
            $accessType = "Menú";
            $accessClass = "menu";
        } elseif (str_contains($name, "Controller")) {
            $accessType = "Módulo";
            $accessClass = "module";
        } else {
            $accessType = "Acción";
            $accessClass = "action";
        }

        return [
            "funcionId" => (int) ($function["funcionId"] ?? 0),
            "funcionNombre" => $name,
            "funcionDescripcion" => (string) ($function["funcionDescripcion"] ?? $name),
            "accessType" => $accessType,
            "accessClass" => $accessClass,
        ];
    }

    /**
     * Orders permissions for scanning: menus, modules, then actions.
     */
    private static function sortFunctions(array &$functions): void
    {
        $typeOrder = ["menu" => 1, "module" => 2, "action" => 3];
        usort(
            $functions,
            static function (array $left, array $right) use ($typeOrder): int {
                $leftOrder = $typeOrder[$left["accessClass"]] ?? 99;
                $rightOrder = $typeOrder[$right["accessClass"]] ?? 99;
                if ($leftOrder !== $rightOrder) {
                    return $leftOrder <=> $rightOrder;
                }
                return strcasecmp(
                    $left["funcionDescripcion"],
                    $right["funcionDescripcion"]
                );
            }
        );
    }
}
