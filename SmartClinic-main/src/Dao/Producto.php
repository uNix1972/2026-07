<?php
namespace Dao;

class Producto extends Table
{
    public const UNIDADES_MEDIDA = [
        "Unidad", "Caja", "Blíster", "Tableta", "Cápsula", "Ampolla",
        "Frasco", "Frasco ampolla", "Vial", "Jarabe", "Mililitro (ml)",
        "Litro (L)", "Miligramo (mg)", "Gramo (g)", "Kilogramo (kg)",
        "Gotas", "Sobre", "Tubo", "Parche", "Supositorio", "Inhalador",
        "Jeringa prellenada"
    ];

    public static function getAll(): array
    {
        $sql = "SELECT * FROM producto ORDER BY nombre ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getActivos(): array
    {
        $sql = "SELECT * FROM producto WHERE estado = 'ACT' ORDER BY nombre ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getById(int $id)
    {
        $sql = "SELECT * FROM producto WHERE id = :id";
        return parent::obtenerUnRegistro($sql, ["id" => $id]);
    }

    public static function insert(
        string $nombre,
        string $descripcion,
        string $unidadMedida,
        int $unidadesPorCaja,
        int $stockMinimo,
        float $precioUnitario
    ): int {
        $sql = "INSERT INTO producto (nombre, descripcion, unidad_medida, unidades_por_caja, stock_actual, stock_minimo, precio_unitario)
                VALUES (:nombre, :descripcion, :unidad_medida, :unidades_por_caja, 0, :stock_minimo, :precio_unitario)";
        parent::executeNonQuery($sql, [
            "nombre" => $nombre,
            "descripcion" => $descripcion,
            "unidad_medida" => $unidadMedida,
            "unidades_por_caja" => $unidadesPorCaja,
            "stock_minimo" => $stockMinimo,
            "precio_unitario" => $precioUnitario
        ]);
        return (int) parent::getLastInsertId();
    }

    public static function update(
        int $id,
        string $nombre,
        string $descripcion,
        string $unidadMedida,
        int $unidadesPorCaja,
        int $stockMinimo,
        float $precioUnitario
    ): bool {
        $sql = "UPDATE producto
                SET nombre = :nombre, descripcion = :descripcion, unidad_medida = :unidad_medida,
                    unidades_por_caja = :unidades_por_caja,
                    stock_minimo = :stock_minimo, precio_unitario = :precio_unitario
                WHERE id = :id";
        return parent::executeNonQuery($sql, [
            "id" => $id,
            "nombre" => $nombre,
            "descripcion" => $descripcion,
            "unidad_medida" => $unidadMedida,
            "unidades_por_caja" => $unidadesPorCaja,
            "stock_minimo" => $stockMinimo,
            "precio_unitario" => $precioUnitario
        ]);
    }

    public static function ajustarStock(int $id, int $delta, &$conn = null): bool
    {
        $sql = "UPDATE producto SET stock_actual = stock_actual + :delta WHERE id = :id";
        return parent::executeNonQuery($sql, ["delta" => $delta, "id" => $id], $conn);
    }

    public static function disable(int $id): bool
    {
        $sql = "UPDATE producto SET estado = 'INA' WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }

    /**
     * Publica una notificación global cuando un movimiento hace que el
     * stock cruce de normal a bajo (no se repite si ya estaba bajo).
     */
    public static function notificarSiStockBajo(
        string $nombre,
        int $stockAntes,
        int $stockDespues,
        int $stockMinimo
    ): void {
        if ($stockAntes >= $stockMinimo && $stockDespues < $stockMinimo) {
            ClinicaAvanzada::crearNotificacion(
                "Stock bajo",
                "El producto \"{$nombre}\" bajó del stock mínimo "
                    . "({$stockDespues}/{$stockMinimo})."
            );
        }
    }
}
