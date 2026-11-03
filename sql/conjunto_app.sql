-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Versión del servidor: 10.4.3-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `conjunto_app`
--
CREATE DATABASE IF NOT EXISTS `conjunto_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `conjunto_app`;

-- --------------------------------------------------------

--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `sp_generar_cuotas`;

DELIMITER $$
CREATE PROCEDURE `sp_generar_cuotas` (
    IN `p_periodo` CHAR(7),
    IN `p_presupuesto_base` DECIMAL(12,2),
    IN `p_fecha_vencimiento` DATE
)
BEGIN
    INSERT INTO cuotas (apartamento_id, periodo, valor, fecha_vencimiento, saldo, estado)
    SELECT 
        a.id,
        p_periodo,
        ROUND(p_presupuesto_base * a.coeficiente, 2),
        p_fecha_vencimiento,
        ROUND(p_presupuesto_base * a.coeficiente, 2),
        'Pendiente'
    FROM apartamentos a
    WHERE NOT EXISTS (
        SELECT 1 FROM cuotas c 
        WHERE c.apartamento_id = a.id AND c.periodo = p_periodo
    );
END$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apartamentos`
--
DROP TABLE IF EXISTS `apartamentos`;
CREATE TABLE `apartamentos` (
  `id` int(11) NOT NULL,
  `torre_id` int(11) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `piso` int(11) NOT NULL,
  `area_m2` decimal(8,2) NOT NULL,
  `coeficiente` decimal(8,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autorizaciones`
--
DROP TABLE IF EXISTS `autorizaciones`;
CREATE TABLE `autorizaciones` (
  `id` int(11) NOT NULL,
  `visitante_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_visita` date NOT NULL,
  `con_vehiculo` tinyint(1) NOT NULL DEFAULT 0,
  `placa` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_pqrs`
--
DROP TABLE IF EXISTS `categorias_pqrs`;
CREATE TABLE `categorias_pqrs` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--
DROP TABLE IF EXISTS `comunicados`;
CREATE TABLE `comunicados` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `vigente_hasta` date NOT NULL,
  `autor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados_leidos`
--
DROP TABLE IF EXISTS `comunicados_leidos`;
CREATE TABLE `comunicados_leidos` (
  `comunicado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuotas`
--
DROP TABLE IF EXISTS `cuotas`;
CREATE TABLE `cuotas` (
  `id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `periodo` char(7) NOT NULL COMMENT 'Formato AAAA-MM',
  `valor` decimal(12,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `saldo` decimal(12,2) NOT NULL,
  `estado` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--
DROP TABLE IF EXISTS `ingresos`;
CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL,
  `visitante_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `portero_id` int(11) NOT NULL,
  `parqueadero_id` int(11) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_salida` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--
DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `cuota_id` int(11) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `medio` varchar(50) NOT NULL,
  `soporte` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `pagos`
--
DROP TRIGGER IF EXISTS `tr_actualizar_saldo_pago`;
DELIMITER $$
CREATE TRIGGER `tr_actualizar_saldo_pago` AFTER INSERT ON `pagos` FOR EACH ROW
BEGIN
    UPDATE cuotas
    SET 
        saldo = GREATEST(0, saldo - NEW.valor),
        estado = CASE 
            WHEN (saldo - NEW.valor) <= 0 THEN 'Pagada'
            ELSE 'Abonada'
        END
    WHERE id = NEW.cuota_id;
END$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parqueaderos_visitantes`
--
DROP TABLE IF EXISTS `parqueaderos_visitantes`;
CREATE TABLE `parqueaderos_visitantes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `ocupado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs`
--
DROP TABLE IF EXISTS `pqrs`;
CREATE TABLE `pqrs` (
  `id` int(11) NOT NULL,
  `radicado` varchar(30) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `adjunto` varchar(255) DEFAULT NULL,
  `estado` enum('Radicada','En trámite','Respondida','Cerrada') NOT NULL DEFAULT 'Radicada',
  `fecha_limite` date NOT NULL,
  `calificacion` tinyint(4) DEFAULT NULL CHECK (`calificacion` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs_seguimiento`
--
DROP TABLE IF EXISTS `pqrs_seguimiento`;
CREATE TABLE `pqrs_seguimiento` (
  `id` int(11) NOT NULL,
  `pqrs_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--
DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` varchar(30) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residencias`
--
DROP TABLE IF EXISTS `residencias`;
CREATE TABLE `residencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `tipo` enum('propietario','arrendatario') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--
INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Residente'),
(3, 'Portero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torres`
--
DROP TABLE IF EXISTS `torres`;
CREATE TABLE `torres` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `pisos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento` varchar(30) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--
INSERT INTO `usuarios` (`id`, `rol_id`, `nombres`, `apellidos`, `documento`, `correo`, `telefono`, `password_hash`, `activo`) VALUES
(5, 2, 'Manolo', 'Prueba', '987654321', 'prueba@gmail.com', '+573005960608', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitantes`
--
DROP TABLE IF EXISTS `visitantes`;
CREATE TABLE `visitantes` (
  `id` int(11) NOT NULL,
  `documento` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_comunes`
--
DROP TABLE IF EXISTS `zonas_comunes`;
CREATE TABLE `zonas_comunes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `aforo` int(11) NOT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `costo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `requiere_deposito` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estado_cuenta`
--
DROP TABLE IF EXISTS `v_estado_cuenta`;
DROP VIEW IF EXISTS `v_estado_cuenta`;

CREATE VIEW `v_estado_cuenta` AS 
SELECT 
    `a`.`id` AS `apartamento_id`, 
    `t`.`nombre` AS `torre`, 
    `a`.`numero` AS `apartamento`, 
    COUNT(CASE WHEN `c`.`saldo` > 0 THEN 1 END) AS `cuotas_pendientes`, 
    COUNT(CASE WHEN `c`.`saldo` > 0 AND `c`.`fecha_vencimiento` < CURDATE() THEN 1 END) AS `cuotas_en_mora`, 
    COALESCE(SUM(`c`.`saldo`), 0.00) AS `saldo_total` 
FROM `apartamentos` `a` 
JOIN `torres` `t` ON `a`.`torre_id` = `t`.`id` 
LEFT JOIN `cuotas` `c` ON `a`.`id` = `c`.`apartamento_id` 
GROUP BY `a`.`id`, `t`.`nombre`, `a`.`numero`;

-- --------------------------------------------------------

--
-- Índices para tablas volcadas
--

ALTER TABLE `apartamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_torre_numero` (`torre_id`,`numero`);

ALTER TABLE `autorizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_autorizaciones_visitantes` (`visitante_id`),
  ADD KEY `fk_autorizaciones_apartamentos` (`apartamento_id`),
  ADD KEY `fk_autorizaciones_usuarios` (`usuario_id`);

ALTER TABLE `categorias_pqrs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comunicados_usuarios` (`autor_id`);

ALTER TABLE `comunicados_leidos`
  ADD PRIMARY KEY (`comunicado_id`,`usuario_id`),
  ADD KEY `fk_leidos_usuarios` (`usuario_id`);

ALTER TABLE `cuotas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_apartamento_periodo` (`apartamento_id`,`periodo`);

ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ingresos_visitantes` (`visitante_id`),
  ADD KEY `fk_ingresos_apartamentos` (`apartamento_id`),
  ADD KEY `fk_ingresos_portero` (`portero_id`),
  ADD KEY `fk_ingresos_parqueaderos` (`parqueadero_id`);

ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagos_cuotas` (`cuota_id`),
  ADD KEY `fk_pagos_usuarios` (`registrado_por`);

ALTER TABLE `parqueaderos_visitantes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pqrs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `radicado` (`radicado`),
  ADD KEY `fk_pqrs_categorias` (`categoria_id`),
  ADD KEY `fk_pqrs_usuarios` (`usuario_id`),
  ADD KEY `fk_pqrs_apartamentos` (`apartamento_id`);

ALTER TABLE `pqrs_seguimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seguimiento_pqrs` (`pqrs_id`),
  ADD KEY `fk_seguimiento_usuarios` (`usuario_id`);

ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservas_zonas` (`zona_id`),
  ADD KEY `fk_reservas_apartamentos` (`apartamento_id`),
  ADD KEY `fk_reservas_usuarios` (`usuario_id`);

ALTER TABLE `residencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_residencias_usuarios` (`usuario_id`),
  ADD KEY `fk_residencias_apartamentos` (`apartamento_id`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `torres`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuarios_roles` (`rol_id`);

ALTER TABLE `visitantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

ALTER TABLE `zonas_comunes`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT de las tablas volcadas
--

ALTER TABLE `apartamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `autorizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `categorias_pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cuotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `parqueaderos_visitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pqrs_seguimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `residencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `torres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `visitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `zonas_comunes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Restricciones para tablas volcadas
--

ALTER TABLE `apartamentos`
  ADD CONSTRAINT `fk_apartamentos_torres` FOREIGN KEY (`torre_id`) REFERENCES `torres` (`id`);

ALTER TABLE `autorizaciones`
  ADD CONSTRAINT `fk_autorizaciones_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_autorizaciones_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_autorizaciones_visitantes` FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`);

ALTER TABLE `comunicados`
  ADD CONSTRAINT `fk_comunicados_usuarios` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `comunicados_leidos`
  ADD CONSTRAINT `fk_leidos_comunicados` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicados` (`id`),
  ADD CONSTRAINT `fk_leidos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `cuotas`
  ADD CONSTRAINT `fk_cuotas_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`);

ALTER TABLE `ingresos`
  ADD CONSTRAINT `fk_ingresos_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_ingresos_parqueaderos` FOREIGN KEY (`parqueadero_id`) REFERENCES `parqueaderos_visitantes` (`id`),
  ADD CONSTRAINT `fk_ingresos_portero` FOREIGN KEY (`portero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ingresos_visitantes` FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`);

ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_cuotas` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas` (`id`),
  ADD CONSTRAINT `fk_pagos_usuarios` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

ALTER TABLE `pqrs`
  ADD CONSTRAINT `fk_pqrs_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_pqrs_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_pqrs` (`id`),
  ADD CONSTRAINT `fk_pqrs_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `pqrs_seguimiento`
  ADD CONSTRAINT `fk_seguimiento_pqrs` FOREIGN KEY (`pqrs_id`) REFERENCES `pqrs` (`id`),
  ADD CONSTRAINT `fk_seguimiento_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_reservas_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_reservas_zonas` FOREIGN KEY (`zona_id`) REFERENCES `zonas_comunes` (`id`);

ALTER TABLE `residencias`
  ADD CONSTRAINT `fk_residencias_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_residencias_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;