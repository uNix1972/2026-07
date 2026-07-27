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
        $sql = "SELECT fcd.*, p.nombre AS producto_nombre, p.unidad_medida AS producto_unidad, p.unidades_por_caja
                FROM factura_compra_detalle fcd
                JOIN producto p ON p.id = fcd.producto_id
                WHERE fcd.factura_compra_id = :factura_compra_id";
        return parent::obtenerRegistros($sql, ["factura_compra_id" => $facturaCompraId]);
    }

    /**
     * @param array $lineas cada elemento: ["producto_id" => int, "cantidad" => int, "precio_unitario" => float,
     *                                       "tipo_compra" => "UNI"|"CAJ", "cantidad_cajas" => int|null]
     *                       cantidad y precio_unitario ya deben venir convertidos a la unidad base del producto.
     */
    public static function insertConDetalle(int $proveedorId, string $numeroFactura, ?int $usuarioId, array $lineas): array
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

            $sqlDetalle = "INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal)
                           VALUES (:factura_compra_id, :producto_id, :cantidad, :precio_unitario, :tipo_compra, :cantidad_cajas, :subtotal)";
            foreach ($lineas as $linea) {
                $subtotal = $linea["cantidad"] * $linea["precio_unitario"];
                parent::executeNonQuery($sqlDetalle, [
                    "factura_compra_id" => $facturaCompraId,
                    "producto_id" => $linea["producto_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "tipo_compra" => $linea["tipo_compra"] ?? "UNI",
                    "cantidad_cajas" => $linea["cantidad_cajas"] ?? null,
                    "subtotal" => $subtotal
                ], $conn);

                Producto::ajustarStock($linea["producto_id"], $linea["cantidad"], $conn);
            }

            $conn->commit();
            return ["id" => $facturaCompraId, "numero_factura" => $numeroFactura];
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $lineas mismo formato que insertConDetalle()
     */
    public static function updateConDetalle(int $facturaCompraId, int $proveedorId, array $lineas): bool
    {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $detalleAnterior = parent::obtenerRegistros(
                "SELECT producto_id, cantidad FROM factura_compra_detalle WHERE factura_compra_id = :factura_compra_id",
                ["factura_compra_id" => $facturaCompraId],
                $conn
            );
            foreach ($detalleAnterior as $anterior) {
                Producto::ajustarStock((int) $anterior["producto_id"], -(int) $anterior["cantidad"], $conn);
            }

            parent::executeNonQuery(
                "DELETE FROM factura_compra_detalle WHERE factura_compra_id = :factura_compra_id",
                ["factura_compra_id" => $facturaCompraId],
                $conn
            );

            $total = 0.0;
            foreach ($lineas as $linea) {
                $total += $linea["cantidad"] * $linea["precio_unitario"];
            }

            parent::executeNonQuery(
                "UPDATE factura_compra SET proveedor_id = :proveedor_id, total = :total WHERE id = :id",
                ["proveedor_id" => $proveedorId, "total" => $total, "id" => $facturaCompraId],
                $conn
            );

            $sqlDetalle = "INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal)
                           VALUES (:factura_compra_id, :producto_id, :cantidad, :precio_unitario, :tipo_compra, :cantidad_cajas, :subtotal)";
            foreach ($lineas as $linea) {
                $subtotal = $linea["cantidad"] * $linea["precio_unitario"];
                parent::executeNonQuery($sqlDetalle, [
                    "factura_compra_id" => $facturaCompraId,
                    "producto_id" => $linea["producto_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "tipo_compra" => $linea["tipo_compra"] ?? "UNI",
                    "cantidad_cajas" => $linea["cantidad_cajas"] ?? null,
                    "subtotal" => $subtotal
                ], $conn);

                Producto::ajustarStock($linea["producto_id"], $linea["cantidad"], $conn);
            }

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
