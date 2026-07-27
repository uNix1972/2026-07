<?php
namespace Dao;

class Proveedor extends Table
{
    public static function getAll(): array
    {
        $sql = "SELECT * FROM proveedor ORDER BY nombre ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getActivos(): array
    {
        $sql = "SELECT * FROM proveedor WHERE estado = 'ACT' ORDER BY nombre ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getById(int $id)
    {
        $sql = "SELECT * FROM proveedor WHERE id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    public static function insert(
        string $nombre,
        string $contacto,
        string $telefono,
        string $email,
        string $direccion
    ): int {
        $sql = "INSERT INTO proveedor (nombre, contacto, telefono, email, direccion)
                VALUES (:nombre, :contacto, :telefono, :email, :direccion)";
        parent::executeNonQuery($sql, [
            "nombre" => $nombre,
            "contacto" => $contacto,
            "telefono" => $telefono,
            "email" => $email,
            "direccion" => $direccion
        ]);
        return (int) parent::getLastInsertId();
    }

    public static function update(
        int $id,
        string $nombre,
        string $contacto,
        string $telefono,
        string $email,
        string $direccion
    ): bool {
        $sql = "UPDATE proveedor
                SET nombre = :nombre, contacto = :contacto, telefono = :telefono,
                    email = :email, direccion = :direccion
                WHERE id = :id";
        return parent::executeNonQuery($sql, [
            "id" => $id,
            "nombre" => $nombre,
            "contacto" => $contacto,
            "telefono" => $telefono,
            "email" => $email,
            "direccion" => $direccion
        ]);
    }

    public static function disable(int $id): bool
    {
        $sql = "UPDATE proveedor SET estado = 'INA' WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }

    public static function enable(int $id): bool
    {
        $sql = "UPDATE proveedor SET estado = 'ACT' WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }
}
