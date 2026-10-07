-- =====================================================================
-- Migración de limpieza: quitar citas.servicio_id
-- -----------------------------------------------------------------
-- Corre esto SOLO si ya subiste los archivos de "multi-servicio" hace
-- rato y todo ha funcionado bien desde entonces. Esta columna ya no la
-- usa ningún archivo del código — la reemplazó la tabla cita_servicio.
--
-- Antes de correr esto: sube primero los archivos de esta entrega
-- (no tocan esta columna para nada, así que el orden aquí no importa
-- tanto como en la migración anterior).
-- =====================================================================

-- Por si el nombre exacto de la llave foránea cambió en algún momento,
-- esto la busca antes de intentar borrarla (evita un error si ya no existe).
SET @fk_existe := (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE table_schema = DATABASE() AND table_name = 'citas'
  AND constraint_name = 'fk_citas_servicio' AND constraint_type = 'FOREIGN KEY'
);
SET @sql_fk := IF(@fk_existe > 0,
  'ALTER TABLE citas DROP FOREIGN KEY fk_citas_servicio',
  'SELECT "La llave foránea ya no existía, no se tocó nada" AS resultado'
);
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- Y ahora la columna en sí
SET @col_existe := (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_schema = DATABASE() AND table_name = 'citas' AND column_name = 'servicio_id'
);
SET @sql_col := IF(@col_existe > 0,
  'ALTER TABLE citas DROP COLUMN servicio_id',
  'SELECT "La columna ya no existía, no se tocó nada" AS resultado'
);
PREPARE stmt_col FROM @sql_col;
EXECUTE stmt_col;
DEALLOCATE PREPARE stmt_col;
