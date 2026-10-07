-- =====================================================================
-- Migración: varios servicios por cita
-- -----------------------------------------------------------------
-- Corre esto UNA vez en la pestaña SQL de phpMyAdmin.
--
-- Antes, una cita solo podía tener UN servicio (columna citas.servicio_id).
-- Esta migración agrega una tabla nueva (cita_servicio) que permite
-- varios, y copia ahí lo que ya tenías guardado en citas.servicio_id
-- para que no pierdas nada.
--
-- OJO con el orden: sube primero los archivos PHP de esta entrega y
-- DESPUÉS corre esta migración. Si la corres antes, no pasa nada malo
-- de inmediato (la columna vieja "servicio_id" se queda intacta), pero
-- las citas nuevas que agregues mientras tanto no van a tener servicios
-- hasta que subas los archivos.
-- =====================================================================

CREATE TABLE IF NOT EXISTS cita_servicio (
  cita_id     INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (cita_id, servicio_id),
  CONSTRAINT fk_cs_cita     FOREIGN KEY (cita_id)     REFERENCES citas(id)     ON DELETE CASCADE,
  CONSTRAINT fk_cs_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copia lo que ya tenías en citas.servicio_id hacia la tabla nueva
-- (INSERT IGNORE: si corres esto dos veces por error, no duplica nada)
INSERT IGNORE INTO cita_servicio (cita_id, servicio_id)
SELECT id, servicio_id FROM citas WHERE servicio_id IS NOT NULL;

-- La columna vieja "citas.servicio_id" se deja tal cual, sin usarla ni
-- borrarla — así no hay ningún riesgo de romper nada. Si en unas
-- semanas todo funciona bien y quieres una base de datos más
-- ordenada, avísame y te paso la migración para quitarla del todo.

-- Índice que ayuda a la revisión de "citas vencidas -> completada"
-- (se corre cada vez que abres la agenda) a no tener que revisar
-- todas las citas de la tabla.
SET @existe_idx := (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_schema = DATABASE() AND table_name = 'citas' AND index_name = 'idx_citas_estado_fecha'
);
SET @sql_idx := IF(@existe_idx = 0,
  'ALTER TABLE citas ADD INDEX idx_citas_estado_fecha (estado, fecha)',
  'SELECT "El índice ya existía, no se tocó nada" AS resultado'
);
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

