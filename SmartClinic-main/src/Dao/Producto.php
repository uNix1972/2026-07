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
     * Borra el producto DE VERDAD (a diferencia de disable(), que solo lo
     * marca como inactivo). OJO: esto es intencionalmente peligroso.
     *
     * - Si el producto tiene ajustes manuales en ajuste_inventario, la
     *   base de datos los borra también en cascada (FK con
     *   ON DELETE CASCADE) — ese historial se pierde para siempre.
     * - Si el producto tiene compras en factura_compra_detalle, la base
     *   de datos RECHAZA el borrado (FK con ON DELETE RESTRICT, a
     *   propósito, para no perder el historial de una compra real) y
     *   lanza una excepción PDOException. Quien llame a este método debe
     *   capturarla y explicarle al usuario que use disable() en su lugar.
     */
    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM producto WHERE id = :id";
        return parent::executeNonQuery($sql, ["id" => $id]);
    }
}
