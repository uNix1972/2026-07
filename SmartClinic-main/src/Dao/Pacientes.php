<?php
namespace Dao;

/**
 * DAO para la tabla paciente
 */
class Pacientes extends Table
{
    public static function getAllPacientes(): array
    {
        // Mostrar pacientes en orden ascendente
        $sql = "SELECT * FROM paciente ORDER BY id ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getPacienteById(int $id)
    {
        $sql = "SELECT * FROM paciente WHERE id = :id";
        $params = ["id" => $id];
        return parent::obtenerUnRegistro($sql, $params);
    }

    /**
     * Comprueba si la identidad ya pertenece a otro paciente (la columna
     * es UNIQUE en la base de datos, pero sin este chequeo el usuario solo
     * vería un error genérico de base de datos en vez de un mensaje claro).
     */
    public static function existsIdentidad(string $identidad, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM paciente
                WHERE identidad = :identidad";
        $params = ["identidad" => $identidad];

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
            $params["exclude_id"] = $excludeId;
        }

        $row = parent::obtenerUnRegistro($sql, $params);
        return (int) ($row["total"] ?? 0) > 0;
    }

    public static function insertPaciente(
        string $identidad,
        string $nombres,
        string $apellidos,
        string $fecha_nacimiento,
        string $telefono,
        string $direccion
    ): int {
        $sql = "INSERT INTO paciente (identidad, nombres, apellidos, fecha_nacimiento, telefono, direccion)
                VALUES (:identidad, :nombres, :apellidos, :fecha_nacimiento, :telefono, :direccion)";
        $params = [
            "identidad" => $identidad,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "fecha_nacimiento" => $fecha_nacimiento,
            "telefono" => $telefono,
            "direccion" => $direccion,
        ];
        parent::executeNonQuery($sql, $params);
        return parent::getLastInsertId();
    }

    public static function updatePaciente(
        int $id,
        string $identidad,
        string $nombres,
        string $apellidos,
        string $fecha_nacimiento,
        string $telefono,
        string $direccion
    ): int {
        $sql = "UPDATE paciente SET
                    identidad = :identidad,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    telefono = :telefono,
                    direccion = :direccion
                WHERE id = :id";
        $params = [
            "id" => $id,
            "identidad" => $identidad,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "fecha_nacimiento" => $fecha_nacimiento,
            "telefono" => $telefono,
            "direccion" => $direccion,
        ];
        parent::executeNonQuery($sql, $params);
        return 1;
    }

    public static function deletePaciente(int $id): int
    {
        $sql = "DELETE FROM paciente WHERE id = :id";
        $params = ["id" => $id];
        parent::executeNonQuery($sql, $params);
        return 1;
    }
}

