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
    public static function getAll(
        string $search = "",
        string $status = ""
    ): array
    {
        $conditions = [];
        $params = [];

        if ($search !== "") {
            $term = "%" . $search . "%";
            $conditions[] = "(
                codigo LIKE :codigo
                OR nombre LIKE :nombre
                OR tipo LIKE :tipo
                OR ciudad LIKE :ciudad
            )";
            $params = [
                "codigo" => $term,
                "nombre" => $term,
                "tipo" => $term,
                "ciudad" => $term
            ];
        }

        if (in_array($status, ["ACT", "INA"], true)) {
            $conditions[] = "estado = :estado";
            $params["estado"] = $status;
        }

        $sql = "SELECT * FROM centro_salud";
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY nombre ASC";

        return parent::obtenerRegistros($sql, $params);
    }

    /**
     * Resume la operación visible en el espacio unificado del catálogo.
     *
     * Las tres cifras se calculan en una sola consulta para evitar cargar
     * listados completos únicamente para contar centros, asignaciones o citas.
     */
    public static function getWorkspaceSummary(): array
    {
        $sql = "SELECT
                    (
                        SELECT COUNT(*)
                        FROM centro_salud
                        WHERE estado = 'ACT'
                    ) AS centros_activos,
                    (
                        SELECT COUNT(*)
                        FROM medico_centro_salud
                        WHERE estado = 'ACT'
                    ) AS medicos_asignados,
                    (
                        SELECT COUNT(*)
                        FROM cita
                        WHERE fecha_hora >= CURRENT_DATE
                          AND fecha_hora < DATE_ADD(
                              CURRENT_DATE,
                              INTERVAL 1 DAY
                          )
                    ) AS citas_hoy";

        $row = parent::obtenerUnRegistro($sql, []);

        return is_array($row)
            ? $row
            : [
                "centros_activos" => 0,
                "medicos_asignados" => 0,
                "citas_hoy" => 0
            ];
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
     * Resume las citas futuras activas que impiden desactivar un centro.
     *
     * Los estados 3, 4 y 5 son terminales: completada, cancelada y no
     * asistió. Esas citas conservan su centro como dato histórico, pero no
     * requieren que la ubicación siga disponible para atención futura.
     *
     * @return array{total:int|string, proxima_fecha:?string}
     */
    public static function getFutureActiveAppointmentSummary(int $id): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       MIN(fecha_hora) AS proxima_fecha
                FROM cita
                WHERE centro_salud_id = :centro_salud_id
                  AND fecha_hora >= CURRENT_TIMESTAMP
                  AND estado_id NOT IN (3, 4, 5)";

        $row = parent::obtenerUnRegistro(
            $sql,
            ["centro_salud_id" => $id]
        );

        return is_array($row)
            ? $row
            : ["total" => 0, "proxima_fecha" => null];
    }

    /**
     * Activa o desactiva un centro sin borrar su registro.
     *
     * Antes de solicitar INA, el controlador debe consultar
     * getFutureActiveAppointmentSummary() y rechazar la operación cuando
     * existan citas que todavía deban atenderse en este centro.
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
