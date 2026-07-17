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

