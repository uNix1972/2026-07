<?php

namespace Dao;

/**
 * Acceso a datos para la relación entre médicos y centros de salud.
 *
 * La relación es muchos-a-muchos porque un médico puede atender en varias
 * ubicaciones y cada centro puede tener varios médicos. El consultorio vive
 * en esta tabla porque depende de la combinación médico-centro, no solamente
 * del médico.
 *
 * Las asignaciones se inactivan en vez de borrarse. Esto conserva el historial
 * de ubicaciones y permite reactivar una relación existente sin crear filas
 * duplicadas.
 */
class MedicoCentroSalud extends Table
{
    /**
     * Obtiene las asignaciones activas de un médico con los datos del centro.
     *
     * Los centros inactivos no se devuelven como opciones operativas aunque
     * la relación todavía exista, porque no deben seleccionarse al editar.
     */
    public static function getActivosByMedico(int $medicoId): array
    {
        $sql = "SELECT mcs.*, cs.codigo, cs.nombre AS centro_nombre,
                       cs.tipo AS centro_tipo, cs.ciudad AS centro_ciudad
                FROM medico_centro_salud mcs
                JOIN centro_salud cs ON cs.id = mcs.centro_salud_id
                WHERE mcs.medico_id = :medico_id
                  AND mcs.estado = 'ACT'
                  AND cs.estado = 'ACT'
                ORDER BY cs.nombre ASC";

        return parent::obtenerRegistros($sql, ["medico_id" => $medicoId]);
    }

    /**
     * Reemplaza el conjunto activo de centros asignados a un médico.
     *
     * Primero inactiva las relaciones actuales y después reactiva o inserta
     * las seleccionadas. El índice único (medico_id, centro_salud_id) permite
     * utilizar ON DUPLICATE KEY UPDATE sin generar duplicados.
     *
     * La conexión opcional permite que Dao\Medicos controle una transacción
     * que incluya tanto los datos del médico como sus centros. Si no se recibe
     * una conexión, se utiliza la conexión compartida del proyecto.
     *
     * Cada elemento de $asignaciones debe contener:
     * - centro_salud_id: ID de un centro activo.
     * - consultorio: ubicación interna del médico en ese centro.
     */
    public static function replaceAssignments(
        int $medicoId,
        array $asignaciones,
        &$conn = null
    ): bool {
        $connection = $conn !== null ? $conn : self::getConn();

        parent::executeNonQuery(
            "UPDATE medico_centro_salud
             SET estado = 'INA'
             WHERE medico_id = :medico_id",
            ["medico_id" => $medicoId],
            $connection
        );

        $sql = "INSERT INTO medico_centro_salud
                    (medico_id, centro_salud_id, consultorio, estado)
                VALUES
                    (:medico_id, :centro_salud_id, :consultorio, 'ACT')
                ON DUPLICATE KEY UPDATE
                    consultorio = VALUES(consultorio),
                    estado = 'ACT',
                    fecha_actualizacion = CURRENT_TIMESTAMP";

        foreach ($asignaciones as $asignacion) {
            parent::executeNonQuery($sql, [
                "medico_id" => $medicoId,
                "centro_salud_id" => (int) $asignacion["centro_salud_id"],
                "consultorio" => (string) $asignacion["consultorio"]
            ], $connection);
        }

        return true;
    }

    /**
     * Cuenta las asignaciones activas de un médico.
     *
     * Se utiliza en pruebas e integraciones donde solo interesa confirmar la
     * cantidad sin recuperar todas las columnas de cada centro.
     */
    public static function countActivosByMedico(int $medicoId): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM medico_centro_salud mcs
                JOIN centro_salud cs ON cs.id = mcs.centro_salud_id
                WHERE mcs.medico_id = :medico_id
                  AND mcs.estado = 'ACT'
                  AND cs.estado = 'ACT'";

        $row = parent::obtenerUnRegistro($sql, ["medico_id" => $medicoId]);
        return (int) ($row["total"] ?? 0);
    }
}

