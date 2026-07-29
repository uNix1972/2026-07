-- =============================================================================
-- seed_test_inventario.sql
--
-- Datos de PRUEBA para el módulo Inventario y Compras (proveedores, productos,
-- ajustes manuales y una compra), pensados específicamente para poder probar:
--   1) El Kárdex (entradas/salidas, filtro por producto/centro/fecha)
--   2) El filtro de fecha en "Movimientos recientes" (pantalla Inventario)
--   3) El inventario histórico "a una fecha" (fecha_corte)
--
-- Este script NO es parte del esquema del proyecto (no toca CREATE TABLE) y
-- NO tiene por qué subirse a git junto con el código: es solo una ayuda para
-- que Johnny pueda probar las pantallas con datos realistas sin tener que
-- llenarlos a mano uno por uno desde el navegador.
--
-- Las fechas se calculan con NOW() - INTERVAL, así que cada vez que se corre
-- este script las fechas relativas ("hace 4 días", "hace 8 días", etc.) son
-- correctas respecto al día en que se ejecuta.
--
-- CÓMO EJECUTARLO (con los contenedores ya levantados: docker compose up -d):
--   docker exec -i smartclinic_db mysql -u root smartclinic_db < scripts/seed_test_inventario.sql
--
-- Se puede correr varias veces sin duplicar: primero borra (por nombre) los
-- registros de prueba que haya insertado una corrida anterior.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --- Limpieza de corridas anteriores (solo los datos de prueba de este script) ---
DELETE fcd FROM factura_compra_detalle fcd
    JOIN factura_compra fc ON fc.id = fcd.factura_compra_id
    WHERE fc.numero_factura LIKE 'TEST-%';
DELETE FROM factura_compra WHERE numero_factura LIKE 'TEST-%';
DELETE FROM ajuste_inventario WHERE motivo LIKE '[TEST]%';
DELETE FROM producto WHERE nombre LIKE '[TEST] %';
DELETE FROM proveedor WHERE nombre LIKE '[TEST] %';

SET FOREIGN_KEY_CHECKS = 1;

-- --- Proveedores de prueba ---------------------------------------------------
INSERT INTO proveedor (nombre, contacto, telefono, email, direccion, estado)
VALUES
    ('[TEST] Farmacéutica del Valle', 'Laura Méndez', '8091234567', 'contacto@farmavalle.test', 'Av. Principal 123, Santo Domingo', 'ACT'),
    ('[TEST] Distribuidora MediPlus', 'Carlos Reyes', '8299876543', 'ventas@mediplus.test', 'Calle 2da #45, Santiago', 'ACT');

-- --- Productos de prueba (fecha_creacion escalonada a propósito) ------------
-- Alcohol en gel se crea hace apenas 2 días: sirve para probar que al
-- consultar el inventario "hace 4 días" o "hace 8 días" ese producto
-- aparezca como "No existía aún" (todavía no se había registrado).
INSERT INTO producto (nombre, descripcion, unidad_medida, unidades_por_caja, stock_actual, stock_minimo, precio_unitario, estado, fecha_creacion)
VALUES
    ('[TEST] Paracetamol 500mg', 'Analgésico / antipirético', 'caja', 100, 230, 50, 85.00, 'ACT', NOW() - INTERVAL 20 DAY),
    ('[TEST] Ibuprofeno 400mg', 'Antiinflamatorio', 'caja', 50, 115, 30, 95.50, 'ACT', NOW() - INTERVAL 15 DAY),
    ('[TEST] Amoxicilina 500mg', 'Antibiótico', 'caja', 30, 95, 20, 140.00, 'ACT', NOW() - INTERVAL 10 DAY),
    ('[TEST] Guantes de nitrilo', 'Talla M, caja de 100', 'caja', 1, 35, 10, 320.00, 'ACT', NOW() - INTERVAL 6 DAY),
    ('[TEST] Alcohol en gel 70%', 'Botella 500ml', 'unidad', 1, 50, 15, 65.00, 'ACT', NOW() - INTERVAL 2 DAY);

-- --- Ajustes manuales (entradas/salidas) escalonados en el tiempo -----------
-- IMPORTANTE: en este proyecto centro_salud_id en ajuste_inventario es
-- NOT NULL (migración aplicada por el equipo), así que TODOS los ajustes
-- manuales y todas las compras necesitan un centro válido.
-- Si @centro_test sale NULL es porque no hay ningún centro_salud creado
-- todavía (poco probable: el propio esquema base ya inserta uno con
-- INSERT IGNORE). Si eso pasara, los INSERT de abajo fallarían con
-- "Column 'centro_salud_id' cannot be null" — en ese caso hay que crear
-- primero un centro desde la pantalla de Centros de Salud.
SET @centro_test = (SELECT id FROM centro_salud ORDER BY id LIMIT 1);

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id, fecha_ajuste)
VALUES
    -- Paracetamol
    ((SELECT id FROM producto WHERE nombre = '[TEST] Paracetamol 500mg'), @centro_test, 'ENTRADA', 200, '[TEST] Carga inicial de stock', NULL, NOW() - INTERVAL 18 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Paracetamol 500mg'), @centro_test, 'SALIDA', 40, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 10 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Paracetamol 500mg'), @centro_test, 'SALIDA', 30, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 1 DAY),
    -- (la entrada de +100 de hace 4 días para Paracetamol se registra más abajo como COMPRA, no como ajuste)

    -- Ibuprofeno
    ((SELECT id FROM producto WHERE nombre = '[TEST] Ibuprofeno 400mg'), @centro_test, 'ENTRADA', 150, '[TEST] Carga inicial de stock', NULL, NOW() - INTERVAL 12 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Ibuprofeno 400mg'), @centro_test, 'SALIDA', 20, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 8 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Ibuprofeno 400mg'), @centro_test, 'SALIDA', 15, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 3 DAY),

    -- Amoxicilina
    ((SELECT id FROM producto WHERE nombre = '[TEST] Amoxicilina 500mg'), @centro_test, 'ENTRADA', 80, '[TEST] Carga inicial de stock', NULL, NOW() - INTERVAL 9 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Amoxicilina 500mg'), @centro_test, 'SALIDA', 10, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 4 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Amoxicilina 500mg'), @centro_test, 'ENTRADA', 25, '[TEST] Reposición de stock', NULL, NOW()),

    -- Guantes de nitrilo
    ((SELECT id FROM producto WHERE nombre = '[TEST] Guantes de nitrilo'), @centro_test, 'ENTRADA', 40, '[TEST] Carga inicial de stock', NULL, NOW() - INTERVAL 5 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Guantes de nitrilo'), @centro_test, 'SALIDA', 5, '[TEST] Uso en consulta externa', NULL, NOW() - INTERVAL 1 DAY),

    -- Alcohol en gel (creado hace 2 días, por eso solo tiene movimientos recientes)
    ((SELECT id FROM producto WHERE nombre = '[TEST] Alcohol en gel 70%'), @centro_test, 'ENTRADA', 60, '[TEST] Carga inicial de stock', NULL, NOW() - INTERVAL 1 DAY),
    ((SELECT id FROM producto WHERE nombre = '[TEST] Alcohol en gel 70%'), @centro_test, 'SALIDA', 10, '[TEST] Uso en consulta externa', NULL, NOW());

-- --- Una compra real (para probar que el Kárdex también cuenta entradas ----
-- por compra, no solo ajustes manuales) -------------------------------------
INSERT INTO factura_compra
    (
        proveedor_id,
        centro_salud_id,
        numero_factura,
        fecha_compra,
        total,
        usuario_id
    )
VALUES (
    (SELECT id FROM proveedor WHERE nombre = '[TEST] Farmacéutica del Valle'),
    @centro_test,
    'TEST-0001',
    NOW() - INTERVAL 4 DAY,
    8500.00,
    NULL
);

-- El saldo operativo se guarda por centro. `producto.stock_actual` conserva
-- el total agregado y los valores de este seed ya fueron calculados para
-- coincidir con la suma de los movimientos insertados arriba.
INSERT INTO inventario_centro
    (producto_id, centro_salud_id, stock_actual)
SELECT p.id, @centro_test, p.stock_actual
FROM producto p
WHERE p.nombre LIKE '[TEST] %'
ON DUPLICATE KEY UPDATE
    stock_actual = VALUES(stock_actual);

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal)
VALUES (
    (SELECT id FROM factura_compra WHERE numero_factura = 'TEST-0001'),
    (SELECT id FROM producto WHERE nombre = '[TEST] Paracetamol 500mg'),
    100,
    85.00,
    'CAJ',
    1,
    8500.00
);

-- =============================================================================
-- Resultado esperado (stock_actual ya viene cuadrado en el INSERT de arriba,
-- pero así se verifica sumando los movimientos):
--   Paracetamol:  200 (ajuste) - 40 - 30 (ajustes) + 100 (compra) = 230
--   Ibuprofeno:   150 - 20 - 15 = 115
--   Amoxicilina:  80 - 10 + 25 = 95
--   Guantes:      40 - 5 = 35
--   Alcohol gel:  60 - 10 = 50
-- =============================================================================

SELECT 'Datos de prueba insertados correctamente.' AS resultado;
