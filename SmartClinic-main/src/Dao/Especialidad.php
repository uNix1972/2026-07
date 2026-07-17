<?php
namespace Dao;

class Especialidad extends Table
{
    public static function getAllEspecialidades(): array
    {
        $sql = "SELECT * FROM especialidad ORDER BY nombre_especialidad ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getEspecialidadById(int $id)
    {
        $sql = "SELECT * FROM especialidad WHERE id = :id";
        $params = ["id" => $id];
        return parent::obtenerUnRegistro($sql, $params);
    }
}
