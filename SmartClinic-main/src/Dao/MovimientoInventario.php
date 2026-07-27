<?php
namespace Dao;

/**
 * Dao\MovimientoInventario
 * ------------------------
 * PROBLEMA QUE RESUELVE ESTA CLASE:
 *
 * En el sistema existen dos formas de que el stock de un producto cambie:
 *
 *   1) Ajustes manuales (InventarioController::ajustar) -> se guardan en la
 *      tabla `ajuste_inventario` con tipo_ajuste ENTRADA o SALIDA y el centro
 *      de salud donde ocurrió el movimiento.
 *
 *   2) Compras a proveedor (ComprasController::create/edit) -> el stock se
 *      incrementa directo con Producto::ajustarStock() y el detalle queda
 *      en `factura_compra_detalle`, pero NO se escribe nada en
 *      `ajuste_inventario`.
 *
 * Es decir: hoy existen dos fuentes de verdad separadas para los
 * movimientos de inventario. Si el Kárdex solo leyera `ajuste_inventario`,
 * las entradas por compra serían invisibles y el kárdex no cuadraría con
 * el stock_actual real del producto.
 *
 * En vez de modificar ComprasController (que ya funciona y es de otro
 * compañero), esta clase UNE ambas fuentes con una consulta UNION ALL y
 * expone una sola lista de movimientos con columnas homogéneas. Así el
 * Kárdex tiene un único punto de lectura y no importa si el movimiento
 * nació de un ajuste manual o de una compra.
 */
class MovimientoInventario extends Table
{
    /**
     * Devuelve el historial de movimientos de inventario (entradas y
     * salidas), combinando ajustes manuales y compras a proveedor.
     *
     * Columnas que devuelve cada fila:
     *   - fecha            (DATETIME)  cuándo ocurrió el movimiento
     *   - producto_id       (INT)
     *   - producto_nombre   (VARCHAR)
     *   - tipo_movimiento   ('ENTRADA' | 'SALIDA')
     *   - cantidad          (INT)       siempre positiva; el signo lo da tipo_movimiento
     *   - origen            ('AJUSTE' | 'COMPRA')  de dónde vino el movimiento
     *   - referencia        (VARCHAR)   motivo del ajuste, o "Factura FC-0001 - Proveedor X" si es compra
     *   - usuario_id        (INT|NULL)
     *   - usuario_nombre    (VARCHAR)   quién registró el movimiento, o "Sistema" si no se guardó usuario
     *   - centro_salud_id   (INT|NULL)  null para compras todavía globales
     *   - centro_nombre     (VARCHAR)   centro del ajuste o "Inventario general"
     *
     * @param int|null    $productoId   Filtra por un producto específico (null = todos)
     * @param string|null $fechaInicio  Formato 'YYYY-MM-DD' (null = sin límite inferior)
     * @param string|null $fechaFin     Formato 'YYYY-MM-DD' (null = sin límite superior)
     * @param string      $orden        'ASC' o 'DESC' (ASC es necesario para calcular saldo acumulado en orden cronológico)
     */
    public static function getMovimientos(
        ?int $productoId = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        string $orden = 'ASC'
    ): array {
        // --- Bloque 1: ajustes manuales -------------------------------------------------
        // Cada fila de ajuste_inventario ya representa un único movimiento
        // (ENTRADA o SALIDA), así que se mapea casi tal cual.
        $sqlAjustes = "
            SELECT
                ai.fecha_ajuste   AS fecha,
                ai.producto_id    AS producto_id,
                p.nombre          AS producto_nombre,
                ai.tipo_ajuste    AS tipo_movimiento,
                ai.cantidad       AS cantidad,
                'AJUSTE'          AS origen,
                ai.motivo         AS referencia,
                ai.usuario_id     AS usuario_id,
                COALESCE(u1.username, 'Sistema') AS usuario_nombre,
                ai.centro_salud_id AS centro_salud_id,
                cs.nombre         AS centro_nombre
            FROM ajuste_inventario ai
            JOIN producto p ON p.id = ai.producto_id
            JOIN centro_salud cs ON cs.id = ai.centro_salud_id
            LEFT JOIN usuario u1 ON u1.usercod = ai.usuario_id
            WHERE (:producto_id_1 IS NULL OR ai.producto_id = :producto_id_1)
              AND (:fecha_inicio_1 IS NULL OR ai.fecha_ajuste >= :fecha_inicio_1)
              AND (:fecha_fin_1 IS NULL OR ai.fecha_ajuste < DATE_ADD(:fecha_fin_1, INTERVAL 1 DAY))
        ";

        // --- Bloque 2: compras a proveedor -----------------------------------------------
        // Cada línea de factura_compra_detalle es una entrada de inventario.
        // La fecha real del movimiento vive en factura_compra.fecha_compra
        // (la línea de detalle no tiene su propia fecha), y el usuario que
        // hizo la compra también vive en el encabezado de la factura.
        // Se arma una "referencia" legible con el número de factura y el
        // proveedor, para que en el kárdex se entienda de dónde vino la entrada.
        $sqlCompras = "
            SELECT
                fc.fecha_compra                                             AS fecha,
                fcd.producto_id                                             AS producto_id,
                p.nombre                                                    AS producto_nombre,
                'ENTRADA'                                                   AS tipo_movimiento,
                fcd.cantidad                                                AS cantidad,
                'COMPRA'                                                    AS origen,
                CONCAT('Factura ', fc.numero_factura, ' - ', pr.nombre)     AS referencia,
                fc.usuario_id                                               AS usuario_id,
                COALESCE(u2.username, 'Sistema')                            AS usuario_nombre,
                NULL                                                        AS centro_salud_id,
                'Inventario general'                                        AS centro_nombre
            FROM factura_compra_detalle fcd
            JOIN factura_compra fc  ON fc.id = fcd.factura_compra_id
            JOIN proveedor pr       ON pr.id = fc.proveedor_id
            JOIN producto p         ON p.id = fcd.producto_id
            LEFT JOIN usuario u2    ON u2.usercod = fc.usuario_id
            WHERE (:producto_id_2 IS NULL OR fcd.producto_id = :producto_id_2)
              AND (:fecha_inicio_2 IS NULL OR fc.fecha_compra >= :fecha_inicio_2)
              AND (:fecha_fin_2 IS NULL OR fc.fecha_compra < DATE_ADD(:fecha_fin_2, INTERVAL 1 DAY))
        ";

        // --- Unión de ambas fuentes --------------------------------------------------------
        // UNION ALL (no UNION) a propósito: no queremos que MySQL elimine
        // "duplicados", cada movimiento es un evento independiente aunque
        // dos filas tengan exactamente los mismos valores.
        $orden = strtoupper($orden) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "($sqlAjustes) UNION ALL ($sqlCompras) ORDER BY fecha $orden, producto_id $orden";

        // Los parámetros con nombre se repiten (uno por cada mitad del UNION)
        // porque cada SELECT se prepara y ejecuta como parte de una sola
        // consulta, pero PDO necesita un placeholder distinto por cada
        // aparición del mismo filtro.
        $params = [
            "producto_id_1"  => $productoId,
            "fecha_inicio_1" => $fechaInicio,
            "fecha_fin_1"    => $fechaFin,
            "producto_id_2"  => $productoId,
            "fecha_inicio_2" => $fechaInicio,
            "fecha_fin_2"    => $fechaFin,
        ];

        return parent::obtenerRegistros($sql, $params);
    }

    /**
     * Igual que getMovimientos(), pero además calcula el saldo acumulado
     * (running balance) de cada producto a lo largo del tiempo.
     *
     * IMPORTANTE: como Producto::insert() siempre arranca stock_actual en 0,
     * y desde ese momento el único modo de moverlo es vía ajuste manual o
     * compra (ambos ya cubiertos por getMovimientos), el saldo acumulado
     * que calculamos aquí, recorriendo TODO el historial desde el inicio,
     * debe coincidir exactamente con producto.stock_actual al llegar a la
     * fila más reciente. Esa coincidencia es justamente la forma de
     * verificar que la unión está completa y no se está perdiendo ningún
     * movimiento.
     *
     * El cálculo se hace en PHP (no en SQL) para mantener la consulta
     * simple y portable; con los volúmenes de un sistema de clínica pequeña
     * esto no representa un problema de rendimiento.
     *
     * @param int|null    $productoId     Si se manda, obligatoriamente se recorre el
     *                                    historial COMPLETO del producto desde el inicio
     *                                    (ignorando fechaInicio) para que el saldo
     *                                    acumulado sea correcto, y luego se recorta
     *                                    la salida al rango de fechas pedido.
     * @param int|null    $centroSaludId  Igual de importante: el stock (producto.stock_actual)
     *                                    sigue siendo GLOBAL, no por centro. Por eso el filtro
     *                                    de centro NUNCA se manda a getMovimientos(): el saldo
     *                                    acumulado siempre se calcula con el historial completo
     *                                    (todos los centros + compras), y solo AL FINAL se
     *                                    recortan de la lista visible las filas que no sean del
     *                                    centro pedido. Así la columna "saldo acumulado" sigue
     *                                    reflejando el stock real del sistema en cada momento,
     *                                    aunque en pantalla solo se vean los movimientos de un
     *                                    centro en particular. Las compras (que todavía no están
     *                                    ligadas a ningún centro) quedan fuera de la vista en
     *                                    cuanto se filtra por un centro específico, porque no
     *                                    ocurrieron físicamente en ese centro.
     */
    public static function getMovimientosConSaldo(
        ?int $productoId = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        ?int $centroSaludId = null
    ): array {
        // Se trae SIEMPRE el historial completo en orden ascendente (desde
        // el principio de los tiempos) para poder calcular un saldo
        // acumulado correcto; filtrar por fecha_inicio antes de sumar
        // rompería el acumulado (empezaría a contar desde un número
        // arbitrario en vez de desde 0). Tampoco se filtra por centro aquí,
        // por la misma razón (ver comentario del parámetro arriba).
        $movimientos = self::getMovimientos($productoId, null, $fechaFin, 'ASC');

        $saldosPorProducto = [];
        foreach ($movimientos as &$mov) {
            $pid = (int) $mov['producto_id'];
            if (!isset($saldosPorProducto[$pid])) {
                $saldosPorProducto[$pid] = 0;
            }

            $delta = $mov['tipo_movimiento'] === 'SALIDA' ? -1 : 1;
            $saldosPorProducto[$pid] += $delta * (int) $mov['cantidad'];
            $mov['saldo_acumulado'] = $saldosPorProducto[$pid];
        }
        unset($mov);

        // Ahora que el saldo ya quedó calculado con el historial completo,
        // se recorta la lista al rango de fechas que realmente se quiere
        // mostrar en pantalla.
        if ($fechaInicio !== null) {
            $movimientos = array_values(array_filter($movimientos, function ($mov) use ($fechaInicio) {
                return $mov['fecha'] >= $fechaInicio;
            }));
        }

        // Y se recorta también por centro de salud, si se pidió uno. Las
        // compras tienen centro_salud_id = NULL (ver getMovimientos), así
        // que un producto_id INT jamás las va a igualar: desaparecen de la
        // vista filtrada por centro, tal como se explica arriba.
        if ($centroSaludId !== null) {
            $movimientos = array_values(array_filter($movimientos, function ($mov) use ($centroSaludId) {
                return (int) ($mov['centro_salud_id'] ?? 0) === $centroSaludId;
            }));
        }

        return $movimientos;
    }

    /**
     * Reconstruye el stock que tenía CADA producto al final de un día
     * específico ("inventario histórico"), sumando y restando en orden
     * cronológico todos los movimientos (ajustes + compras) ocurridos
     * hasta esa fecha inclusive. Es la misma idea del saldo acumulado que
     * ya usa el Kárdex, solo que aquí se calcula para todos los productos
     * de una sola pasada en vez de uno a la vez.
     *
     * Devuelve un mapa [producto_id => saldo_a_esa_fecha]. Un producto que
     * no aparece en el mapa significa que, hasta esa fecha, nunca tuvo
     * ningún movimiento (pudo no haber existido todavía, o haber existido
     * con stock en cero) — el llamador decide cómo mostrar ese caso.
     *
     * @param string $fechaCorte 'YYYY-MM-DD'. Se interpreta como "hasta el
     *                           final de este día" (mismo criterio que usa
     *                           fechaFin en getMovimientos).
     */
    public static function getSaldosPorProductoAFecha(string $fechaCorte): array
    {
        $movimientos = self::getMovimientos(null, null, $fechaCorte, 'ASC');

        $saldosPorProducto = [];
        foreach ($movimientos as $mov) {
            $pid = (int) $mov['producto_id'];
            if (!isset($saldosPorProducto[$pid])) {
                $saldosPorProducto[$pid] = 0;
            }
            $delta = $mov['tipo_movimiento'] === 'SALIDA' ? -1 : 1;
            $saldosPorProducto[$pid] += $delta * (int) $mov['cantidad'];
        }

        return $saldosPorProducto;
    }

    /**
     * Devuelve los últimos $limit movimientos (ajustes + compras), del más
     * reciente al más antiguo. Pensado para widgets tipo "Movimientos
     * recientes" en pantallas de resumen, donde no hace falta el saldo
     * acumulado (eso es lo que sí calcula getMovimientosConSaldo, pensado
     * para la pantalla de Kárdex).
     */
    public static function getRecientes(int $limit = 10): array
    {
        $movimientos = self::getMovimientos(null, null, null, 'DESC');
        return array_slice($movimientos, 0, $limit);
    }
}
