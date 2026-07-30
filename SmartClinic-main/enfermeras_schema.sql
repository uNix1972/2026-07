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
