-- Base de datos para Sistema de Gestión de Eventos Académicos
-- Creado para cumplir con los requisitos de gestión de congresos, seminarios, etc.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Responsable de Inscripción', 'Gestiona inscripciones y pagos'),
(3, 'Asistente', 'Apoyo en control de asistencia y eventos'),
(4, 'Participante', 'Usuario externo que se inscribe a eventos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Usuario Admin por defecto (Password: admin123)
-- Se recomienda cambiar el hash por uno generado con password_hash() en PHP
--

INSERT INTO `usuarios` (`nombre_completo`, `email`, `password`, `id_rol`, `estado`) VALUES
('Administrador Principal', 'admin@evento.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_evento` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `lugar` varchar(200) DEFAULT NULL,
  `capacidad_maxima` int(11) DEFAULT NULL,
  `estado` enum('activo','cancelado','finalizado') DEFAULT 'activo',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id_evento`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `fk_evento_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
-- (Para manejar eventos simultáneos y cronograma)
--

CREATE TABLE `sesiones` (
  `id_sesion` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `nombre_sesion` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `lugar_sesion` varchar(200) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `fk_sesion_evento` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

CREATE TABLE `participantes` (
  `id_participante` int(11) NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `institucion` varchar(150) DEFAULT NULL,
  `tipo_participante` enum('estudiante','profesional','ponente','invitado') DEFAULT 'estudiante',
  `fecha_registro` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id_participante`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `id_participante` int(11) NOT NULL,
  `fecha_inscripcion` timestamp DEFAULT current_timestamp(),
  `estado_inscripcion` enum('pendiente','confirmada','cancelada') DEFAULT 'pendiente',
  PRIMARY KEY (`id_inscripcion`),
  UNIQUE KEY `unique_inscripcion` (`id_evento`,`id_participante`),
  KEY `id_evento` (`id_evento`),
  KEY `id_participante` (`id_participante`),
  CONSTRAINT `fk_inscripcion_evento` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`),
  CONSTRAINT `fk_inscripcion_participante` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_inscripcion` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','qr','tarjeta') NOT NULL,
  `comprobante_url` varchar(255) DEFAULT NULL,
  `estado_pago` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `registrado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `id_inscripcion` (`id_inscripcion`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `fk_pago_inscripcion` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_sesion` int(11) NOT NULL,
  `id_participante` int(11) NOT NULL,
  `fecha_hora_entrada` timestamp DEFAULT current_timestamp(),
  `estado` enum('presente','tardanza','ausente') DEFAULT 'presente',
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `unique_asistencia` (`id_sesion`,`id_participante`),
  KEY `id_sesion` (`id_sesion`),
  KEY `id_participante` (`id_participante`),
  CONSTRAINT `fk_asistencia_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones` (`id_sesion`),
  CONSTRAINT `fk_asistencia_participante` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificados`
--

CREATE TABLE `certificados` (
  `id_certificado` int(11) NOT NULL AUTO_INCREMENT,
  `id_inscripcion` int(11) NOT NULL,
  `codigo_validacion` varchar(50) NOT NULL,
  `fecha_emision` date NOT NULL,
  `archivo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_certificado`),
  UNIQUE KEY `codigo_validacion` (`codigo_validacion`),
  UNIQUE KEY `id_inscripcion` (`id_inscripcion`),
  CONSTRAINT `fk_certificado_inscripcion` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro_afectado` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `fecha_hora` timestamp DEFAULT current_timestamp(),
  `ip_origen` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
