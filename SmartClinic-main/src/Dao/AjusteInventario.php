<?php
namespace Dao;

/**
 * Acceso a datos para ajustes manuales de inventario.
 *
 * Cada ajuste pertenece a un producto y a un centro de salud. La transacción
 * modifica el saldo autoritativo de esa ubicación y el total agregado del
 * producto, conservando ambos valores sincronizados.
 */
class AjusteInventario extends Table
{
    /**
     * Inserta el registro histórico de un ajuste.
     *
     * La conexión opcional permite incluir esta escritura en la misma
     * transacción que modifica `producto.stock_actual`.
     */
    public static function insert(
        int $productoId,
        int $centroSaludId,
        string $tipoAjuste,
        int $cantidad,
        string $motivo,
        ?int $usuarioId,
        &$conn = null
    ): int {
        $sql = "INSERT INTO ajuste_inventario
                    (
                        producto_id,
                        centro_salud_id,
                        tipo_ajuste,
                        cantidad,
                        motivo,
                        usuario_id
                    )
                VALUES
                    (
                        :producto_id,
                        :centro_salud_id,
                        :tipo_ajuste,
                        :cantidad,
                        :motivo,
                        :usuario_id
                    )";
        parent::executeNonQuery($sql, [
            "producto_id" => $productoId,
            "centro_salud_id" => $centroSaludId,
            "tipo_ajuste" => $tipoAjuste,
            "cantidad" => $cantidad,
            "motivo" => $motivo,
            "usuario_id" => $usuarioId
        ], $conn);

        return (int) ($conn !== null
            ? $conn->lastInsertId()
            : parent::getLastInsertId());
    }

    /**
     * Modifica el stock y registra el ajuste como una operación atómica.
     *
     * SELECT ... FOR UPDATE bloquea la fila del producto durante la
     * transacción. Así dos salidas simultáneas no pueden validar contra el
     * mismo saldo y dejar un inventario negativo.
     *
     * @throws \DomainException Cuando una salida excede el stock disponible.
     * @throws \RuntimeException Cuando el producto ya no existe.
     */
    public static function registerWithStockChange(
        int $productoId,
        int $centroSaludId,
        string $tipoAjuste,
        int $cantidad,
        string $motivo,
        ?int $usuarioId
    ): int {
        $conn = self::getConn();
        $ownsTransaction = !$conn->inTransaction();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            $delta = $tipoAjuste === "SALIDA" ? -$cantidad : $cantidad;
            InventarioCentro::ajustarStock(
                $productoId,
                $centroSaludId,
                $delta,
                $conn
            );
            Producto::ajustarStock($productoId, $delta, $conn);
            $adjustmentId = self::insert(
                $productoId,
                $centroSaludId,
                $tipoAjuste,
                $cantidad,
                $motivo,
                $usuarioId,
                $conn
            );

            if ($ownsTransaction) {
                $conn->commit();
            }

            return $adjustmentId;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Obtiene los ajustes manuales más recientes con producto y centro.
     */
    public static function getRecientes(int $limit = 10): array
    {
        $sql = "SELECT ai.*, p.nombre AS producto_nombre,
                       cs.nombre AS centro_nombre
                FROM ajuste_inventario ai
                JOIN producto p ON p.id = ai.producto_id
                JOIN centro_salud cs ON cs.id = ai.centro_salud_id
                ORDER BY ai.fecha_ajuste DESC
                LIMIT " . intval($limit);

        return parent::obtenerRegistros($sql, []);
    }
}
