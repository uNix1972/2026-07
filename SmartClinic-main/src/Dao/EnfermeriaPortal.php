<?php

namespace Dao;

/**
 * Data access for the nursing portal queue and arrival confirmation.
 *
 * The authenticated user is never trusted to submit a nurse or health-center
 * identity. Both are derived from `enfermera.usuario_id` and the active rows
 * in `enfermera_centro_salud`. Consequently, every queue query remains scoped
 * to the centers where that nurse is currently authorized to work.
 *
 * The only write exposed by this phase is the atomic transition from a
 * confirmed appointment to the waiting room. It repeats the same user-center
 * authorization in the UPDATE itself rather than trusting a previous read.
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

    /**
     * Marks a confirmed appointment as waiting after validating nurse scope.
     *
     * The JOIN chain is the authorization boundary: the appointment must be
     * scheduled today in an active center currently assigned to the active
     * nurse linked to the authenticated user. The state predicate makes the
     * transition idempotent and concurrency-safe; a repeated click or a state
     * changed by another user affects zero rows.
     *
     * This method does not accept a nurse or center ID from the request.
     */
    public static function confirmarLlegadaEnCentroAsignado(
        int $citaId,
        int $usuarioId,
        string $fechaInicio,
        string $fechaFin
    ): bool {
        $sql = "UPDATE cita c
                INNER JOIN centro_salud cs
                    ON cs.id = c.centro_salud_id
                   AND cs.estado = 'ACT'
                INNER JOIN enfermera_centro_salud ecs
                    ON ecs.centro_salud_id = c.centro_salud_id
                   AND ecs.estado = 'ACT'
                INNER JOIN enfermera e
                    ON e.id = ecs.enfermera_id
                   AND e.estado = 'ACT'
                   AND e.usuario_id = :usuario_id
                SET c.estado_id = 6
                WHERE c.id = :cita_id
                  AND c.estado_id = 2
                  AND c.fecha_hora >= :fecha_inicio
                  AND c.fecha_hora < :fecha_fin";

        $statement = parent::getConn()->prepare($sql);
        $statement->execute([
            "cita_id" => $citaId,
            "usuario_id" => $usuarioId,
            "fecha_inicio" => $fechaInicio,
            "fecha_fin" => $fechaFin
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * Loads one waiting appointment for nurse-led preclinical care.
     *
     * The appointment is returned only when it is scheduled today in an
     * active health center assigned to the active nurse linked to the
     * authenticated user. Existing vital signs are included so the nurse can
     * correct them while the patient remains in the waiting room.
     */
    public static function getCitaPreclinicaByUsuario(
        int $citaId,
        int $usuarioId,
        string $fechaInicio,
        string $fechaFin
    ): ?array {
        $sql = "SELECT c.id, c.fecha_hora, c.estado_id, c.consultorio,
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
                       sv.id AS signos_vitales_id,
                       sv.temperatura, sv.presion_sistolica,
                       sv.presion_diastolica, sv.frecuencia_cardiaca,
                       sv.frecuencia_respiratoria, sv.saturacion_oxigeno,
                       sv.peso, sv.talla, sv.notas AS signos_notas
                FROM cita c
                INNER JOIN paciente p ON p.id = c.paciente_id
                INNER JOIN medico m ON m.id = c.medico_id
                LEFT JOIN especialidad esp ON esp.id = m.especialidad_id
                INNER JOIN estado_cita ec ON ec.id = c.estado_id
                INNER JOIN centro_salud cs
                    ON cs.id = c.centro_salud_id
                   AND cs.estado = 'ACT'
                INNER JOIN enfermera_centro_salud ecs
                    ON ecs.centro_salud_id = c.centro_salud_id
                   AND ecs.estado = 'ACT'
                INNER JOIN enfermera e
                    ON e.id = ecs.enfermera_id
                   AND e.estado = 'ACT'
                   AND e.usuario_id = :usuario_id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE c.id = :cita_id
                  AND c.estado_id = 6
                  AND c.fecha_hora >= :fecha_inicio
                  AND c.fecha_hora < :fecha_fin
                LIMIT 1";

        $row = parent::obtenerUnRegistro($sql, [
            "cita_id" => $citaId,
            "usuario_id" => $usuarioId,
            "fecha_inicio" => $fechaInicio,
            "fecha_fin" => $fechaFin
        ]);

        return $row ?: null;
    }

    /**
     * Creates or corrects vital signs for an authorized waiting appointment.
     *
     * Authorization is embedded in the INSERT SELECT so a submitted
     * appointment ID cannot bypass the nurse-center relationship. The unique
     * cita_id key makes this an upsert, while the waiting-state predicate
     * prevents changes after the doctor starts or finishes the consultation.
     */
    public static function guardarSignosVitalesEnCentroAsignado(
        int $citaId,
        int $usuarioId,
        string $fechaInicio,
        string $fechaFin,
        array $datos
    ): bool {
        $sql = "INSERT INTO signos_vitales
                    (cita_id, temperatura, presion_sistolica,
                     presion_diastolica, frecuencia_cardiaca,
                     frecuencia_respiratoria, saturacion_oxigeno,
                     peso, talla, notas)
                SELECT c.id, :temperatura, :presion_sistolica,
                       :presion_diastolica, :frecuencia_cardiaca,
                       :frecuencia_respiratoria, :saturacion_oxigeno,
                       :peso, :talla, :notas
                FROM cita c
                INNER JOIN centro_salud cs
                    ON cs.id = c.centro_salud_id
                   AND cs.estado = 'ACT'
                INNER JOIN enfermera_centro_salud ecs
                    ON ecs.centro_salud_id = c.centro_salud_id
                   AND ecs.estado = 'ACT'
                INNER JOIN enfermera e
                    ON e.id = ecs.enfermera_id
                   AND e.estado = 'ACT'
                   AND e.usuario_id = :usuario_id
                WHERE c.id = :cita_id
                  AND c.estado_id = 6
                  AND c.fecha_hora >= :fecha_inicio
                  AND c.fecha_hora < :fecha_fin
                ON DUPLICATE KEY UPDATE
                    temperatura = VALUES(temperatura),
                    presion_sistolica = VALUES(presion_sistolica),
                    presion_diastolica = VALUES(presion_diastolica),
                    frecuencia_cardiaca = VALUES(frecuencia_cardiaca),
                    frecuencia_respiratoria = VALUES(frecuencia_respiratoria),
                    saturacion_oxigeno = VALUES(saturacion_oxigeno),
                    peso = VALUES(peso),
                    talla = VALUES(talla),
                    notas = VALUES(notas)";

        $params = array_merge($datos, [
            "cita_id" => $citaId,
            "usuario_id" => $usuarioId,
            "fecha_inicio" => $fechaInicio,
            "fecha_fin" => $fechaFin
        ]);
        $conn = parent::getConn();
        $statement = $conn->prepare($sql);
        $statement->execute($params);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $verifySql = "SELECT sv.id
                      FROM signos_vitales sv
                      INNER JOIN cita c ON c.id = sv.cita_id
                      INNER JOIN centro_salud cs
                          ON cs.id = c.centro_salud_id
                         AND cs.estado = 'ACT'
                      INNER JOIN enfermera_centro_salud ecs
                          ON ecs.centro_salud_id = c.centro_salud_id
                         AND ecs.estado = 'ACT'
                      INNER JOIN enfermera e
                          ON e.id = ecs.enfermera_id
                         AND e.estado = 'ACT'
                         AND e.usuario_id = :usuario_id
                      WHERE c.id = :cita_id
                        AND c.estado_id = 6
                        AND c.fecha_hora >= :fecha_inicio
                        AND c.fecha_hora < :fecha_fin
                      LIMIT 1";
        $verify = $conn->prepare($verifySql);
        $verify->execute([
            "cita_id" => $citaId,
            "usuario_id" => $usuarioId,
            "fecha_inicio" => $fechaInicio,
            "fecha_fin" => $fechaFin
        ]);

        return $verify->fetchColumn() !== false;
    }
}
