-- Respaldo de base de datos: control_ganado_lm
-- Fecha: 2026-11-21 04:21:26

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Estructura de tabla `ganado`
DROP TABLE IF EXISTS `ganado`;
CREATE TABLE `ganado` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(40) NOT NULL DEFAULT 'Ternero/a',
  `sexo` enum('Macho','Hembra') NOT NULL DEFAULT 'Macho',
  `origen` enum('Compra','Nacimiento','Repasto') NOT NULL DEFAULT 'Compra',
  `peso_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_compra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fecha_ingreso` date NOT NULL,
  `estado` enum('Activo','Repasto','Vendido','Baja') NOT NULL DEFAULT 'Activo',
  `observacion` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ganado_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estructura de tabla `medicacion_comidas`
DROP TABLE IF EXISTS `medicacion_comidas`;
CREATE TABLE `medicacion_comidas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_registro` enum('Medicacion','Comida') NOT NULL DEFAULT 'Medicacion',
  `ganado_id` int(10) unsigned DEFAULT NULL,
  `producto` varchar(120) NOT NULL,
  `frecuencia` enum('Unica','Semanal','Mensual','Trimestral','Anual') NOT NULL DEFAULT 'Unica',
  `fecha_aplicacion` date NOT NULL,
  `proxima_fecha` date DEFAULT NULL,
  `costo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacion` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mc_tipo` (`tipo_registro`),
  KEY `idx_mc_fecha` (`fecha_aplicacion`),
  KEY `fk_mc_ganado` (`ganado_id`),
  CONSTRAINT `fk_mc_ganado` FOREIGN KEY (`ganado_id`) REFERENCES `ganado` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estructura de tabla `presupuesto_movimientos`
DROP TABLE IF EXISTS `presupuesto_movimientos`;
CREATE TABLE `presupuesto_movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_fondo` enum('Normal','Prestamo') NOT NULL DEFAULT 'Normal',
  `tipo_movimiento` enum('Ingreso','Gasto') NOT NULL DEFAULT 'Gasto',
  `monto` decimal(12,2) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `ganado_id` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_presupuesto_fondo` (`tipo_fondo`),
  KEY `idx_presupuesto_fecha` (`fecha_movimiento`),
  KEY `fk_presupuesto_ganado` (`ganado_id`),
  CONSTRAINT `fk_presupuesto_ganado` FOREIGN KEY (`ganado_id`) REFERENCES `ganado` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de tabla `presupuesto_movimientos`
INSERT INTO `presupuesto_movimientos` VALUES (1, 'Prestamo', 'Ingreso', '250000.00', '2026-11-18', NULL, 'Agregado a Fondo de préstamo', '2026-11-20 19:27:30');

-- Estructura de tabla `usuarios`
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'Administrador',
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de tabla `usuarios`
INSERT INTO `usuarios` VALUES (1, 'Administrador', 'admin', '12345', 'Administrador', 'Activo', '2026-11-20 18:54:00');
INSERT INTO `usuarios` VALUES (2, 'Walther', 'Walther', '1505', 'Encargado', 'Activo', '2026-11-20 20:07:38');

-- Estructura de tabla `ventas`
DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_venta` enum('Normal','Repasto') NOT NULL DEFAULT 'Normal',
  `ganado_id` int(10) unsigned DEFAULT NULL,
  `comprador` varchar(120) DEFAULT NULL,
  `peso_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_total` decimal(12,2) NOT NULL,
  `fecha_venta` date NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ventas_tipo` (`tipo_venta`),
  KEY `idx_ventas_fecha` (`fecha_venta`),
  KEY `fk_ventas_ganado` (`ganado_id`),
  CONSTRAINT `fk_ventas_ganado` FOREIGN KEY (`ganado_id`) REFERENCES `ganado` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
