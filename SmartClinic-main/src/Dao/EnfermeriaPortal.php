<?php

namespace Dao;

/**
 * Read-only data access for the nursing portal.
 *
 * The authenticated user is never trusted to submit a nurse or health-center
 * identity. Both are derived from `enfermera.usuario_id` and the active rows
 * in `enfermera_centro_salud`. Consequently, every queue query remains scoped
 * to the centers where that nurse is currently authorized to work.
 *
 * This DAO intentionally exposes no INSERT, UPDATE, or DELETE operation. The
 * first nursing-portal increment is limited to viewing today's patient queue.
 */
class EnfermeriaPortal extends Table
{
    /**
     * Returns the active nurse linked to an authenticated user.
     *
     * A role assignment grants access to the module, while this relationship
     * establishes the clinical identity used to scope patient information.
     */
    public static function getEnfermeraByUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT e.id, e.nombres, e.apellidos, e.num_colegiatura,
                       e.telefono, e.estado, e.usuario_id
                FROM enfermera e
                INNER JOIN usuario u ON u.usercod = e.usuario_id
                WHERE e.usuario_id = :usuario_id
                  AND e.estado = 'ACT'
                  AND u.userest = 'ACT'
                LIMIT 1";

        $row = parent::obtenerUnRegistro($sql, ["usuario_id" => $usuarioId]);
        return $row ?: null;
    }

    /**
     * Lists the active health-center assignments available to a nurse user.
     *
     * The area belongs to the nurse-center relationship, so it can vary when
     * the same nurse works at more than one location.
     */
    public static function getCentrosActivosByUsuario(int $usuarioId): array
    {
        $sql = "SELECT ecs.centro_salud_id, ecs.area,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.ciudad AS centro_ciudad
                FROM enfermera e
                INNER JOIN enfermera_centro_salud ecs
                    ON ecs.enfermera_id = e.id
                   AND ecs.estado = 'ACT'
                INNER JOIN centro_salud cs
                    ON cs.id = ecs.centro_salud_id
                   AND cs.estado = 'ACT'
                WHERE e.usuario_id = :usuario_id
                  AND e.estado = 'ACT'
                ORDER BY cs.nombre ASC";

        return parent::obtenerRegistros($sql, ["usuario_id" => $usuarioId]);
    }

    /**
     * Returns today's appointments from the nurse's active health centers.
     *
     * The half-open date interval keeps the database comparison index-friendly
     * and includes every time from midnight up to, but not including, the next
     * day. Operational states appear first; final states remain visible for an
     * accurate account of the center's complete day.
     */
    public static function getColaDelDiaByUsuario(
        int $usuarioId,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $sql = "SELECT c.id, c.fecha_hora, c.estado_id, c.consultorio,
                       c.centro_salud_id, c.medico_id, c.paciente_id,
                       p.identidad AS paciente_identidad,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       esp.nombre_especialidad,
                       ec.nombre_estado,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.ciudad AS centro_ciudad,
                       ecs.area AS enfermera_area,
                       sv.id AS signos_vitales_id
                FROM enfermera e
                INNER JOIN enfermera_centro_salud ecs
                    ON ecs.enfermera_id = e.id
                   AND ecs.estado = 'ACT'
                INNER JOIN centro_salud cs
                    ON cs.id = ecs.centro_salud_id
                   AND cs.estado = 'ACT'
                INNER JOIN cita c
                    ON c.centro_salud_id = ecs.centro_salud_id
                   AND c.fecha_hora >= :fecha_inicio
                   AND c.fecha_hora < :fecha_fin
                INNER JOIN paciente p ON p.id = c.paciente_id
                INNER JOIN medico m ON m.id = c.medico_id
                LEFT JOIN especialidad esp ON esp.id = m.especialidad_id
                INNER JOIN estado_cita ec ON ec.id = c.estado_id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE e.usuario_id = :usuario_id
                  AND e.estado = 'ACT'
                ORDER BY
                    CASE c.estado_id
                        WHEN 6 THEN 1
                        WHEN 2 THEN 2
                        WHEN 7 THEN 3
                        WHEN 1 THEN 4
                        WHEN 3 THEN 5
                        WHEN 5 THEN 6
                        WHEN 4 THEN 7
                        ELSE 8
                    END,
                    c.fecha_hora ASC,
                    c.id ASC";

        return parent::obtenerRegistros($sql, [
            "usuario_id" => $usuarioId,
            "fecha_inicio" => $fechaInicio,
            "fecha_fin" => $fechaFin
        ]);
    }
}
