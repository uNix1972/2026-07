<?php

namespace Dao\Security;

use Dao\Table;

// DAO de funciones/permisos de seguridad
class Funciones extends Table
{
    // =============================
    // GETFUNCIONES
    // =============================
    public static function getFunciones(
        string $partialName = "",
        string $status = "",
        string $type = "",
        string $orderBy = "",
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        // Lista funciones con filtros, orden y paginacion
        $sql = "SELECT funcionNombre AS fncod, funcionDescripcion AS fndsc, funcionStatus AS fnest, 'FNC' AS fntyp FROM funciones WHERE 1=1 ";
        $countSql = "SELECT COUNT(*) as total FROM funciones WHERE 1=1 ";
        $params = [];

        if ($partialName !== "") {
            $sql .= " AND (funcionNombre LIKE :partialName OR funcionDescripcion LIKE :partialName) ";
            $countSql .= " AND (funcionNombre LIKE :partialName OR funcionDescripcion LIKE :partialName) ";
            $params["partialName"] = "%" . $partialName . "%";
        }

        if ($status !== "") {
            $sql .= " AND funcionStatus = :status ";
            $countSql .= " AND funcionStatus = :status ";
            $params["status"] = $status;
        }

        $columnMap = [
            "fncod" => "funcionNombre",
            "fndsc" => "funcionDescripcion",
            "fnest" => "funcionStatus"
        ];

        if ($orderBy !== "" && isset($columnMap[$orderBy])) {
            $sql .= " ORDER BY " . $columnMap[$orderBy];
            if ($orderDescending) {
                $sql .= " DESC";
            }
        }

        $totalResult = self::obtenerUnRegistro($countSql, $params);
        $total = $totalResult["total"] ?? 0;

        if ($itemsPerPage > 0) {
            $offset = $page * $itemsPerPage;
            $sql .= " LIMIT $offset, $itemsPerPage";
        }

        $registros = self::obtenerRegistros($sql, $params);

        return [
            "funciones" => $registros,
            "total" => $total,
            "page" => $page,
            "itemsPerPage" => $itemsPerPage
        ];
    }

    // =============================
    // GETFUNCIONBYID
    // =============================
    public static function getFuncionById(string $fncod): array|false
    {
        $sql = "SELECT funcionNombre AS fncod, funcionDescripcion AS fndsc, funcionStatus AS fnest, 'FNC' AS fntyp FROM funciones WHERE funcionNombre = :funcionNombre";
        $params = ["funcionNombre" => $fncod];
        return self::obtenerUnRegistro($sql, $params);
    }

    // =============================
    // INSERTFUNCION
    // =============================
    public static function insertFuncion(
        string $fncod,
        string $fndsc,
        string $fnest,
        string $fntyp
    ): int {
        // Inserta una nueva funcion/permiso sin almacenamiento de tipo tipo
        $sql = "INSERT INTO funciones (funcionNombre, funcionDescripcion, funcionStatus)
                VALUES (:funcionNombre, :funcionDescripcion, :funcionStatus)";
        $params = [
            "funcionNombre" => $fncod,
            "funcionDescripcion" => $fndsc,
            "funcionStatus" => $fnest
        ];
        return self::executeNonQuery($sql, $params);
    }

    // =============================
    // UPDATEFUNCION
    // =============================
    public static function updateFuncion(
        string $fncod,
        string $fndsc,
        string $fnest,
        string $fntyp,
        ?string $originalFncod = null
    ): int {
        // Actualiza descripcion/estado de funcion existente
        $sql = "UPDATE funciones
                SET funcionNombre = :funcionNombre,
                    funcionDescripcion = :funcionDescripcion,
                    funcionStatus = :funcionStatus
                WHERE funcionNombre = " . ($originalFncod !== null ? ":originalFncod" : ":funcionNombre");
        $params = [
            "funcionNombre" => $fncod,
            "funcionDescripcion" => $fndsc,
            "funcionStatus" => $fnest
        ];
        if ($originalFncod !== null) {
            $params["originalFncod"] = $originalFncod;
        }
        return self::executeNonQuery($sql, $params);
    }

    // =============================
    // DELETEFUNCION
    // =============================
    public static function deleteFuncion(string $fncod): int
    {
        $sql = "DELETE FROM funciones WHERE funcionNombre = :funcionNombre";
        $params = ["funcionNombre" => $fncod];
        return self::executeNonQuery($sql, $params);
    }
}
