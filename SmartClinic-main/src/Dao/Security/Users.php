<?php

namespace Dao\Security;

use Dao\Table;

/**
 * Manages user accounts and their active role assignments.
 *
 * The `usuario.usertipo` column is retained only for compatibility with older
 * code. Authorization must use the active rows in `roles_usuarios`, where one
 * user can own any number of roles.
 */
class Users extends Table
{
    /**
     * Returns every user once, including all active role IDs and names.
     */
    public static function getAllUsers(): array
    {
        return self::searchUsers();
    }

    /**
     * Returns one user with its active roles aggregated into comma/delimited
     * strings that controllers can safely convert to arrays.
     */
    public static function getUserById(int $usercod): array|false
    {
        $sql = "SELECT
                    u.usercod,
                    u.username,
                    u.useremail,
                    u.userest,
                    u.usertipo,
                    GROUP_CONCAT(DISTINCT r.rolId ORDER BY r.rolId SEPARATOR ',') AS role_ids,
                    GROUP_CONCAT(DISTINCT r.rolNombre ORDER BY r.rolId SEPARATOR '||') AS role_names
                FROM usuario u
                LEFT JOIN roles_usuarios ru
                    ON ru.usuarioId = u.usercod
                   AND ru.ruStatus = 'ACT'
                   AND ru.ruFechaInicio <= CURRENT_TIMESTAMP
                   AND ru.ruFechaFin >= CURRENT_TIMESTAMP
                LEFT JOIN roles r
                    ON r.rolId = ru.rolId
                   AND r.rolStatus = 'ACT'
                WHERE u.usercod = :usercod
                GROUP BY
                    u.usercod,
                    u.username,
                    u.useremail,
                    u.userest,
                    u.usertipo";

        return self::obtenerUnRegistro($sql, ["usercod" => $usercod]);
    }

    /**
     * Returns active roles with the effective access granted by each role.
     *
     * The role DAO owns permission composition. The optional connection keeps
     * role validation inside the same transaction as a user write.
     */
    public static function getActiveRoles(&$conn = null): array
    {
        return Roles::getActiveRolesWithPermissions($conn);
    }

    /**
     * Inserts the base user row. Call createUserWithRoles() from controllers so
     * the account and its roles are committed atomically.
     */
    public static function insertUser(
        string $username,
        string $useremail,
        string $userpswd,
        string $userfching,
        string $userpswdest,
        string $userpswdexp,
        string $userest,
        string $useractcod,
        string $userpswdchg,
        string $usertipo,
        &$conn = null
    ): int {
        $connection = $conn ?? self::getConn();
        $sql = "INSERT INTO usuario
                    (username, useremail, userpswd, userfching, userpswdest,
                     userpswdexp, userest, useractcod, userpswdchg, usertipo)
                VALUES
                    (:username, :useremail, :userpswd, :userfching, :userpswdest,
                     :userpswdexp, :userest, :useractcod, :userpswdchg, :usertipo)";
        $params = [
            "username" => $username,
            "useremail" => $useremail,
            "userpswd" => $userpswd,
            "userfching" => $userfching,
            "userpswdest" => $userpswdest,
            "userpswdexp" => $userpswdexp,
            "userest" => $userest,
            "useractcod" => $useractcod,
            "userpswdchg" => $userpswdchg,
            "usertipo" => $usertipo,
        ];

        self::executeNonQuery($sql, $params, $connection);
        return (int) $connection->lastInsertId();
    }

    /**
     * Updates editable account fields. Password changes remain in the dedicated
     * password flow and are intentionally not handled here.
     */
    public static function updateUser(
        int $usercod,
        string $username,
        string $useremail,
        string $userpswd,
        string $userfching,
        string $userpswdest,
        string $userpswdexp,
        string $userest,
        string $useractcod,
        string $userpswdchg,
        string $usertipo,
        &$conn = null
    ): int {
        $sql = "UPDATE usuario SET
                    username = :username,
                    useremail = :useremail,
                    userest = :userest,
                    usertipo = :usertipo
                WHERE usercod = :usercod";
        $params = [
            "usercod" => $usercod,
            "username" => $username,
            "useremail" => $useremail,
            "userest" => $userest,
            "usertipo" => $usertipo,
        ];

        return (int) self::executeNonQuery($sql, $params, $conn);
    }

    /**
     * Creates an account and all selected role assignments in one transaction.
     */
    public static function createUserWithRoles(array $user, array $roleIds): int
    {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $legacyType = self::legacyTypeForRoles($roleIds);
            $userId = self::insertUser(
                $user["username"],
                $user["useremail"],
                $user["userpswd"],
                $user["userfching"],
                $user["userpswdest"],
                $user["userpswdexp"],
                $user["userest"],
                $user["useractcod"],
                $user["userpswdchg"],
                $legacyType,
                $conn
            );
            self::replaceRoles($userId, $roleIds, $conn);
            $conn->commit();
            return $userId;
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Updates an account and replaces its complete active role set atomically.
     */
    public static function updateUserWithRoles(array $user, array $roleIds): void
    {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $legacyType = self::legacyTypeForRoles($roleIds);
            self::updateUser(
                (int) $user["usercod"],
                $user["username"],
                $user["useremail"],
                "",
                $user["userfching"],
                $user["userpswdest"],
                $user["userpswdexp"],
                $user["userest"],
                $user["useractcod"],
                "",
                $legacyType,
                $conn
            );
            self::replaceRoles((int) $user["usercod"], $roleIds, $conn);
            $conn->commit();
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Replaces active roles without deleting history.
     *
     * Existing rows are deactivated first. A previous user/role row is reused
     * when possible; otherwise a new assignment is inserted.
     */
    public static function replaceRoles(int $userId, array $roleIds, &$conn = null): void
    {
        $normalizedRoleIds = array_values(array_unique(array_filter(
            array_map("intval", $roleIds),
            static fn(int $roleId): bool => $roleId > 0
        )));

        if ($normalizedRoleIds === []) {
            throw new \InvalidArgumentException("Debe seleccionar al menos un rol.");
        }

        $activeRoleIds = array_map(
            "intval",
            array_column(self::getActiveRoles($conn), "rolId")
        );
        if (array_diff($normalizedRoleIds, $activeRoleIds) !== []) {
            throw new \InvalidArgumentException("Uno o más roles seleccionados no están activos.");
        }

        self::executeNonQuery(
            "UPDATE roles_usuarios
             SET ruStatus = 'INA', ruFechaFin = CURRENT_TIMESTAMP
             WHERE usuarioId = :usuarioId",
            ["usuarioId" => $userId],
            $conn
        );

        foreach ($normalizedRoleIds as $roleId) {
            $existing = self::obtenerUnRegistro(
                "SELECT rolUsuarioId
                 FROM roles_usuarios
                 WHERE usuarioId = :usuarioId AND rolId = :rolId
                 ORDER BY rolUsuarioId ASC
                 LIMIT 1",
                ["usuarioId" => $userId, "rolId" => $roleId],
                $conn
            );

            if ($existing) {
                self::executeNonQuery(
                    "UPDATE roles_usuarios
                     SET ruStatus = 'ACT',
                         ruFechaInicio = CURRENT_TIMESTAMP,
                         ruFechaFin = '2099-12-31 23:59:59'
                     WHERE rolUsuarioId = :rolUsuarioId",
                    ["rolUsuarioId" => (int) $existing["rolUsuarioId"]],
                    $conn
                );
                continue;
            }

            self::executeNonQuery(
                "INSERT INTO roles_usuarios
                    (usuarioId, rolId, ruStatus, ruFechaInicio, ruFechaFin)
                 VALUES
                    (:usuarioId, :rolId, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59')",
                ["usuarioId" => $userId, "rolId" => $roleId],
                $conn
            );
        }
    }

    /**
     * Deletes a user. The roles_usuarios foreign key removes assignments.
     */
    public static function deleteUser(int $usercod): int
    {
        return (int) self::executeNonQuery(
            "DELETE FROM usuario WHERE usercod = :usercod",
            ["usercod" => $usercod]
        );
    }

    /**
     * Searches users by name, status, and membership in one active role.
     *
     * EXISTS filters by role without trimming the role list displayed for each
     * matching user. GROUP_CONCAT also prevents duplicate user rows.
     */
    public static function searchUsers(
        string $partialName = "",
        string $status = "",
        int $roleId = 0
    ): array {
        $sql = "SELECT
                    u.usercod,
                    u.username,
                    u.useremail,
                    u.userest,
                    u.usertipo,
                    GROUP_CONCAT(DISTINCT r.rolId ORDER BY r.rolId SEPARATOR ',') AS role_ids,
                    GROUP_CONCAT(DISTINCT r.rolNombre ORDER BY r.rolId SEPARATOR '||') AS role_names
                FROM usuario u
                LEFT JOIN roles_usuarios ru
                    ON ru.usuarioId = u.usercod
                   AND ru.ruStatus = 'ACT'
                   AND ru.ruFechaInicio <= CURRENT_TIMESTAMP
                   AND ru.ruFechaFin >= CURRENT_TIMESTAMP
                LEFT JOIN roles r
                    ON r.rolId = ru.rolId
                   AND r.rolStatus = 'ACT'
                WHERE 1 = 1";
        $params = [];

        if ($partialName !== "") {
            $sql .= " AND u.username LIKE :partialName";
            $params["partialName"] = "%" . $partialName . "%";
        }

        if (in_array($status, ["ACT", "INA"], true)) {
            $sql .= " AND u.userest = :status";
            $params["status"] = $status;
        }

        if ($roleId > 0) {
            $sql .= " AND EXISTS (
                        SELECT 1
                        FROM roles_usuarios filter_ru
                        INNER JOIN roles filter_r ON filter_r.rolId = filter_ru.rolId
                        WHERE filter_ru.usuarioId = u.usercod
                          AND filter_ru.rolId = :roleId
                          AND filter_ru.ruStatus = 'ACT'
                          AND filter_ru.ruFechaInicio <= CURRENT_TIMESTAMP
                          AND filter_ru.ruFechaFin >= CURRENT_TIMESTAMP
                          AND filter_r.rolStatus = 'ACT'
                    )";
            $params["roleId"] = $roleId;
        }

        $sql .= " GROUP BY
                    u.usercod,
                    u.username,
                    u.useremail,
                    u.userest,
                    u.usertipo
                  ORDER BY u.username ASC";

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Keeps the obsolete usertipo value coherent for untouched legacy code.
     */
    private static function legacyTypeForRoles(array $roleIds): string
    {
        return in_array(1, array_map("intval", $roleIds), true) ? "ADM" : "NOR";
    }
}
