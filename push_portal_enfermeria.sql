-- Cambios del push de JuanRa "Portal de enfermería". Seguro de correr más
-- de una vez (todo usa ON DUPLICATE KEY UPDATE / WHERE NOT EXISTS, o
-- revisa information_schema antes de tocar columnas/índices).

-- 1) Índice para deduplicar notificaciones de stock bajo por referencia.
--    (La columna `referencia` ya la tenías de una migración anterior; esto
--    solo la vuelve a comprobar por si acaso y agrega el índice nuevo.
--    Se evita "ADD COLUMN IF NOT EXISTS"/"CREATE INDEX IF NOT EXISTS"
--    porque esa sintaxis ya te dio error 1064 antes en esta base.)
SET @dbname = DATABASE();

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'notificaciones' AND COLUMN_NAME = 'referencia') > 0,
  'SELECT 1',
  'ALTER TABLE notificaciones ADD COLUMN referencia VARCHAR(120) NULL AFTER fecha_creacion'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement2 = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'notificaciones' AND INDEX_NAME = 'idx_notificaciones_referencia_leida') > 0,
  'SELECT 1',
  'CREATE INDEX idx_notificaciones_referencia_leida ON notificaciones (referencia, leida)'
));
PREPARE createIndexIfNotExists FROM @preparedStatement2;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- 2) Permiso para que un Administrador autorizado pueda editar su propia
--    cuenta (correo, estado, roles) desde la pantalla de Usuarios.
INSERT INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus)
SELECT 42, 'GestionarPerfilPropio', 'Modificar datos, estado y roles de la propia cuenta', 'ACT'
WHERE NOT EXISTS (
    SELECT 1 FROM funciones WHERE funcionId = 42 OR funcionNombre = 'GestionarPerfilPropio'
);

UPDATE funciones
SET funcionDescripcion = 'Modificar datos, estado y roles de la propia cuenta', funcionStatus = 'ACT'
WHERE funcionNombre = 'GestionarPerfilPropio';

INSERT INTO funciones_roles (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT f.funcionId, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'GestionarPerfilPropio'
  AND NOT EXISTS (
      SELECT 1 FROM funciones_roles fr WHERE fr.funcionId = f.funcionId AND fr.rolId = 1
  );

UPDATE funciones_roles fr
INNER JOIN funciones f ON f.funcionId = fr.funcionId
SET fr.frStatus = 'ACT', fr.frFechaInicio = CURRENT_TIMESTAMP, fr.frFechaFin = '2099-12-31 23:59:59'
WHERE f.funcionNombre = 'GestionarPerfilPropio' AND fr.rolId = 1;

-- 3) Portal de Enfermería: rol nuevo (rolId 5), su controlador y su menú.
INSERT INTO roles (rolId, rolNombre, rolDescripcion, rolStatus)
VALUES (5, 'Enfermería', 'Acceso a la cola operativa de pacientes de los centros asignados', 'ACT')
ON DUPLICATE KEY UPDATE
    rolNombre = VALUES(rolNombre), rolDescripcion = VALUES(rolDescripcion), rolStatus = 'ACT';

INSERT INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus)
VALUES
    (43, 'Controllers\\EnfermeriaPortalController', 'Consultar la cola diaria de los centros asignados a la enfermera', 'ACT'),
    (44, 'Menu_EnfermeriaPortal', 'Acceso al menú Portal Enfermería', 'ACT')
ON DUPLICATE KEY UPDATE funcionDescripcion = VALUES(funcionDescripcion), funcionStatus = 'ACT';

INSERT INTO funciones_roles (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT f.funcionId, 5, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre IN ('Controllers\\EnfermeriaPortalController', 'Menu_EnfermeriaPortal')
  AND NOT EXISTS (
      SELECT 1 FROM funciones_roles fr WHERE fr.funcionId = f.funcionId AND fr.rolId = 5
  );

UPDATE funciones_roles fr
INNER JOIN funciones f ON f.funcionId = fr.funcionId
SET fr.frStatus = 'ACT', fr.frFechaInicio = CURRENT_TIMESTAMP, fr.frFechaFin = '2099-12-31 23:59:59'
WHERE fr.rolId = 5
  AND f.funcionNombre IN ('Controllers\\EnfermeriaPortalController', 'Menu_EnfermeriaPortal');

-- 4) Cuenta y ficha de demostración para poder abrir el portal ya mismo
--    (usuario "enfermeria@smartclinic.com", localizado por correo).
INSERT INTO usuario
    (username, useremail, userpswd, userfching, userpswdest, userpswdexp, userest, useractcod, userpswdchg, usertipo)
VALUES
    (
        'Enfermera Demo',
        'enfermeria@smartclinic.com',
        '$2y$12$CBLsYIAIjCfwS0OwCc/D6uIOqBapby2lgBl9MsUaMaFVXNhY46BH2',
        CURRENT_TIMESTAMP,
        'ACT',
        NULL,
        'ACT',
        'ENFERMERIA',
        NULL,
        'NOR'
    )
ON DUPLICATE KEY UPDATE
    username = VALUES(username), userpswd = VALUES(userpswd), userpswdest = 'ACT',
    userest = 'ACT', useractcod = 'ENFERMERIA';

SET @enfermeria_usuario_id = (
    SELECT usercod FROM usuario WHERE useremail = 'enfermeria@smartclinic.com' LIMIT 1
);

UPDATE roles_usuarios
SET ruStatus = 'ACT', ruFechaInicio = CURRENT_TIMESTAMP, ruFechaFin = '2099-12-31 23:59:59'
WHERE usuarioId = @enfermeria_usuario_id AND rolId = 5;

INSERT INTO roles_usuarios (usuarioId, rolId, ruStatus, ruFechaInicio, ruFechaFin)
SELECT @enfermeria_usuario_id, 5, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'
WHERE @enfermeria_usuario_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM roles_usuarios WHERE usuarioId = @enfermeria_usuario_id AND rolId = 5
  );

INSERT INTO enfermera (nombres, apellidos, num_colegiatura, telefono, estado, usuario_id)
SELECT 'Laura Isabel', 'Mendoza', 'ENF-DEMO-001', '+504 9999-0101', 'ACT', @enfermeria_usuario_id
WHERE @enfermeria_usuario_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM enfermera WHERE usuario_id = @enfermeria_usuario_id OR num_colegiatura = 'ENF-DEMO-001'
  );

UPDATE enfermera SET estado = 'ACT' WHERE usuario_id = @enfermeria_usuario_id;

SET @enfermeria_id = (
    SELECT id FROM enfermera WHERE usuario_id = @enfermeria_usuario_id LIMIT 1
);

INSERT INTO enfermera_centro_salud (enfermera_id, centro_salud_id, area, estado)
SELECT @enfermeria_id, cs.id, 'Preclínica', 'ACT'
FROM centro_salud cs
WHERE cs.codigo = 'SMARTCLINIC' AND @enfermeria_id IS NOT NULL
ON DUPLICATE KEY UPDATE area = VALUES(area), estado = 'ACT', fecha_actualizacion = CURRENT_TIMESTAMP;
