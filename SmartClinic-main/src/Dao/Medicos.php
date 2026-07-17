<?php
namespace Dao;

class Medicos extends Table
{
    public static function getAllMedicos(): array
    {
        // Mostrar el directorio completo de médicos.
        $sql = "SELECT m.*, e.nombre_especialidad FROM medico m JOIN especialidad e ON m.especialidad_id = e.id ORDER BY m.id DESC";
        return parent::obtenerRegistros($sql, []);
    }


    public static function getAllMedicosReport(): array
    {
        return self::getAllMedicos();
    }

    public static function getMedicoById(int $id)
    {
        $sql = "SELECT * FROM medico WHERE id = :id";
        $params = ["id" => $id];
        return parent::obtenerUnRegistro($sql, $params);
    }

    public static function insertMedico(
        int $especialidad_id,
        string $nombres,
        string $apellidos,
        string $num_colegiatura,
        string $telefono
    ): int {
        $sql = "INSERT INTO medico (especialidad_id, nombres, apellidos, num_colegiatura, telefono)
                VALUES (:especialidad_id, :nombres, :apellidos, :num_colegiatura, :telefono)";
        $params = [
            "especialidad_id" => $especialidad_id,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $num_colegiatura,
            "telefono" => $telefono,
        ];
        parent::executeNonQuery($sql, $params);
        return parent::getLastInsertId();
    }

    public static function updateMedico(
        int $id,
        int $especialidad_id,
        string $nombres,
        string $apellidos,
        string $num_colegiatura,
        string $telefono
    ): int {
        $sql = "UPDATE medico SET
                    especialidad_id = :especialidad_id,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    num_colegiatura = :num_colegiatura,
                    telefono = :telefono
                WHERE id = :id";
        $params = [
            "id" => $id,
            "especialidad_id" => $especialidad_id,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "num_colegiatura" => $num_colegiatura,
            "telefono" => $telefono,
        ];
        parent::executeNonQuery($sql, $params);
        return 1;
    }

    public static function deleteMedico(int $id): int
    {
        $sql = "DELETE FROM medico WHERE id = :id";
        $params = ["id" => $id];
        parent::executeNonQuery($sql, $params);
        return 1;
    }
}
