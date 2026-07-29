<?php
namespace Dao;

/**
 * Acceso a facturas de compra y sus movimientos de inventario.
 *
 * Toda compra pertenece a un centro de salud. El encabezado, el detalle y los
 * saldos del centro se escriben en una sola transaccion para evitar facturas
 * guardadas sin existencias o existencias sin su factura de origen.
 */
class FacturaCompra extends Table
{
    /**
     * Lista compras con proveedor y centro de destino.
     */
    public static function getAll(?int $centroSaludId = null): array
    {
        $sql = "SELECT fc.*, pr.nombre AS proveedor_nombre,
                       cs.nombre AS centro_nombre
                FROM factura_compra fc
                JOIN proveedor pr ON pr.id = fc.proveedor_id
                JOIN centro_salud cs ON cs.id = fc.centro_salud_id
                WHERE (
                    :centro_filtro IS NULL
                    OR fc.centro_salud_id = :centro_valor
                )
                ORDER BY fc.fecha_compra DESC";
        return parent::obtenerRegistros($sql, [
            "centro_filtro" => $centroSaludId,
            "centro_valor" => $centroSaludId
        ]);
    }

    /**
     * Obtiene una compra con su ubicacion de inventario.
     */
    public static function getById(int $id)
    {
        $sql = "SELECT fc.*, pr.nombre AS proveedor_nombre,
                       cs.nombre AS centro_nombre
                FROM factura_compra fc
                JOIN proveedor pr ON pr.id = fc.proveedor_id
                JOIN centro_salud cs ON cs.id = fc.centro_salud_id
                WHERE fc.id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    /**
     * Devuelve las lineas de una factura en unidades base.
     */
    public static function getDetalleByFactura(int $facturaCompraId): array
    {
        $sql = "SELECT fcd.*, p.nombre AS producto_nombre, p.unidad_medida AS producto_unidad, p.unidades_por_caja
                FROM factura_compra_detalle fcd
                JOIN producto p ON p.id = fcd.producto_id
                WHERE fcd.factura_compra_id = :factura_compra_id";
        return parent::obtenerRegistros($sql, ["factura_compra_id" => $facturaCompraId]);
    }

    /**
     * Genera el siguiente numero mientras la tabla esta bloqueada por la
     * transaccion de compra.
     */
    private static function generarNumeroFactura($conn): string
    {
        $sql = "SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM factura_compra FOR UPDATE";
        $result = parent::obtenerUnRegistro($sql, [], $conn);
        $siguiente = (int) ($result['siguiente'] ?? 1);
        return "FC-" . str_pad((string) $siguiente, 4, "0", STR_PAD_LEFT);
    }

    /**
     * @param array $lineas cada elemento: ["producto_id" => int, "cantidad" => int, "precio_unitario" => float,
     *                                       "tipo_compra" => "UNI"|"CAJ", "cantidad_cajas" => int|null]
     *                       cantidad y precio_unitario ya deben venir convertidos a la unidad base del producto.
     */
    public static function insertConDetalle(
        int $proveedorId,
        int $centroSaludId,
        ?int $usuarioId,
        array $lineas
    ): array {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $numeroFactura = self::generarNumeroFactura($conn);

            $total = 0.0;
            foreach ($lineas as $linea) {
                $total += $linea["cantidad"] * $linea["precio_unitario"];
            }

            $sqlHeader = "INSERT INTO factura_compra
                            (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
                          VALUES
                            (:proveedor_id, :centro_salud_id, :numero_factura, :total, :usuario_id)";
            parent::executeNonQuery($sqlHeader, [
                "proveedor_id" => $proveedorId,
                "centro_salud_id" => $centroSaludId,
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

                InventarioCentro::ajustarStock(
                    (int) $linea["producto_id"],
                    $centroSaludId,
                    (int) $linea["cantidad"],
                    $conn
                );
                Producto::ajustarStock(
                    (int) $linea["producto_id"],
                    (int) $linea["cantidad"],
                    $conn
                );
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
    public static function updateConDetalle(
        int $facturaCompraId,
        int $proveedorId,
        int $centroSaludId,
        array $lineas
    ): bool {
        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $facturaAnterior = parent::obtenerUnRegistro(
                "SELECT centro_salud_id
                 FROM factura_compra
                 WHERE id = :id
                 FOR UPDATE",
                ["id" => $facturaCompraId],
                $conn
            );
            if (!$facturaAnterior) {
                throw new \RuntimeException(
                    "La factura de compra ya no existe."
                );
            }
            $centroAnteriorId = (int) $facturaAnterior["centro_salud_id"];

            $detalleAnterior = parent::obtenerRegistros(
                "SELECT producto_id, cantidad FROM factura_compra_detalle WHERE factura_compra_id = :factura_compra_id",
                ["factura_compra_id" => $facturaCompraId],
                $conn
            );
            foreach ($detalleAnterior as $anterior) {
                $deltaAnterior = -(int) $anterior["cantidad"];
                InventarioCentro::ajustarStock(
                    (int) $anterior["producto_id"],
                    $centroAnteriorId,
                    $deltaAnterior,
                    $conn
                );
                Producto::ajustarStock(
                    (int) $anterior["producto_id"],
                    $deltaAnterior,
                    $conn
                );
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
                "UPDATE factura_compra
                 SET proveedor_id = :proveedor_id,
                     centro_salud_id = :centro_salud_id,
                     total = :total
                 WHERE id = :id",
                [
                    "proveedor_id" => $proveedorId,
                    "centro_salud_id" => $centroSaludId,
                    "total" => $total,
                    "id" => $facturaCompraId
                ],
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

                InventarioCentro::ajustarStock(
                    (int) $linea["producto_id"],
                    $centroSaludId,
                    (int) $linea["cantidad"],
                    $conn
                );
                Producto::ajustarStock(
                    (int) $linea["producto_id"],
                    (int) $linea["cantidad"],
                    $conn
                );
            }

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
