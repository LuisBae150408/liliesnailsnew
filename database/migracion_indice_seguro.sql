-- Reemplaza SOLO el bloque del índice (el resto de migracion_optimizacion.sql
-- —las 3 líneas UPDATE de las fotos— se corre igual, sin cambios).
--
-- Esta versión revisa primero si el índice ya existe antes de crearlo,
-- así que es segura de correr aunque ya lo hayas intentado antes.

SET @existe := (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_schema = DATABASE()
    AND table_name   = 'servicios'
    AND index_name    = 'idx_servicios_visible_orden'
);

SET @sql := IF(@existe = 0,
  'ALTER TABLE servicios ADD INDEX idx_servicios_visible_orden (visible_publico, orden)',
  'SELECT "El índice ya existía, no se tocó nada" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
