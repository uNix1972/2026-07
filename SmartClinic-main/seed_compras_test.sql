-- Datos de prueba: 7 facturas de compra (20 líneas, cubren los 20
-- productos) + 6 ajustes manuales, para poblar Kárdex/Movimientos e
-- Inventario. Replica exactamente lo que hace ComprasController al
-- registrar una compra real (src/Dao/FacturaCompra.php e
-- InventarioCentro.php), en vez de tocar el stock directo:
--   1) INSERT en factura_compra (número FC-#### autogenerado igual que
--      la pantalla: MAX(id)+1 con padding de 4 dígitos)
--   2) INSERT en factura_compra_detalle por cada línea (cantidad y
--      precio_unitario ya convertidos a unidad base cuando la compra es
--      "por caja", igual que hace el formulario)
--   3) UPDATE inventario_centro (saldo por centro, fuente autoritativa)
--   4) UPDATE producto.stock_actual (total agregado, se mantiene por
--      compatibilidad con pantallas viejas)
--   5) UPDATE factura_compra.total = suma de sus líneas
-- Al final se agregan 6 ajustes manuales (5 SALIDA simulando consumo,
-- 1 ENTRADA simulando conteo físico), con las mismas actualizaciones de
-- stock que hace AjusteInventario::registerWithStockChange().
--
-- Usa tus proveedores (1-5), centros (1-4) y productos (1-20) reales,
-- confirmados por ti mismo con SELECT antes de escribir esto. Las
-- cantidades de las SALIDA se calcularon para nunca dejar el saldo en
-- negativo (se valida igual que en pantalla: lanzaría error si no
-- alcanzara).
--
-- Cómo aplicarlo (PowerShell, con Docker corriendo):
--   Get-Content seed_compras_test.sql -Raw | docker exec -i smartclinic_db mysql -uroot smartclinic_db

-- ===================== FACTURA 1 =====================
-- MediSupply Honduras -> SmartClinic Center
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (1, 1, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f1 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f1, 1, 500, 3.80, 'CAJ', 5, 500*3.80),
(@f1, 2, 200, 2.40, 'CAJ', 4, 200*2.40),
(@f1, 3, 300, 3.40, 'CAJ', 3, 300*3.40);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (1,1,0),(2,1,0),(3,1,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 500, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=1 AND centro_salud_id=1;
UPDATE inventario_centro SET stock_actual = stock_actual + 200, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=2 AND centro_salud_id=1;
UPDATE inventario_centro SET stock_actual = stock_actual + 300, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=3 AND centro_salud_id=1;
UPDATE producto SET stock_actual = stock_actual + 500 WHERE id=1;
UPDATE producto SET stock_actual = stock_actual + 200 WHERE id=2;
UPDATE producto SET stock_actual = stock_actual + 300 WHERE id=3;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f1) WHERE id=@f1;

-- ===================== FACTURA 2 =====================
-- Distribuidora Farmaceutica Central -> SmartClinic Center
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (2, 1, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f2 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f2, 4, 24, 80.00, 'CAJ', 2, 24*80.00),
(@f2, 5, 500, 2.20, 'CAJ', 5, 500*2.20),
(@f2, 6, 36, 35.00, 'CAJ', 3, 36*35.00);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (4,1,0),(5,1,0),(6,1,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 24, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=4 AND centro_salud_id=1;
UPDATE inventario_centro SET stock_actual = stock_actual + 500, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=5 AND centro_salud_id=1;
UPDATE inventario_centro SET stock_actual = stock_actual + 36, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=6 AND centro_salud_id=1;
UPDATE producto SET stock_actual = stock_actual + 24 WHERE id=4;
UPDATE producto SET stock_actual = stock_actual + 500 WHERE id=5;
UPDATE producto SET stock_actual = stock_actual + 36 WHERE id=6;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f2) WHERE id=@f2;

-- ===================== FACTURA 3 =====================
-- Insumos Clinicos del Norte -> SmartClinic Norte
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (3, 2, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f3 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f3, 7, 96, 40.00, 'CAJ', 4, 96*40.00),
(@f3, 11, 200, 1.10, 'CAJ', 2, 200*1.10),
(@f3, 12, 36, 65.00, 'CAJ', 3, 36*65.00);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (7,2,0),(11,2,0),(12,2,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 96, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=7 AND centro_salud_id=2;
UPDATE inventario_centro SET stock_actual = stock_actual + 200, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=11 AND centro_salud_id=2;
UPDATE inventario_centro SET stock_actual = stock_actual + 36, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=12 AND centro_salud_id=2;
UPDATE producto SET stock_actual = stock_actual + 96 WHERE id=7;
UPDATE producto SET stock_actual = stock_actual + 200 WHERE id=11;
UPDATE producto SET stock_actual = stock_actual + 36 WHERE id=12;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f3) WHERE id=@f3;

-- ===================== FACTURA 4 =====================
-- Laboratorios VitalCare -> SmartClinic Norte (equipo, se compra por unidad)
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (4, 2, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f4 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f4, 8, 10, 130.00, 'UNI', NULL, 10*130.00),
(@f4, 9, 5, 800.00, 'UNI', NULL, 5*800.00),
(@f4, 10, 8, 580.00, 'UNI', NULL, 8*580.00);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (8,2,0),(9,2,0),(10,2,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 10, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=8 AND centro_salud_id=2;
UPDATE inventario_centro SET stock_actual = stock_actual + 5, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=9 AND centro_salud_id=2;
UPDATE inventario_centro SET stock_actual = stock_actual + 8, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=10 AND centro_salud_id=2;
UPDATE producto SET stock_actual = stock_actual + 10 WHERE id=8;
UPDATE producto SET stock_actual = stock_actual + 5 WHERE id=9;
UPDATE producto SET stock_actual = stock_actual + 8 WHERE id=10;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f4) WHERE id=@f4;

-- ===================== FACTURA 5 =====================
-- Equipo Medico Hondureno -> SmartClinic Central
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (5, 3, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f5 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f5, 13, 200, 17.00, 'CAJ', 4, 200*17.00),
(@f5, 14, 300, 7.20, 'CAJ', 3, 300*7.20),
(@f5, 15, 500, 1.30, 'CAJ', 5, 500*1.30);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (13,3,0),(14,3,0),(15,3,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 200, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=13 AND centro_salud_id=3;
UPDATE inventario_centro SET stock_actual = stock_actual + 300, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=14 AND centro_salud_id=3;
UPDATE inventario_centro SET stock_actual = stock_actual + 500, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=15 AND centro_salud_id=3;
UPDATE producto SET stock_actual = stock_actual + 200 WHERE id=13;
UPDATE producto SET stock_actual = stock_actual + 300 WHERE id=14;
UPDATE producto SET stock_actual = stock_actual + 500 WHERE id=15;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f5) WHERE id=@f5;

-- ===================== FACTURA 6 =====================
-- MediSupply Honduras -> SmartClinic Litoral
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (1, 4, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f6 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f6, 16, 36, 95.00, 'CAJ', 3, 36*95.00),
(@f6, 17, 12, 130.00, 'CAJ', 2, 12*130.00),
(@f6, 18, 150, 25.50, 'CAJ', 3, 150*25.50);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (16,4,0),(17,4,0),(18,4,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 36, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=16 AND centro_salud_id=4;
UPDATE inventario_centro SET stock_actual = stock_actual + 12, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=17 AND centro_salud_id=4;
UPDATE inventario_centro SET stock_actual = stock_actual + 150, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=18 AND centro_salud_id=4;
UPDATE producto SET stock_actual = stock_actual + 36 WHERE id=16;
UPDATE producto SET stock_actual = stock_actual + 12 WHERE id=17;
UPDATE producto SET stock_actual = stock_actual + 150 WHERE id=18;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f6) WHERE id=@f6;

-- ===================== FACTURA 7 =====================
-- Distribuidora Farmaceutica Central -> SmartClinic Central (incluye 2a compra de Guantes de nitrilo)
SELECT COALESCE(MAX(id),0)+1 INTO @siguiente FROM factura_compra;
INSERT INTO factura_compra (proveedor_id, centro_salud_id, numero_factura, total, usuario_id)
VALUES (2, 3, CONCAT('FC-', LPAD(@siguiente, 4, '0')), 0, 1);
SET @f7 = LAST_INSERT_ID();

INSERT INTO factura_compra_detalle (factura_compra_id, producto_id, cantidad, precio_unitario, tipo_compra, cantidad_cajas, subtotal) VALUES
(@f7, 19, 400, 1.70, 'CAJ', 4, 400*1.70),
(@f7, 20, 36, 51.00, 'CAJ', 3, 36*51.00),
(@f7, 1, 200, 3.90, 'CAJ', 2, 200*3.90);

INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (19,3,0),(20,3,0),(1,3,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 400, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=19 AND centro_salud_id=3;
UPDATE inventario_centro SET stock_actual = stock_actual + 36, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=20 AND centro_salud_id=3;
UPDATE inventario_centro SET stock_actual = stock_actual + 200, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=1 AND centro_salud_id=3;
UPDATE producto SET stock_actual = stock_actual + 400 WHERE id=19;
UPDATE producto SET stock_actual = stock_actual + 36 WHERE id=20;
UPDATE producto SET stock_actual = stock_actual + 200 WHERE id=1;

UPDATE factura_compra SET total = (SELECT SUM(subtotal) FROM factura_compra_detalle WHERE factura_compra_id=@f7) WHERE id=@f7;

-- ===================== AJUSTES MANUALES =====================
-- 5 SALIDA (consumo) + 1 ENTRADA (conteo físico). Cantidades elegidas
-- para nunca superar lo que se acaba de comprar arriba.

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(1, 1, 'SALIDA', 50, 'Consumo en atencion de pacientes', 1);
UPDATE inventario_centro SET stock_actual = stock_actual - 50, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=1 AND centro_salud_id=1;
UPDATE producto SET stock_actual = stock_actual - 50 WHERE id=1;

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(4, 1, 'SALIDA', 6, 'Uso en procedimientos clinicos', 1);
UPDATE inventario_centro SET stock_actual = stock_actual - 6, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=4 AND centro_salud_id=1;
UPDATE producto SET stock_actual = stock_actual - 6 WHERE id=4;

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(8, 2, 'SALIDA', 2, 'Prestamo a consultorio para toma de signos vitales', 1);
UPDATE inventario_centro SET stock_actual = stock_actual - 2, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=8 AND centro_salud_id=2;
UPDATE producto SET stock_actual = stock_actual - 2 WHERE id=8;

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(15, 3, 'SALIDA', 80, 'Consumo en atencion de pacientes', 1);
UPDATE inventario_centro SET stock_actual = stock_actual - 80, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=15 AND centro_salud_id=3;
UPDATE producto SET stock_actual = stock_actual - 80 WHERE id=15;

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(20, 3, 'SALIDA', 10, 'Consumo en atencion de pacientes', 1);
UPDATE inventario_centro SET stock_actual = stock_actual - 10, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=20 AND centro_salud_id=3;
UPDATE producto SET stock_actual = stock_actual - 10 WHERE id=20;

INSERT INTO ajuste_inventario (producto_id, centro_salud_id, tipo_ajuste, cantidad, motivo, usuario_id) VALUES
(11, 2, 'ENTRADA', 15, 'Ajuste por conteo fisico: se encontraron mas unidades de las registradas', 1);
INSERT IGNORE INTO inventario_centro (producto_id, centro_salud_id, stock_actual) VALUES (11,2,0);
UPDATE inventario_centro SET stock_actual = stock_actual + 15, fecha_actualizacion = CURRENT_TIMESTAMP WHERE producto_id=11 AND centro_salud_id=2;
UPDATE producto SET stock_actual = stock_actual + 15 WHERE id=11;
