<?php
namespace Dao;

class AjusteInventario extends Table
{
    public static function insert(int $productoId, string $tipoAjuste, int $cantidad, string $motivo, ?int $usuarioId): int
    {
        $sql = "INSERT INTO ajuste_inventario (producto_id, tipo_ajuste, cantidad, motivo, usuario_id)
                VALUES (:producto_id, :tipo_ajuste, :cantidad, :motivo, :usuario_id)";
        parent::executeNonQuery($sql, [
            "producto_id" => $productoId,
            "tipo_ajuste" => $tipoAjuste,
            "cantidad" => $cantidad,
            "motivo" => $motivo,
            "usuario_id" => $usuarioId
        ]);
        return (int) parent::getLastInsertId();
    }

    public static function getRecientes(int $limit = 10): array
    {
        $sql = "SELECT ai.*, p.nombre AS producto_nombre
                FROM ajuste_inventario ai
                JOIN producto p ON p.id = ai.producto_id
                ORDER BY ai.fecha_ajuste DESC
                LIMIT " . intval($limit);
        return parent::obtenerRegistros($sql, []);
    }
}
