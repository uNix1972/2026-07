<?php

namespace Dao;

/**
 * Acceso a datos para la relación entre enfermeras y centros de salud.
 *
 * La relación es muchos-a-muchos porque una enfermera puede atender en
 * varias ubicaciones y cada centro puede tener varias enfermeras. El área
 * (o turno) vive en esta tabla porque depende de la combinación
 * enfermera-centro, no solamente de la enfermera. A diferencia de
 * medico_centro_salud, aquí no se valida conflicto de sala: varias
 * enfermeras pueden compartir la misma área/turno en un mismo centro.
 *
 * Las asignaciones se inactivan en vez de borrarse. Esto conserva el
 * historial de ubicaciones y permite reactivar una relación existente sin
 * crear filas duplicadas.
 */
class EnfermeraCentroSalud extends Table
{
    /**
     * Obtiene todas las asignaciones activas con los datos del centro.
     */
    public static function getAllActivos(): array
    {
        $sql = "SELECT ecs.enfermera_id, ecs.centro_salud_id, ecs.area,
                       cs.codigo, cs.nombre AS centro_nombre,
                       cs.tipo AS centro_tipo, cs.direccion AS centro_direccion,
                       cs.ciudad AS centro_ciudad
                FROM enfermera_centro_salud ecs
                JOIN centro_salud cs ON cs.id = ecs.centro_salud_id
                JOIN enfermera e ON e.id = ecs.enfermera_id
                WHERE ecs.estado = 'ACT'
                  AND cs.estado = 'ACT'
                ORDER BY e.apellidos ASC, e.nombres ASC, cs.nombre ASC";

        return parent::obtenerRegistros($sql, []);
    }

    /**
     * Obtiene las asignaciones activas de una enfermera con los datos del centro.
     *
     * Los centros inactivos no se devuelven como opciones operativas aunque
     * la relación todavía exista, porque no deben seleccionarse al editar.
     */
    public static function getActivosByEnfermera(int $enfermeraId): array
    {
        $sql = "SELECT ecs.*, cs.codigo, cs.nombre AS centro_nombre,
                       cs.tipo AS centro_tipo, cs.ciudad AS centro_ciudad
                FROM enfermera_centro_salud ecs
                JOIN centro_salud cs ON cs.id = ecs.centro_salud_id
                WHERE ecs.enfermera_id = :enfermera_id
                  AND ecs.estado = 'ACT'
                  AND cs.estado = 'ACT'
                ORDER BY cs.nombre ASC";

        return parent::obtenerRegistros($sql, ["enfermera_id" => $enfermeraId]);
    }

    /**
     * Bloquea las asignaciones activas de una enfermera dentro de una
     * transacción (misma idea que
     * MedicoCentroSalud::getActivosByMedicoForUpdate, por si en el futuro
     * se necesita coordinar con otro cambio concurrente).
     */
    public static function getActivosByEnfermeraForUpdate(
        int $enfermeraId,
        &$conn
    ): array {
        $sql = "SELECT enfermera_id, centro_salud_id, area
                FROM enfermera_centro_salud
                WHERE enfermera_id = :enfermera_id
                  AND estado = 'ACT'
                ORDER BY centro_salud_id ASC
                FOR UPDATE";

        return parent::obtenerRegistros(
            $sql,
            ["enfermera_id" => $enfermeraId],
            $conn
        );
    }

    /**
     * Reemplaza el conjunto activo de centros asignados a una enfermera.
     *
     * Primero inactiva las relaciones actuales y después reactiva o inserta
     * las seleccionadas. El índice único (enfermera_id, centro_salud_id)
     * permite utilizar ON DUPLICATE KEY UPDATE sin generar duplicados.
     *
     * La conexión opcional permite que Dao\Enfermeras controle una
     * transacción que incluya tanto los datos de la enfermera como sus
     * centros. Si no se recibe una conexión, se utiliza la conexión
     * compartida del proyecto.
     *
     * Cada elemento de $asignaciones debe contener:
     * - centro_salud_id: ID de un centro activo.
     * - area: área o turno de la enfermera en ese centro.
     */
    public static function replaceAssignments(
        int $enfermeraId,
        array $asignaciones,
        &$conn = null
    ): bool {
        $connection = $conn !== null ? $conn : self::getConn();

        parent::executeNonQuery(
            "UPDATE enfermera_centro_salud
             SET estado = 'INA'
             WHERE enfermera_id = :enfermera_id",
            ["enfermera_id" => $enfermeraId],
            $connection
        );

        $sql = "INSERT INTO enfermera_centro_salud
                    (enfermera_id, centro_salud_id, area, estado)
                VALUES
                    (:enfermera_id, :centro_salud_id, :area, 'ACT')
                ON DUPLICATE KEY UPDATE
                    area = VALUES(area),
                    estado = 'ACT',
                    fecha_actualizacion = CURRENT_TIMESTAMP";

        foreach ($asignaciones as $asignacion) {
            parent::executeNonQuery($sql, [
                "enfermera_id" => $enfermeraId,
                "centro_salud_id" => (int) $asignacion["centro_salud_id"],
                "area" => (string) $asignacion["area"]
            ], $connection);
        }

        return true;
    }

    /**
     * Cuenta las asignaciones activas de una enfermera.
     */
    public static function countActivosByEnfermera(int $enfermeraId): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM enfermera_centro_salud ecs
                JOIN centro_salud cs ON cs.id = ecs.centro_salud_id
                WHERE ecs.enfermera_id = :enfermera_id
                  AND ecs.estado = 'ACT'
                  AND cs.estado = 'ACT'";

        $row = parent::obtenerUnRegistro($sql, ["enfermera_id" => $enfermeraId]);
        return (int) ($row["total"] ?? 0);
    }
}
