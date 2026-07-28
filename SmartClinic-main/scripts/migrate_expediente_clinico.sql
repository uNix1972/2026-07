-- Migración idempotente para instalaciones existentes de SmartClinic.
USE smartclinic_db;
SET NAMES utf8mb4;

INSERT INTO funciones (funcionNombre, funcionDescripcion, funcionStatus)
SELECT
    'Controllers\\DoctoresController',
    'Portal de doctores, sala de espera e historial clínico',
    'ACT'
WHERE NOT EXISTS (
    SELECT 1
    FROM funciones
    WHERE funcionNombre = 'Controllers\\DoctoresController'
);

INSERT INTO funciones (funcionNombre, funcionDescripcion, funcionStatus)
SELECT
    'Controllers\\PacientePortalController',
    'Portal de autoservicio del paciente',
    'ACT'
WHERE NOT EXISTS (
    SELECT 1
    FROM funciones
    WHERE funcionNombre = 'Controllers\\PacientePortalController'
);

INSERT INTO funciones (funcionNombre, funcionDescripcion, funcionStatus)
SELECT 'Menu_Doctor', 'Acceso al menú Portal Doctor', 'ACT'
WHERE NOT EXISTS (
    SELECT 1 FROM funciones WHERE funcionNombre = 'Menu_Doctor'
);

INSERT INTO funciones (funcionNombre, funcionDescripcion, funcionStatus)
SELECT 'Menu_PacientePortal', 'Acceso al menú Portal Paciente', 'ACT'
WHERE NOT EXISTS (
    SELECT 1 FROM funciones WHERE funcionNombre = 'Menu_PacientePortal'
);

INSERT INTO funciones_roles
    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT
    f.funcionId,
    3,
    'ACT',
    CURRENT_TIMESTAMP,
    '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'Controllers\\DoctoresController'
  AND NOT EXISTS (
      SELECT 1
      FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId
        AND fr.rolId = 3
  );

INSERT INTO funciones_roles
    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT
    f.funcionId,
    4,
    'ACT',
    CURRENT_TIMESTAMP,
    '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'Controllers\\PacientePortalController'
  AND NOT EXISTS (
      SELECT 1
      FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId
        AND fr.rolId = 4
  );

INSERT INTO funciones_roles
    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT f.funcionId, 3, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'Menu_Doctor'
  AND NOT EXISTS (
      SELECT 1 FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId AND fr.rolId = 3
  );

INSERT INTO funciones_roles
    (funcionId, rolId, frStatus, frFechaInicio, frFechaFin)
SELECT f.funcionId, 4, 'ACT', CURRENT_TIMESTAMP, '2099-12-31 23:59:59'
FROM funciones f
WHERE f.funcionNombre = 'Menu_PacientePortal'
  AND NOT EXISTS (
      SELECT 1 FROM funciones_roles fr
      WHERE fr.funcionId = f.funcionId AND fr.rolId = 4
  );

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
    CONSTRAINT fk_signos_cita
        FOREIGN KEY (cita_id) REFERENCES cita(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
