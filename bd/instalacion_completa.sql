-- =====================================================
-- SISTEMA DE GESTIÓN DE EVENTOS ACADÉMICOS
-- Instalación Completa de Base de Datos
-- =====================================================
-- 
-- INSTRUCCIONES:
-- 1. Crear una base de datos llamada: evento_academico
-- 2. Ejecutar este archivo SQL completo
-- 3. El sistema creará todas las tablas, datos, procedimientos, funciones, triggers y vistas
--
-- Usuario por defecto:
-- Email: admin@evento.com
-- Password: admin123
-- =====================================================

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS evento_academico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evento_academico;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- PASO 1: CREACIÓN DE TABLAS
-- =====================================================

-- Tabla: roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Responsable de Inscripción', 'Gestiona inscripciones y pagos'),
(3, 'Asistente', 'Apoyo en control de asistencia y eventos'),
(4, 'Participante', 'Usuario externo que se inscribe a eventos');

-- Tabla: usuarios
DROP TABLE IF EXISTS `usuarios`;
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

-- Usuario admin (password: admin123)
INSERT INTO `usuarios` (`nombre_completo`, `email`, `password`, `id_rol`, `estado`) VALUES
('Administrador Principal', 'admin@evento.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'activo');

-- Tabla: eventos
DROP TABLE IF EXISTS `eventos`;
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

-- Tabla: sesiones
DROP TABLE IF EXISTS `sesiones`;
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

-- Tabla: participantes
DROP TABLE IF EXISTS `participantes`;
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

-- Tabla: inscripciones
DROP TABLE IF EXISTS `inscripciones`;
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

-- Tabla: pagos
DROP TABLE IF EXISTS `pagos`;
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

-- Tabla: asistencias
DROP TABLE IF EXISTS `asistencias`;
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

-- Tabla: certificados
DROP TABLE IF EXISTS `certificados`;
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

-- Tabla: auditoria
DROP TABLE IF EXISTS `auditoria`;
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

-- =====================================================
-- PASO 2: STORED PROCEDURES
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_registrar_participante_completo$$
CREATE PROCEDURE sp_registrar_participante_completo(
    IN p_dni VARCHAR(20),
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_institucion VARCHAR(150),
    IN p_tipo VARCHAR(20),
    IN p_evento_id INT,
    OUT p_participante_id INT,
    OUT p_inscripcion_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_participante_id = -1;
        SET p_inscripcion_id = -1;
    END;
    
    START TRANSACTION;
    INSERT INTO participantes (dni, nombres, apellidos, email, telefono, institucion, tipo_participante)
    VALUES (p_dni, p_nombres, p_apellidos, p_email, p_telefono, p_institucion, p_tipo);
    SET p_participante_id = LAST_INSERT_ID();
    
    INSERT INTO inscripciones (id_evento, id_participante, estado_inscripcion)
    VALUES (p_evento_id, p_participante_id, 'confirmada');
    SET p_inscripcion_id = LAST_INSERT_ID();
    COMMIT;
END$$

DROP PROCEDURE IF EXISTS sp_generar_certificados_evento$$
CREATE PROCEDURE sp_generar_certificados_evento(IN p_evento_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_inscripcion_id INT;
    DECLARE v_codigo VARCHAR(50);
    
    DECLARE cur_inscripciones CURSOR FOR
        SELECT i.id_inscripcion
        FROM inscripciones i
        WHERE i.id_evento = p_evento_id 
          AND i.estado_inscripcion = 'confirmada'
          AND NOT EXISTS (SELECT 1 FROM certificados WHERE id_inscripcion = i.id_inscripcion);
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_inscripciones;
    read_loop: LOOP
        FETCH cur_inscripciones INTO v_inscripcion_id;
        IF done THEN LEAVE read_loop; END IF;
        
        SET v_codigo = CONCAT('CERT-', p_evento_id, '-', v_inscripcion_id, '-', UNIX_TIMESTAMP());
        INSERT INTO certificados (id_inscripcion, codigo_validacion, fecha_emision)
        VALUES (v_inscripcion_id, v_codigo, CURDATE());
    END LOOP;
    CLOSE cur_inscripciones;
END$$

DROP PROCEDURE IF EXISTS sp_actualizar_estados_eventos$$
CREATE PROCEDURE sp_actualizar_estados_eventos()
BEGIN
    UPDATE eventos SET estado = 'finalizado'
    WHERE fecha_fin < CURDATE() AND estado = 'activo';
    SELECT ROW_COUNT() as eventos_finalizados;
END$$

DROP PROCEDURE IF EXISTS sp_estadisticas_asistencia$$
CREATE PROCEDURE sp_estadisticas_asistencia(IN p_evento_id INT)
BEGIN
    SELECT e.nombre_evento, s.nombre_sesion, s.fecha,
           COUNT(DISTINCT i.id_participante) as total_inscritos,
           COUNT(DISTINCT a.id_participante) as total_asistentes,
           ROUND((COUNT(DISTINCT a.id_participante) / NULLIF(COUNT(DISTINCT i.id_participante), 0) * 100), 2) as porcentaje_asistencia
    FROM eventos e
    INNER JOIN sesiones s ON e.id_evento = s.id_evento
    LEFT JOIN inscripciones i ON e.id_evento = i.id_evento AND i.estado_inscripcion = 'confirmada'
    LEFT JOIN asistencias a ON s.id_sesion = a.id_sesion
    WHERE e.id_evento = p_evento_id
    GROUP BY s.id_sesion
    ORDER BY s.fecha, s.hora_inicio;
END$$

DROP PROCEDURE IF EXISTS sp_cancelar_inscripcion$$
CREATE PROCEDURE sp_cancelar_inscripcion(IN p_inscripcion_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error' as resultado;
    END;
    
    START TRANSACTION;
    UPDATE inscripciones SET estado_inscripcion = 'cancelada' WHERE id_inscripcion = p_inscripcion_id;
    UPDATE pagos SET estado_pago = 'rechazado' WHERE id_inscripcion = p_inscripcion_id AND estado_pago = 'pendiente';
    COMMIT;
    SELECT 'OK' as resultado;
END$$

DROP PROCEDURE IF EXISTS sp_resumen_pagos_evento$$
CREATE PROCEDURE sp_resumen_pagos_evento(IN p_evento_id INT)
BEGIN
    SELECT e.nombre_evento,
           COUNT(DISTINCT p.id_pago) as total_pagos,
           SUM(CASE WHEN p.estado_pago = 'aprobado' THEN 1 ELSE 0 END) as pagos_aprobados,
           SUM(CASE WHEN p.estado_pago = 'pendiente' THEN 1 ELSE 0 END) as pagos_pendientes,
           SUM(CASE WHEN p.estado_pago = 'aprobado' THEN p.monto ELSE 0 END) as monto_total_aprobado
    FROM eventos e
    LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
    LEFT JOIN pagos p ON i.id_inscripcion = p.id_inscripcion
    WHERE e.id_evento = p_evento_id
    GROUP BY e.id_evento;
END$$

DROP PROCEDURE IF EXISTS sp_historial_participante$$
CREATE PROCEDURE sp_historial_participante(IN p_participante_id INT)
BEGIN
    SELECT e.nombre_evento, e.fecha_inicio, e.fecha_fin,
           i.estado_inscripcion,
           IFNULL(pag.estado_pago, 'Sin pago') as estado_pago,
           c.codigo_validacion as certificado
    FROM inscripciones i
    INNER JOIN eventos e ON i.id_evento = e.id_evento
    LEFT JOIN pagos pag ON i.id_inscripcion = pag.id_inscripcion
    LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
    WHERE i.id_participante = p_participante_id
    ORDER BY e.fecha_inicio DESC;
END$$

DROP PROCEDURE IF EXISTS sp_marcar_asistencia_masiva$$
CREATE PROCEDURE sp_marcar_asistencia_masiva(IN p_sesion_id INT, IN p_participantes_ids TEXT)
BEGIN
    DECLARE v_participant_id INT;
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_next_pos INT;
    DECLARE v_str_length INT;
    
    SET v_str_length = CHAR_LENGTH(p_participantes_ids);
    
    WHILE v_pos <= v_str_length DO
        SET v_next_pos = LOCATE(',', p_participantes_ids, v_pos);
        IF v_next_pos = 0 THEN SET v_next_pos = v_str_length + 1; END IF;
        
        SET v_participant_id = CAST(SUBSTRING(p_participantes_ids, v_pos, v_next_pos - v_pos) AS UNSIGNED);
        INSERT IGNORE INTO asistencias (id_sesion, id_participante, estado)
        VALUES (p_sesion_id, v_participant_id, 'presente');
        
        SET v_pos = v_next_pos + 1;
    END WHILE;
END$$

-- =====================================================
-- PASO 3: FUNCTIONS
-- =====================================================

DROP FUNCTION IF EXISTS fn_total_recaudado_evento$$
CREATE FUNCTION fn_total_recaudado_evento(p_evento_id INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE v_total DECIMAL(10,2);
    SELECT IFNULL(SUM(p.monto), 0) INTO v_total
    FROM pagos p
    INNER JOIN inscripciones i ON p.id_inscripcion = i.id_inscripcion
    WHERE i.id_evento = p_evento_id AND p.estado_pago = 'aprobado';
    RETURN v_total;
END$$

DROP FUNCTION IF EXISTS fn_porcentaje_asistencia_participante$$
CREATE FUNCTION fn_porcentaje_asistencia_participante(p_participante_id INT, p_evento_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE v_total_sesiones INT;
    DECLARE v_sesiones_asistidas INT;
    
    SELECT COUNT(*) INTO v_total_sesiones FROM sesiones WHERE id_evento = p_evento_id;
    SELECT COUNT(*) INTO v_sesiones_asistidas
    FROM asistencias a INNER JOIN sesiones s ON a.id_sesion = s.id_sesion
    WHERE a.id_participante = p_participante_id AND s.id_evento = p_evento_id;
    
    RETURN IF(v_total_sesiones > 0, (v_sesiones_asistidas / v_total_sesiones) * 100, 0);
END$$

DROP FUNCTION IF EXISTS fn_califica_certificado$$
CREATE FUNCTION fn_califica_certificado(p_participante_id INT, p_evento_id INT)
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE v_porcentaje DECIMAL(5,2);
    DECLARE v_pago_aprobado BOOLEAN;
    
    SET v_porcentaje = fn_porcentaje_asistencia_participante(p_participante_id, p_evento_id);
    SELECT EXISTS(
        SELECT 1 FROM pagos p
        INNER JOIN inscripciones i ON p.id_inscripcion = i.id_inscripcion
        WHERE i.id_participante = p_participante_id AND i.id_evento = p_evento_id AND p.estado_pago = 'aprobado'
    ) INTO v_pago_aprobado;
    
    RETURN (v_porcentaje >= 80 AND v_pago_aprobado);
END$$

DROP FUNCTION IF EXISTS fn_siguiente_sesion_evento$$
CREATE FUNCTION fn_siguiente_sesion_evento(p_evento_id INT)
RETURNS VARCHAR(200)
DETERMINISTIC
BEGIN
    DECLARE v_nombre_sesion VARCHAR(200);
    SELECT nombre_sesion INTO v_nombre_sesion
    FROM sesiones
    WHERE id_evento = p_evento_id AND CONCAT(fecha, ' ', hora_inicio) > NOW()
    ORDER BY fecha, hora_inicio LIMIT 1;
    RETURN IFNULL(v_nombre_sesion, 'No hay sesiones próximas');
END$$

DROP FUNCTION IF EXISTS fn_eventos_activos$$
CREATE FUNCTION fn_eventos_activos()
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count FROM eventos WHERE estado = 'activo';
    RETURN v_count;
END$$

DROP FUNCTION IF EXISTS fn_espacios_disponibles$$
CREATE FUNCTION fn_espacios_disponibles(p_evento_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_capacidad INT;
    DECLARE v_inscritos INT;
    
    SELECT capacidad_maxima INTO v_capacidad FROM eventos WHERE id_evento = p_evento_id;
    IF v_capacidad IS NULL THEN RETURN 9999; END IF;
    
    SELECT COUNT(*) INTO v_inscritos
    FROM inscripciones WHERE id_evento = p_evento_id AND estado_inscripcion != 'cancelada';
    
    RETURN IF((v_capacidad - v_inscritos) < 0, 0, (v_capacidad - v_inscritos));
END$$

DROP FUNCTION IF EXISTS fn_total_eventos_participante$$
CREATE FUNCTION fn_total_eventos_participante(p_participante_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count
    FROM inscripciones WHERE id_participante = p_participante_id AND estado_inscripcion = 'confirmada';
    RETURN v_count;
END$$

DROP FUNCTION IF EXISTS fn_dias_hasta_evento$$
CREATE FUNCTION fn_dias_hasta_evento(p_evento_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_dias INT;
    SELECT DATEDIFF(fecha_inicio, CURDATE()) INTO v_dias FROM eventos WHERE id_evento = p_evento_id;
    RETURN IFNULL(v_dias, -1);
END$$

DELIMITER ;

-- =====================================================
-- PASO 4: TRIGGERS
-- =====================================================

DELIMITER $$

DROP TRIGGER IF EXISTS trg_validar_capacidad_inscripcion$$
CREATE TRIGGER trg_validar_capacidad_inscripcion
BEFORE INSERT ON inscripciones FOR EACH ROW
BEGIN
    DECLARE v_capacidad INT;
    DECLARE v_inscritos INT;
    
    SELECT capacidad_maxima INTO v_capacidad FROM eventos WHERE id_evento = NEW.id_evento;
    IF v_capacidad IS NOT NULL THEN
        SELECT COUNT(*) INTO v_inscritos
        FROM inscripciones WHERE id_evento = NEW.id_evento AND estado_inscripcion != 'cancelada';
        
        IF v_inscritos >= v_capacidad THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Capacidad máxima alcanzada';
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_evitar_inscripcion_duplicada$$
CREATE TRIGGER trg_evitar_inscripcion_duplicada
BEFORE INSERT ON inscripciones FOR EACH ROW
BEGIN
    DECLARE v_existe INT;
    SELECT COUNT(*) INTO v_existe
    FROM inscripciones
    WHERE id_evento = NEW.id_evento AND id_participante = NEW.id_participante AND estado_inscripcion != 'cancelada';
    
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Participante ya inscrito';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_validar_monto_pago$$
CREATE TRIGGER trg_validar_monto_pago
BEFORE INSERT ON pagos FOR EACH ROW
BEGIN
    IF NEW.monto <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Monto inválido';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_validar_asistencia_inscripcion$$
CREATE TRIGGER trg_validar_asistencia_inscripcion
BEFORE INSERT ON asistencias FOR EACH ROW
BEGIN
    DECLARE v_inscrito INT;
    DECLARE v_evento_id INT;
    
    SELECT id_evento INTO v_evento_id FROM sesiones WHERE id_sesion = NEW.id_sesion;
    SELECT COUNT(*) INTO v_inscrito
    FROM inscripciones
    WHERE id_evento = v_evento_id AND id_participante = NEW.id_participante AND estado_inscripcion = 'confirmada';
    
    IF v_inscrito = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Participante no inscrito';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_actualizar_inscripcion_pago$$
CREATE TRIGGER trg_actualizar_inscripcion_pago
AFTER UPDATE ON pagos FOR EACH ROW
BEGIN
    IF NEW.estado_pago = 'aprobado' AND OLD.estado_pago != 'aprobado' THEN
        UPDATE inscripciones SET estado_inscripcion = 'confirmada' WHERE id_inscripcion = NEW.id_inscripcion;
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_auditoria_usuarios_insert$$
CREATE TRIGGER trg_auditoria_usuarios_insert
AFTER INSERT ON usuarios FOR EACH ROW
BEGIN
    INSERT INTO auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles)
    VALUES (NEW.id_usuario, 'INSERT', 'usuarios', NEW.id_usuario, CONCAT('Usuario creado: ', NEW.email));
END$$

DROP TRIGGER IF EXISTS trg_auditoria_usuarios_update$$
CREATE TRIGGER trg_auditoria_usuarios_update
AFTER UPDATE ON usuarios FOR EACH ROW
BEGIN
    INSERT INTO auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles)
    VALUES (NEW.id_usuario, 'UPDATE', 'usuarios', NEW.id_usuario, 
            CONCAT('Estado: ', OLD.estado, '->', NEW.estado));
END$$

DROP TRIGGER IF EXISTS trg_actualizar_estado_evento$$
CREATE TRIGGER trg_actualizar_estado_evento
BEFORE UPDATE ON eventos FOR EACH ROW
BEGIN
    IF NEW.fecha_fin < CURDATE() AND OLD.estado = 'activo' THEN
        SET NEW.estado = 'finalizado';
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- PASO 5: VIEWS
-- =====================================================

CREATE OR REPLACE VIEW vista_eventos_resumen AS
SELECT 
    e.id_evento, e.nombre_evento, e.fecha_inicio, e.fecha_fin,
    e.lugar, e.capacidad_maxima, e.estado,
    u.nombre_completo as creador,
    COUNT(DISTINCT i.id_inscripcion) as total_inscripciones,
    COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'confirmada' THEN i.id_inscripcion END) as inscripciones_confirmadas,
    COUNT(DISTINCT s.id_sesion) as total_sesiones,
   fn_total_recaudado_evento(e.id_evento) as monto_recaudado,
    fn_espacios_disponibles(e.id_evento) as espacios_disponibles
FROM eventos e
INNER JOIN usuarios u ON e.creado_por = u.id_usuario
LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
LEFT JOIN sesiones s ON e.id_evento = s.id_evento
GROUP BY e.id_evento;

CREATE OR REPLACE VIEW vista_participantes_actividad AS
SELECT 
    p.id_participante, p.dni, p.nombres, p.apellidos, p.email,
    p.institucion, p.tipo_participante,
    fn_total_eventos_participante(p.id_participante) as eventos_inscritos,
    COUNT(DISTINCT a.id_asistencia) as sesiones_asistidas,
    COUNT(DISTINCT c.id_certificado) as certificados_obtenidos
FROM participantes p
LEFT JOIN inscripciones i ON p.id_participante = i.id_participante
LEFT JOIN asistencias a ON p.id_participante = a.id_participante
LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
GROUP BY p.id_participante;

CREATE OR REPLACE VIEW vista_pagos_pendientes AS
SELECT 
    pag.id_pago, pag.monto, pag.fecha_pago, pag.metodo_pago,
    p.nombres, p.apellidos, p.email,
    e.nombre_evento,
    DATEDIFF(CURDATE(), pag.fecha_pago) as dias_pendiente
FROM pagos pag
INNER JOIN inscripciones i ON pag.id_inscripcion = i.id_inscripcion
INNER JOIN participantes p ON i.id_participante = p.id_participante
INNER JOIN eventos e ON i.id_evento = e.id_evento
WHERE pag.estado_pago = 'pendiente'
ORDER BY pag.fecha_pago;

CREATE OR REPLACE VIEW vista_certificados_pendientes AS
SELECT 
    i.id_inscripcion, p.nombres, p.apellidos, p.email, e.nombre_evento,
    fn_porcentaje_asistencia_participante(p.id_participante, e.id_evento) as porcentaje_asistencia,
    fn_califica_certificado(p.id_participante, e.id_evento) as califica
FROM inscripciones i
INNER JOIN participantes p ON i.id_participante = p.id_participante
INNER JOIN eventos e ON i.id_evento = e.id_evento
WHERE i.estado_inscripcion = 'confirmada'
  AND NOT EXISTS (SELECT 1 FROM certificados WHERE id_inscripcion = i.id_inscripcion)
HAVING califica = 1;

-- =====================================================
-- INSTALACIÓN COMPLETA
-- =====================================================

SELECT '✅ BASE DE DATOS INSTALADA CORRECTAMENTE' as RESULTADO;
SELECT '👤 Usuario: admin@evento.com / Password: admin123' as ACCESO;
