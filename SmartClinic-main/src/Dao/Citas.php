<?php

namespace Dao;

/**
 * Acceso a datos de citas y sus reglas de ocupación de agenda.
 *
 * Cada cita pertenece a un paciente, un médico y uno de los centros activos
 * asignados a ese médico. Las consultas enriquecen las filas con nombres,
 * especialidad, estado, centro y consultorio para que los controladores no
 * tengan que ejecutar SQL ni reconstruir la ubicación.
 */
class Citas extends Table
{
    protected static string $table = "cita";
    protected static string $primary = "id";

    /**
     * Registra una cita con la ubicación seleccionada y devuelve su ID.
     *
     * La llave foránea compuesta de la tabla valida en último término que la
     * pareja médico-centro exista en `medico_centro_salud`.
     */
    public static function insertCita(
        int $pacienteId,
        int $medicoId,
        int $centroSaludId,
        int $estadoId,
        string $fechaHora
    ): int {
        $sql = "INSERT INTO cita
                    (paciente_id, medico_id, centro_salud_id, estado_id, fecha_hora)
                VALUES
                    (:paciente_id, :medico_id, :centro_salud_id, :estado_id, :fecha_hora)";

        parent::executeNonQuery($sql, [
            "paciente_id" => $pacienteId,
            "medico_id" => $medicoId,
            "centro_salud_id" => $centroSaludId,
            "estado_id" => $estadoId,
            "fecha_hora" => $fechaHora
        ]);

        return (int) parent::getLastInsertId();
    }

    /**
     * Obtiene una cita por ID con todos sus datos descriptivos.
     *
     * El LEFT JOIN con la relación médico-centro conserva la ubicación
     * histórica incluso si la asignación fue marcada como inactiva después.
     */
    public static function getCitaById(int $id): ?array
    {
        $sql = "SELECT c.*,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.tipo AS centro_tipo,
                       cs.direccion AS centro_direccion,
                       cs.ciudad AS centro_ciudad,
                       mcs.consultorio
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                LEFT JOIN centro_salud cs ON c.centro_salud_id = cs.id
                LEFT JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                WHERE c.id = :id";

        $result = parent::obtenerUnRegistro($sql, ["id" => $id]);
        return $result ?: null;
    }

    /**
     * Obtiene las citas de un paciente con médico, estado y ubicación.
     */
    public static function getCitasByPaciente(int $pacienteId): array
    {
        $sql = "SELECT c.*,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.direccion AS centro_direccion,
                       cs.ciudad AS centro_ciudad,
                       mcs.consultorio
                FROM cita c
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                LEFT JOIN centro_salud cs ON c.centro_salud_id = cs.id
                LEFT JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                WHERE c.paciente_id = :paciente_id
                ORDER BY c.fecha_hora ASC";

        return parent::obtenerRegistros($sql, ["paciente_id" => $pacienteId]);
    }

    /**
     * Obtiene las citas de un médico con paciente, estado y ubicación.
     */
    public static function getCitasByMedico(int $medicoId): array
    {
        $sql = "SELECT c.*,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono,
                       ec.nombre_estado,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.direccion AS centro_direccion,
                       cs.ciudad AS centro_ciudad,
                       mcs.consultorio
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                LEFT JOIN centro_salud cs ON c.centro_salud_id = cs.id
                LEFT JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                WHERE c.medico_id = :medico_id
                ORDER BY c.fecha_hora ASC";

        return parent::obtenerRegistros($sql, ["medico_id" => $medicoId]);
    }

    /**
     * Obtiene todas las citas para agenda, calendario y reportes.
     */
    public static function getAllCitas(): array
    {
        $sql = "SELECT c.*,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado,
                       cs.codigo AS centro_codigo,
                       cs.nombre AS centro_nombre,
                       cs.tipo AS centro_tipo,
                       cs.direccion AS centro_direccion,
                       cs.ciudad AS centro_ciudad,
                       mcs.consultorio
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                LEFT JOIN centro_salud cs ON c.centro_salud_id = cs.id
                LEFT JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                ORDER BY c.fecha_hora ASC";

        return parent::obtenerRegistros($sql, []);
    }

    /**
     * Devuelve las citas que entran en conflicto con un nuevo horario.
     *
     * Una cita dura 30 minutos. Las comparaciones son estrictas para permitir
     * horarios consecutivos: una cita de 08:00 no bloquea la de 08:30. El
     * médico se evalúa globalmente entre centros y el paciente entre médicos,
     * porque ninguno puede participar en dos citas que se superpongan.
     *
     * El resultado incluye nombres de médico y paciente para que la interfaz
     * pueda explicar exactamente quién tiene el conflicto.
     */
    public static function getAvailabilityConflicts(
        int $medicoId,
        int $pacienteId,
        string $fechaHora,
        int $excludeId = 0
    ): array {
        $sql = "SELECT c.id, c.fecha_hora, c.estado_id,
                       c.medico_id, c.paciente_id, c.centro_salud_id,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       cs.nombre AS centro_nombre,
                       mcs.consultorio
                FROM cita c
                LEFT JOIN paciente p ON p.id = c.paciente_id
                LEFT JOIN medico m ON m.id = c.medico_id
                LEFT JOIN centro_salud cs ON cs.id = c.centro_salud_id
                LEFT JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                WHERE c.fecha_hora > DATE_SUB(:fecha_inicio, INTERVAL 30 MINUTE)
                  AND c.fecha_hora < DATE_ADD(:fecha_fin, INTERVAL 30 MINUTE)
                  AND c.estado_id NOT IN (4, 5)
                  AND (
                      c.medico_id = :medico_id
                      OR c.paciente_id = :paciente_id
                  )";

        if ($excludeId > 0) {
            $sql .= " AND c.id != :exclude_id";
        }

        $sql .= " ORDER BY ABS(TIMESTAMPDIFF(
                    SECOND,
                    c.fecha_hora,
                    :fecha_orden
                  )) ASC, c.id ASC";

        $params = [
            "medico_id" => $medicoId,
            "paciente_id" => $pacienteId,
            "fecha_inicio" => $fechaHora,
            "fecha_fin" => $fechaHora,
            "fecha_orden" => $fechaHora
        ];

        if ($excludeId > 0) {
            $params["exclude_id"] = $excludeId;
        }

        return parent::obtenerRegistros($sql, $params);
    }

    /**
     * Indica si médico y paciente están libres para el horario solicitado.
     *
     * Se conserva como envoltorio booleano para los flujos que no necesitan
     * presentar el detalle del conflicto.
     */
    public static function checkDisponibilidad(
        int $medicoId,
        int $pacienteId,
        string $fechaHora,
        int $excludeId = 0
    ): bool {
        return count(self::getAvailabilityConflicts(
            $medicoId,
            $pacienteId,
            $fechaHora,
            $excludeId
        )) === 0;
    }

    /**
     * Devuelve las horas ocupadas de un médico o paciente en una fecha.
     *
     * La ocupación se evalúa globalmente entre centros por la misma razón que
     * getAvailabilityConflicts(). El paciente es opcional para conservar los
     * usos donde todavía no se ha seleccionado uno. $excludeId permite editar
     * una cita sin bloquear su propia hora.
     */
    public static function getBookedTimeSlots(
        int $medicoId,
        string $date,
        int $excludeId = 0,
        int $pacienteId = 0
    ): array {
        if ($medicoId <= 0 || $date === "") {
            return [];
        }

        $sql = "SELECT DATE_FORMAT(fecha_hora, '%H:%i') AS hora
                FROM cita
                WHERE (
                      medico_id = :medico_id
                      OR paciente_id = NULLIF(:paciente_id, 0)
                  )
                  AND DATE(fecha_hora) = :fecha
                  AND estado_id NOT IN (4, 5)";

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
        }

        $params = [
            "medico_id" => $medicoId,
            "paciente_id" => $pacienteId,
            "fecha" => $date
        ];
        if ($excludeId > 0) {
            $params["exclude_id"] = $excludeId;
        }

        $rows = parent::obtenerRegistros($sql, $params);
        return array_column($rows, "hora");
    }

    /**
     * Cuenta las citas activas de un médico durante un día.
     */
    public static function countCitasMedicoDia(
        int $medicoId,
        string $date,
        int $excludeId = 0
    ): int {
        $sql = "SELECT COUNT(*) AS total
                FROM cita
                WHERE medico_id = :medico_id
                  AND DATE(fecha_hora) = :fecha
                  AND estado_id NOT IN (4, 5)";

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
        }

        $params = ["medico_id" => $medicoId, "fecha" => $date];
        if ($excludeId > 0) {
            $params["exclude_id"] = $excludeId;
        }

        $result = parent::obtenerUnRegistro($sql, $params);
        return (int) ($result["total"] ?? 0);
    }

    /**
     * Actualiza los participantes, ubicación, estado y horario de una cita.
     */
    public static function updateCita(
        int $id,
        int $pacienteId,
        int $medicoId,
        int $centroSaludId,
        int $estadoId,
        string $fechaHora
    ): void {
        $sql = "UPDATE cita
                SET paciente_id = :paciente_id,
                    medico_id = :medico_id,
                    centro_salud_id = :centro_salud_id,
                    estado_id = :estado_id,
                    fecha_hora = :fecha_hora
                WHERE id = :id";

        parent::executeNonQuery($sql, [
            "id" => $id,
            "paciente_id" => $pacienteId,
            "medico_id" => $medicoId,
            "centro_salud_id" => $centroSaludId,
            "estado_id" => $estadoId,
            "fecha_hora" => $fechaHora
        ]);
    }

    /**
     * Elimina una cita por su ID.
     *
     * El controlador restringe esta operación a citas futuras no completadas.
     */
    public static function deleteCita(int $id): void
    {
        parent::executeNonQuery(
            "DELETE FROM cita WHERE id = :id",
            ["id" => $id]
        );
    }
}
