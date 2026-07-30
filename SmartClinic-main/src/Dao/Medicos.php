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
                       ), '') AS centros_salud,
                       EXISTS(
                           SELECT 1 FROM cita c WHERE c.medico_id = m.id
                       ) AS tiene_citas
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
        array $asignaciones,
        ?array &$consultorioMoves = null
    ): bool {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();
        $consultorioMoves = [];

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            $currentAssignments =
                MedicoCentroSalud::getActivosByMedicoForUpdate($id, $conn);
            $requestedByCenter = [];
            foreach ($asignaciones as $asignacion) {
                $requestedByCenter[(int) $asignacion["centro_salud_id"]] =
                    trim((string) $asignacion["consultorio"]);
            }

            foreach ($currentAssignments as $currentAssignment) {
                $centerId = (int) $currentAssignment["centro_salud_id"];
                if (!array_key_exists($centerId, $requestedByCenter)) {
                    continue;
                }

                $currentRoom = trim(
                    (string) $currentAssignment["consultorio"]
                );
                $requestedRoom = $requestedByCenter[$centerId];
                if ($currentRoom === $requestedRoom) {
                    continue;
                }

                $movedAppointments =
                    Citas::moveFutureActiveAppointmentsToConsultorio(
                        $id,
                        $centerId,
                        $requestedRoom,
                        $conn
                    );
                $consultorioMoves = array_merge(
                    $consultorioMoves,
                    $movedAppointments
                );
            }

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
            $consultorioMoves = [];
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Borra un médico DE VERDAD. Solo debe llamarse después de confirmar
     * con tieneCitas() que nunca tuvo ninguna cita — la relación con
     * centros usa ON DELETE CASCADE (se borra sola), pero esto no protege
     * contra borrar por error a un médico con historial real.
     */
    public static function deleteMedico(int $id): bool
    {
        $sql = "DELETE FROM medico WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }

    /**
     * Indica si el médico tiene alguna cita registrada (en cualquier
     * estado, pasada o futura). Es la condición que decide si se puede
     * borrar de verdad o si solo se puede desactivar.
     */
    public static function tieneCitas(int $id): bool
    {
        $row = parent::obtenerUnRegistro(
            "SELECT 1 AS existe FROM cita WHERE medico_id = :id LIMIT 1",
            ["id" => $id]
        );
        return $row !== false && $row !== null;
    }

    /**
     * Desactiva un médico (no lo borra): deja de aparecer disponible para
     * agendar citas nuevas, pero su información e historial se conservan
     * intactos. Mismo patrón que Producto::disable()/Proveedor::disable().
     */
    public static function disable(int $id): bool
    {
        return parent::executeNonQuery(
            "UPDATE medico SET estado = 'INA' WHERE id = :id",
            ["id" => $id]
        );
    }

    /**
     * Reactiva un médico desactivado con disable().
     */
    public static function enable(int $id): bool
    {
        return parent::executeNonQuery(
            "UPDATE medico SET estado = 'ACT' WHERE id = :id",
            ["id" => $id]
        );
    }

    /**
     * Busca el médico actualmente vinculado a una cuenta de usuario (si
     * hay alguno). Se usa desde la pantalla de Usuarios para precargar el
     * buscador de "Médico vinculado" al editar una cuenta existente.
     */
    public static function getByUsuarioId(int $usuarioId)
    {
        return parent::obtenerUnRegistro(
            "SELECT * FROM medico WHERE usuario_id = :usuario_id",
            ["usuario_id" => $usuarioId]
        );
    }

    /**
     * Quita el vínculo de cuenta de cualquier médico que hoy tenga esta
     * usuario_id. Se llama SIEMPRE antes de vincularUsuario(), para que
     * cambiar la selección en el formulario de Usuarios no deje dos
     * médicos apuntando (por error) a la misma cuenta.
     */
    public static function desvincularUsuario(int $usuarioId, &$conn = null): bool
    {
        return parent::executeNonQuery(
            "UPDATE medico SET usuario_id = NULL WHERE usuario_id = :usuario_id",
            ["usuario_id" => $usuarioId],
            $conn
        );
    }

    /**
     * Vincula un médico puntual con una cuenta de usuario. El llamador
     * (Dao\Security\Users) es responsable de validar antes que ese médico
     * no esté ya vinculado a OTRA cuenta distinta.
     */
    public static function vincularUsuario(int $medicoId, int $usuarioId, &$conn = null): bool
    {
        return parent::executeNonQuery(
            "UPDATE medico SET usuario_id = :usuario_id WHERE id = :id",
            ["id" => $medicoId, "usuario_id" => $usuarioId],
            $conn
        );
    }
}
