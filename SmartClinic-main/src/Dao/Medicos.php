<?php

namespace Dao;

/**
 * Acceso a datos de médicos y coordinación de sus asignaciones operativas.
 *
 * Los datos personales y profesionales viven en `medico`; las ubicaciones y
 * consultorios se delegan a Dao\MedicoCentroSalud. Los métodos coordinadores
 * de creación y edición utilizan una sola transacción para evitar médicos sin
 * centros por fallos parciales.
 */
class Medicos extends Table
{
    /**
     * Obtiene el directorio de médicos con especialidad y centros activos.
     *
     * La subconsulta evita duplicar médicos cuando tienen varias ubicaciones.
     * GROUP_CONCAT produce un resumen legible para la tabla del directorio.
     */
    public static function getAllMedicos(): array
    {
        $sql = "SELECT m.*, e.nombre_especialidad,
                       COALESCE((
                           SELECT GROUP_CONCAT(
                               CONCAT(cs.nombre, ' - Consultorio ', mcs.consultorio)
                               ORDER BY cs.nombre
                               SEPARATOR ', '
                           )
                           FROM medico_centro_salud mcs
                           JOIN centro_salud cs ON cs.id = mcs.centro_salud_id
                           WHERE mcs.medico_id = m.id
                             AND mcs.estado = 'ACT'
                             AND cs.estado = 'ACT'
                       ), '') AS centros_salud
                FROM medico m
                JOIN especialidad e ON m.especialidad_id = e.id
                ORDER BY m.id DESC";

        return parent::obtenerRegistros($sql, []);
    }

    /**
     * Reutiliza el directorio enriquecido para los reportes existentes.
     */
    public static function getAllMedicosReport(): array
    {
        return self::getAllMedicos();
    }

    /**
     * Obtiene un médico por su llave primaria.
     *
     * El método base devuelve false cuando no encuentra una fila.
     */
    public static function getMedicoById(int $id)
    {
        $sql = "SELECT * FROM medico WHERE id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    /**
     * Comprueba si el número de colegiatura pertenece a otro médico.
     */
    public static function existsNumColegiatura(
        string $numColegiatura,
        int $excludeId = 0
    ): bool {
        $sql = "SELECT COUNT(*) AS total
                FROM medico
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
     * Inserta únicamente la fila principal del médico.
     *
     * La conexión opcional permite que el método participe en una transacción
     * coordinada por insertMedicoConCentros().
     */
    public static function insertMedico(
        int $especialidadId,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        &$conn = null
    ): int {
        $connection = $conn !== null ? $conn : self::getConn();
        $sql = "INSERT INTO medico
                    (especialidad_id, nombres, apellidos, num_colegiatura, telefono)
                VALUES
                    (:especialidad_id, :nombres, :apellidos, :num_colegiatura, :telefono)";

        parent::executeNonQuery($sql, [
            "especialidad_id" => $especialidadId,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $numColegiatura,
            "telefono" => $telefono
        ], $connection);

        return (int) $connection->lastInsertId();
    }

    /**
     * Crea un médico y todas sus asignaciones en una operación atómica.
     *
     * Si la conexión ya participa en una transacción (por ejemplo, durante
     * una prueba), este método no la confirma ni la revierte. En operación
     * normal crea y administra su propia transacción.
     */
    public static function insertMedicoConCentros(
        int $especialidadId,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        array $asignaciones
    ): int {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            $medicoId = self::insertMedico(
                $especialidadId,
                $nombres,
                $apellidos,
                $numColegiatura,
                $telefono,
                $conn
            );
            MedicoCentroSalud::replaceAssignments($medicoId, $asignaciones, $conn);

            if ($ownsTransaction) {
                $conn->commit();
            }

            return $medicoId;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Actualiza únicamente los datos principales del médico.
     *
     * La conexión opcional permite incluir la actualización dentro de la
     * misma transacción que reemplaza los centros asignados.
     */
    public static function updateMedico(
        int $id,
        int $especialidadId,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        &$conn = null
    ): bool {
        $connection = $conn !== null ? $conn : self::getConn();
        $sql = "UPDATE medico SET
                    especialidad_id = :especialidad_id,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    num_colegiatura = :num_colegiatura,
                    telefono = :telefono
                WHERE id = :id";

        return parent::executeNonQuery($sql, [
            "id" => $id,
            "especialidad_id" => $especialidadId,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $numColegiatura,
            "telefono" => $telefono
        ], $connection);
    }

    /**
     * Actualiza un médico y reemplaza sus centros dentro de una transacción.
     */
    public static function updateMedicoConCentros(
        int $id,
        int $especialidadId,
        string $nombres,
        string $apellidos,
        string $numColegiatura,
        string $telefono,
        array $asignaciones
    ): bool {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            self::updateMedico(
                $id,
                $especialidadId,
                $nombres,
                $apellidos,
                $numColegiatura,
                $telefono,
                $conn
            );
            MedicoCentroSalud::replaceAssignments($id, $asignaciones, $conn);

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
     * Elimina un médico.
     *
     * Las relaciones con centros usan ON DELETE CASCADE, pero otras tablas
     * como citas pueden impedir la eliminación para proteger su historial.
     */
    public static function deleteMedico(int $id): bool
    {
        $sql = "DELETE FROM medico WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }
}
