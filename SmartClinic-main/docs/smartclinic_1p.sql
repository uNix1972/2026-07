SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS smartclinic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE smartclinic_db;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS roles (
    rolId INT AUTO_INCREMENT PRIMARY KEY,
    rolNombre VARCHAR(50) NOT NULL UNIQUE,
    rolDescripcion VARCHAR(150) NOT NULL,
    rolStatus CHAR(3) NOT NULL DEFAULT 'ACT'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS funciones (
    funcionId INT AUTO_INCREMENT PRIMARY KEY,
    funcionNombre VARCHAR(100) NOT NULL,
    funcionDescripcion VARCHAR(200) NOT NULL,
    funcionStatus CHAR(3) NOT NULL DEFAULT 'ACT'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS roles_usuarios (
    rolUsuarioId INT AUTO_INCREMENT PRIMARY KEY,
    usuarioId INT NOT NULL,
    rolId INT NOT NULL,
    ruStatus CHAR(3) NOT NULL DEFAULT 'ACT',
    ruFechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ruFechaFin DATETIME NOT NULL,
    FOREIGN KEY (rolId) REFERENCES roles (rolId) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS funciones_roles (
    funcionRolId INT AUTO_INCREMENT PRIMARY KEY,
    funcionId INT NOT NULL,
    rolId INT NOT NULL,
    frStatus CHAR(3) NOT NULL DEFAULT 'ACT',
    frFechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    frFechaFin DATETIME NOT NULL,
    FOREIGN KEY (funcionId) REFERENCES funciones (funcionId) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (rolId) REFERENCES roles (rolId) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS usuario (
    usercod INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    useremail VARCHAR(150) NOT NULL UNIQUE,
    userpswd VARCHAR(255) NOT NULL,
    userfching DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userpswdest CHAR(3) NOT NULL DEFAULT 'ACT',
    userpswdexp DATETIME DEFAULT NULL,
    userest CHAR(3) NOT NULL DEFAULT 'ACT',
    useractcod VARCHAR(100) DEFAULT NULL,
    userpswdchg DATETIME DEFAULT NULL,
    usertipo CHAR(3) NOT NULL DEFAULT 'NOR'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE roles_usuarios
ADD CONSTRAINT fk_roles_usuarios_usuario FOREIGN KEY (usuarioId) REFERENCES usuario (usercod) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS especialidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_especialidad VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS estado_cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estado VARCHAR(50) NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identidad VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS medico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    especialidad_id INT NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    num_colegiatura VARCHAR(50) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    estado_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    FOREIGN KEY (paciente_id) REFERENCES paciente (id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medico (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (estado_id) REFERENCES estado_cita (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    roles (
        rolId,
        rolNombre,
        rolDescripcion,
        rolStatus
    )
VALUES (
        1,
        'Administrador',
        'Acceso total al sistema',
        'ACT'
    ),
    (
        2,
        'Recepción',
        'Gestión de pacientes y citas',
        'ACT'
    );

INSERT INTO
    funciones (
        funcionId,
        funcionNombre,
        funcionDescripcion,
        funcionStatus
    )
VALUES (
        1,
        'Ver panel',
        'Acceso al panel principal',
        'ACT'
    ),
    (
        2,
        'Gestionar usuarios',
        'Administrar cuentas de acceso',
        'ACT'
    ),
    (
        3,
        'Gestionar roles',
        'Administrar roles del sistema',
        'ACT'
    ),
    (
        4,
        'Gestionar funciones',
        'Administrar permisos y accesos',
        'ACT'
    ),
    (
        5,
        'Gestionar pacientes',
        'Registrar y actualizar pacientes',
        'ACT'
    ),
    (
        6,
        'Gestionar médicos',
        'Registrar y actualizar médicos',
        'ACT'
    ),
    (
        7,
        'Gestionar citas',
        'Registrar y actualizar citas',
        'ACT'
    );

-- La contraseña original del administrador es: SmartClinic#2026
-- En la base de datos se guarda la contraseña hasheada.
INSERT INTO
    usuario (
        usercod,
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
VALUES (
        1,
        'Administrador',
        'admin@smartclinic.com',
        '$2y$10$qozQxczCslUQ0Jk6AShyXOCQh7HZwMePuCgHq7LKWMIdmC2HDZNBm',
        CURRENT_TIMESTAMP,
        'ACT',
        NULL,
        'ACT',
        'ADMIN',
        NULL,
        'ADM'
    );

INSERT INTO
    roles_usuarios (
        rolUsuarioId,
        usuarioId,
        rolId,
        ruStatus,
        ruFechaInicio,
        ruFechaFin
    )
VALUES (
        1,
        1,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    );

INSERT INTO
    funciones_roles (
        funcionRolId,
        funcionId,
        rolId,
        frStatus,
        frFechaInicio,
        frFechaFin
    )
VALUES (
        1,
        1,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        2,
        2,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        3,
        3,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        4,
        4,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        5,
        5,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        6,
        6,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        7,
        7,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        8,
        5,
        2,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        9,
        7,
        2,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    );

INSERT IGNORE INTO
    funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus)
VALUES
    (8, 'Menu_Dashboard', 'Acceso al Panel/Dashboard', 'ACT'),
    (9, 'Menu_Medicos', 'Acceso al menú Médicos', 'ACT'),
    (10, 'Menu_Pacientes', 'Acceso al menú Pacientes', 'ACT'),
    (11, 'Menu_Citas', 'Acceso al menú Citas', 'ACT'),
    (12, 'Menu_Profile', 'Acceso al Perfil', 'ACT'),
    (13, 'MedicosController', 'Controlador CRUD Médicos', 'ACT'),
    (14, 'PacientesController', 'Controlador CRUD Pacientes', 'ACT'),
    (15, 'CitasController', 'Controlador CRUD Citas', 'ACT'),
    (16, 'Menu_Users', 'Acceso al menú Usuarios', 'ACT'),
    (17, 'Menu_Roles', 'Acceso al menú Roles', 'ACT');

INSERT IGNORE INTO
    funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
VALUES
    (10, 8, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (11, 9, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (12, 10, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (13, 11, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (14, 12, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

INSERT IGNORE INTO
    funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
VALUES
    (15, 14, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (16, 15, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (17, 16, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (18, 17, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

INSERT INTO
    especialidad (id, nombre_especialidad)
VALUES (1, 'Medicina General'),
    (2, 'Pediatría'),
    (3, 'Ginecología'),
    (4, 'Cardiología'),
    (5, 'Dermatología'),
    (6, 'Ortopedia'),
    (7, 'Neurología');

INSERT INTO
    estado_cita (id, nombre_estado)
VALUES (1, 'Pendiente'),
    (2, 'Confirmada'),
    (3, 'Completada'),
    (4, 'Cancelada'),
    (5, 'No Asistió')
ON DUPLICATE KEY UPDATE nombre_estado = VALUES(nombre_estado);

INSERT INTO
    paciente (
        identidad,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        direccion
    )
VALUES (
        '0801199901234',
        'Ana María',
        'Gómez',
        '1989-05-21',
        '+502 5555-1234',
        'Calle Real 123, Ciudad'
    );

INSERT INTO
    paciente (
        identidad,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        direccion
    )
VALUES (
        '0702199405678',
        'Carlos Eduardo',
        'López',
        '1994-02-10',
        '+502 5555-2345',
        'Avenida Central 45, Zona 1'
    );

INSERT INTO
    paciente (
        identidad,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        direccion
    )
VALUES (
        '0903198504321',
        'Beatriz Elena',
        'Santos',
        '1985-03-18',
        '+502 5555-3456',
        'Boulevard del Lago 78, Villa'
    );

INSERT INTO
    paciente (
        identidad,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        direccion
    )
VALUES (
        '0104199309876',
        'David Javier',
        'Méndez',
        '1993-04-05',
        '+502 5555-4567',
        'Colonia Primavera 12, Mixco'
    );

INSERT INTO
    paciente (
        identidad,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        direccion
    )
VALUES (
        '0205199208765',
        'Laura Isabel',
        'Ramírez',
        '1992-05-02',
        '+502 5555-5678',
        'Residencial Sol 5, Zona 10'
    );

INSERT INTO
    medico (
        especialidad_id,
        nombres,
        apellidos,
        num_colegiatura,
        telefono
    )
VALUES (
        1,
        'José Manuel',
        'Pérez',
        'MED-00123',
        '+502 4777-1122'
    );

INSERT INTO
    medico (
        especialidad_id,
        nombres,
        apellidos,
        num_colegiatura,
        telefono
    )
VALUES (
        2,
        'María Fernanda',
        'Ortiz',
        'MED-00124',
        '+502 4777-2233'
    );

INSERT INTO
    medico (
        especialidad_id,
        nombres,
        apellidos,
        num_colegiatura,
        telefono
    )
VALUES (
        1,
        'Ricardo',
        'Vargas',
        'MED-00125',
        '+502 4777-3344'
    );

INSERT INTO
    medico (
        especialidad_id,
        nombres,
        apellidos,
        num_colegiatura,
        telefono
    )
VALUES (
        3,
        'Sofía',
        'Alvarez',
        'MED-00126',
        '+502 4777-4455'
    );

INSERT INTO
    medico (
        especialidad_id,
        nombres,
        apellidos,
        num_colegiatura,
        telefono
    )
VALUES (
        2,
        'Miguel Ángel',
        'Ruiz',
        'MED-00127',
        '+502 4777-5566'
    );
-- =============================================================
-- Mejoras completas V1 + V2/V3 académicas: doctores, paciente,
-- historial, recetas, pagos simulados, notificaciones, recuperación
-- de contraseña y BI.
-- =============================================================

ALTER TABLE medico ADD COLUMN usuario_id INT NULL UNIQUE;
ALTER TABLE paciente ADD COLUMN usuario_id INT NULL UNIQUE;
ALTER TABLE medico ADD CONSTRAINT fk_medico_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(usercod) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE paciente ADD CONSTRAINT fk_paciente_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(usercod) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT IGNORE INTO roles (rolId, rolNombre, rolDescripcion, rolStatus) VALUES
    (3, 'Médico', 'Acceso al portal médico, sala de espera e historial clínico', 'ACT'),
    (4, 'Paciente', 'Acceso al portal web de autoservicio del paciente', 'ACT');

INSERT IGNORE INTO estado_cita (id, nombre_estado) VALUES
    (6, 'En Espera'),
    (7, 'En Atención'),
    (8, 'Confirmada y Pagada');

INSERT IGNORE INTO usuario
    (usercod, username, useremail, userpswd, userfching, userpswdest, userpswdexp, userest, useractcod, userpswdchg, usertipo)
VALUES
    (2, 'Doctor Demo', 'doctor@smartclinic.com', '$2y$12$kaRQy2afvaYspEWqwHZT1e63Ihc.HDz9N/BNpxm32PYsDVJsyZgeq', CURRENT_TIMESTAMP, 'ACT', NULL, 'ACT', 'DOCTOR', NULL, 'NOR'),
    (3, 'Paciente Demo', 'paciente@smartclinic.com', '$2y$12$3a.uBLZlGuMzEbdI04JbqOHV1Qtfnyqg5FMuUeLiskhOCuERKQllS', CURRENT_TIMESTAMP, 'ACT', NULL, 'ACT', 'PACIENTE', NULL, 'NOR');

INSERT IGNORE INTO roles_usuarios (rolUsuarioId, usuarioId, rolId, ruStatus, ruFechaInicio, ruFechaFin) VALUES
    (30, 2, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (31, 3, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

UPDATE medico SET usuario_id = 2 WHERE id = 1;
UPDATE paciente SET usuario_id = 3 WHERE id = 1;

CREATE TABLE IF NOT EXISTS historial_medico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL UNIQUE,
    motivo_consulta VARCHAR(255) NOT NULL,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NULL,
    observaciones TEXT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES cita(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS receta_medica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NOT NULL,
    medicamento VARCHAR(180) NOT NULL,
    indicaciones TEXT NOT NULL,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (historial_id) REFERENCES historial_medico(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pago_factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL UNIQUE,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo_pago VARCHAR(50) NOT NULL,
    id_transaccion_api VARCHAR(100) NOT NULL,
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES cita(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS factura_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    concepto VARCHAR(150) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (factura_id) REFERENCES pago_factura(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(80) NOT NULL,
    mensaje TEXT NOT NULL,
    usuario_destino_id INT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_destino_id) REFERENCES usuario(usercod) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    useremail VARCHAR(150) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_email_token (useremail, token)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus) VALUES
    (18, 'ReportesController', 'Controlador de reportes operativos y exportación CSV', 'ACT'),
    (19, 'AuditController', 'Controlador de bitácora de auditoría', 'ACT'),
    (20, 'DoctoresController', 'Portal de doctores, sala de espera e historial clínico', 'ACT'),
    (21, 'PacientePortalController', 'Portal de autoservicio del paciente', 'ACT'),
    (22, 'PagosController', 'Consulta de pagos y recibos simulados', 'ACT'),
    (23, 'NotificacionesController', 'Centro de notificaciones internas', 'ACT'),
    (24, 'BIController', 'Dashboard analítico de inteligencia de negocio', 'ACT'),
    (25, 'Menu_Reportes', 'Acceso al menú Reportes', 'ACT'),
    (26, 'Menu_Bitacora', 'Acceso al menú Bitácora', 'ACT'),
    (27, 'Menu_Doctor', 'Acceso al menú Portal Doctor', 'ACT'),
    (28, 'Menu_PacientePortal', 'Acceso al menú Portal Paciente', 'ACT'),
    (29, 'Menu_Pagos', 'Acceso al menú Pagos', 'ACT'),
    (30, 'Menu_Notificaciones', 'Acceso al menú Notificaciones', 'ACT'),
    (31, 'Menu_BI', 'Acceso al menú BI', 'ACT');

INSERT IGNORE INTO funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin) VALUES
    (40, 18, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (41, 19, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (42, 20, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (43, 21, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (44, 22, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (45, 23, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (46, 24, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (47, 25, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (48, 26, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (49, 27, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (50, 28, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (51, 29, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (52, 30, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (53, 31, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (54, 20, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (55, 27, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (56, 23, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (57, 30, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (58, 21, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (59, 28, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (60, 23, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (61, 30, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (62, 25, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (63, 18, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (64, 23, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (65, 30, 2, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

INSERT IGNORE INTO cita (id, paciente_id, medico_id, estado_id, fecha_hora) VALUES
    (1, 1, 1, 2, CONCAT(CURDATE(), ' 09:00:00')),
    (2, 2, 1, 6, CONCAT(CURDATE(), ' 10:00:00')),
    (3, 3, 2, 1, DATE_ADD(CONCAT(CURDATE(), ' 11:00:00'), INTERVAL 1 DAY)),
    (4, 4, 3, 4, DATE_SUB(CONCAT(CURDATE(), ' 08:30:00'), INTERVAL 1 DAY)),
    (5, 5, 2, 3, DATE_SUB(CONCAT(CURDATE(), ' 14:00:00'), INTERVAL 2 DAY));

INSERT IGNORE INTO historial_medico (id, cita_id, motivo_consulta, diagnostico, tratamiento, observaciones) VALUES
    (1, 5, 'Control general', 'Paciente estable, signos vitales dentro de rango esperado.', 'Reposo relativo e hidratación.', 'Seguimiento en 30 días.');

INSERT IGNORE INTO receta_medica (id, historial_id, medicamento, indicaciones) VALUES
    (1, 1, 'Acetaminofén 500 mg', 'Tomar 1 tableta cada 8 horas si hay dolor, por un máximo de 3 días.');

INSERT IGNORE INTO pago_factura (id, cita_id, total, metodo_pago, id_transaccion_api) VALUES
    (1, 1, 750.00, 'Tarjeta demo', 'SIM-INICIAL-001');

INSERT IGNORE INTO factura_detalle (id, factura_id, concepto, cantidad, precio_unitario, subtotal) VALUES
    (1, 1, 'Consulta médica general', 1, 750.00, 750.00);

INSERT IGNORE INTO notificaciones (id, tipo, mensaje, usuario_destino_id, leida) VALUES
    (1, 'Nueva cita web', 'Paciente Demo tiene una cita confirmada y pagada para hoy.', NULL, 0),
    (2, 'Sala de espera', 'Carlos Eduardo López se encuentra en sala de espera.', NULL, 0);

-- Módulo de Inventario y Compras

CREATE TABLE IF NOT EXISTS producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    unidad_medida VARCHAR(30) NOT NULL DEFAULT 'unidad',
    unidades_por_caja INT NOT NULL DEFAULT 1,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS proveedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(150) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    direccion VARCHAR(255) NULL,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS ajuste_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo_ajuste ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    usuario_id INT NULL,
    fecha_ajuste DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES producto(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(usercod) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS factura_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    numero_factura VARCHAR(50) NOT NULL,
    fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    usuario_id INT NULL,
    UNIQUE KEY uq_proveedor_numero_factura (proveedor_id, numero_factura),
    FOREIGN KEY (proveedor_id) REFERENCES proveedor(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(usercod) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS factura_compra_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    tipo_compra CHAR(3) NOT NULL DEFAULT 'UNI',
    cantidad_cajas INT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_compra_id) REFERENCES factura_compra(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES producto(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus) VALUES
    (32, 'InventarioController', 'Controlador de inventario: productos, stock y ajustes', 'ACT'),
    (33, 'ComprasController', 'Controlador de compras: proveedores y facturas de compra', 'ACT'),
    (34, 'Menu_Inventario', 'Acceso al menú Inventario', 'ACT'),
    (35, 'Menu_Compras', 'Acceso al menú Compras', 'ACT');

INSERT IGNORE INTO funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin) VALUES
    (66, 32, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (67, 33, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (68, 34, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (69, 35, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

-- cambios para agregar el catálogo de centros de salud

CREATE TABLE IF NOT EXISTS centro_salud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'Centro de Salud',
    direccion VARCHAR(255) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO funciones (funcionId, funcionNombre, funcionDescripcion, funcionStatus) VALUES
    (36, 'Controllers\\CentrosSaludController', 'Controlador del catálogo de centros de salud', 'ACT'),
    (37, 'Menu_CentrosSalud', 'Acceso al menú Centros de Salud', 'ACT');

INSERT IGNORE INTO funciones_roles (funcionRolId, funcionId, rolId, frStatus, frFechaInicio, frFechaFin) VALUES
    (70, 36, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'),
    (71, 37, 1, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59');

-- cambios para asociar médicos con centros de salud

CREATE TABLE IF NOT EXISTS medico_centro_salud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medico_id INT NOT NULL,
    centro_salud_id INT NOT NULL,
    consultorio VARCHAR(30) NOT NULL,
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_medico_centro_salud (medico_id, centro_salud_id),
    FOREIGN KEY (medico_id) REFERENCES medico(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (centro_salud_id) REFERENCES centro_salud(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO centro_salud
    (codigo, nombre, tipo, direccion, ciudad, estado)
VALUES
    (
        'SMARTCLINIC',
        'SmartClinic Center',
        'Clínica',
        'Centro principal SmartClinic',
        'Tegucigalpa',
        'ACT'
    );

INSERT IGNORE INTO medico_centro_salud
    (medico_id, centro_salud_id, consultorio, estado)
SELECT
    m.id,
    cs.id,
    '01',
    'ACT'
FROM medico m
JOIN centro_salud cs
    ON cs.codigo = 'SMARTCLINIC';
