-- Datos actuales exportados desde la app original (respaldo_nails.json)
-- Se reasignan IDs secuenciales (los IDs originales eran timestamps de JS)

INSERT INTO materiales (id, nombre, precio, rinde) VALUES
(1, 'Alcohol', 7, 25),
(2, 'Base coat', 3, 25),
(3, 'Brillo color', 2, 20),
(4, 'Esmalte', 2, 25),
(5, 'Top coat', 6, 20),
(6, 'Celulosas', 4, 100),
(7, 'Uñas Postizas', 7, 15),
(8, 'Primer', 4, 100),
(9, 'Builder Gel', 5, 25),
(10, 'Lima', 1, 30),
(11, 'Efecto cromado', 2, 50),
(12, 'Deco gel', 5, 60),
(13, '6 en 1 Tiffany', 5, 20),
(14, 'Espuma limpiadora', 6, 40),
(15, 'Removedor', 3, 5);

INSERT INTO servicios (id, nombre, margen_porcentaje, ganancia_hora, tiempo_minutos, visible_publico) VALUES
(1, 'Soft gel', 50, 3, 150, 1),
(2, 'Nivelación básica', 50, 3, 150, 1);

INSERT INTO servicio_material (servicio_id, material_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 10),
(1, 13),
(2, 1),
(2, 2),
(2, 4),
(2, 5),
(2, 6),
(2, 8),
(2, 9),
(2, 10),
(2, 14);
