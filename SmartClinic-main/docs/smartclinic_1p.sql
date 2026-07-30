SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS smartclinic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE smartclinic_db;
SET NAMES utf8mb4;

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
    -- Igual que producto/proveedor: en vez de borrar médicos con historial de
    -- citas, se desactivan (INA) para que dejen de aparecer en Agenda/Citas
    -- sin perder su información. Solo se permite el borrado definitivo si el
    -- médico nunca tuvo ninguna cita (ver MedicosController::eliminar()).
    estado CHAR(3) NOT NULL DEFAULT 'ACT',
    FOREIGN KEY (especialidad_id) REFERENCES especialidad (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    centro_salud_id INT NULL,
    consultorio VARCHAR(30) NULL,
    estado_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    hora_inicio_atencion DATETIME NULL,
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

-- cambios para corregir textos de roles guardados con doble codificación UTF-8
UPDATE roles
SET
    rolNombre = 'Recepción',
    rolDescripcion = 'Gestión de pacientes y citas'
WHERE rolId = 2;

UPDATE roles
SET
    rolNombre = 'Médico',
    rolDescripcion = 'Acceso al portal médico, sala de espera e historial clínico'
WHERE rolId = 3;

-- cambios para corregir textos de funciones guardados con doble codificación UTF-8
UPDATE funciones
SET funcionNombre = CONVERT(
    BINARY(CONVERT(funcionNombre USING latin1))
    USING utf8mb4
)
WHERE
    HEX(funcionNombre) LIKE '%C383C2%'
    OR HEX(funcionNombre) LIKE '%C382C2%';

UPDATE funciones
SET funcionDescripcion = CONVERT(
    BINARY(CONVERT(funcionDescripcion USING latin1))
    USING utf8mb4
)
WHERE
    HEX(funcionDescripcion) LIKE '%C383C2%'
    OR HEX(funcionDescripcion) LIKE '%C382C2%';

INSERT IGNORE INTO estado_cita (id, nombre_estado) VALUES
    (6, 'En Espera'),
    (7, 'En Atención');

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

CREATE TABLE IF NOT EXISTS signos_vitales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL UNIQUE,
    temperatura DECIMAL(4,1) NULL,
    presion_sistolica SMALLINT UNSIGNED NULL,
    presion_diastolica SMALLINT UNSIGNED NULL,
    frecuencia_cardiaca SMALLINT UNSIGNED NULL,
    frecuencia_respiratoria SMALLINT UNSIGNED NULL,
    saturacion_oxigeno DECIMAL(5,2) NULL,
    peso DECIMAL(6,2) NULL,
    talla DECIMAL(5,2) NULL,
    notas VARCHAR(500) NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES cita(id) ON DELETE CASCADE ON UPDATE CASCADE
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
    -- Clave interna (no se muestra en pantalla) para que avisos que se
    -- vuelven a evaluar en cada carga, como el de stock bajo, no generen
    -- una fila duplicada mientras ya haya una sin leer con esta misma
    -- referencia. Ver Dao\ClinicaAvanzada::existeNotificacionActivaPorReferencia().
    referencia VARCHAR(120) NULL,
    INDEX idx_notificaciones_referencia_leida (referencia, leida),
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
    (20, 'Controllers\\DoctoresController', 'Portal de doctores, sala de espera e historial clínico', 'ACT'),
    (21, 'Controllers\\PacientePortalController', 'Portal de autoservicio del paciente', 'ACT'),
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

INSERT IGNORE INTO cita
    (id, paciente_id, medico_id, consultorio, estado_id, fecha_hora)
VALUES
    (1, 1, 1, '01', 2, CONCAT(CURDATE(), ' 09:00:00')),
    (2, 2, 1, '01', 6, CONCAT(CURDATE(), ' 10:00:00')),
    (3, 3, 2, '02', 1, DATE_ADD(CONCAT(CURDATE(), ' 11:00:00'), INTERVAL 1 DAY)),
    (4, 4, 3, '03', 4, DATE_SUB(CONCAT(CURDATE(), ' 08:30:00'), INTERVAL 1 DAY)),
    (5, 5, 2, '02', 3, DATE_SUB(CONCAT(CURDATE(), ' 14:00:00'), INTERVAL 2 DAY));

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
    centro_salud_id INT NULL,
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
    centro_salud_id INT NULL,
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
    consultorio_activo VARCHAR(30)
        AS (
            CASE
                WHEN estado = 'ACT' THEN TRIM(consultorio)
                ELSE NULL
            END
        ) STORED,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_medico_centro_salud (medico_id, centro_salud_id),
    UNIQUE KEY uq_centro_consultorio_activo (centro_salud_id, consultorio_activo),
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
    LPAD(m.id, 2, '0'),
    'ACT'
FROM medico m
JOIN centro_salud cs
    ON cs.codigo = 'SMARTCLINIC';

-- cambios para aplicar centros de salud a los ajustes de inventario
-- La columna centro_salud_id se crea directamente en ajuste_inventario.
-- Equivale a una migración ADD COLUMN IF NOT EXISTS centro_salud_id.

UPDATE ajuste_inventario ai
JOIN centro_salud cs
    ON cs.codigo = 'SMARTCLINIC'
SET ai.centro_salud_id = cs.id
WHERE ai.centro_salud_id IS NULL;

ALTER TABLE ajuste_inventario
    MODIFY COLUMN centro_salud_id INT NOT NULL;

SET @fk_ajuste_inventario_centro_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ajuste_inventario'
      AND CONSTRAINT_NAME = 'fk_ajuste_inventario_centro_salud'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @fk_ajuste_inventario_centro_sql = IF(
    @fk_ajuste_inventario_centro_exists = 0,
    'ALTER TABLE ajuste_inventario ADD CONSTRAINT fk_ajuste_inventario_centro_salud FOREIGN KEY (centro_salud_id) REFERENCES centro_salud (id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'DO 1'
);

PREPARE fk_ajuste_inventario_centro_statement
    FROM @fk_ajuste_inventario_centro_sql;
EXECUTE fk_ajuste_inventario_centro_statement;
DEALLOCATE PREPARE fk_ajuste_inventario_centro_statement;

-- cambios para evitar consultorios duplicados en un centro de salud

UPDATE medico_centro_salud mcs
JOIN (
    SELECT
        active_assignments.id,
        LPAD(
            ROW_NUMBER() OVER (
                PARTITION BY active_assignments.centro_salud_id
                ORDER BY active_assignments.medico_id, active_assignments.id
            ),
            2,
            '0'
        ) AS nuevo_consultorio
    FROM medico_centro_salud active_assignments
    JOIN (
        SELECT centro_salud_id
        FROM medico_centro_salud
        WHERE estado = 'ACT'
        GROUP BY centro_salud_id
        HAVING COUNT(*) > COUNT(DISTINCT TRIM(consultorio))
    ) centros_duplicados
        ON centros_duplicados.centro_salud_id =
           active_assignments.centro_salud_id
    WHERE active_assignments.estado = 'ACT'
) habitaciones_unicas
    ON habitaciones_unicas.id = mcs.id
SET mcs.consultorio = habitaciones_unicas.nuevo_consultorio;

-- consultorio_activo se crea directamente con medico_centro_salud.

SET @uq_centro_consultorio_activo_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'medico_centro_salud'
      AND INDEX_NAME = 'uq_centro_consultorio_activo'
);

SET @uq_centro_consultorio_activo_sql = IF(
    @uq_centro_consultorio_activo_exists = 0,
    'ALTER TABLE medico_centro_salud ADD UNIQUE KEY uq_centro_consultorio_activo (centro_salud_id, consultorio_activo)',
    'DO 1'
);

PREPARE uq_centro_consultorio_activo_statement
    FROM @uq_centro_consultorio_activo_sql;
EXECUTE uq_centro_consultorio_activo_statement;
DEALLOCATE PREPARE uq_centro_consultorio_activo_statement;

-- cambios para aplicar centros de salud a las citas
-- La columna se crea con cita; equivale a ADD COLUMN IF NOT EXISTS centro_salud_id.

UPDATE cita c
SET c.centro_salud_id = (
    SELECT mcs.centro_salud_id
    FROM medico_centro_salud mcs
    JOIN centro_salud cs
        ON cs.id = mcs.centro_salud_id
    WHERE mcs.medico_id = c.medico_id
      AND mcs.estado = 'ACT'
      AND cs.estado = 'ACT'
    ORDER BY
        CASE WHEN cs.codigo = 'SMARTCLINIC' THEN 0 ELSE 1 END,
        mcs.id ASC
    LIMIT 1
)
WHERE c.centro_salud_id IS NULL;

-- cambios para preservar el consultorio historico de cada cita

SET @cita_consultorio_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cita'
      AND COLUMN_NAME = 'consultorio'
);

SET @cita_consultorio_column_sql = IF(
    @cita_consultorio_column_exists = 0,
    'ALTER TABLE cita ADD COLUMN consultorio VARCHAR(30) NULL AFTER centro_salud_id',
    'DO 1'
);

PREPARE cita_consultorio_column_statement
    FROM @cita_consultorio_column_sql;
EXECUTE cita_consultorio_column_statement;
DEALLOCATE PREPARE cita_consultorio_column_statement;

UPDATE cita c
JOIN medico_centro_salud mcs
    ON mcs.medico_id = c.medico_id
   AND mcs.centro_salud_id = c.centro_salud_id
SET c.consultorio = mcs.consultorio
WHERE c.consultorio IS NULL
   OR TRIM(c.consultorio) = '';

ALTER TABLE cita
    MODIFY COLUMN centro_salud_id INT NOT NULL,
    MODIFY COLUMN consultorio VARCHAR(30) NOT NULL;

SET @fk_cita_medico_centro_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cita'
      AND CONSTRAINT_NAME = 'fk_cita_medico_centro'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @fk_cita_medico_centro_sql = IF(
    @fk_cita_medico_centro_exists = 0,
    'ALTER TABLE cita ADD CONSTRAINT fk_cita_medico_centro FOREIGN KEY (medico_id, centro_salud_id) REFERENCES medico_centro_salud (medico_id, centro_salud_id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'DO 1'
);

PREPARE fk_cita_medico_centro_statement FROM @fk_cita_medico_centro_sql;
EXECUTE fk_cita_medico_centro_statement;
DEALLOCATE PREPARE fk_cita_medico_centro_statement;

-- cambios para separar el inventario por centro de salud

SET @factura_compra_centro_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'factura_compra'
      AND COLUMN_NAME = 'centro_salud_id'
);

SET @factura_compra_centro_column_sql = IF(
    @factura_compra_centro_column_exists = 0,
    'ALTER TABLE factura_compra ADD COLUMN centro_salud_id INT NULL AFTER proveedor_id',
    'DO 1'
);

PREPARE factura_compra_centro_column_statement
    FROM @factura_compra_centro_column_sql;
EXECUTE factura_compra_centro_column_statement;
DEALLOCATE PREPARE factura_compra_centro_column_statement;

UPDATE factura_compra fc
JOIN centro_salud cs
    ON cs.codigo = 'SMARTCLINIC'
SET fc.centro_salud_id = cs.id
WHERE fc.centro_salud_id IS NULL;

ALTER TABLE factura_compra
    MODIFY COLUMN centro_salud_id INT NOT NULL;

SET @fk_factura_compra_centro_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'factura_compra'
      AND CONSTRAINT_NAME = 'fk_factura_compra_centro_salud'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @fk_factura_compra_centro_sql = IF(
    @fk_factura_compra_centro_exists = 0,
    'ALTER TABLE factura_compra ADD CONSTRAINT fk_factura_compra_centro_salud FOREIGN KEY (centro_salud_id) REFERENCES centro_salud (id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'DO 1'
);

PREPARE fk_factura_compra_centro_statement
    FROM @fk_factura_compra_centro_sql;
EXECUTE fk_factura_compra_centro_statement;
DEALLOCATE PREPARE fk_factura_compra_centro_statement;

CREATE TABLE IF NOT EXISTS inventario_centro (
    producto_id INT NOT NULL,
    centro_salud_id INT NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (producto_id, centro_salud_id),
    KEY idx_inventario_centro_centro (centro_salud_id),
    CONSTRAINT fk_inventario_centro_producto
        FOREIGN KEY (producto_id)
        REFERENCES producto (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_inventario_centro_centro_salud
        FOREIGN KEY (centro_salud_id)
        REFERENCES centro_salud (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO inventario_centro
    (producto_id, centro_salud_id, stock_actual)
SELECT p.id, cs.id, 0
FROM producto p
CROSS JOIN centro_salud cs;

UPDATE inventario_centro ic
LEFT JOIN (
    SELECT
        movimientos.producto_id,
        movimientos.centro_salud_id,
        SUM(movimientos.delta) AS saldo_movimientos
    FROM (
        SELECT
            ai.producto_id,
            ai.centro_salud_id,
            CASE
                WHEN ai.tipo_ajuste = 'SALIDA' THEN -ai.cantidad
                ELSE ai.cantidad
            END AS delta
        FROM ajuste_inventario ai
        UNION ALL
        SELECT
            fcd.producto_id,
            fc.centro_salud_id,
            fcd.cantidad AS delta
        FROM factura_compra_detalle fcd
        JOIN factura_compra fc
            ON fc.id = fcd.factura_compra_id
    ) movimientos
    GROUP BY movimientos.producto_id, movimientos.centro_salud_id
) saldos
    ON saldos.producto_id = ic.producto_id
   AND saldos.centro_salud_id = ic.centro_salud_id
SET ic.stock_actual = COALESCE(saldos.saldo_movimientos, 0);

INSERT INTO ajuste_inventario
    (
        producto_id,
        centro_salud_id,
        tipo_ajuste,
        cantidad,
        motivo,
        usuario_id
    )
SELECT
    p.id,
    centro_default.id,
    CASE
        WHEN p.stock_actual - COALESCE(totales.total_centros, 0) < 0
            THEN 'SALIDA'
        ELSE 'ENTRADA'
    END,
    ABS(p.stock_actual - COALESCE(totales.total_centros, 0)),
    '[MIGRACION_CENTRO] Saldo inicial conciliado',
    NULL
FROM producto p
JOIN centro_salud centro_default
    ON centro_default.codigo = 'SMARTCLINIC'
LEFT JOIN (
    SELECT producto_id, SUM(stock_actual) AS total_centros
    FROM inventario_centro
    GROUP BY producto_id
) totales
    ON totales.producto_id = p.id
WHERE p.stock_actual <> COALESCE(totales.total_centros, 0)
  AND NOT EXISTS (
      SELECT 1
      FROM ajuste_inventario ajuste_migracion
      WHERE ajuste_migracion.producto_id = p.id
        AND ajuste_migracion.motivo =
            '[MIGRACION_CENTRO] Saldo inicial conciliado'
  );

UPDATE inventario_centro inventario_default
JOIN centro_salud centro_default
    ON centro_default.id = inventario_default.centro_salud_id
   AND centro_default.codigo = 'SMARTCLINIC'
JOIN producto p
    ON p.id = inventario_default.producto_id
LEFT JOIN (
    SELECT producto_id, SUM(stock_actual) AS total_centros
    FROM inventario_centro
    GROUP BY producto_id
) totales
    ON totales.producto_id = p.id
SET inventario_default.stock_actual =
    inventario_default.stock_actual
    + (p.stock_actual - COALESCE(totales.total_centros, 0));

-- datos de demostracion para centros de salud, proveedores y productos

INSERT IGNORE INTO centro_salud
    (
        codigo,
        nombre,
        tipo,
        direccion,
        ciudad,
        telefono,
        email,
        estado
    )
VALUES
    (
        'SC-NORTE',
        'SmartClinic Norte',
        'Clinica Ambulatoria',
        'Boulevard del Norte, Colonia Universidad',
        'San Pedro Sula',
        '+504 2550-1100',
        'norte@smartclinic.hn',
        'ACT'
    ),
    (
        'SC-CENTRAL',
        'SmartClinic Central',
        'Centro de Especialidades',
        'Barrio Arriba, Avenida Centenario',
        'Comayagua',
        '+504 2772-2200',
        'central@smartclinic.hn',
        'ACT'
    ),
    (
        'SC-LITORAL',
        'SmartClinic Litoral',
        'Clinica Ambulatoria',
        'Avenida San Isidro, Barrio El Centro',
        'La Ceiba',
        '+504 2442-3300',
        'litoral@smartclinic.hn',
        'ACT'
    );

INSERT INTO proveedor
    (nombre, contacto, telefono, email, direccion, estado)
SELECT
    semilla.nombre,
    semilla.contacto,
    semilla.telefono,
    semilla.email,
    semilla.direccion,
    'ACT'
FROM (
    SELECT
        'MediSupply Honduras' AS nombre,
        'Andrea Mejia' AS contacto,
        '+504 2235-4101' AS telefono,
        'ventas@medisupply.hn' AS email,
        'Colonia Palmira, Tegucigalpa' AS direccion
    UNION ALL
    SELECT
        'Distribuidora Farmaceutica Central',
        'Carlos Pineda',
        '+504 2237-4102',
        'pedidos@dfcentral.hn',
        'Barrio La Granja, Tegucigalpa'
    UNION ALL
    SELECT
        'Insumos Clinicos del Norte',
        'Melissa Rivera',
        '+504 2552-4103',
        'ventas@icnorte.hn',
        'Colonia Trejo, San Pedro Sula'
    UNION ALL
    SELECT
        'Laboratorios VitalCare',
        'Roberto Lagos',
        '+504 2239-4104',
        'contacto@vitalcare.hn',
        'Residencial America, Tegucigalpa'
    UNION ALL
    SELECT
        'Equipo Medico Hondureno',
        'Sofia Martinez',
        '+504 2241-4105',
        'cotizaciones@emh.hn',
        'Boulevard Morazan, Tegucigalpa'
) semilla
WHERE NOT EXISTS (
    SELECT 1
    FROM proveedor existente
    WHERE existente.nombre = semilla.nombre
);

INSERT INTO producto
    (
        nombre,
        descripcion,
        unidad_medida,
        unidades_por_caja,
        stock_actual,
        stock_minimo,
        precio_unitario,
        estado
    )
SELECT
    semilla.nombre,
    semilla.descripcion,
    semilla.unidad_medida,
    semilla.unidades_por_caja,
    0,
    semilla.stock_minimo,
    semilla.precio_unitario,
    'ACT'
FROM (
    SELECT 'Guantes de nitrilo' AS nombre, 'Guantes de examen sin latex, talla mediana' AS descripcion, 'par' AS unidad_medida, 100 AS unidades_por_caja, 200 AS stock_minimo, 4.50 AS precio_unitario
    UNION ALL SELECT 'Mascarilla quirurgica', 'Mascarilla desechable de tres capas', 'unidad', 50, 150, 3.00
    UNION ALL SELECT 'Jeringa desechable 5 ml', 'Jeringa esteril con aguja', 'unidad', 100, 100, 4.25
    UNION ALL SELECT 'Alcohol etilico 70% 1 L', 'Solucion antiseptica para uso clinico', 'botella', 12, 24, 85.00
    UNION ALL SELECT 'Gasa esteril 10 x 10 cm', 'Compresa de gasa esteril individual', 'unidad', 100, 100, 2.75
    UNION ALL SELECT 'Venda elastica 10 cm', 'Venda elastica de compresion', 'rollo', 12, 24, 38.00
    UNION ALL SELECT 'Solucion salina 500 ml', 'Solucion de cloruro de sodio al 0.9%', 'bolsa', 24, 48, 42.00
    UNION ALL SELECT 'Termometro digital', 'Termometro clinico digital', 'unidad', 1, 5, 145.00
    UNION ALL SELECT 'Tensiometro aneroide', 'Tensiometro manual con brazalete adulto', 'unidad', 1, 3, 890.00
    UNION ALL SELECT 'Oximetro de pulso', 'Oximetro digital de dedo', 'unidad', 1, 4, 650.00
    UNION ALL SELECT 'Bajalenguas de madera', 'Bajalenguas desechable no esteril', 'unidad', 100, 100, 1.25
    UNION ALL SELECT 'Algodon absorbente 500 g', 'Rollo de algodon para uso clinico', 'rollo', 12, 12, 72.00
    UNION ALL SELECT 'Cateter intravenoso 22G', 'Cateter periferico esteril', 'unidad', 50, 50, 18.50
    UNION ALL SELECT 'Tubo de muestra tapa roja', 'Tubo al vacio sin anticoagulante', 'unidad', 100, 100, 8.00
    UNION ALL SELECT 'Curita adhesiva', 'Aposito adhesivo individual', 'unidad', 100, 100, 1.50
    UNION ALL SELECT 'Desinfectante de superficies 1 L', 'Desinfectante concentrado para areas clinicas', 'botella', 12, 12, 110.00
    UNION ALL SELECT 'Papel para camilla 50 m', 'Rollo de papel desechable para camilla', 'rollo', 6, 12, 135.00
    UNION ALL SELECT 'Bata desechable', 'Bata de aislamiento de manga larga', 'unidad', 50, 50, 28.00
    UNION ALL SELECT 'Gorro quirurgico', 'Gorro desechable tipo acordeon', 'unidad', 100, 100, 2.00
    UNION ALL SELECT 'Gel antibacterial 500 ml', 'Gel para higiene de manos con alcohol', 'botella', 12, 24, 68.00
) semilla
WHERE NOT EXISTS (
    SELECT 1
    FROM producto existente
    WHERE existente.nombre = semilla.nombre
);

INSERT IGNORE INTO inventario_centro
    (producto_id, centro_salud_id, stock_actual)
SELECT producto.id, centro.id, 0
FROM producto
CROSS JOIN centro_salud centro;

-- cambios para agregar el flujo persistente de contacto

CREATE TABLE IF NOT EXISTS contacto_mensaje (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(160) NOT NULL,
    asunto VARCHAR(120) NOT NULL,
    mensaje TEXT NOT NULL,
    ip_origen VARCHAR(45) NULL,
    estado CHAR(3) NOT NULL DEFAULT 'PEN',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME NULL,
    fecha_resolucion DATETIME NULL,
    usuario_gestion_id INT NULL,
    KEY idx_contacto_mensaje_estado_fecha (estado, fecha_creacion),
    KEY idx_contacto_mensaje_email (email),
    CONSTRAINT fk_contacto_mensaje_usuario_gestion
        FOREIGN KEY (usuario_gestion_id)
        REFERENCES usuario (usercod)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT IGNORE INTO funciones
    (funcionId, funcionNombre, funcionDescripcion, funcionStatus)
VALUES
    (
        38,
        'Controllers\\ContactoMensajesController',
        'Administrar mensajes recibidos desde el formulario de contacto',
        'ACT'
    ),
    (
        39,
        'Menu_ContactoMensajes',
        'Acceso al menu Mensajes de contacto',
        'ACT'
    );

INSERT IGNORE INTO funciones_roles
    (
        funcionRolId,
        funcionId,
        rolId,
        frStatus,
        frFechaInicio,
        frFechaFin
    )
VALUES
    (
        72,
        38,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    ),
    (
        73,
        39,
        1,
        'ACT',
        CURRENT_TIMESTAMP,
        '2099-12-31 23:59:59'
    );

-- cambios para agregar el módulo de Enfermeras
-- Mismo patrón que medico/medico_centro_salud: la enfermera nunca se borra
-- si ya tuvo alguna asignación a un centro (se desactiva en su lugar), y la
-- relación con centros de salud es muchos-a-muchos porque una enfermera
-- puede atender en varias ubicaciones. usuario_id es un vínculo opcional y
-- único con una cuenta de acceso ya existente (no crea cuentas nuevas).

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

-- cambios para permitir que un usuario autorizado administre su propia cuenta
INSERT INTO funciones (
    funcionId,
    funcionNombre,
    funcionDescripcion,
    funcionStatus
)
SELECT
    42,
    'GestionarPerfilPropio',
    'Modificar datos, estado y roles de la propia cuenta',
    'ACT'
WHERE NOT EXISTS (
    SELECT 1
    FROM funciones
    WHERE funcionId = 42
       OR funcionNombre = 'GestionarPerfilPropio'
);

UPDATE funciones
SET
    funcionDescripcion = 'Modificar datos, estado y roles de la propia cuenta',
    funcionStatus = 'ACT'
WHERE funcionNombre = 'GestionarPerfilPropio';

INSERT INTO funciones_roles (
    funcionRolId,
    funcionId,
    rolId,
    frStatus,
    frFechaInicio,
    frFechaFin
)
SELECT
    75,
    f.funcionId,
    1,
    'ACT',
    CURRENT_TIMESTAMP,
    '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'GestionarPerfilPropio'
  AND NOT EXISTS (
      SELECT 1
      FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId
        AND fr.rolId = 1
  );

UPDATE funciones_roles fr
INNER JOIN funciones f ON f.funcionId = fr.funcionId
SET
    fr.frStatus = 'ACT',
    fr.frFechaInicio = CURRENT_TIMESTAMP,
    fr.frFechaFin = '2099-12-31 23:59:59'
WHERE f.funcionNombre = 'GestionarPerfilPropio'
  AND fr.rolId = 1;

-- cambios para agregar el Portal de Enfermería: cola de pacientes de hoy
-- Primera fase estrictamente de consulta. El usuario obtiene su identidad
-- clínica desde enfermera.usuario_id y solo puede ver citas pertenecientes a
-- centros activos en enfermera_centro_salud. No se modifican citas ni signos.

INSERT INTO roles (rolId, rolNombre, rolDescripcion, rolStatus)
VALUES (
    5,
    'Enfermería',
    'Acceso a la cola operativa de pacientes de los centros asignados',
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
    'Menu_EnfermeriaPortal'
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
      'Menu_EnfermeriaPortal'
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
