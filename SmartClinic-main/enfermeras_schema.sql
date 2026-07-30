-- Módulo de Enfermeras: correr una sola vez en la base de datos en vivo.
-- (El docs/smartclinic_1p.sql ya quedó actualizado para instalaciones nuevas,
-- pero tu base ya existe y ese archivo solo se aplica en un volumen nuevo.)

CREATE TABLE IF NOT EXISTS enfermera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    num_colegiatura VARCHAR(50) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    usuario_id INT NULL UNIQUE,
    FOREIGN KEY (usuario_id) REFERENCES usuario (usercod) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS enfermera_centro_salud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enfermera_id INT NOT NULL,
    centro_salud_id INT NOT NULL,
    area VARCHAR(50) NOT NULL,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enfermera_centro_salud (enfermera_id, centro_salud_id),
    FOREIGN KEY (enfermera_id) REFERENCES enfermera (id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (centro_salud_id) REFERENCES centro_salud (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus) VALUES
    (40, 'Controllers\\EnfermerasController', 'Controlador CRUD Enfermeras', 'ACT'),
    (41, 'Menu_Enfermeras', 'Acceso al menú Enfermeras', 'ACT');

INSERT IGNORE INTO funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin) VALUES
    (74, 41, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

-- Portal de Enfermería: cola, confirmación de llegada y preclínica.
INSERT INTO roles (rolId, rolNombre, rolDescripcion, rolStatus)
VALUES (
    5,
    'Enfermería',
    'Acceso a la cola y preclínica de pacientes de los centros asignados',
    'ACT'
)
ON DUPLICATE KEY UPDATE
    rolNombre = VALUES(rolNombre),
    rolDescripcion = VALUES(rolDescripcion),
    rolStatus = 'ACT';

INSERT INTO funciones
    (funcionId, funcionNombre, funcionDescripcion, funcionStatus)
VALUES
    (
        43,
        'Controllers\\EnfermeriaPortalController',
        'Consultar la cola diaria de los centros asignados a la enfermera',
        'ACT'
    ),
    (
        44,
        'Menu_EnfermeriaPortal',
        'Acceso al menú Portal Enfermería',
        'ACT'
    ),
    (
        45,
        'ConfirmarLlegadaEnfermeria',
        'Confirmar la llegada de pacientes en centros asignados a la enfermera',
        'ACT'
    ),
    (
        46,
        'RegistrarPreclinicaEnfermeria',
        'Registrar signos vitales de citas en espera en centros asignados',
        'ACT'
    )
ON DUPLICATE KEY UPDATE
    funcionDescripcion = VALUES(funcionDescripcion),
    funcionStatus = 'ACT';

INSERT INTO funciones_roles
    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT
    f.funcionId,
    5,
    'ACT',
    CURRENT_TIMESTAMP,
    '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre IN (
    'Controllers\\EnfermeriaPortalController',
    'Menu_EnfermeriaPortal',
    'ConfirmarLlegadaEnfermeria',
    'RegistrarPreclinicaEnfermeria'
)
  AND NOT EXISTS (
      SELECT 1
      FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId
        AND fr.rolId = 5
  );

UPDATE funciones_roles fr
INNER JOIN funciones f ON f.funcionId = fr.funcionId
SET
    fr.frStatus = 'ACT',
    fr.frFechaInicio = CURRENT_TIMESTAMP,
    fr.frFechaFin = '2099-12-31 23:59:59'
WHERE fr.rolId = 5
  AND f.funcionNombre IN (
      'Controllers\\EnfermeriaPortalController',
      'Menu_EnfermeriaPortal',
      'ConfirmarLlegadaEnfermeria',
      'RegistrarPreclinicaEnfermeria'
  );

-- Cuenta clínica de demostración para poder abrir el portal en instalaciones
-- nuevas. Se localiza por correo, nunca por un ID de usuario asumido.
INSERT INTO usuario
    (
        username,
        useremail,
        userpswd,
        userfching,
        userpswdest,
        userpswdexp,
        userest,
        useractcod,
        userpswdchg,
        usertipo
    )
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
    username = VALUES(username),
    userpswd = VALUES(userpswd),
    userpswdest = 'ACT',
    userest = 'ACT',
    useractcod = 'ENFERMERIA';

SET @enfermeria_usuario_id = (
    SELECT usercod
    FROM usuario
    WHERE useremail = 'enfermeria@smartclinic.com'
    LIMIT 1
);

UPDATE roles_usuarios
SET
    ruStatus = 'ACT',
    ruFechaInicio = CURRENT_TIMESTAMP,
    ruFechaFin = '2099-12-31 23:59:59'
WHERE usuarioId = @enfermeria_usuario_id
  AND rolId = 5;

INSERT INTO roles_usuarios
    (usuarioId, rolId, ruStatus, ruFechaInicio, ruFechaFin)
SELECT
    @enfermeria_usuario_id,
    5,
    'ACT',
    CURRENT_TIMESTAMP,
    '2099-12-31 23:59:59'
WHERE @enfermeria_usuario_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM roles_usuarios
      WHERE usuarioId = @enfermeria_usuario_id
        AND rolId = 5
  );

INSERT INTO enfermera
    (
        nombres,
        apellidos,
        num_colegiatura,
        telefono,
        estado,
        usuario_id
    )
SELECT
    'Laura Isabel',
    'Mendoza',
    'ENF-DEMO-001',
    '+504 9999-0101',
    'ACT',
    @enfermeria_usuario_id
WHERE @enfermeria_usuario_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM enfermera
      WHERE usuario_id = @enfermeria_usuario_id
         OR num_colegiatura = 'ENF-DEMO-001'
  );

UPDATE enfermera
SET estado = 'ACT'
WHERE usuario_id = @enfermeria_usuario_id;

SET @enfermeria_id = (
    SELECT id
    FROM enfermera
    WHERE usuario_id = @enfermeria_usuario_id
    LIMIT 1
);

INSERT INTO enfermera_centro_salud
    (enfermera_id, centro_salud_id, area, estado)
SELECT
    @enfermeria_id,
    cs.id,
    'Preclínica',
    'ACT'
FROM centro_salud cs
WHERE cs.codigo = 'SMARTCLINIC'
  AND @enfermeria_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    area = VALUES(area),
    estado = 'ACT',
    fecha_actualizacion = CURRENT_TIMESTAMP;
