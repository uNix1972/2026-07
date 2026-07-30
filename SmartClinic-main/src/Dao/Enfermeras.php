<?php

namespace Dao;

/**
 * Acceso a datos de enfermeras y coordinación de sus asignaciones operativas.
 *
 * Mismo patrón que Dao\Medicos: los datos personales viven en `enfermera`;
 * las ubicaciones se delegan a Dao\EnfermeraCentroSalud. Los métodos
 * coordinadores de creación y edición utilizan una sola transacción para
 * evitar enfermeras sin centros por fallos parciales. usuario_id es un
 * vínculo opcional y único con una cuenta de acceso ya existente.
 */
class Enfermeras extends Table
{
    /**
     * Obtiene el directorio de enfermeras con centros activos y la cuenta
     * de usuario vinculada (si tiene una).
     *
     * La subconsulta evita duplicar enfermeras cuando tienen varias
     * ubicaciones. GROUP_CONCAT produce un resumen legible para la tabla
     * del directorio.
     */
    public static function getAllEnfermeras(): array
    {
        $sql = "SELECT en.*, u.username AS usuario_username,
                       u.useremail AS usuario_useremail,
                       COALESCE((
                           SELECT GROUP_CONCAT(
                               CONCAT(cs.nombre, ' - Área: ', ecs.area)
                               ORDER BY cs.nombre
                               SEPARATOR ', '
                           )
                           FROM enfermera_centro_salud ecs
                           JOIN centro_salud cs ON cs.id = ecs.centro_salud_id
                           WHERE ecs.enfermera_id = en.id
                             AND ecs.estado = 'ACT'
                             AND cs.estado = 'ACT'
                       ), '') AS centros_salud,
                       EXISTS(
                           SELECT 1 FROM enfermera_centro_salud ecs2
                           WHERE ecs2.enfermera_id = en.id
                       ) AS tiene_asignaciones
                FROM enfermera en
                LEFT JOIN usuario u ON u.usercod = en.usuario_id
                ORDER BY en.id DESC";

        return parent::obtenerRegistros($sql, []);
    }

    /**
     * Obtiene una enfermera por su llave primaria.
     *
     * El método base devuelve false cuando no encuentra una fila.
     */
    public static function getEnfermeraById(int $id)
    {
        $sql = "SELECT * FROM enfermera WHERE id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    /**
     * Comprueba si el número de colegiatura pertenece a otra enfermera.
     */
    public static function existsNumColegiatura(
        string $numColegiatura,
        int $excludeId = 0
    ): bool {
        $sql = "SELECT COUNT(*) AS total
                FROM enfermera
                WHERE num_colegiatura = :num_colegiatura";
        $params = ["num_colegiatura" => $numColegiatura];

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
            $params["exclude_id"] = $excludeId;
        }

        $row = parent::obtenerUnRegistro($sql, $params);
        return (int) ($row["total"] ?? 0) > 0;
    }

    /**
     * Lista las cuentas de usuario activas que aún no están vinculadas a
     * ningún médico, paciente ni otra enfermera, para poblar el selector
     * de "vincular a un usuario" del formulario.
     *
     * $excludeEnfermeraId permite que, al editar, la cuenta ya vinculada a
     * ESTA MISMA enfermera no se descarte por estar "tomada" por ella.
     */
    public static function getUsuariosDisponibles(int $excludeEnfermeraId = 0): array
    {
        $sql = "SELECT u.usercod, u.username, u.useremail
                FROM usuario u
                WHERE u.userest = 'ACT'
                  AND NOT EXISTS (
                      SELECT 1 FROM medico m WHERE m.usuario_id = u.usercod
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM paciente p WHERE p.usuario_id = u.usercod
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM enfermera en
                      WHERE en.usuario_id = u.usercod
                        AND en.id != :exclude_id
                  )
                ORDER BY u.username ASC";

        return parent::obtenerRegistros($sql, ["exclude_id" => $excludeEnfermeraId]);
    }

    /**
     * Confirma que una cuenta de usuario esté activa y libre para
     * vincularse a esta enfermera (validación autoritativa antes de
     * guardar, independiente de lo que haya mostrado el selector).
     */
    public static function usuarioDisponible(int $usuarioId, int $excludeEnfermeraId = 0): bool
    {
        $sql = "SELECT 1 AS existe
                FROM usuario u
                WHERE u.usercod = :usuario_id
                  AND u.userest = 'ACT'
                  AND NOT EXISTS (
                      SELECT 1 FROM medico m WHERE m.usuario_id = u.usercod
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM paciente p WHERE p.usuario_id = u.usercod
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM enfermera en
                      WHERE en.usuario_id = u.usercod
                        AND en.id != :exclude_id
                  )";

        $row = parent::obtenerUnRegistro($sql, [
            "usuario_id" => $usuarioId,
            "exclude_id" => $excludeEnfermeraId
        ]);

        return $row !== false && $row !== null;
    }

    /**
     * Inserta únicamente la fila principal de la enfermera.
     *
     * La conexión opcional permite que el método participe en una
     * transacción coordinada por insertEnfermeraConCentros(). usuario_id
     * nulo significa que no se vinculó ninguna cuenta.
     */
    public static function insertEnfermera(
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        ?int $usuarioId,
        &$conn = null
    ): int {
        $connection = $conn !== null ? $conn : self::getConn();
        $sql = "INSERT INTO enfermera
                    (nombres, apellidos, num_colegiatura, telefono, usuario_id)
                VALUES
                    (:nombres, :apellidos, :num_colegiatura, :telefono, :usuario_id)";

        parent::executeNonQuery($sql, [
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $numColegiatura,
            "telefono" => $telefono,
            "usuario_id" => $usuarioId
        ], $connection);

        return (int) $connection->lastInsertId();
    }

    /**
     * Crea una enfermera y todas sus asignaciones en una operación atómica.
     *
     * Si la conexión ya participa en una transacción, este método no la
     * confirma ni la revierte. En operación normal crea y administra su
     * propia transacción.
     */
    public static function insertEnfermeraConCentros(
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        ?int $usuarioId,
        array $asignaciones
    ): int {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            $enfermeraId = self::insertEnfermera(
                $nombres,
                $apellidos,
                $numColegiatura,
                $telefono,
                $usuarioId,
                $conn
            );
            EnfermeraCentroSalud::replaceAssignments($enfermeraId, $asignaciones, $conn);

            if ($ownsTransaction) {
                $conn->commit();
            }

            return $enfermeraId;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Actualiza únicamente los datos principales de la enfermera.
     *
     * La conexión opcional permite incluir la actualización dentro de la
     * misma transacción que reemplaza los centros asignados.
     */
    public static function updateEnfermera(
        int $id,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        ?int $usuarioId,
        &$conn = null
    ): bool {
        $connection = $conn !== null ? $conn : self::getConn();
        $sql = "UPDATE enfermera SET
                    nombres = :nombres,
                    apellidos = :apellidos,
                    num_colegiatura = :num_colegiatura,
                    telefono = :telefono,
                    usuario_id = :usuario_id
                WHERE id = :id";

        return parent::executeNonQuery($sql, [
            "id" => $id,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $numColegiatura,
            "telefono" => $telefono,
            "usuario_id" => $usuarioId
        ], $connection);
    }

    /**
     * Actualiza una enfermera y reemplaza sus centros dentro de una
     * transacción.
     */
    public static function updateEnfermeraConCentros(
        int $id,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        ?int $usuarioId,
        array $asignaciones
    ): bool {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            self::updateEnfermera(
                $id,
                $nombres,
                $apellidos,
                $numColegiatura,
                $telefono,
                $usuarioId,
                $conn
            );
            EnfermeraCentroSalud::replaceAssignments($id, $asignaciones, $conn);

            if ($ownsTransaction) {
                $conn->commit();
            }

            return true;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Borra una enfermera DE VERDAD. Solo debe llamarse después de
     * confirmar con tieneAsignaciones() que nunca tuvo ningún centro
     * asignado — la relación con centros usa ON DELETE CASCADE (se borra
     * sola), pero esto no protege contra borrar por error a una enfermera
     * con historial real.
     */
    public static function deleteEnfermera(int $id): bool
    {
        $sql = "DELETE FROM enfermera WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }

    /**
     * Indica si la enfermera tiene alguna asignación a un centro de salud
     * registrada (activa o inactiva). Es la condición que decide si se
     * puede borrar de verdad o si solo se puede desactivar.
     */
    public static function tieneAsignaciones(int $id): bool
    {
        $row = parent::obtenerUnRegistro(
            "SELECT 1 AS existe FROM enfermera_centro_salud WHERE enfermera_id = :id LIMIT 1",
            ["id" => $id]
        );
        return $row !== false && $row !== null;
    }

    /**
     * Desactiva una enfermera (no la borra): deja de estar disponible para
     * asignaciones nuevas, pero su información e historial se conservan
     * intactos. Mismo patrón que Medicos::disable().
     */
    public static function disable(int $id): bool
    {
        return parent::executeNonQuery(
            "UPDATE enfermera SET estado = 'INA' WHERE id = :id",
            ["id" => $id]
        );
    }

    /**
     * Reactiva una enfermera desactivada con disable().
     */
    public static function enable(int $id): bool
    {
        return parent::executeNonQuery(
            "UPDATE enfermera SET estado = 'ACT' WHERE id = :id",
            ["id" => $id]
        );
    }
}
