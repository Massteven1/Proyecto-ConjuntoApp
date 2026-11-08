-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 a las 16:07:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `conjunto_app`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE PROCEDURE `sp_generar_cuotas` (IN `p_periodo` CHAR(7), IN `p_presupuesto_base` DECIMAL(12,2), IN `p_fecha_vencimiento` DATE)   BEGIN
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
        WHERE c.apartamento_id = a.id AND c.periodo = p_periodo COLLATE utf8mb4_unicode_ci
    );
    SELECT ROW_COUNT() AS generadas;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apartamentos`
--

CREATE TABLE `apartamentos` (
  `id` int(11) NOT NULL,
  `torre_id` int(11) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `piso` int(11) NOT NULL,
  `area_m2` decimal(8,2) NOT NULL,
  `coeficiente` decimal(8,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `apartamentos`
--

INSERT INTO `apartamentos` (`id`, `torre_id`, `numero`, `piso`, `area_m2`, `coeficiente`) VALUES
(1, 1, '101', 1, 85.50, 0.0200),
(2, 1, '102', 1, 85.50, 0.0200),
(3, 2, '201', 2, 92.00, 0.0215),
(4, 2, '202', 2, 92.00, 0.0215),
(5, 3, '301', 3, 78.00, 0.0182),
(6, 3, '302', 3, 78.00, 0.0182),
(7, 4, '401', 4, 110.00, 0.0257),
(8, 4, '402', 4, 110.00, 0.0257),
(9, 1, '501', 5, 65.00, 0.0152),
(10, 1, '502', 5, 65.00, 0.0152),
(11, 2, '601', 6, 120.00, 0.0280),
(12, 2, '602', 6, 120.00, 0.0280),
(13, 3, '701', 7, 95.00, 0.0222),
(14, 3, '702', 7, 95.00, 0.0222),
(15, 4, '801', 8, 88.00, 0.0206),
(16, 1, '103', 1, 86.20, 0.0080),
(17, 1, '104', 1, 86.90, 0.0080),
(18, 1, '105', 1, 87.60, 0.0080),
(19, 1, '106', 1, 88.30, 0.0080),
(20, 1, '107', 1, 89.00, 0.0080),
(21, 1, '108', 1, 89.70, 0.0080),
(22, 2, '101', 1, 90.40, 0.0080),
(23, 2, '102', 1, 91.10, 0.0080),
(24, 2, '103', 1, 91.80, 0.0080),
(25, 2, '104', 1, 92.50, 0.0080),
(26, 2, '105', 1, 93.20, 0.0080),
(27, 2, '106', 1, 93.90, 0.0080),
(28, 3, '101', 1, 94.60, 0.0080),
(29, 3, '102', 1, 95.30, 0.0080),
(30, 3, '103', 1, 96.00, 0.0080),
(31, 3, '104', 1, 96.70, 0.0080),
(32, 3, '105', 1, 97.40, 0.0080),
(33, 3, '106', 1, 98.10, 0.0080),
(34, 4, '101', 1, 98.80, 0.0080),
(35, 4, '102', 1, 99.50, 0.0080),
(36, 4, '103', 1, 100.20, 0.0080),
(37, 4, '104', 1, 100.90, 0.0080),
(38, 4, '105', 1, 101.60, 0.0080),
(39, 4, '106', 1, 102.30, 0.0080),
(40, 4, '107', 1, 103.00, 0.0080);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autorizaciones`
--

CREATE TABLE `autorizaciones` (
  `id` int(11) NOT NULL,
  `visitante_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_visita` date NOT NULL,
  `con_vehiculo` tinyint(1) NOT NULL DEFAULT 0,
  `placa` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `autorizaciones`
--

INSERT INTO `autorizaciones` (`id`, `visitante_id`, `apartamento_id`, `usuario_id`, `fecha_visita`, `con_vehiculo`, `placa`) VALUES
(1, 1, 1, 5, '2025-11-01', 0, NULL),
(2, 2, 2, 6, '2025-11-02', 1, 'ABC123'),
(3, 3, 3, 7, '2025-11-03', 0, NULL),
(4, 4, 4, 8, '2025-11-04', 1, 'XYZ789'),
(5, 5, 5, 9, '2025-11-05', 0, NULL),
(6, 6, 6, 10, '2025-11-06', 1, 'DEF456'),
(7, 7, 7, 11, '2025-11-07', 0, NULL),
(8, 8, 8, 12, '2025-11-08', 1, 'GHI321'),
(9, 9, 9, 13, '2025-11-09', 0, NULL),
(10, 10, 10, 14, '2025-11-10', 1, 'JKL654'),
(11, 11, 11, 15, '2025-11-11', 0, NULL),
(12, 12, 12, 5, '2025-11-12', 1, 'MNO987'),
(13, 13, 13, 6, '2025-11-13', 0, NULL),
(14, 14, 14, 7, '2025-11-14', 1, 'PQR147'),
(15, 15, 15, 8, '2025-11-15', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_pqrs`
--

CREATE TABLE `categorias_pqrs` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_pqrs`
--

INSERT INTO `categorias_pqrs` (`id`, `nombre`) VALUES
(1, 'Ruido'),
(2, 'Convivencia'),
(3, 'Mascotas'),
(4, 'Parqueadero'),
(5, 'Zonas comunes'),
(6, 'Mantenimiento'),
(7, 'Seguridad'),
(8, 'Aseo'),
(9, 'Facturación'),
(10, 'Portería'),
(11, 'Daños locativos'),
(12, 'Servicios públicos'),
(13, 'Reservas'),
(14, 'Correspondencia'),
(15, 'Otros');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--

CREATE TABLE `comunicados` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `vigente_hasta` date NOT NULL,
  `autor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comunicados`
--

INSERT INTO `comunicados` (`id`, `titulo`, `contenido`, `fecha_publicacion`, `vigente_hasta`, `autor_id`) VALUES
(1, 'Mantenimiento de ascensores', 'Se realizará mantenimiento preventivo en los ascensores de las torres A y B.', '2025-11-01 08:00:00', '2025-11-10', 1),
(2, 'Asamblea general', 'Convocatoria a asamblea general de copropietarios.', '2025-11-02 09:00:00', '2025-11-15', 1),
(3, 'Corte de agua programado', 'Corte de agua el 12 de noviembre de 8am a 12pm.', '2025-11-03 10:00:00', '2025-11-12', 2),
(4, 'Nuevo horario piscina', 'La piscina ajusta su horario a 9am - 7pm.', '2025-11-04 11:00:00', '2025-12-04', 1),
(5, 'Jornada de vacunación', 'Jornada de vacunación para mascotas en zona común.', '2025-11-05 12:00:00', '2025-11-20', 2),
(6, 'Fumigación', 'Fumigación programada en todas las torres.', '2025-11-06 13:00:00', '2025-11-13', 1),
(7, 'Simulacro de evacuación', 'Simulacro de evacuación el 18 de noviembre a las 10am.', '2025-11-07 14:00:00', '2025-11-18', 1),
(8, 'Cambio de administración', 'Se informa cambio en la administración del conjunto.', '2025-11-08 15:00:00', '2025-12-08', 2),
(9, 'Recolección de reciclaje', 'Nuevos días de recolección: martes y viernes.', '2025-11-09 16:00:00', '2025-12-09', 1),
(10, 'Campaña de ahorro', 'Campaña de ahorro de energía y agua.', '2025-11-10 17:00:00', '2025-12-10', 2),
(11, 'Pintura de fachadas', 'Inicio de pintura de fachadas de las torres C y D.', '2025-11-11 18:00:00', '2025-12-11', 1),
(12, 'Reunión Consejo', 'Reunión del consejo de administración el 22 de noviembre.', '2025-11-12 19:00:00', '2025-11-22', 2),
(13, 'Fiesta de fin de año', 'Organización de la fiesta de fin de año en el salón social.', '2025-11-13 20:00:00', '2025-12-31', 1),
(14, 'Nuevo reglamento', 'Actualización del reglamento de zonas comunes.', '2025-11-14 21:00:00', '2025-12-14', 2),
(15, 'Entrega de obsequios', 'Entrega de obsequios navideños para niños del conjunto.', '2025-11-15 22:00:00', '2025-12-20', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados_leidos`
--

CREATE TABLE `comunicados_leidos` (
  `comunicado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comunicados_leidos`
--

INSERT INTO `comunicados_leidos` (`comunicado_id`, `usuario_id`, `fecha`) VALUES
(1, 5, '2025-11-01 09:15:00'),
(1, 6, '2025-11-01 10:00:00'),
(2, 7, '2025-11-02 11:30:00'),
(2, 8, '2025-11-02 12:45:00'),
(3, 9, '2025-11-03 13:00:00'),
(3, 10, '2025-11-03 14:20:00'),
(4, 11, '2025-11-04 15:10:00'),
(5, 12, '2025-11-05 16:00:00'),
(6, 13, '2025-11-06 17:30:00'),
(7, 14, '2025-11-07 18:45:00'),
(8, 15, '2025-11-08 19:00:00'),
(9, 5, '2025-11-09 20:15:00'),
(10, 6, '2025-11-10 21:30:00'),
(11, 7, '2025-11-11 22:00:00'),
(12, 8, '2025-11-12 08:10:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuotas`
--

CREATE TABLE `cuotas` (
  `id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `periodo` char(7) NOT NULL COMMENT 'Formato AAAA-MM',
  `valor` decimal(12,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `saldo` decimal(12,2) NOT NULL,
  `estado` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuotas`
--

INSERT INTO `cuotas` (`id`, `apartamento_id`, `periodo`, `valor`, `fecha_vencimiento`, `saldo`, `estado`) VALUES
(1, 1, '2025-10', 180000.00, '2025-10-15', 0.00, 'Pagada'),
(2, 2, '2025-10', 180000.00, '2025-10-15', 50000.00, 'Abonada'),
(3, 3, '2025-10', 193500.00, '2025-10-15', 193500.00, 'Pendiente'),
(4, 4, '2025-10', 193500.00, '2025-10-15', 0.00, 'Pagada'),
(5, 5, '2025-10', 163800.00, '2025-10-15', 163800.00, 'Pendiente'),
(6, 6, '2025-10', 163800.00, '2025-10-15', 80000.00, 'Abonada'),
(7, 7, '2025-10', 231300.00, '2025-10-15', 0.00, 'Pagada'),
(8, 8, '2025-10', 231300.00, '2025-10-15', 231300.00, 'Pendiente'),
(9, 9, '2025-10', 136800.00, '2025-10-15', 0.00, 'Pagada'),
(10, 10, '2025-10', 136800.00, '2025-10-15', 136800.00, 'Pendiente'),
(11, 11, '2025-10', 252000.00, '2025-10-15', 100000.00, 'Abonada'),
(12, 12, '2025-10', 252000.00, '2025-10-15', 0.00, 'Pagada'),
(13, 13, '2025-10', 199800.00, '2025-10-15', 199800.00, 'Pendiente'),
(14, 14, '2025-10', 199800.00, '2025-10-15', 0.00, 'Pagada'),
(15, 15, '2025-10', 185400.00, '2025-10-15', 185400.00, 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

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

--
-- Volcado de datos para la tabla `ingresos`
--

INSERT INTO `ingresos` (`id`, `visitante_id`, `apartamento_id`, `portero_id`, `parqueadero_id`, `placa`, `fecha_ingreso`, `fecha_salida`) VALUES
(1, 1, 1, 3, NULL, NULL, '2025-11-01 08:15:00', '2025-11-01 12:30:00'),
(2, 2, 2, 3, 2, 'ABC123', '2025-11-02 09:00:00', '2025-11-02 11:45:00'),
(3, 3, 3, 4, NULL, NULL, '2025-11-03 14:20:00', NULL),
(4, 4, 4, 3, 4, 'XYZ789', '2025-11-04 10:10:00', '2025-11-04 13:00:00'),
(5, 5, 5, 4, NULL, NULL, '2025-11-05 16:40:00', NULL),
(6, 6, 6, 3, 6, 'DEF456', '2025-11-06 07:55:00', '2025-11-06 10:30:00'),
(7, 7, 7, 4, NULL, NULL, '2025-11-07 11:25:00', '2025-11-07 15:10:00'),
(8, 8, 8, 3, 8, 'GHI321', '2025-11-08 18:05:00', NULL),
(9, 9, 9, 4, NULL, NULL, '2025-11-09 09:45:00', '2025-11-09 12:00:00'),
(10, 10, 10, 3, 10, 'JKL654', '2025-11-10 13:30:00', NULL),
(11, 11, 11, 4, NULL, NULL, '2025-11-11 08:00:00', '2025-11-11 09:15:00'),
(12, 12, 12, 3, 12, 'MNO987', '2025-11-12 17:20:00', NULL),
(13, 13, 13, 4, NULL, NULL, '2025-11-13 10:50:00', '2025-11-13 14:35:00'),
(14, 14, 14, 3, 14, 'PQR147', '2025-11-14 19:00:00', NULL),
(15, 15, 15, 4, NULL, NULL, '2025-11-15 12:15:00', '2025-11-15 13:45:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

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
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `cuota_id`, `valor`, `fecha_pago`, `medio`, `soporte`, `registrado_por`) VALUES
(1, 1, 180000.00, '2025-10-05', 'Transferencia', 'soporte1.pdf', 1),
(2, 2, 130000.00, '2025-10-06', 'PSE', 'soporte2.pdf', 1),
(3, 4, 193500.00, '2025-10-07', 'Efectivo', NULL, 2),
(4, 6, 83800.00, '2025-10-08', 'Transferencia', 'soporte4.pdf', 1),
(5, 7, 231300.00, '2025-10-09', 'PSE', 'soporte5.pdf', 2),
(6, 9, 136800.00, '2025-10-10', 'Efectivo', NULL, 1),
(7, 11, 152000.00, '2025-10-11', 'Transferencia', 'soporte7.pdf', 2),
(8, 12, 252000.00, '2025-10-12', 'PSE', 'soporte8.pdf', 1),
(9, 14, 199800.00, '2025-10-13', 'Efectivo', NULL, 2),
(10, 1, 0.00, '2025-10-14', 'Transferencia', NULL, 1),
(11, 3, 50000.00, '2025-10-15', 'PSE', 'soporte11.pdf', 2),
(12, 5, 30000.00, '2025-10-16', 'Transferencia', 'soporte12.pdf', 1),
(13, 8, 100000.00, '2025-10-17', 'Efectivo', NULL, 2),
(14, 10, 50000.00, '2025-10-18', 'PSE', 'soporte14.pdf', 1),
(15, 13, 80000.00, '2025-10-19', 'Transferencia', 'soporte15.pdf', 2);

--
-- Disparadores `pagos`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_saldo_pago` AFTER INSERT ON `pagos` FOR EACH ROW BEGIN
    UPDATE cuotas
    SET 
        saldo = GREATEST(0, saldo - NEW.valor),
        estado = CASE 
            WHEN (saldo - NEW.valor) <= 0 THEN 'Pagada'
            ELSE 'Abonada'
        END
    WHERE id = NEW.cuota_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parqueaderos_visitantes`
--

CREATE TABLE `parqueaderos_visitantes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `ocupado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `parqueaderos_visitantes`
--

INSERT INTO `parqueaderos_visitantes` (`id`, `codigo`, `ocupado`) VALUES
(1, 'V-01', 0),
(2, 'V-02', 1),
(3, 'V-03', 0),
(4, 'V-04', 0),
(5, 'V-05', 1),
(6, 'V-06', 0),
(7, 'V-07', 0),
(8, 'V-08', 1),
(9, 'V-09', 0),
(10, 'V-10', 0),
(11, 'V-11', 0),
(12, 'V-12', 1),
(13, 'V-13', 0),
(14, 'V-14', 0),
(15, 'V-15', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs`
--

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

--
-- Volcado de datos para la tabla `pqrs`
--

INSERT INTO `pqrs` (`id`, `radicado`, `tipo`, `categoria_id`, `usuario_id`, `apartamento_id`, `asunto`, `descripcion`, `adjunto`, `estado`, `fecha_limite`, `calificacion`) VALUES
(1, 'PQRS-2025-001', 'Petición', 1, 5, 1, 'Ruido excesivo', 'Ruido en el piso superior después de las 11pm.', 'ruido.pdf', 'Radicada', '2025-11-20', NULL),
(2, 'PQRS-2025-002', 'Queja', 2, 6, 2, 'Conflicto vecinos', 'Discusión en el pasillo del piso 2.', 'evidencia.mp4', 'En trámite', '2025-11-22', NULL),
(3, 'PQRS-2025-003', 'Reclamo', 3, 7, 3, 'Mascotas sueltas', 'Perro sin correa en zona verde.', 'foto1.jpg', 'Respondida', '2025-11-18', 5),
(4, 'PQRS-2025-004', 'Sugerencia', 5, 8, 4, 'Mejorar iluminación', 'Iluminación baja en el parque infantil.', NULL, 'Radicada', '2025-11-25', NULL),
(5, 'PQRS-2025-005', 'Petición', 4, 9, 5, 'Parqueadero asignado', 'Solicito revisión de mi parqueadero.', 'solicitud.pdf', 'En trámite', '2025-11-21', NULL),
(6, 'PQRS-2025-006', 'Queja', 6, 10, 6, 'Fuga de agua', 'Fuga en el baño común de la torre F.', 'fuga.jpg', 'Respondida', '2025-11-15', 4),
(7, 'PQRS-2025-007', 'Reclamo', 7, 11, 7, 'Portón dañado', 'El portón peatonal no cierra.', 'porton.mp4', 'Cerrada', '2025-11-10', 5),
(8, 'PQRS-2025-008', 'Sugerencia', 8, 12, 8, 'Aseo zonas', 'Reforzar aseo en escaleras.', NULL, 'Radicada', '2025-11-28', NULL),
(9, 'PQRS-2025-009', 'Petición', 9, 13, 9, 'Revisión factura', 'Revisar cobro de la cuota de octubre.', 'factura.pdf', 'En trámite', '2025-11-23', NULL),
(10, 'PQRS-2025-010', 'Queja', 10, 14, 10, 'Demora en portería', 'Demoras en la entrega de correspondencia.', NULL, 'Respondida', '2025-11-19', 3),
(11, 'PQRS-2025-011', 'Reclamo', 11, 15, 11, 'Humedad', 'Humedad en pared del pasillo.', 'humedad.jpg', 'Radicada', '2025-11-30', NULL),
(12, 'PQRS-2025-012', 'Petición', 12, 5, 12, 'Servicios', 'Corte de agua programado sin aviso.', NULL, 'En trámite', '2025-11-24', NULL),
(13, 'PQRS-2025-013', 'Sugerencia', 13, 6, 13, 'Reservas online', 'Permitir reservas desde la app.', 'idea.pdf', 'Respondida', '2025-11-17', 4),
(14, 'PQRS-2025-014', 'Queja', 14, 7, 14, 'Correspondencia', 'Paquete reportado como entregado pero no recibido.', NULL, 'Cerrada', '2025-11-12', 4),
(15, 'PQRS-2025-015', 'Reclamo', 15, 8, 15, 'Otro', 'Situación general de convivencia.', 'descripcion.docx', 'Radicada', '2025-11-29', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs_seguimiento`
--

CREATE TABLE `pqrs_seguimiento` (
  `id` int(11) NOT NULL,
  `pqrs_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pqrs_seguimiento`
--

INSERT INTO `pqrs_seguimiento` (`id`, `pqrs_id`, `usuario_id`, `estado_anterior`, `estado_nuevo`, `comentario`, `fecha`) VALUES
(1, 1, 1, NULL, 'Radicada', 'PQRS recibida y radicada.', '2025-11-01 09:00:00'),
(2, 2, 1, 'Radicada', 'En trámite', 'Se inicia investigación del caso.', '2025-11-02 10:15:00'),
(3, 3, 2, 'En trámite', 'Respondida', 'Se notificó al residente involucrado.', '2025-11-03 11:30:00'),
(4, 3, 5, 'Respondida', 'Cerrada', 'Residente conforme con la respuesta.', '2025-11-04 08:45:00'),
(5, 4, 1, NULL, 'Radicada', 'Sugerencia registrada.', '2025-11-05 12:00:00'),
(6, 5, 2, NULL, 'En trámite', 'En revisión con administración.', '2025-11-06 13:10:00'),
(7, 6, 1, 'En trámite', 'Respondida', 'Mantenimiento realizó la reparación.', '2025-11-07 14:20:00'),
(8, 7, 2, 'En trámite', 'Respondida', 'Portón ajustado por proveedor.', '2025-11-08 15:30:00'),
(9, 7, 11, 'Respondida', 'Cerrada', 'Verificado en sitio.', '2025-11-09 16:40:00'),
(10, 8, 1, NULL, 'Radicada', 'Sugerencia de aseo.', '2025-11-10 17:50:00'),
(11, 9, 2, NULL, 'En trámite', 'Contabilidad revisa la factura.', '2025-11-11 09:05:00'),
(12, 10, 1, 'En trámite', 'Respondida', 'Se capacitó al personal de portería.', '2025-11-12 10:25:00'),
(13, 11, 2, NULL, 'Radicada', 'Reclamo por humedad.', '2025-11-13 11:35:00'),
(14, 12, 1, NULL, 'En trámite', 'Se solicitó info a servicios públicos.', '2025-11-14 12:45:00'),
(15, 14, 2, 'En trámite', 'Cerrada', 'Paquete localizado y entregado.', '2025-11-15 13:55:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

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

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `zona_id`, `apartamento_id`, `usuario_id`, `fecha`, `hora_inicio`, `hora_fin`, `estado`, `observaciones`) VALUES
(1, 1, 1, 5, '2025-11-20', '18:00:00', '22:00:00', 'Aprobada', 'Cumpleaños familiar'),
(2, 2, 2, 6, '2025-11-21', '10:00:00', '12:00:00', 'Aprobada', NULL),
(3, 3, 3, 7, '2025-11-21', '06:00:00', '07:00:00', 'Aprobada', 'Rutina de ejercicio'),
(4, 4, 4, 8, '2025-11-22', '12:00:00', '15:00:00', 'Pendiente', 'Asado familiar'),
(5, 6, 5, 9, '2025-11-22', '16:00:00', '18:00:00', 'Aprobada', 'Partido de fútbol'),
(6, 8, 6, 10, '2025-11-23', '09:00:00', '11:00:00', 'Aprobada', 'Reunión de copropietarios'),
(7, 9, 7, 11, '2025-11-23', '19:00:00', '22:00:00', 'Pendiente', 'Evento privado'),
(8, 5, 8, 12, '2025-11-24', '13:00:00', '16:00:00', 'Aprobada', NULL),
(9, 11, 9, 13, '2025-11-24', '18:00:00', '19:00:00', 'Aprobada', NULL),
(10, 12, 10, 14, '2025-11-25', '17:00:00', '19:00:00', 'Pendiente', 'Relajación'),
(11, 13, 11, 15, '2025-11-25', '08:00:00', '12:00:00', 'Aprobada', 'Trabajo remoto'),
(12, 14, 12, 5, '2025-11-26', '20:00:00', '22:30:00', 'Aprobada', 'Noche de películas'),
(13, 15, 13, 6, '2025-11-26', '07:00:00', '08:30:00', 'Aprobada', 'Partido de tenis'),
(14, 7, 14, 7, '2025-11-27', '15:00:00', '17:00:00', 'Cancelada', 'Clima'),
(15, 10, 15, 8, '2025-11-27', '07:00:00', '08:00:00', 'Aprobada', 'Paseo con mascota');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residencias`
--

CREATE TABLE `residencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `apartamento_id` int(11) NOT NULL,
  `tipo` enum('propietario','arrendatario') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `residencias`
--

INSERT INTO `residencias` (`id`, `usuario_id`, `apartamento_id`, `tipo`, `fecha_inicio`, `fecha_fin`) VALUES
(1, 5, 1, 'propietario', '2022-01-15', NULL),
(2, 6, 2, 'arrendatario', '2023-03-01', NULL),
(3, 7, 3, 'propietario', '2021-06-10', NULL),
(4, 8, 4, 'arrendatario', '2023-08-15', NULL),
(5, 9, 5, 'propietario', '2020-02-20', NULL),
(6, 10, 6, 'arrendatario', '2024-01-05', NULL),
(7, 11, 7, 'propietario', '2019-11-30', NULL),
(8, 12, 8, 'arrendatario', '2023-05-12', NULL),
(9, 13, 9, 'propietario', '2022-09-01', NULL),
(10, 14, 10, 'arrendatario', '2024-02-18', NULL),
(11, 15, 11, 'propietario', '2021-12-01', NULL),
(12, 5, 12, 'propietario', '2020-07-22', '2023-07-21'),
(13, 6, 13, 'arrendatario', '2023-09-10', NULL),
(14, 7, 14, 'propietario', '2018-04-05', NULL),
(15, 8, 15, 'arrendatario', '2024-03-01', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

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

CREATE TABLE `torres` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `pisos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `torres`
--

INSERT INTO `torres` (`id`, `nombre`, `pisos`) VALUES
(1, 'Torre A', 8),
(2, 'Torre B', 8),
(3, 'Torre C', 8),
(4, 'Torre D', 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

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
(1, 1, 'Carlos', 'Ramírez', '100000001', 'admin1@conjunto.com', '+573001000001', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(2, 1, 'Laura', 'Gómez', '100000002', 'admin2@conjunto.com', '+573001000002', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(3, 3, 'Pedro', 'Martínez', '100000003', 'portero1@conjunto.com', '+573001000003', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(4, 3, 'Ana', 'López', '100000004', 'portero2@conjunto.com', '+573001000004', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(5, 2, 'Manolo', 'Prueba', '987654321', 'prueba@gmail.com', '+573005960608', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(6, 2, 'Sofía', 'Hernández', '100000006', 'sofia@correo.com', '+573001000006', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(7, 2, 'Jorge', 'Castro', '100000007', 'jorge@correo.com', '+573001000007', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(8, 2, 'Valentina', 'Rojas', '100000008', 'valentina@correo.com', '+573001000008', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(9, 2, 'Andrés', 'Moreno', '100000009', 'andres@correo.com', '+573001000009', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(10, 2, 'Camila', 'Díaz', '100000010', 'camila@correo.com', '+573001000010', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(11, 2, 'Felipe', 'Vargas', '100000011', 'felipe@correo.com', '+573001000011', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(12, 2, 'Daniela', 'Ortiz', '100000012', 'daniela@correo.com', '+573001000012', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(13, 2, 'Santiago', 'Peña', '100000013', 'santiago@correo.com', '+573001000013', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(14, 2, 'Isabella', 'Torres', '100000014', 'isabella@correo.com', '+573001000014', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
(15, 2, 'Mateo', 'Ramírez', '100000015', 'mateo@correo.com', '+573001000015', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitantes`
--

CREATE TABLE `visitantes` (
  `id` int(11) NOT NULL,
  `documento` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `visitantes`
--

INSERT INTO `visitantes` (`id`, `documento`, `nombre`, `telefono`) VALUES
(1, '200000001', 'Juan Pérez', '+573101000001'),
(2, '200000002', 'María Rodríguez', '+573101000002'),
(3, '200000003', 'Luis Sánchez', '+573101000003'),
(4, '200000004', 'Diana Torres', '+573101000004'),
(5, '200000005', 'Diego Herrera', '+573101000005'),
(6, '200000006', 'Paula Jiménez', '+573101000006'),
(7, '200000007', 'Ricardo Molina', '+573101000007'),
(8, '200000008', 'Natalia Silva', '+573101000008'),
(9, '200000009', 'Óscar Rincón', '+573101000009'),
(10, '200000010', 'Elena Pardo', '+573101000010'),
(11, '200000011', 'Javier Suárez', '+573101000011'),
(12, '200000012', 'Marcela Cruz', '+573101000012'),
(13, '200000013', 'Kevin Arias', '+573101000013'),
(14, '200000014', 'Sandra Mejía', '+573101000014'),
(15, '200000015', 'Álvaro Benítez', '+573101000015');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_estado_cuenta`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_estado_cuenta` (
`apartamento_id` int(11)
,`torre` varchar(50)
,`apartamento` varchar(20)
,`cuotas_pendientes` bigint(21)
,`cuotas_en_mora` bigint(21)
,`saldo_total` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_comunes`
--

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

--
-- Volcado de datos para la tabla `zonas_comunes`
--

INSERT INTO `zonas_comunes` (`id`, `nombre`, `aforo`, `hora_apertura`, `hora_cierre`, `costo`, `requiere_deposito`, `activo`) VALUES
(1, 'Salón Social', 80, '08:00:00', '22:00:00', 150000.00, 1, 1),
(2, 'Piscina', 40, '09:00:00', '19:00:00', 0.00, 0, 1),
(3, 'Gimnasio', 25, '05:00:00', '22:00:00', 0.00, 0, 1),
(4, 'BBQ Zona 1', 15, '10:00:00', '21:00:00', 50000.00, 1, 1),
(5, 'BBQ Zona 2', 15, '10:00:00', '21:00:00', 50000.00, 1, 1),
(6, 'Cancha Múltiple', 30, '07:00:00', '21:00:00', 0.00, 0, 1),
(7, 'Parque Infantil', 20, '07:00:00', '20:00:00', 0.00, 0, 1),
(8, 'Salón de Juntas', 12, '08:00:00', '18:00:00', 30000.00, 0, 1),
(9, 'Terraza', 50, '10:00:00', '23:00:00', 100000.00, 1, 1),
(10, 'Zona de Mascotas', 10, '06:00:00', '21:00:00', 0.00, 0, 1),
(11, 'Sauna', 8, '08:00:00', '20:00:00', 20000.00, 0, 1),
(12, 'Jacuzzi', 10, '09:00:00', '20:00:00', 30000.00, 0, 1),
(13, 'Coworking', 15, '07:00:00', '21:00:00', 0.00, 0, 1),
(14, 'Cine Privado', 20, '14:00:00', '23:00:00', 80000.00, 1, 1),
(15, 'Cancha de Tenis', 4, '07:00:00', '20:00:00', 25000.00, 0, 1);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estado_cuenta`
--
DROP TABLE IF EXISTS `v_estado_cuenta`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_estado_cuenta`  AS SELECT `a`.`id` AS `apartamento_id`, `t`.`nombre` AS `torre`, `a`.`numero` AS `apartamento`, count(case when `c`.`saldo` > 0 then 1 end) AS `cuotas_pendientes`, count(case when `c`.`saldo` > 0 and `c`.`fecha_vencimiento` < curdate() then 1 end) AS `cuotas_en_mora`, coalesce(sum(`c`.`saldo`),0.00) AS `saldo_total` FROM ((`apartamentos` `a` join `torres` `t` on(`a`.`torre_id` = `t`.`id`)) left join `cuotas` `c` on(`a`.`id` = `c`.`apartamento_id`)) GROUP BY `a`.`id`, `t`.`nombre`, `a`.`numero` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `apartamentos`
--
ALTER TABLE `apartamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_torre_numero` (`torre_id`,`numero`);

--
-- Indices de la tabla `autorizaciones`
--
ALTER TABLE `autorizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_autorizaciones_visitantes` (`visitante_id`),
  ADD KEY `fk_autorizaciones_apartamentos` (`apartamento_id`),
  ADD KEY `fk_autorizaciones_usuarios` (`usuario_id`);

--
-- Indices de la tabla `categorias_pqrs`
--
ALTER TABLE `categorias_pqrs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comunicados_usuarios` (`autor_id`);

--
-- Indices de la tabla `comunicados_leidos`
--
ALTER TABLE `comunicados_leidos`
  ADD PRIMARY KEY (`comunicado_id`,`usuario_id`),
  ADD KEY `fk_leidos_usuarios` (`usuario_id`);

--
-- Indices de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_apartamento_periodo` (`apartamento_id`,`periodo`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ingresos_visitantes` (`visitante_id`),
  ADD KEY `fk_ingresos_apartamentos` (`apartamento_id`),
  ADD KEY `fk_ingresos_portero` (`portero_id`),
  ADD KEY `fk_ingresos_parqueaderos` (`parqueadero_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagos_cuotas` (`cuota_id`),
  ADD KEY `fk_pagos_usuarios` (`registrado_por`);

--
-- Indices de la tabla `parqueaderos_visitantes`
--
ALTER TABLE `parqueaderos_visitantes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `radicado` (`radicado`),
  ADD KEY `fk_pqrs_categorias` (`categoria_id`),
  ADD KEY `fk_pqrs_usuarios` (`usuario_id`),
  ADD KEY `fk_pqrs_apartamentos` (`apartamento_id`);

--
-- Indices de la tabla `pqrs_seguimiento`
--
ALTER TABLE `pqrs_seguimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seguimiento_pqrs` (`pqrs_id`),
  ADD KEY `fk_seguimiento_usuarios` (`usuario_id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservas_zonas` (`zona_id`),
  ADD KEY `fk_reservas_apartamentos` (`apartamento_id`),
  ADD KEY `fk_reservas_usuarios` (`usuario_id`);

--
-- Indices de la tabla `residencias`
--
ALTER TABLE `residencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_residencias_usuarios` (`usuario_id`),
  ADD KEY `fk_residencias_apartamentos` (`apartamento_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `torres`
--
ALTER TABLE `torres`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuarios_roles` (`rol_id`);

--
-- Indices de la tabla `visitantes`
--
ALTER TABLE `visitantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `zonas_comunes`
--
ALTER TABLE `zonas_comunes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `apartamentos`
--
ALTER TABLE `apartamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `autorizaciones`
--
ALTER TABLE `autorizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `categorias_pqrs`
--
ALTER TABLE `categorias_pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `parqueaderos_visitantes`
--
ALTER TABLE `parqueaderos_visitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `pqrs_seguimiento`
--
ALTER TABLE `pqrs_seguimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `residencias`
--
ALTER TABLE `residencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `torres`
--
ALTER TABLE `torres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `visitantes`
--
ALTER TABLE `visitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `zonas_comunes`
--
ALTER TABLE `zonas_comunes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `apartamentos`
--
ALTER TABLE `apartamentos`
  ADD CONSTRAINT `fk_apartamentos_torres` FOREIGN KEY (`torre_id`) REFERENCES `torres` (`id`);

--
-- Filtros para la tabla `autorizaciones`
--
ALTER TABLE `autorizaciones`
  ADD CONSTRAINT `fk_autorizaciones_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_autorizaciones_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_autorizaciones_visitantes` FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`);

--
-- Filtros para la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `fk_comunicados_usuarios` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `comunicados_leidos`
--
ALTER TABLE `comunicados_leidos`
  ADD CONSTRAINT `fk_leidos_comunicados` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicados` (`id`),
  ADD CONSTRAINT `fk_leidos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD CONSTRAINT `fk_cuotas_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`);

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `fk_ingresos_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_ingresos_parqueaderos` FOREIGN KEY (`parqueadero_id`) REFERENCES `parqueaderos_visitantes` (`id`),
  ADD CONSTRAINT `fk_ingresos_portero` FOREIGN KEY (`portero_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ingresos_visitantes` FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_cuotas` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas` (`id`),
  ADD CONSTRAINT `fk_pagos_usuarios` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD CONSTRAINT `fk_pqrs_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_pqrs_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_pqrs` (`id`),
  ADD CONSTRAINT `fk_pqrs_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pqrs_seguimiento`
--
ALTER TABLE `pqrs_seguimiento`
  ADD CONSTRAINT `fk_seguimiento_pqrs` FOREIGN KEY (`pqrs_id`) REFERENCES `pqrs` (`id`),
  ADD CONSTRAINT `fk_seguimiento_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_reservas_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_reservas_zonas` FOREIGN KEY (`zona_id`) REFERENCES `zonas_comunes` (`id`);

--
-- Filtros para la tabla `residencias`
--
ALTER TABLE `residencias`
  ADD CONSTRAINT `fk_residencias_apartamentos` FOREIGN KEY (`apartamento_id`) REFERENCES `apartamentos` (`id`),
  ADD CONSTRAINT `fk_residencias_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
