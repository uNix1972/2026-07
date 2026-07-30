-- Seed de 20 enfermeras de prueba.
--
-- Respeta la misma regla que exige la pantalla "Registrar enfermera": toda
-- enfermera debe quedar con AL MENOS un centro de salud asignado (con su
-- área/turno) para no violar la lógica de la aplicación, igual que se hizo
-- con el seed de médicos de prueba de esta misma sesión.
--
-- Nombres y números de colegiatura sin acentos, por el mismo problema de
-- charset del cliente de mysql detectado antes con los médicos de prueba.
--
-- Es re-ejecutable: si lo corres dos veces no duplica nada (INSERT IGNORE +
-- limpieza previa de las asignaciones de estas mismas 20 enfermeras).

INSERT IGNORE INTO enfermera (nombres, apellidos, num_colegiatura, telefono, estado) VALUES
('Maria Fernanda', 'Lopez Diaz',        'ENF-00001', '+502 2233-1001', 'ACT'),
('Ana Lucia',      'Martinez Reyes',    'ENF-00002', '+502 2233-1002', 'ACT'),
('Carlos Eduardo',  'Gomez Paz',         'ENF-00003', '+502 2233-1003', 'ACT'),
('Karla Beatriz',  'Hernandez Ortiz',   'ENF-00004', '+502 2233-1004', 'ACT'),
('Jose Ramon',     'Castillo Mejia',    'ENF-00005', '+502 2233-1005', 'ACT'),
('Susy Paola',     'Rivera Cruz',       'ENF-00006', '+502 2233-1006', 'ACT'),
('Douglas Alberto','Fuentes Solis',     'ENF-00007', '+502 2233-1007', 'ACT'),
('Wendy Marisol',  'Aguilar Ponce',     'ENF-00008', '+502 2233-1008', 'ACT'),
('Ivan Geovanny',  'Escobar Nunez',     'ENF-00009', '+502 2233-1009', 'ACT'),
('Yesenia Carolina','Ramos Villeda',    'ENF-00010', '+502 2233-1010', 'ACT'),
('Alexander Josue','Duarte Leiva',      'ENF-00011', '+502 2233-1011', 'ACT'),
('Mirna Yolanda',  'Chavez Rosales',    'ENF-00012', '+502 2233-1012', 'ACT'),
('Elmer Rolando',  'Barahona Cabrera',  'ENF-00013', '+502 2233-1013', 'ACT'),
('Dilcia Esperanza','Amador Bonilla',   'ENF-00014', '+502 2233-1014', 'ACT'),
('Fredy Otoniel',  'Zuniga Portillo',   'ENF-00015', '+502 2233-1015', 'ACT'),
('Rosa Argentina', 'Maldonado Funez',   'ENF-00016', '+502 2233-1016', 'ACT'),
('Byron Estuardo', 'Pineda Salgado',    'ENF-00017', '+502 2233-1017', 'ACT'),
('Claudia Patricia','Sarmiento Vindel', 'ENF-00018', '+502 2233-1018', 'ACT'),
('Marlon Danilo',  'Cardona Estrada',   'ENF-00019', '+502 2233-1019', 'ACT'),
('Suyapa Elizabeth','Bustillo Mancia',  'ENF-00020', '+502 2233-1020', 'ACT');

-- Limpieza idempotente: si el script ya se corrió antes, quita solo las
-- asignaciones de ESTAS 20 enfermeras de prueba antes de re-crearlas
-- (no toca asignaciones de ninguna otra enfermera real).
DELETE ecs
FROM enfermera_centro_salud ecs
JOIN enfermera en ON en.id = ecs.enfermera_id
WHERE en.num_colegiatura BETWEEN 'ENF-00001' AND 'ENF-00020';

-- Asignación principal: reparte las 20 enfermeras de prueba en round-robin
-- entre TODOS los centros de salud activos (igual que se hizo con los
-- médicos de prueba), para que ninguna quede sin al menos un centro.
INSERT INTO enfermera_centro_salud (enfermera_id, centro_salud_id, area, estado)
SELECT
    en.id,
    centros.id,
    CONCAT('Turno TEST-', LPAD(en.rn + 1, 3, '0')),
    'ACT'
FROM (
    SELECT id, (ROW_NUMBER() OVER (ORDER BY id) - 1) AS rn
    FROM enfermera
    WHERE num_colegiatura BETWEEN 'ENF-00001' AND 'ENF-00020'
) en
JOIN (
    SELECT id, (ROW_NUMBER() OVER (ORDER BY id) - 1) AS rn2,
           COUNT(*) OVER () AS total
    FROM centro_salud
    WHERE estado = 'ACT'
) centros ON centros.rn2 = en.rn % centros.total;

-- Asignación secundaria (opcional, solo para probar que una enfermera puede
-- tener varios centros): cada 3ra enfermera de prueba recibe un segundo
-- centro distinto al primero. Solo aplica si hay más de un centro activo.
INSERT IGNORE INTO enfermera_centro_salud (enfermera_id, centro_salud_id, area, estado)
SELECT
    en.id,
    centros.id,
    CONCAT('Turno TEST-B-', LPAD(en.rn + 1, 3, '0')),
    'ACT'
FROM (
    SELECT id, (ROW_NUMBER() OVER (ORDER BY id) - 1) AS rn
    FROM enfermera
    WHERE num_colegiatura BETWEEN 'ENF-00001' AND 'ENF-00020'
      AND id % 3 = 0
) en
JOIN (
    SELECT id, (ROW_NUMBER() OVER (ORDER BY id) - 1) AS rn2,
           COUNT(*) OVER () AS total
    FROM centro_salud
    WHERE estado = 'ACT'
) centros ON centros.rn2 = (en.rn + 1) % centros.total
WHERE centros.total > 1;
