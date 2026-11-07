-- =====================================================================
-- Base de datos: servicio_tecnico
-- Sistema de servicio técnico para carros, motos y scooters eléctricos
--
-- NOTA: No es obligatorio importar este archivo. El sistema crea la base
-- de datos y las tablas automáticamente la primera vez que se abre.
-- Se incluye por si prefieres crearla a mano desde phpMyAdmin.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `servicio_tecnico`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `servicio_tecnico`;

-- Cada fila es un caso / ingreso de un vehículo al taller
CREATE TABLE IF NOT EXISTS `servicios` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_nombre`       VARCHAR(120) NOT NULL,
  `cliente_documento`    VARCHAR(30)  NULL,
  `cliente_telefono`     VARCHAR(30)  NOT NULL,
  `cliente_email`        VARCHAR(120) NULL,
  `tipo_vehiculo`        VARCHAR(20)  NOT NULL,          -- carro, moto, scooter, bicicleta, otro
  `marca`                VARCHAR(60)  NULL,
  `modelo`               VARCHAR(60)  NULL,
  `placa_serial`         VARCHAR(60)  NULL,
  `color`                VARCHAR(30)  NULL,
  `kilometraje`          VARCHAR(20)  NULL,
  `nivel_bateria`        VARCHAR(20)  NULL,
  `motivo`               VARCHAR(30)  NOT NULL,          -- mantenimiento, reparacion, revision, garantia, otro
  `descripcion_problema` TEXT         NOT NULL,
  `accesorios`           TEXT         NULL,
  `prioridad`            TINYINT      NOT NULL DEFAULT 3, -- 1 urgente, 2 alta, 3 normal, 4 baja
  `estado`               VARCHAR(30)  NOT NULL DEFAULT 'recibido',
                         -- pendientes: recibido, diagnostico, en_reparacion, esperando_repuestos
                         -- cerrados:   terminado, entregado, cancelado
  `tecnico`              VARCHAR(80)  NULL,
  `fecha_ingreso`        DATETIME     NOT NULL,
  `fecha_estimada`       DATE         NULL,
  `fecha_terminado`      DATETIME     NULL,
  `fecha_entrega`        DATETIME     NULL,
  `diagnostico`          TEXT         NULL,
  `trabajo_realizado`    TEXT         NULL,
  `recomendaciones`      TEXT         NULL,
  `garantia_dias`        INT          NOT NULL DEFAULT 0,
  INDEX `idx_servicios_estado` (`estado`),
  INDEX `idx_servicios_placa` (`placa_serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repuestos y mano de obra de cada servicio (lo que sale en la orden)
CREATE TABLE IF NOT EXISTS `servicio_items` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `servicio_id`     INT UNSIGNED NOT NULL,
  `tipo`            VARCHAR(20)  NOT NULL,                -- mano_obra, repuesto
  `descripcion`     VARCHAR(200) NOT NULL,
  `cantidad`        DECIMAL(10,2) NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_items_servicio` FOREIGN KEY (`servicio_id`)
    REFERENCES `servicios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
