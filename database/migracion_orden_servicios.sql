-- =====================================================================
-- Migración: orden de los servicios en el catálogo público
-- -----------------------------------------------------------------
-- Corre esto UNA vez en la pestaña SQL de phpMyAdmin.
-- =====================================================================

ALTER TABLE servicios
  ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 0 AFTER imagen;

-- Le da a los servicios que ya existen un orden inicial según su id,
-- para que no todos queden empatados en 0 (y no se reordenen solos
-- la primera vez que alguien use las flechitas).
SET @n = 0;
UPDATE servicios SET orden = (@n := @n + 1) ORDER BY id ASC;
