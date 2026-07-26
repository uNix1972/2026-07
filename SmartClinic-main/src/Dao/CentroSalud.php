<?php

namespace Dao;

/**
 * Acceso a datos del catálogo de centros de salud.
 *
 * Todas las consultas se concentran en este DAO para que los controladores
 * no conozcan detalles de SQL. Los centros no se eliminan físicamente:
 * su estado cambia entre ACT e INA para conservar el historial y permitir
 * que otros módulos los referencien mediante llaves foráneas en el futuro.
 */
class CentroSalud extends Table
{
    /**
     * Obtiene todos los centros y permite filtrar por sus datos principales.
     *
     * Se usan parámetros distintos para cada LIKE porque el proyecto trabaja
     * con sentencias preparadas de PDO y así se evita reutilizar un mismo
     * marcador nombrado varias veces dentro de la consulta.
     */
    public static function getAll(string $search = ""): array
    {
        if ($search === "") {
            $sql = "SELECT * FROM centro_salud ORDER BY nombre ASC";
            return parent::obtenerRegistros($sql, []);
        }

        $term = "%" . $search . "%";
        $sql = "SELECT * FROM centro_salud
                WHERE codigo LIKE :codigo
                   OR nombre LIKE :nombre
                   OR tipo LIKE :tipo
                   OR ciudad LIKE :ciudad
                ORDER BY nombre ASC";

        return parent::obtenerRegistros($sql, [
            "codigo" => $term,
            "nombre" => $term,
            "tipo" => $term,
            "ciudad" => $term
        ]);
    }

    /**
     * Obtiene solo los centros activos para futuros selectores operativos.
     */
    public static function getActivos(): array
    {
        $sql = "SELECT * FROM centro_salud
                WHERE estado = 'ACT'
                ORDER BY nombre ASC";

        return parent::obtenerRegistros($sql, []);
    }

    /**
     * Busca un centro por su llave primaria.
     *
     * El método base devuelve false cuando no encuentra una fila, por eso
     * este método no declara un tipo de retorno array estricto.
     */
    public static function getById(int $id)
    {
        $sql = "SELECT * FROM centro_salud WHERE id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    /**
     * Comprueba si un código ya pertenece a otro centro.
     *
     * $excludeId permite editar un registro sin considerar su propio código
     * como duplicado.
     */
    public static function existsCodigo(string $codigo, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM centro_salud
                WHERE codigo = :codigo";
        $params = ["codigo" => $codigo];

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
            $params["exclude_id"] = $excludeId;
        }

        $row = parent::obtenerUnRegistro($sql, $params);
        return (int) ($row["total"] ?? 0) > 0;
    }

    /**
     * Registra un centro de salud y devuelve su ID autogenerado.
     */
    public static function insert(
        string $codigo,
        string $nombre,
        string $tipo,
        string $direccion,
        string $ciudad,
        string $telefono,
        string $email
    ): int {
        $sql = "INSERT INTO centro_salud
                    (codigo, nombre, tipo, direccion, ciudad, telefono, email)
                VALUES
                    (:codigo, :nombre, :tipo, :direccion, :ciudad, :telefono, :email)";

        parent::executeNonQuery($sql, [
            "codigo" => $codigo,
            "nombre" => $nombre,
            "tipo" => $tipo,
            "direccion" => $direccion,
            "ciudad" => $ciudad,
            "telefono" => $telefono === "" ? null : $telefono,
            "email" => $email === "" ? null : $email
        ]);

        return (int) parent::getLastInsertId();
    }

    /**
     * Actualiza los datos descriptivos de un centro existente.
     *
     * El estado se administra por separado para que activar o desactivar sea
     * una acción explícita, auditable y protegida por CSRF.
     */
    public static function update(
        int $id,
        string $codigo,
        string $nombre,
        string $tipo,
        string $direccion,
        string $ciudad,
        string $telefono,
        string $email
    ): bool {
        $sql = "UPDATE centro_salud
                SET codigo = :codigo,
                    nombre = :nombre,
                    tipo = :tipo,
                    direccion = :direccion,
                    ciudad = :ciudad,
                    telefono = :telefono,
                    email = :email
                WHERE id = :id";

        return parent::executeNonQuery($sql, [
            "id" => $id,
            "codigo" => $codigo,
            "nombre" => $nombre,
            "tipo" => $tipo,
            "direccion" => $direccion,
            "ciudad" => $ciudad,
            "telefono" => $telefono === "" ? null : $telefono,
            "email" => $email === "" ? null : $email
        ]);
    }

    /**
     * Activa o desactiva un centro sin borrar su registro.
     */
    public static function setStatus(int $id, string $estado): bool
    {
        $sql = "UPDATE centro_salud
                SET estado = :estado
                WHERE id = :id";

        return parent::executeNonQuery($sql, [
            "id" => $id,
            "estado" => $estado
        ]);
    }
}

