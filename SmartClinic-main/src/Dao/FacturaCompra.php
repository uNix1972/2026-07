<?php
namespace Dao;

class FacturaCompra extends Table
{
    public static function getAll(): array
    {
        $sql = "SELECT fc.*, pr.nombre AS proveedor_nombre
                FROM factura_compra fc
                JOIN proveedor pr ON pr.id = fc.proveedor_id
                ORDER BY fc.fecha_compra DESC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getById(int $id)
    {
        $sql = "SELECT fc.*, pr.nombre AS proveedor_nombre
                FROM factura_compra fc
                JOIN proveedor pr ON pr.id = fc.proveedor_id
                WHERE fc.id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    public static function getDetalleByFactura(int $facturaCompraId): array
    {
        $sql = "SELECT fcd.*, p.nombre AS producto_nombre
                FROM factura_compra_detalle fcd
                JOIN producto p ON p.id = fcd.producto_id
                WHERE fcd.factura_compra_id = :factura_compra_id";
        return parent::obtenerRegistros($sql, ["factura_compra_id" => $facturaCompraId]);
    }

    /**
     * @param array $lineas cada elemento: ["producto_id" => int, "cantidad" => int, "precio_unitario" => float]
     */
    public static function insertConDetalle(int $proveedorId, string $numeroFactura, ?int $usuarioId, array $lineas): int
    {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $total = 0.0;
            foreach ($lineas as $linea) {
                $total += $linea["cantidad"] * $linea["precio_unitario"];
            }

            $sqlHeader = "INSERT INTO factura_compra (proveedor_id, numero_factura, total, usuario_id)
                          VALUES (:proveedor_id, :numero_factura, :total, :usuario_id)";
            parent::executeNonQuery($sqlHeader, [
                "proveedor_id" => $proveedorId,
                "numero_factura" => $numeroFactura,
                "total" => $total,
                "usuario_id" => $usuarioId
            ], $conn);
            $facturaCompraId = (int) $conn->lastInsertId();

            $sqlDetalle = "INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, subtotal)
                           VALUES (:factura_compra_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
            foreach ($lineas as $linea) {
                $subtotal = $linea["cantidad"] * $linea["precio_unitario"];
                parent::executeNonQuery($sqlDetalle, [
                    "factura_compra_id" => $facturaCompraId,
                    "producto_id" => $linea["producto_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "subtotal" => $subtotal
                ], $conn);

                Producto::ajustarStock($linea["producto_id"], $linea["cantidad"], $conn);
            }

            $conn->commit();
            return $facturaCompraId;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
