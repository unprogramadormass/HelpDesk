-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-03-2026 a las 07:14:14
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
-- Base de datos: `tickets`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id`, `nombre`, `fecha_creacion`) VALUES
(1, 'Sistema', '2025-12-09 00:18:05'),
(2, 'Recursos Humanos', '2025-12-09 01:04:11'),
(3, 'Gerencia', '2026-02-28 00:17:20'),
(4, 'Supervisor', '2026-02-28 00:17:33'),
(5, 'Cajas', '2026-02-28 00:17:40'),
(6, 'Credito', '2026-02-28 00:17:48'),
(7, 'Captacion', '2026-02-28 00:17:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_usuario`
--

CREATE TABLE `estados_usuario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_usuario`
--

INSERT INTO `estados_usuario` (`id`, `nombre`) VALUES
(1, 'Activo'),
(2, 'Suspendido'),
(3, 'Desactivado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias_cat_1`
--

CREATE TABLE `incidencias_cat_1` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tiempo_resolucion` int(11) NOT NULL DEFAULT 0,
  `prioridad` enum('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Baja',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incidencias_cat_1`
--

INSERT INTO `incidencias_cat_1` (`id`, `nombre`, `tiempo_resolucion`, `prioridad`, `fecha_creacion`) VALUES
(1, 'Ajuste de Ficha', 5, 'Alta', '2025-12-09 17:52:22'),
(2, 'No break dañado', 10, 'Media', '2025-12-09 17:53:17'),
(3, 'Monitor apagado (No se ve nada)', 10, 'Media', '2025-12-09 18:15:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias_cat_2`
--

CREATE TABLE `incidencias_cat_2` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tiempo_resolucion` int(11) NOT NULL DEFAULT 0,
  `prioridad` enum('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Baja',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incidencias_cat_2`
--

INSERT INTO `incidencias_cat_2` (`id`, `nombre`, `tiempo_resolucion`, `prioridad`, `fecha_creacion`) VALUES
(1, 'No break dañado', 10, 'Media', '2025-12-09 17:53:17'),
(2, 'Monitor apagado (No se ve nada)', 10, 'Media', '2025-12-09 18:15:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias_cat_3`
--

CREATE TABLE `incidencias_cat_3` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tiempo_resolucion` int(11) NOT NULL DEFAULT 0,
  `prioridad` enum('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Baja',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles_incidencias`
--

CREATE TABLE `niveles_incidencias` (
  `id` int(11) NOT NULL,
  `nombre_mostrar` varchar(150) NOT NULL,
  `nombre_tabla_db` varchar(150) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `niveles_incidencias`
--

INSERT INTO `niveles_incidencias` (`id`, `nombre_mostrar`, `nombre_tabla_db`, `fecha_creacion`) VALUES
(1, 'Cajas', 'incidencias_cat_1', '2025-12-09 02:04:26'),
(2, 'Operativo', 'incidencias_cat_2', '2025-12-09 02:17:43'),
(3, 'Externos', 'incidencias_cat_3', '2026-01-07 18:30:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pendientes`
--

CREATE TABLE `pendientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('pendiente','completada','eliminada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pendientes`
--

INSERT INTO `pendientes` (`id`, `usuario_id`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 2, 'ff', 'eliminada', '2026-02-27 15:29:02'),
(2, 2, 'w', 'pendiente', '2026-02-27 15:30:07'),
(3, 2, 'D', 'pendiente', '2026-02-28 01:07:14'),
(4, 2, 'D', 'pendiente', '2026-02-28 01:07:17'),
(5, 2, 'D', 'pendiente', '2026-02-28 01:07:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `descripcion` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `clave`, `descripcion`) VALUES
(1, 'CREAR_TICKET', 'Puede crear tickets'),
(2, 'COMENTAR', 'Puede comentar en tickets'),
(3, 'ADMIN_PANEL', 'Acceso al panel de administración'),
(4, 'EDITAR_USUARIOS', 'Puede editar usuarios'),
(5, 'VER_REPORTES', 'Puede ver reportes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puesto`
--

CREATE TABLE `puesto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `puesto`
--

INSERT INTO `puesto` (`id`, `nombre`, `fecha_creacion`) VALUES
(1, 'Auxiliar', '2025-12-09 01:07:08'),
(2, 'Gerente', '2025-12-09 01:16:27'),
(3, 'Supervisor', '2026-02-28 00:18:03'),
(4, 'Jefe', '2026-02-28 00:18:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `redirect_url` varchar(255) NOT NULL DEFAULT '/helpdesk/index.php'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `clave`, `nombre`, `fecha_creacion`, `redirect_url`) VALUES
(1, 'ADMIN', 'Administrador', '2025-12-02 23:13:16', './pages/usr/admin/index.php'),
(2, 'SUP', 'Supervisor', '2025-12-02 23:13:16', './pages/usr/supervisor/index.php'),
(3, 'AGT', 'Agente', '2025-12-02 23:13:16', './pages/usr/soporte/index.php'),
(4, 'CORP', 'Corporativo', '2025-12-02 23:13:16', '/helpdesk/pages/usr/user/index.php'),
(5, 'OPER', 'Operativo', '2025-12-02 23:13:16', '/helpdesk/pages/usr/user/index.php');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_activas`
--

CREATE TABLE `sesiones_activas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_sesion` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `dispositivo` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT 'Desconocida',
  `ultimo_acceso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones_activas`
--

INSERT INTO `sesiones_activas` (`id`, `usuario_id`, `token_sesion`, `ip_address`, `dispositivo`, `user_agent`, `ubicacion`, `ultimo_acceso`, `activo`) VALUES
(82, 1, 'ji8rak77tta435r0bpdub0h4f6', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 03:33:59', 0),
(83, 1, 'imnglutmh657ruublc7act4j34', '127.0.0.1', 'Móvil', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', 'Localhost (Dev)', '2026-02-28 04:06:32', 0),
(84, 2, 'iipmigpf20n5grismb805m8956', '192.168.0.108', 'Móvil', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/145.0.7632.108 Mobile/15E148 Safari/604.1', 'Ubicación Desconocida', '2026-02-28 03:54:58', 1),
(85, 1, '4he38qmcc7lvmeb2l5k6ggsoq8', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 04:30:01', 0),
(86, 9, 'cequ4gl4kp7589af779f38l6dd', '127.0.0.1', 'Móvil', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:15:29', 0),
(87, 9, 'agm2h7sdso20uihfnug7mrdeu0', '127.0.0.1', 'Móvil', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:18:54', 0),
(88, 9, 'lmr7ih8rj2gr2d0std8knv36jm', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:23:42', 0),
(89, 9, 'jn5d2mnsv7clg6o5f76gejomku', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:26:13', 0),
(90, 9, '021e4ivev6ddgjf62sp8asgi4t', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:29:16', 0),
(91, 9, '9j807bq66qanhqs5pmakg69fo3', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-02-28 07:33:17', 0),
(92, 9, 'fenavi9mjk44hq9kamnsgsramd', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 03:36:36', 0),
(93, 9, 'qed6likuj8vg2o1id0nqukqnrf', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 04:16:37', 0),
(94, 9, 'o7salg4tvnkhr35u8rbbvdt02e', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 04:32:03', 0),
(95, 9, '3efea2lnupddp1ffc7ut7jp2dv', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 04:36:29', 0),
(96, 9, 'h315sgcp7bo46rc7m306otimnn', '127.0.0.1', 'Móvil', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', 'Localhost (Dev)', '2026-03-02 04:52:58', 0),
(97, 9, 'tgo456vqaj1rkj0p2f5ev9ahta', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 05:51:09', 0),
(98, 2, 'viv3ksvo4gi1f2hopasaujnnfh', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 06:00:54', 0),
(99, 2, 'd4jq9g8ak37jr6po9d2nbjjd63', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 06:02:12', 0),
(100, 1, '1ergeh2k8cq4arle0rvpbirqkc', '127.0.0.1', 'PC', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'Localhost (Dev)', '2026-03-02 06:02:50', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `folio` varchar(255) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` varchar(50) DEFAULT 'OPERATIVA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `nombre`, `folio`, `direccion`, `telefono`, `fecha_creacion`, `estatus`) VALUES
(1, 'Corporativo', '100', 'C. Josefa Ortiz de Domínguez 3008, Libertad, 44750 Guadalajara, Jal.', '+52 33 3883 1830', '2025-12-05 23:47:57', 'OPERATIVA'),
(2, 'Matriz', '1', 'C. Josefa Ortiz de Domínguez 3008, Libertad, 44750 Guadalajara, Jal.', '+52 33 3883 1830', '2025-12-06 00:10:29', 'CERRADA'),
(3, 'Zalatitán', '2', 'Av. Zalatitan 370, Colonia Alameda de Zalatitán, 45407 Tonalá, Jal.', '+52 33 3607 3717', '2025-12-06 00:34:54', 'OPERATIVA'),
(4, 'San Pedrito', '4', 'Poza Rica 4932, San Pedrito, 45625 San Pedro Tlaquepaque, Jal.', '+52 33 3600 3162', '2025-12-06 01:06:36', 'OPERATIVA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket`
--

CREATE TABLE `ticket` (
  `id` int(11) NOT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `usuario_creador_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `evidence` text DEFAULT NULL,
  `prioridad` enum('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Baja',
  `estado` enum('Abierto','Asignado','En Proceso','Espera','Resuelto','Cerrado','Cancelado') NOT NULL DEFAULT 'Abierto',
  `nivel_incidencia_id` int(11) DEFAULT NULL,
  `agente_actual_id` int(11) DEFAULT NULL,
  `fecha_asignacion` datetime DEFAULT NULL,
  `agente_anterior_id` int(11) DEFAULT NULL,
  `fecha_reasignacion` datetime DEFAULT NULL,
  `motivo_reasignacion` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ticket`
--

INSERT INTO `ticket` (`id`, `folio`, `usuario_creador_id`, `sucursal_id`, `area_id`, `titulo`, `descripcion`, `evidence`, `prioridad`, `estado`, `nivel_incidencia_id`, `agente_actual_id`, `fecha_asignacion`, `agente_anterior_id`, `fecha_reasignacion`, `motivo_reasignacion`, `fecha_creacion`, `fecha_ultima_actualizacion`, `fecha_cierre`) VALUES
(1, '#TCK-1', 2, 1, 2, 'No break dañado', 'gg', NULL, 'Baja', 'Abierto', 2, 1, '2026-01-09 09:12:11', NULL, NULL, NULL, '2026-01-09 15:13:12', '2026-02-28 01:12:51', NULL),
(2, '#TCK-2', 2, 1, 2, 'Monitor apagado (No se ve nada)', 'sdfdf', NULL, 'Media', 'Asignado', 2, 9, '2026-03-01 22:17:15', NULL, NULL, NULL, '2026-02-28 00:13:44', '2026-03-02 04:17:15', NULL),
(3, '#TCK-3', 2, 1, 3, 'Monitor apagado (No se ve nada)', 'PRUEBA', '', 'Alta', 'En Proceso', 2, NULL, NULL, NULL, NULL, NULL, '2026-02-28 00:56:39', '2026-02-28 01:12:58', NULL),
(4, '#TCK-4', 2, 1, 7, 'Monitor apagado (No se ve nada)', 'RR', '[\"evidencia_1772240335_69a23dcf3d313.png\"]', 'Crítica', 'Espera', 2, NULL, NULL, NULL, NULL, NULL, '2026-02-28 00:58:55', '2026-02-28 01:13:02', NULL),
(5, '#TCK-5', 2, 1, 3, 'Monitor apagado (No se ve nada)', 'fff', '[\"evidencia_1772241058_69a240a2ceb59.jpeg\"]', 'Baja', 'Asignado', 2, 1, '2026-02-27 20:46:25', NULL, NULL, NULL, '2026-02-28 01:10:58', '2026-02-28 02:47:46', NULL),
(6, '#TCK-6', 2, 1, 4, 'No break dañado', 'ff', '[\"evidencia_1772241073_69a240b156c66.jpg\"]', 'Media', 'Cerrado', 2, 8, '2026-03-01 22:00:16', 9, NULL, NULL, '2026-02-28 01:11:13', '2026-03-02 05:38:10', '2026-03-02 06:38:10'),
(7, '#TCK-7', 2, 1, 4, 'No break dañado', 'PRUEBA CONTEO DASHBOARD', '[\"evidencia_1772250975_69a2675fb8520.jpeg\"]', 'Media', 'Asignado', 2, 9, '2026-03-01 22:40:56', NULL, NULL, NULL, '2026-02-28 03:56:15', '2026-03-02 04:40:56', NULL),
(8, '#TCK-8', 2, 1, 6, 'Monitor apagado (No se ve nada)', 'fsvfv', NULL, 'Media', 'Resuelto', 2, 9, '2026-03-01 22:53:17', NULL, NULL, NULL, '2026-02-28 04:06:00', '2026-03-02 05:38:57', '2026-03-02 06:38:57'),
(9, '#TCK-9', 2, 1, 7, 'No break dañado', 'd', NULL, 'Media', 'Asignado', 2, 8, '2026-03-01 23:00:19', 9, NULL, NULL, '2026-02-28 04:07:07', '2026-03-02 05:28:35', NULL);

--
-- Disparadores `ticket`
--
DELIMITER $$
CREATE TRIGGER `trg_ticket_folio` BEFORE INSERT ON `ticket` FOR EACH ROW BEGIN
    DECLARE ultimo INT;

    SELECT 
        CAST(SUBSTRING(folio, 6) AS UNSIGNED) -- Extrae el número después de #TCK-
    INTO ultimo
    FROM ticket
    WHERE folio LIKE '#TCK-%'
    ORDER BY id DESC
    LIMIT 1;

    IF ultimo IS NULL THEN
        SET ultimo = 0;
    END IF;

    SET NEW.folio = CONCAT('#TCK-', ultimo + 1);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_historial`
--

CREATE TABLE `ticket_historial` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `usuario_responsable_id` int(11) NOT NULL,
  `tipo_movimiento` enum('Creación','Asignación','Reasignación','Cambio Estado','Comentario','Actualización') NOT NULL,
  `descripcion_evento` text NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `vista` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ticket_historial`
--

INSERT INTO `ticket_historial` (`id`, `ticket_id`, `usuario_responsable_id`, `tipo_movimiento`, `descripcion_evento`, `fecha_movimiento`, `vista`) VALUES
(1, 1, 1, 'Actualización', 'Actualización: Estado: En Proceso', '2026-01-10 16:21:26', 1),
(2, 1, 1, 'Actualización', 'Actualización: Estado: Cerrado', '2026-01-10 16:21:35', 1),
(3, 1, 1, 'Comentario', 'HH', '2026-01-10 16:22:17', 1),
(4, 1, 1, 'Actualización', 'Actualización: Estado: Espera', '2026-01-10 18:03:40', 1),
(5, 1, 1, 'Actualización', 'Actualización: Estado: Resuelto', '2026-01-10 18:03:47', 1),
(6, 1, 1, 'Actualización', 'Modificación de gestión', '2026-01-10 18:04:27', 1),
(7, 1, 1, 'Actualización', 'Modificación de gestión', '2026-01-10 18:04:32', 1),
(8, 1, 1, 'Actualización', 'Actualización: Prioridad: Crítica', '2026-01-10 18:13:57', 1),
(9, 1, 1, 'Actualización', 'Actualización: Prioridad: Media', '2026-01-10 18:22:19', 1),
(10, 1, 1, 'Actualización', 'Actualización: Estado: En Proceso', '2026-02-25 17:22:08', 1),
(11, 1, 1, 'Actualización', 'Actualización: Estado: Cancelado', '2026-02-25 17:22:45', 1),
(12, 2, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 2.', '2026-02-28 00:13:44', 1),
(13, 3, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 3.', '2026-02-28 00:56:39', 0),
(14, 4, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 7.', '2026-02-28 00:58:55', 0),
(15, 5, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 3.', '2026-02-28 01:10:58', 1),
(16, 6, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 4.', '2026-02-28 01:11:13', 1),
(17, 5, 1, 'Asignación', 'Ticket asignado a agente ID: 1', '2026-02-28 02:46:25', 1),
(18, 5, 1, 'Comentario', 'kiki', '2026-02-28 02:47:24', 1),
(19, 5, 1, 'Actualización', 'Actualización: Estado: Asignado', '2026-02-28 02:47:46', 1),
(20, 7, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 4.', '2026-02-28 03:56:15', 1),
(21, 8, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 6.', '2026-02-28 04:06:00', 1),
(22, 9, 2, 'Creación', 'Creación de ticket. Prioridad: Media. Área destino ID: 7.', '2026-02-28 04:07:07', 0),
(23, 6, 9, '', 'El agente (ID: 9) ha tomado este ticket.', '2026-03-02 04:00:16', 1),
(24, 2, 9, '', 'El agente (ID: 9) ha tomado este ticket.', '2026-03-02 04:17:15', 1),
(25, 7, 9, '', 'El agente (ID: 9) ha tomado este ticket.', '2026-03-02 04:40:56', 1),
(26, 8, 9, '', 'El agente (ID: 9) ha tomado este ticket.', '2026-03-02 04:53:17', 1),
(27, 9, 9, 'Asignación', 'El agente Andres zsn ha tomado este ticket.', '2026-03-02 05:00:19', 1),
(28, 8, 9, 'Actualización', 'Actualización: Estado: En Proceso', '2026-03-02 05:21:22', 1),
(29, 8, 9, 'Actualización', 'Actualización: Estado: Cerrado', '2026-03-02 05:22:13', 1),
(30, 9, 9, 'Actualización', 'Actualización: Agente reasignado', '2026-03-02 05:28:35', 1),
(31, 6, 9, 'Actualización', 'Actualización: Agente reasignado de Andres zsn a Andres d', '2026-03-02 05:38:10', 1),
(32, 8, 9, 'Actualización', 'Actualización: Estado: Resuelto', '2026-03-02 05:38:57', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `folio` varchar(20) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `secondname` varchar(100) DEFAULT NULL,
  `firstapellido` varchar(100) NOT NULL,
  `secondapellido` varchar(100) DEFAULT NULL,
  `extension` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `requiere_cambio_password` tinyint(1) DEFAULT 1,
  `tipo_usuario` int(11) NOT NULL,
  `puesto_id` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `estado_id` int(11) DEFAULT 1,
  `incidencia_id` int(11) DEFAULT NULL,
  `noti_whatsapp` tinyint(1) DEFAULT 0,
  `noti_email` tinyint(1) DEFAULT 0,
  `noti_nuevo` tinyint(1) DEFAULT 0,
  `noti_sistema` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `connected` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `folio`, `avatar`, `username`, `firstname`, `secondname`, `firstapellido`, `secondapellido`, `extension`, `email`, `celular`, `password`, `requiere_cambio_password`, `tipo_usuario`, `puesto_id`, `sucursal_id`, `estado_id`, `incidencia_id`, `noti_whatsapp`, `noti_email`, `noti_nuevo`, `noti_sistema`, `fecha_creacion`, `connected`) VALUES
(1, '#CPSP-0001', 'user_1_1765401022.jpg', 'sistemas8', 'Andres', 'De jesus', 'Tolosa', 'Tapia', 1233, 'andres.tolosa@cajasanpablo.com', '3324879134', '$2y$10$UJrvXdeGefCJ155VvM.oNOxUyHhCK1lf6t8mYaD1Fzospm2u398wa', 0, 1, 1, 1, 1, NULL, 1, 1, 1, 1, '2025-12-09 19:11:30', 1),
(2, '#CPSP-0002', 'user_2_1772240752.jpg', 'gerentem', 'Hector', '', 'Matriz', '', 5454, 'andres.tolosa124@gmail.com', '', '$2y$10$UJrvXdeGefCJ155VvM.oNOxUyHhCK1lf6t8mYaD1Fzospm2u398wa', 0, 4, 2, 1, 2, 2, 0, 0, 0, 0, '2026-01-09 15:11:55', 1),
(6, '#CPSP-003', NULL, 'h', 'h', 'h', 'h', 'h', NULL, 'd', '', '$2y$10$OY13fJihF4JasNx3BjZTbe8997yOw9lJ0G4HDNRIuSPzXtgneDXx2', 1, 4, 2, 1, 1, 2, 0, 0, 0, 0, '2026-02-27 20:10:43', 0),
(8, '#CPSP-0004', NULL, 'gg', 'Andres', 'Tolosa', 'd', '', NULL, 'unprogramadormass@gmail.com', '', '$2y$10$QG1PZ7d4CzcuHUPWJgcE0.Gbuy3NiCb/xIq8d8G.X7bGLoK/NeQaW', 1, 3, 1, 1, 1, NULL, 0, 0, 0, 0, '2026-02-28 04:09:58', 0),
(9, '#CPSP-2147', 'user_9_1772264147.jpg', 'ggg', 'Andres', 'Tolosa', 'zsn', 'uhuhuh', 22, 'andrewwtoulouse306@gmail.com', '', '$2y$10$V12LnXiAuBJkTY4zmTwBMOpxQwxF/G8zlGr23.L9MLa5RG2MlCJAS', 0, 3, 1, 1, 1, 2, 0, 0, 1, 0, '2026-02-28 04:27:09', 1);

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_usuarios_folio` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
    DECLARE ultimo INT;

    SELECT 
        CAST(SUBSTRING(folio, 7) AS UNSIGNED) -- ¡Aquí está la magia, cambiado a 7!
    INTO ultimo
    FROM usuarios
    WHERE folio LIKE '#CPSP-%'
    ORDER BY id DESC
    LIMIT 1;

    IF ultimo IS NULL THEN
        SET ultimo = 0;
    END IF;

    SET NEW.folio = CONCAT('#CPSP-', LPAD(ultimo + 1, 4, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_areas`
--

CREATE TABLE `usuario_areas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_areas`
--

INSERT INTO `usuario_areas` (`id`, `usuario_id`, `area_id`) VALUES
(1, 1, 1),
(4, 6, 2),
(5, 6, 1),
(11, 2, 5),
(12, 2, 7),
(13, 2, 6),
(14, 2, 3),
(15, 2, 4),
(16, 8, 1),
(19, 9, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permisos`
--

CREATE TABLE `usuario_permisos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_permisos`
--

INSERT INTO `usuario_permisos` (`id`, `usuario_id`, `permiso_id`) VALUES
(19, 1, 2),
(20, 1, 3),
(21, 1, 4),
(22, 1, 5),
(29, 2, 1),
(30, 2, 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estados_usuario`
--
ALTER TABLE `estados_usuario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `incidencias_cat_1`
--
ALTER TABLE `incidencias_cat_1`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `incidencias_cat_2`
--
ALTER TABLE `incidencias_cat_2`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `incidencias_cat_3`
--
ALTER TABLE `incidencias_cat_3`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `niveles_incidencias`
--
ALTER TABLE `niveles_incidencias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pendientes`
--
ALTER TABLE `pendientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pendiente_usuario` (`usuario_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `puesto`
--
ALTER TABLE `puesto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`),
  ADD KEY `fk_ticket_creador` (`usuario_creador_id`),
  ADD KEY `fk_ticket_sucursal` (`sucursal_id`),
  ADD KEY `fk_ticket_area` (`area_id`),
  ADD KEY `fk_ticket_agente` (`agente_actual_id`),
  ADD KEY `fk_ticket_agente_ant` (`agente_anterior_id`),
  ADD KEY `fk_ticket_nivel` (`nivel_incidencia_id`);

--
-- Indices de la tabla `ticket_historial`
--
ALTER TABLE `ticket_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historial_ticket` (`ticket_id`),
  ADD KEY `fk_historial_usuario` (`usuario_responsable_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `folio` (`folio`),
  ADD KEY `tipo_usuario` (`tipo_usuario`),
  ADD KEY `puesto_id` (`puesto_id`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `estado_id` (`estado_id`),
  ADD KEY `fk_usuario_nivel` (`incidencia_id`);

--
-- Indices de la tabla `usuario_areas`
--
ALTER TABLE `usuario_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ua_usuario` (`usuario_id`),
  ADD KEY `fk_ua_area` (`area_id`);

--
-- Indices de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `permiso_id` (`permiso_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `estados_usuario`
--
ALTER TABLE `estados_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `incidencias_cat_1`
--
ALTER TABLE `incidencias_cat_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `incidencias_cat_2`
--
ALTER TABLE `incidencias_cat_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `incidencias_cat_3`
--
ALTER TABLE `incidencias_cat_3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `niveles_incidencias`
--
ALTER TABLE `niveles_incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pendientes`
--
ALTER TABLE `pendientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `puesto`
--
ALTER TABLE `puesto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `ticket_historial`
--
ALTER TABLE `ticket_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuario_areas`
--
ALTER TABLE `usuario_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pendientes`
--
ALTER TABLE `pendientes`
  ADD CONSTRAINT `fk_pendiente_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `fk_ticket_agente` FOREIGN KEY (`agente_actual_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ticket_agente_ant` FOREIGN KEY (`agente_anterior_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ticket_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  ADD CONSTRAINT `fk_ticket_creador` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ticket_nivel` FOREIGN KEY (`nivel_incidencia_id`) REFERENCES `niveles_incidencias` (`id`),
  ADD CONSTRAINT `fk_ticket_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Filtros para la tabla `ticket_historial`
--
ALTER TABLE `ticket_historial`
  ADD CONSTRAINT `fk_historial_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_responsable_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_nivel` FOREIGN KEY (`incidencia_id`) REFERENCES `niveles_incidencias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`tipo_usuario`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`puesto_id`) REFERENCES `puesto` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_4` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_5` FOREIGN KEY (`estado_id`) REFERENCES `estados_usuario` (`id`);

--
-- Filtros para la tabla `usuario_areas`
--
ALTER TABLE `usuario_areas`
  ADD CONSTRAINT `fk_ua_area_const` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ua_usuario_const` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
