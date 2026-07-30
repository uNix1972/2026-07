<?php
namespace Dao;

/**
 * Acceso a facturas de venta de medicamentos al paciente, generadas desde
 * el historial clínico cuando el médico marca que el paciente compra un
 * medicamento recetado con la clínica en vez de en otro lado.
 *
 * Mismo patrón que Dao\FacturaCompra (encabezado + detalle + ajuste de
 * inventario en una sola transacción), pero en sentido contrario: aquí se
 * DESCUENTA stock en vez de aumentarlo.
 */
class FacturaVenta extends Table
{
    /**
     * Una consulta puede no tener ninguna venta asociada (el paciente no
     * compró nada con la clínica), por eso puede devolver null.
     */
    public static function getByHistorial(int $historialId)
    {
        $sql = "SELECT * FROM factura_venta WHERE historial_id = :historial_id";
        return parent::obtenerUnRegistro($sql, ["historial_id" => $historialId]);
    }

    public static function getDetalleByFactura(int $facturaVentaId): array
    {
        $sql = "SELECT fvd.*, p.nombre AS producto_nombre, p.unidad_medida AS producto_unidad
                FROM factura_venta_detalle fvd
                JOIN producto p ON p.id = fvd.producto_id
                WHERE fvd.factura_venta_id = :factura_venta_id";
        return parent::obtenerRegistros($sql, ["factura_venta_id" => $facturaVentaId]);
    }

    /**
     * Genera el siguiente número mientras la tabla está bloqueada por la
     * transacción de venta (mismo patrón que FacturaCompra).
     */
    private static function generarNumeroFactura($conn): string
    {
        $sql = "SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM factura_venta FOR UPDATE";
        $result = parent::obtenerUnRegistro($sql, [], $conn);
        $siguiente = (int) ($result['siguiente'] ?? 1);
        return "FV-" . str_pad((string) $siguiente, 4, "0", STR_PAD_LEFT);
    }

    /**
     * @param array $lineas cada elemento: ["producto_id" => int, "cantidad" => int, "precio_unitario" => float]
     * @throws \DomainException si alguna línea deja el stock del centro en negativo.
     * @throws \RuntimeException si algún producto ya no existe.
     */
    public static function insertConDetalle(
        int $historialId,
        int $pacienteId,
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

            $sqlHeader = "INSERT INTO factura_venta
                            (historial_id, paciente_id, centro_salud_id, numero_factura, total, usuario_id)
                          VALUES
                            (:historial_id, :paciente_id, :centro_salud_id, :numero_factura, :total, :usuario_id)";
            parent::executeNonQuery($sqlHeader, [
                "historial_id" => $historialId,
                "paciente_id" => $pacienteId,
                "centro_salud_id" => $centroSaludId,
                "numero_factura" => $numeroFactura,
                "total" => $total,
                "usuario_id" => $usuarioId
            ], $conn);
            $facturaVentaId = (int) $conn->lastInsertId();

            $sqlDetalle = "INSERT INTO factura_venta_detalle (factura_venta_id, producto_id, cantidad, precio_unitario, subtotal)
                           VALUES (:factura_venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
            foreach ($lineas as $linea) {
                $subtotal = $linea["cantidad"] * $linea["precio_unitario"];
                parent::executeNonQuery($sqlDetalle, [
                    "factura_venta_id" => $facturaVentaId,
                    "producto_id" => $linea["producto_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "subtotal" => $subtotal
                ], $conn);

                // Delta negativo: una venta descuenta stock (lo contrario de
                // una compra). InventarioCentro::ajustarStock() ya rechaza
                // dejar el saldo del centro en negativo.
                InventarioCentro::ajustarStock(
                    (int) $linea["producto_id"],
                    $centroSaludId,
                    -1 * (int) $linea["cantidad"],
                    $conn
                );
                Producto::ajustarStock(
                    (int) $linea["producto_id"],
                    -1 * (int) $linea["cantidad"],
                    $conn
                );
            }

            $conn->commit();
            return [
                "id" => $facturaVentaId,
                "numero_factura" => $numeroFactura,
                "total" => $total
            ];
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }
}
