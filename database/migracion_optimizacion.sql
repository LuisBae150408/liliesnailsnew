-- =====================================================================
-- Migración: optimización y limpieza (refactor de rendimiento)
-- -----------------------------------------------------------------
-- Corre esto UNA vez en la pestaña "SQL" de phpMyAdmin, en InfinityFree.
-- =====================================================================

-- 1) Índice que faltaba: acelera el catálogo público (filtra por
--    visible_publico y ordena por "orden" en la misma consulta).
ALTER TABLE servicios
  ADD INDEX idx_servicios_visible_orden (visible_publico, orden);

-- 2) Las 3 fotos de servicios que aún pesaban varios MB (subidas antes
--    de que existiera la compresión automática) ya vienen reemplazadas
--    en este entregable por versiones comprimidas a WebP — mismo
--    contenido, mucho más livianas. Esto solo actualiza el nombre de
--    archivo guardado en la base de datos para que apunte a las nuevas.
--    Si alguno de estos "id" ya no existe o ya tiene otra foto propia
--    (la reemplazaste manualmente), esa línea simplemente no cambia nada.
UPDATE servicios SET imagen = 'servicio_1_dfdd996b790a4.webp' WHERE id = 1 AND imagen = 'servicio_1_6a7c76e0afa33.jpg';
UPDATE servicios SET imagen = 'servicio_2_cbfbf97023484.webp' WHERE id = 2 AND imagen = 'servicio_2_6a7c028161e84.jpg';
UPDATE servicios SET imagen = 'servicio_3_16430fff28074.webp' WHERE id = 3 AND imagen = 'servicio_3_6a838798c583d.png';
