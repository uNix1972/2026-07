-- Reconciliación histórica de la receta del historial #2.
-- Alcohol etílico: 5 unidades; Tubo de muestra: 10 unidades.
-- El ajuste inicial compensa el inventario legado que estaba en cero antes
-- de registrar la venta, de modo que el saldo final siga siendo no negativo.
-- Es seguro ejecutar este script más de una vez.

START TRANSACTION;

SET @historial_id = 2;
SET @alcohol_id = (
    SELECT id
    FROM producto
    WHERE nombre = 'Alcohol etilico 70% 1 L'
    ORDER BY id
    LIMIT 1
);
SET @tubo_id = (
    SELECT id
    FROM producto
    WHERE nombre = 'Tubo de muestra tapa roja'
    ORDER BY id
    LIMIT 1
);
SET @centro_id = (
    SELECT c.centro_salud_id
    FROM historial_medico h
    JOIN cita c ON c.id = h.cita_id
    WHERE h.id = @historial_id
);
SET @paciente_id = (
    SELECT c.paciente_id
    FROM historial_medico h
    JOIN cita c ON c.id = h.cita_id
    WHERE h.id = @historial_id
);
SET @usuario_id = (
    SELECT m.usuario_id
    FROM historial_medico h
    JOIN cita c ON c.id = h.cita_id
    JOIN medico m ON m.id = c.medico_id
    WHERE h.id = @historial_id
);
SET @fecha_receta = (
    SELECT MIN(fecha_emision)
    FROM receta_medica
    WHERE historial_id = @historial_id
);
SET @numero_factura = CONCAT('FV-RECON-H', @historial_id);
SET @reconciliar = (
    SELECT CASE WHEN EXISTS (
        SELECT 1
        FROM factura_venta
        WHERE historial_id = @historial_id
           OR numero_factura = @numero_factura
    ) THEN 0 ELSE 1 END
);

INSERT INTO ajuste_inventario
    (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id, fecha_ajuste)
SELECT
    @alcohol_id,
    @centro_id,
    'ENTRADA',
    5,
    '[RECONCILIACION_RECETA] Existencia previa a venta historica H2',
    @usuario_id,
    DATE_SUB(@fecha_receta, INTERVAL 1 SECOND)
WHERE @reconciliar = 1;

INSERT INTO ajuste_inventario
    (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id, fecha_ajuste)
SELECT
    @tubo_id,
    @centro_id,
    'ENTRADA',
    10,
    '[RECONCILIACION_RECETA] Existencia previa a venta historica H2',
    @usuario_id,
    DATE_SUB(@fecha_receta, INTERVAL 1 SECOND)
WHERE @reconciliar = 1;

INSERT IGNORE INTO inventario_centro
    (producto_id, centro_salud_id, stock_actual)
VALUES
    (@alcohol_id, @centro_id, 0),
    (@tubo_id, @centro_id, 0);

UPDATE inventario_centro
SET stock_actual = stock_actual + 5
WHERE @reconciliar = 1
  AND producto_id = @alcohol_id
  AND centro_salud_id = @centro_id;

UPDATE inventario_centro
SET stock_actual = stock_actual + 10
WHERE @reconciliar = 1
  AND producto_id = @tubo_id
  AND centro_salud_id = @centro_id;

UPDATE producto
SET stock_actual = stock_actual + 5
WHERE @reconciliar = 1
  AND id = @alcohol_id;

UPDATE producto
SET stock_actual = stock_actual + 10
WHERE @reconciliar = 1
  AND id = @tubo_id;

INSERT INTO factura_venta
    (
        historial_id,
        paciente_id,
        centro_salud_id,
        numero_factura,
        fecha_venta,
        total,
        usuario_id
    )
SELECT
    @historial_id,
    @paciente_id,
    @centro_id,
    @numero_factura,
    @fecha_receta,
    (5 * pa.precio_unitario) + (10 * pt.precio_unitario),
    @usuario_id
FROM producto pa
JOIN producto pt ON pt.id = @tubo_id
WHERE pa.id = @alcohol_id
  AND @reconciliar = 1;

SET @factura_venta_id = (
    SELECT id
    FROM factura_venta
    WHERE historial_id = @historial_id
    ORDER BY id
    LIMIT 1
);

INSERT INTO factura_venta_detalle
    (factura_venta_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT
    @factura_venta_id,
    p.id,
    5,
    p.precio_unitario,
    5 * p.precio_unitario
FROM producto p
WHERE p.id = @alcohol_id
  AND @reconciliar = 1;

INSERT INTO factura_venta_detalle
    (factura_venta_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT
    @factura_venta_id,
    p.id,
    10,
    p.precio_unitario,
    10 * p.precio_unitario
FROM producto p
WHERE p.id = @tubo_id
  AND @reconciliar = 1;

UPDATE inventario_centro
SET stock_actual = stock_actual - 5
WHERE @reconciliar = 1
  AND producto_id = @alcohol_id
  AND centro_salud_id = @centro_id;

UPDATE inventario_centro
SET stock_actual = stock_actual - 10
WHERE @reconciliar = 1
  AND producto_id = @tubo_id
  AND centro_salud_id = @centro_id;

UPDATE producto
SET stock_actual = stock_actual - 5
WHERE @reconciliar = 1
  AND id = @alcohol_id;

UPDATE producto
SET stock_actual = stock_actual - 10
WHERE @reconciliar = 1
  AND id = @tubo_id;

COMMIT;
