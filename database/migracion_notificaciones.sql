-- =====================================================================
-- Migración: recordatorios de citas
-- -----------------------------------------------------------------
-- Tu base de datos ya existe con datos reales, así que esto se corre
-- UNA sola vez en phpMyAdmin (pestaña "SQL" de tu base de datos en
-- InfinityFree), y no se debe volver a correr.
-- =====================================================================

ALTER TABLE citas
  ADD COLUMN recordatorio_minutos INT UNSIGNED NULL AFTER estado,
  ADD COLUMN notificacion_id VARCHAR(64) NULL AFTER recordatorio_minutos;
