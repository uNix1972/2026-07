-- Añade la referencia usada para deduplicar alertas de inventario.
-- Es seguro ejecutar este script más de una vez en MariaDB.

ALTER TABLE notificaciones
    ADD COLUMN IF NOT EXISTS referencia VARCHAR(120) NULL
    AFTER fecha_creacion;

CREATE INDEX IF NOT EXISTS idx_notificaciones_referencia_leida
    ON notificaciones (referencia, leida);
