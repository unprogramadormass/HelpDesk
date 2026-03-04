-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-03-2026 a las 21:31:51
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
(1, 'Sistemas', '2025-12-09 00:18:05');

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
-- Estructura de tabla para la tabla `niveles_incidencias`
--

CREATE TABLE `niveles_incidencias` (
  `id` int(11) NOT NULL,
  `nombre_mostrar` varchar(150) NOT NULL,
  `nombre_tabla_db` varchar(150) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'Gerente', '2025-12-09 01:07:08');

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
(1, 'Corporativo', '100', 'C. Josefa Ortiz de Domínguez 3008, Libertad, 44750 Guadalajara, Jal.', '+52 33 3883 1830', '2025-12-05 23:47:57', 'OPERATIVA');

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
(1, 'UID-0001', NULL, 'Admin', 'Admin', '', '', '', NULL, 'unprogramadormass@gmail.com', NULL, '$2y$10$MfhjgKgdjNgbc/04JHoZoeN9DhcosnlN5A35CIDBbtjbFH8GooS5u', 1, 1, NULL, 1, 1, NULL, 1, 1, 1, 1, '2025-12-09 19:11:30', 1);

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_usuarios_folio` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
    DECLARE ultimo INT;

    SELECT 
        CAST(SUBSTRING(folio, 5) AS UNSIGNED) -- ¡Aquí está la magia, cambiado a 7!
    INTO ultimo
    FROM usuarios
    WHERE folio LIKE 'UID-%'
    ORDER BY id DESC
    LIMIT 1;

    IF ultimo IS NULL THEN
        SET ultimo = 0;
    END IF;

    SET NEW.folio = CONCAT('UID-', LPAD(ultimo + 1, 4, '0'));
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
(1, 1, 1);

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
(1, 1, 2),
(2, 1, 3),
(3, 1, 4),
(4, 1, 5);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estados_usuario`
--
ALTER TABLE `estados_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `niveles_incidencias`
--
ALTER TABLE `niveles_incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pendientes`
--
ALTER TABLE `pendientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `puesto`
--
ALTER TABLE `puesto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_historial`
--
ALTER TABLE `ticket_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuario_areas`
--
ALTER TABLE `usuario_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
