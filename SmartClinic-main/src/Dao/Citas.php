<?php

namespace Dao;

use Dao\Table;

class Citas extends Table
{
    protected static string $table = 'cita';
    protected static string $primary = 'id';

    public static function insertCita(int $pacienteId, int $medicoId, int $estadoId, string $fechaHora): int
    {
        $sql = "INSERT INTO cita (paciente_id, medico_id, estado_id, fecha_hora)
                VALUES (:paciente_id, :medico_id, :estado_id, :fecha_hora)";

        $params = [
            "paciente_id" => $pacienteId,
            "medico_id" => $medicoId,
            "estado_id" => $estadoId,
            "fecha_hora" => $fechaHora
        ];

        parent::executeNonQuery($sql, $params);
        return parent::getLastInsertId();
    }

    public static function getCitaById(int $id): ?array
    {
        $sql = "SELECT c.*, 
                       p.nombres as paciente_nombres, 
                       p.apellidos as paciente_apellidos,
                       m.nombres as medico_nombres,
                       m.apellidos as medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                WHERE c.id = :id";

        $params = ["id" => $id];
        $result = parent::obtenerUnRegistro($sql, $params);
        return $result ?: null;
    }

    public static function getCitasByPaciente(int $pacienteId): array
    {
        $sql = "SELECT c.*, 
                       m.nombres as medico_nombres,
                       m.apellidos as medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado
                FROM cita c
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                WHERE c.paciente_id = :paciente_id
                ORDER BY c.fecha_hora ASC";

        $params = ["paciente_id" => $pacienteId];
        return parent::obtenerRegistros($sql, $params);
    }

    public static function getCitasByMedico(int $medicoId): array
    {
        $sql = "SELECT c.*, 
                       p.nombres as paciente_nombres,
                       p.apellidos as paciente_apellidos,
                       ec.nombre_estado
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                WHERE c.medico_id = :medico_id
                ORDER BY c.fecha_hora ASC";

        $params = ["medico_id" => $medicoId];
        return parent::obtenerRegistros($sql, $params);
    }

    public static function getAllCitas(): array
    {
        $sql = "SELECT c.*, 
                       p.nombres as paciente_nombres,
                       p.apellidos as paciente_apellidos,
                       m.nombres as medico_nombres,
                       m.apellidos as medico_apellidos,
                       e.nombre_especialidad,
                       ec.nombre_estado
                FROM cita c
                LEFT JOIN paciente p ON c.paciente_id = p.id
                LEFT JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.id
                ORDER BY c.fecha_hora ASC";

        return parent::obtenerRegistros($sql, []);
    }

    public static function checkDisponibilidad(int $medicoId, int $pacienteId, string $fechaHora, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) as conflictos FROM cita
                WHERE fecha_hora BETWEEN DATE_SUB(:fecha_hora, INTERVAL 30 MINUTE)
                  AND DATE_ADD(:fecha_hora, INTERVAL 30 MINUTE)
                  AND estado_id NOT IN (4, 5)
                  AND (medico_id = :medico_id OR paciente_id = :paciente_id)";

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
        }

        $params = [
            "medico_id" => $medicoId,
            "paciente_id" => $pacienteId,
            "fecha_hora" => $fechaHora,
        ];

        if ($excludeId > 0) {
            $params['exclude_id'] = $excludeId;
        }

        $result = parent::obtenerUnRegistro($sql, $params);
        return ($result['conflictos'] ?? 0) === 0;
    }

    public static function getBookedTimeSlots(int $medicoId, string $date, int $excludeId = 0): array
    {
        if ($medicoId <= 0 || $date === '') {
            return [];
        }

        $sql = "SELECT DATE_FORMAT(fecha_hora, '%H:%i') as hora FROM cita
                WHERE medico_id = :medico_id
                  AND DATE(fecha_hora) = :fecha
                  AND estado_id NOT IN (4, 5)";

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
        }

        $params = [
            "medico_id" => $medicoId,
            "fecha" => $date,
        ];

        if ($excludeId > 0) {
            $params['exclude_id'] = $excludeId;
        }

        $rows = parent::obtenerRegistros($sql, $params);
        return array_column($rows, 'hora');
    }

    public static function countCitasMedicoDia(int $medicoId, string $date, int $excludeId = 0): int
    {
        $sql = "SELECT COUNT(*) as total FROM cita
                WHERE medico_id = :medico_id
                  AND DATE(fecha_hora) = :fecha
                  AND estado_id NOT IN (4, 5)";

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
        }

        $params = [
            "medico_id" => $medicoId,
            "fecha" => $date,
        ];

        if ($excludeId > 0) {
            $params['exclude_id'] = $excludeId;
        }

        $result = parent::obtenerUnRegistro($sql, $params);
        return intval($result['total'] ?? 0);
    }

    public static function updateCita(int $id, int $pacienteId, int $medicoId, int $estadoId, string $fechaHora): void
    {
        $sql = "UPDATE cita
                SET paciente_id = :paciente_id, medico_id = :medico_id, estado_id = :estado_id, fecha_hora = :fecha_hora
                WHERE id = :id";

        $params = [
            "id" => $id,
            "paciente_id" => $pacienteId,
            "medico_id" => $medicoId,
            "estado_id" => $estadoId,
            "fecha_hora" => $fechaHora
        ];

        parent::executeNonQuery($sql, $params);
    }

    public static function deleteCita(int $id): void
    {
        $sql = "DELETE FROM cita WHERE id = :id";
        $params = ["id" => $id];
        parent::executeNonQuery($sql, $params);
    }
}
