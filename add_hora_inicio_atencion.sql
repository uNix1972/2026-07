-- Agrega la columna que guarda cuándo inició realmente cada consulta
-- (usada por el timer de "Iniciar consulta" en el portal del doctor).
-- Seguro de correr más de una vez: revisa si la columna ya existe antes
-- de agregarla (compatible con versiones de MySQL/MariaDB que no
-- soportan "ADD COLUMN IF NOT EXISTS").

SET @dbname = DATABASE();
SET @tablename = 'cita';
SET @columnname = 'hora_inicio_atencion';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE cita ADD COLUMN hora_inicio_atencion DATETIME NULL AFTER fecha_hora'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
