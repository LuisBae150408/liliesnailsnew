-- =====================================================================
-- LiliesNails · Esquema de base de datos (MySQL 8 / Aiven)
-- =====================================================================
-- Ejecutar completo en una base de datos vacía. Pensado para el
-- servicio MySQL de Aiven, pero funciona en cualquier MySQL 8+.
-- Importar con:
--   mysql --host=HOST --port=PORT -u USER -p --ssl-ca=ca.pem DB < schema.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- usuarios: cuentas del panel de administración
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(100) NOT NULL,
  usuario        VARCHAR(50)  NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_acceso  DATETIME     NULL,
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- sitio_config: datos generales del sitio (clave / valor)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sitio_config (
  clave  VARCHAR(60) PRIMARY KEY,
  valor  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- materiales: inventario usado para calcular el costo de los servicios
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS materiales (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(120) NOT NULL,
  precio         DECIMAL(10,2) NOT NULL DEFAULT 0,
  rinde          INT UNSIGNED NOT NULL DEFAULT 1,
  -- costo_uso se recalcula solo: precio / rinde (columna generada)
  costo_uso      DECIMAL(10,4) GENERATED ALWAYS AS (precio / NULLIF(rinde, 0)) STORED,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_materiales_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- servicios: catálogo de servicios (calculadora interna + catálogo público)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicios (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre              VARCHAR(150) NOT NULL,
  margen_porcentaje   DECIMAL(6,2) NOT NULL DEFAULT 0,   -- % de ganancia deseado
  ganancia_hora       DECIMAL(10,2) NOT NULL DEFAULT 0,  -- $ de mano de obra por hora
  tiempo_minutos      INT UNSIGNED NOT NULL DEFAULT 0,   -- duración estimada
  visible_publico     TINYINT(1) NOT NULL DEFAULT 0,     -- aparece en el catálogo público
  descripcion_publica TEXT NULL,
  imagen              VARCHAR(255) NULL,
  orden               INT NOT NULL DEFAULT 0,
  creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_servicios_nombre (nombre),
  KEY idx_servicios_visible (visible_publico),
  KEY idx_servicios_visible_orden (visible_publico, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- servicio_material: relación N:M entre servicios y materiales
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicio_material (
  servicio_id INT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (servicio_id, material_id),
  CONSTRAINT fk_sm_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
  CONSTRAINT fk_sm_material FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- galeria: fotos de diseños de uñas para el sitio público
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS galeria (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo      VARCHAR(150) NULL,
  imagen      VARCHAR(255) NOT NULL,
  descripcion VARCHAR(255) NULL,
  orden       INT NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- clientas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientas (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(150) NOT NULL,
  telefono  VARCHAR(30) NULL,
  notas     TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_clientas_nombre (nombre),
  KEY idx_clientas_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- citas: agenda del negocio
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS citas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clienta_id     INT UNSIGNED NOT NULL,
  fecha          DATE NOT NULL,
  hora_inicio    TIME NOT NULL,
  hora_fin       TIME NOT NULL,
  estado         ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  recordatorio_minutos INT UNSIGNED NULL,
  notificacion_id VARCHAR(64) NULL,
  notas          TEXT NULL,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_citas_clienta  FOREIGN KEY (clienta_id)  REFERENCES clientas(id)  ON DELETE CASCADE,
  KEY idx_citas_fecha (fecha),
  KEY idx_citas_estado_fecha (estado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- cita_servicio: relación N:M — una cita puede tener varios servicios
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cita_servicio (
  cita_id     INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (cita_id, servicio_id),
  CONSTRAINT fk_cs_cita     FOREIGN KEY (cita_id)     REFERENCES citas(id)     ON DELETE CASCADE,
  CONSTRAINT fk_cs_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------------
-- Configuración inicial sugerida (editable desde el panel más adelante)
-- ---------------------------------------------------------------------
INSERT INTO sitio_config (clave, valor) VALUES
  ('nombre_negocio',   'LiliesNails'),
  ('telefono_whatsapp', ''),
  ('direccion',         ''),
  ('horario',           'Lunes a Sábado, 9:00 am - 7:00 pm'),
  ('instagram',         ''),
  ('texto_bienvenida',  'Diseños de uñas hechos con dedicación, para que tus manos siempre luzcan perfectas.')
ON DUPLICATE KEY UPDATE clave = clave;

-- ---------------------------------------------------------------------
-- Usuario administrador inicial
-- Usuario: admin   Contraseña: CambiaEstaClave123
-- ¡IMPORTANTE! Cambia esta contraseña apenas entres al panel por
-- primera vez (o genera tu propio hash con password_hash() en PHP y
-- reemplaza el valor de abajo antes de importar).
-- ---------------------------------------------------------------------
INSERT INTO usuarios (nombre, usuario, password_hash) VALUES
  ('Administradora', 'admin', '$2b$10$yQeZnorpe5i5AN6AEslw/eCtutYfu5w/16zy0s2sIy0x95x6u5Nv6')
ON DUPLICATE KEY UPDATE usuario = usuario;
-- (el hash de arriba corresponde a "CambiaEstaClave123", verificado con password_verify())
