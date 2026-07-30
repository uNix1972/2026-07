-- Adds the timestamp used when a doctor starts a consultation.
-- Safe to run repeatedly against existing SmartClinic databases.
SET @doctor_start_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cita'
      AND COLUMN_NAME = 'hora_inicio_atencion'
);
SET @doctor_start_sql = IF(
    @doctor_start_column_exists = 0,
    'ALTER TABLE cita ADD COLUMN hora_inicio_atencion DATETIME NULL AFTER fecha_hora',
    'SET @doctor_start_noop = 1'
);
PREPARE doctor_start_statement FROM @doctor_start_sql;
EXECUTE doctor_start_statement;
DEALLOCATE PREPARE doctor_start_statement;
