<?php
namespace Dao;

/**
 * Acceso a los saldos de inventario separados por centro de salud.
 *
 * `inventario_centro` es la fuente autoritativa para saber cuanto stock puede
 * utilizar una ubicacion. `producto.stock_actual` se mantiene como total
 * agregado por compatibilidad con modulos y reportes anteriores.
 */
class InventarioCentro extends Table
{
    /**
     * Lista el catalogo completo con el saldo disponible en un centro.
     *
     * El LEFT JOIN permite mostrar productos nuevos con saldo cero aunque
     * todavia no hayan recibido compras ni ajustes en esa ubicacion.
     */
    public static function getProductosByCentro(
        int $centroSaludId,
        bool $soloActivos = false
    ): array {
        $sql = "SELECT p.*,
                       COALESCE(ic.stock_actual, 0) AS stock_centro
                FROM producto p
                LEFT JOIN inventario_centro ic
                  ON ic.producto_id = p.id
                 AND ic.centro_salud_id = :centro_salud_id";

        if ($soloActivos) {
            $sql .= " WHERE p.estado = 'ACT'";
        }

        $sql .= " ORDER BY p.nombre ASC";

        return parent::obtenerRegistros($sql, [
            "centro_salud_id" => $centroSaludId
        ]);
    }

    /**
     * Devuelve el saldo actual de un producto en un centro.
     *
     * La ausencia de una fila significa que el producto aun no ha tenido
     * movimientos en esa ubicacion y, por tanto, su saldo es cero.
     */
    public static function getStock(int $productoId, int $centroSaludId): int
    {
        $row = parent::obtenerUnRegistro(
            "SELECT stock_actual
             FROM inventario_centro
             WHERE producto_id = :producto_id
               AND centro_salud_id = :centro_salud_id",
            [
                "producto_id" => $productoId,
                "centro_salud_id" => $centroSaludId
            ]
        );

        return (int) ($row["stock_actual"] ?? 0);
    }

    /**
     * Devuelve todos los saldos como
     * [producto_id => [centro_salud_id => stock_actual]].
     *
     * Se usa en formularios que necesitan validar varias líneas en el
     * navegador sin ejecutar una consulta por cada producto y centro.
     */
    public static function getStockMap(): array
    {
        $rows = parent::obtenerRegistros(
            "SELECT producto_id, centro_salud_id, stock_actual
             FROM inventario_centro",
            []
        );

        $map = [];
        foreach ($rows as $row) {
            $productoId = (int) $row["producto_id"];
            $centroSaludId = (int) $row["centro_salud_id"];
            if (!isset($map[$productoId])) {
                $map[$productoId] = [];
            }
            $map[$productoId][$centroSaludId] =
                (int) $row["stock_actual"];
        }

        return $map;
    }

    /**
     * Aplica un delta al saldo del centro dentro de una transaccion.
     *
     * Primero bloquea el producto y luego la fila centro-producto. Todos los
     * flujos usan este mismo orden para reducir el riesgo de interbloqueos.
     *
     * @throws \DomainException Si el movimiento dejaria saldo negativo.
     * @throws \RuntimeException Si el producto no existe.
     */
    public static function ajustarStock(
        int $productoId,
        int $centroSaludId,
        int $delta,
        &$conn
    ): bool {
        $producto = parent::obtenerUnRegistro(
            "SELECT id FROM producto WHERE id = :id FOR UPDATE",
            ["id" => $productoId],
            $conn
        );
        if (!$producto) {
            throw new \RuntimeException(
                "El producto seleccionado ya no existe."
            );
        }

        parent::executeNonQuery(
            "INSERT IGNORE INTO inventario_centro
                (producto_id, centro_salud_id, stock_actual)
             VALUES
                (:producto_id, :centro_salud_id, 0)",
            [
                "producto_id" => $productoId,
                "centro_salud_id" => $centroSaludId
            ],
            $conn
        );

        $saldo = parent::obtenerUnRegistro(
            "SELECT stock_actual
             FROM inventario_centro
             WHERE producto_id = :producto_id
               AND centro_salud_id = :centro_salud_id
             FOR UPDATE",
            [
                "producto_id" => $productoId,
                "centro_salud_id" => $centroSaludId
            ],
            $conn
        );

        $nuevoSaldo = (int) ($saldo["stock_actual"] ?? 0) + $delta;
        if ($nuevoSaldo < 0) {
            throw new \DomainException(
                "No hay suficiente stock disponible en el centro seleccionado."
            );
        }

        return parent::executeNonQuery(
            "UPDATE inventario_centro
             SET stock_actual = :stock_actual,
                 fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE producto_id = :producto_id
               AND centro_salud_id = :centro_salud_id",
            [
                "stock_actual" => $nuevoSaldo,
                "producto_id" => $productoId,
                "centro_salud_id" => $centroSaludId
            ],
            $conn
        );
    }
}
