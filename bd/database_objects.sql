-- =====================================================
-- STORED PROCEDURES, FUNCTIONS, TRIGGERS, CURSORS, VIEWS
-- Database Objects for Academic Event Management System
-- =====================================================

USE evento_academico;

-- =====================================================
-- STORED PROCEDURES (Procedimientos Almacenados)
-- =====================================================

-- Procedure 1: Register complete participant with inscription
DELIMITER $$
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
    
    -- Insert participant
    INSERT INTO participantes (dni, nombres, apellidos, email, telefono, institucion, tipo_participante)
    VALUES (p_dni, p_nombres, p_apellidos, p_email, p_telefono, p_institucion, p_tipo);
    
    SET p_participante_id = LAST_INSERT_ID();
    
    -- Create inscription
    INSERT INTO inscripciones (id_evento, id_participante, estado_inscripcion)
    VALUES (p_evento_id, p_participante_id, 'confirmada');
    
    SET p_inscripcion_id = LAST_INSERT_ID();
    
    COMMIT;
END$$
DELIMITER ;

-- Procedure 2: Generate certificates for event
DELIMITER $$
CREATE PROCEDURE sp_generar_certificados_evento(IN p_evento_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_inscripcion_id INT;
    DECLARE v_participante_nombre VARCHAR(200);
    DECLARE v_codigo VARCHAR(50);
    
    DECLARE cur_inscripciones CURSOR FOR
        SELECT i.id_inscripcion, CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        WHERE i.id_evento = p_evento_id 
          AND i.estado_inscripcion = 'confirmada'
          AND NOT EXISTS (SELECT 1 FROM certificados WHERE id_inscripcion = i.id_inscripcion);
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_inscripciones;
    
    read_loop: LOOP
        FETCH cur_inscripciones INTO v_inscripcion_id, v_participante_nombre;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Generate unique code
        SET v_codigo = CONCAT('CERT-', p_evento_id, '-', v_inscripcion_id, '-', UNIX_TIMESTAMP());
        
        -- Insert certificate
        INSERT INTO certificados (id_inscripcion, codigo_validacion, fecha_emision)
        VALUES (v_inscripcion_id, v_codigo, CURDATE());
    END LOOP;
    
    CLOSE cur_inscripciones;
    
    SELECT CONCAT('Certificados generados: ', ROW_COUNT()) as resultado;
END$$
DELIMITER ;

-- Procedure 3: Update event status based on dates
DELIMITER $$
CREATE PROCEDURE sp_actualizar_estados_eventos()
BEGIN
    -- Mark as finished if date has passed
    UPDATE eventos
    SET estado = 'finalizado'
    WHERE fecha_fin < CURDATE() AND estado = 'activo';
    
    SELECT ROW_COUNT() as eventos_finalizados;
END$$
DELIMITER ;

-- Procedure 4: Get attendance statistics
DELIMITER $$
CREATE PROCEDURE sp_estadisticas_asistencia(IN p_evento_id INT)
BEGIN
    SELECT 
        e.nombre_evento,
        s.nombre_sesion,
        s.fecha,
        COUNT(DISTINCT i.id_participante) as total_inscritos,
        COUNT(DISTINCT a.id_participante) as total_asistentes,
        ROUND((COUNT(DISTINCT a.id_participante) / COUNT(DISTINCT i.id_participante) * 100), 2) as porcentaje_asistencia
    FROM eventos e
    INNER JOIN sesiones s ON e.id_evento = s.id_evento
    LEFT JOIN inscripciones i ON e.id_evento = i.id_evento AND i.estado_inscripcion = 'confirmada'
    LEFT JOIN asistencias a ON s.id_sesion = a.id_sesion
    WHERE e.id_evento = p_evento_id
    GROUP BY s.id_sesion
    ORDER BY s.fecha, s.hora_inicio;
END$$
DELIMITER ;

-- Procedure 5: Cancel inscription and related data
DELIMITER $$
CREATE PROCEDURE sp_cancelar_inscripcion(IN p_inscripcion_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error al cancelar inscripción' as resultado;
    END;
    
    START TRANSACTION;
    
    -- Update inscription status
    UPDATE inscripciones SET estado_inscripcion = 'cancelada' WHERE id_inscripcion = p_inscripcion_id;
    
    -- Cancel related payments
    UPDATE pagos SET estado_pago = 'rechazado' WHERE id_inscripcion = p_inscripcion_id AND estado_pago = 'pendiente';
    
    COMMIT;
    SELECT 'Inscripción cancelada exitosamente' as resultado;
END$$
DELIMITER ;

-- Procedure 6: Payment summary by event
DELIMITER $$
CREATE PROCEDURE sp_resumen_pagos_evento(IN p_evento_id INT)
BEGIN
    SELECT 
        e.nombre_evento,
        COUNT(DISTINCT p.id_pago) as total_pagos,
        SUM(CASE WHEN p.estado_pago = 'aprobado' THEN 1 ELSE 0 END) as pagos_aprobados,
        SUM(CASE WHEN p.estado_pago = 'pendiente' THEN 1 ELSE 0 END) as pagos_pendientes,
        SUM(CASE WHEN p.estado_pago = 'aprobado' THEN p.monto ELSE 0 END) as monto_total_aprobado,
        SUM(CASE WHEN p.estado_pago = 'pendiente' THEN p.monto ELSE 0 END) as monto_pendiente
    FROM eventos e
    LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
    LEFT JOIN pagos p ON i.id_inscripcion = p.id_inscripcion
    WHERE e.id_evento = p_evento_id
    GROUP BY e.id_evento;
END$$
DELIMITER ;

-- Procedure 7: Get participant history
DELIMITER $$
CREATE PROCEDURE sp_historial_participante(IN p_participante_id INT)
BEGIN
    SELECT 
        e.nombre_evento,
        e.fecha_inicio,
        e.fecha_fin,
        i.estado_inscripcion,
        IFNULL(pag.estado_pago, 'Sin pago') as estado_pago,
        (SELECT COUNT(*) FROM asistencias a 
         INNER JOIN sesiones s ON a.id_sesion = s.id_sesion 
         WHERE s.id_evento = e.id_evento AND a.id_participante = p_participante_id) as sesiones_asistidas,
        (SELECT COUNT(*) FROM sesiones WHERE id_evento = e.id_evento) as total_sesiones,
        c.codigo_validacion as certificado
    FROM inscripciones i
    INNER JOIN eventos e ON i.id_evento = e.id_evento
    LEFT JOIN pagos pag ON i.id_inscripcion = pag.id_inscripcion
    LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
    WHERE i.id_participante = p_participante_id
    ORDER BY e.fecha_inicio DESC;
END$$
DELIMITER ;

-- Procedure 8: Bulk attendance marking
DELIMITER $$
CREATE PROCEDURE sp_marcar_asistencia_masiva(
    IN p_sesion_id INT,
    IN p_participantes_ids TEXT
)
BEGIN
    DECLARE v_participant_id INT;
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_next_pos INT;
    DECLARE v_str_length INT;
    
    SET v_str_length = CHAR_LENGTH(p_participantes_ids);
    
    WHILE v_pos <= v_str_length DO
        SET v_next_pos = LOCATE(',', p_participantes_ids, v_pos);
        
        IF v_next_pos = 0 THEN
            SET v_next_pos = v_str_length + 1;
        END IF;
        
        SET v_participant_id = CAST(SUBSTRING(p_participantes_ids, v_pos, v_next_pos - v_pos) AS UNSIGNED);
        
        -- Insert if not exists
        INSERT IGNORE INTO asistencias (id_sesion, id_participante, estado)
        VALUES (p_sesion_id, v_participant_id, 'presente');
        
        SET v_pos = v_next_pos + 1;
    END WHILE;
    
    SELECT CONCAT('Asistencias registradas: ', ROW_COUNT()) as resultado;
END$$
DELIMITER ;

-- =====================================================
-- FUNCTIONS (Funciones Almacenadas)
-- =====================================================

-- Function 1: Calculate total payments for an event
DELIMITER $$
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
DELIMITER ;

-- Function 2: Calculate attendance percentage
DELIMITER $$
CREATE FUNCTION fn_porcentaje_asistencia_participante(p_participante_id INT, p_evento_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE v_total_sesiones INT;
    DECLARE v_sesiones_asistidas INT;
    DECLARE v_porcentaje DECIMAL(5,2);
    
    SELECT COUNT(*) INTO v_total_sesiones
    FROM sesiones
    WHERE id_evento = p_evento_id;
    
    SELECT COUNT(*) INTO v_sesiones_asistidas
    FROM asistencias a
    INNER JOIN sesiones s ON a.id_sesion = s.id_sesion
    WHERE a.id_participante = p_participante_id AND s.id_evento = p_evento_id;
    
    IF v_total_sesiones > 0 THEN
        SET v_porcentaje = (v_sesiones_asistidas / v_total_sesiones) * 100;
    ELSE
        SET v_porcentaje = 0;
    END IF;
    
    RETURN v_porcentaje;
END$$
DELIMITER ;

-- Function 3: Check if participant qualifies for certificate
DELIMITER $$
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
        WHERE i.id_participante = p_participante_id 
          AND i.id_evento = p_evento_id 
          AND p.estado_pago = 'aprobado'
    ) INTO v_pago_aprobado;
    
    -- Qualifies if attended > 80% and payment approved
    RETURN (v_porcentaje >= 80 AND v_pago_aprobado);
END$$
DELIMITER ;

-- Function 4: Get next session for event
DELIMITER $$
CREATE FUNCTION fn_siguiente_sesion_evento(p_evento_id INT)
RETURNS VARCHAR(200)
DETERMINISTIC
BEGIN
    DECLARE v_nombre_sesion VARCHAR(200);
    
    SELECT nombre_sesion INTO v_nombre_sesion
    FROM sesiones
    WHERE id_evento = p_evento_id 
      AND CONCAT(fecha, ' ', hora_inicio) > NOW()
    ORDER BY fecha, hora_inicio
    LIMIT 1;
    
    RETURN IFNULL(v_nombre_sesion, 'No hay sesiones próximas');
END$$
DELIMITER ;

-- Function 5: Count active events
DELIMITER $$
CREATE FUNCTION fn_eventos_activos()
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count FROM eventos WHERE estado = 'activo';
    RETURN v_count;
END$$
DELIMITER ;

-- Function 6: Get available capacity for event
DELIMITER $$
CREATE FUNCTION fn_espacios_disponibles(p_evento_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_capacidad INT;
    DECLARE v_inscritos INT;
    DECLARE v_disponibles INT;
    
    SELECT capacidad_maxima INTO v_capacidad
    FROM eventos
    WHERE id_evento = p_evento_id;
    
    IF v_capacidad IS NULL THEN
        RETURN 9999; -- Unlimited
    END IF;
    
    SELECT COUNT(*) INTO v_inscritos
    FROM inscripciones
    WHERE id_evento = p_evento_id AND estado_inscripcion != 'cancelada';
    
    SET v_disponibles = v_capacidad - v_inscritos;
    
    RETURN IF(v_disponibles < 0, 0, v_disponibles);
END$$
DELIMITER ;

-- Function 7: Get participant's total events
DELIMITER $$
CREATE FUNCTION fn_total_eventos_participante(p_participante_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    
    SELECT COUNT(*) INTO v_count
    FROM inscripciones
    WHERE id_participante = p_participante_id AND estado_inscripcion = 'confirmada';
    
    RETURN v_count;
END$$
DELIMITER ;

-- Function 8: Calculate days until event
DELIMITER $$
CREATE FUNCTION fn_dias_hasta_evento(p_evento_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_dias INT;
    
    SELECT DATEDIFF(fecha_inicio, CURDATE()) INTO v_dias
    FROM eventos
    WHERE id_evento = p_evento_id;
    
    RETURN IFNULL(v_dias, -1);
END$$
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger 1: Validate capacity before inscription
DELIMITER $$
CREATE TRIGGER trg_validar_capacidad_inscripcion
BEFORE INSERT ON inscripciones
FOR EACH ROW
BEGIN
    DECLARE v_capacidad INT;
    DECLARE v_inscritos INT;
    
    SELECT capacidad_maxima INTO v_capacidad
    FROM eventos WHERE id_evento = NEW.id_evento;
    
    IF v_capacidad IS NOT NULL THEN
        SELECT COUNT(*) INTO v_inscritos
        FROM inscripciones
        WHERE id_evento = NEW.id_evento AND estado_inscripcion != 'cancelada';
        
        IF v_inscritos >= v_capacidad THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Evento ha alcanzado su capacidad máxima';
        END IF;
    END IF;
END$$
DELIMITER ;

-- Trigger 2: Auto-update event status
DELIMITER $$
CREATE TRIGGER trg_actualizar_estado_evento
BEFORE UPDATE ON eventos
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin < CURDATE() AND OLD.estado = 'activo' THEN
        SET NEW.estado = 'finalizado';
    END IF;
END$$
DELIMITER ;

-- Trigger 3: Prevent duplicate inscriptions
DELIMITER $$
CREATE TRIGGER trg_evitar_inscripcion_duplicada
BEFORE INSERT ON inscripciones
FOR EACH ROW
BEGIN
    DECLARE v_existe INT;
    
    SELECT COUNT(*) INTO v_existe
    FROM inscripciones
    WHERE id_evento = NEW.id_evento 
      AND id_participante = NEW.id_participante
      AND estado_inscripcion != 'cancelada';
    
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El participante ya está inscrito en este evento';
    END IF;
END$$
DELIMITER ;

-- Trigger 4: Automatic audit on user changes
DELIMITER $$
CREATE TRIGGER trg_auditoria_usuarios_insert
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles)
    VALUES (NEW.id_usuario, 'INSERT', 'usuarios', NEW.id_usuario, CONCAT('Usuario creado: ', NEW.email));
END$$
DELIMITER ;

-- Trigger 5: Audit on user updates
DELIMITER $$
CREATE TRIGGER trg_auditoria_usuarios_update
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles)
    VALUES (NEW.id_usuario, 'UPDATE', 'usuarios', NEW.id_usuario, 
            CONCAT('Usuario modificado. Estado: ', OLD.estado, ' -> ', NEW.estado));
END$$
DELIMITER ;

-- Trigger 6: Validate payment amount
DELIMITER $$
CREATE TRIGGER trg_validar_monto_pago
BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
    IF NEW.monto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del pago debe ser mayor a 0';
    END IF;
END$$
DELIMITER ;

-- Trigger 7: Prevent attendance without inscription
DELIMITER $$
CREATE TRIGGER trg_validar_asistencia_inscripcion
BEFORE INSERT ON asistencias
FOR EACH ROW
BEGIN
    DECLARE v_inscrito INT;
    DECLARE v_evento_id INT;
    
    SELECT id_evento INTO v_evento_id FROM sesiones WHERE id_sesion = NEW.id_sesion;
    
    SELECT COUNT(*) INTO v_inscrito
    FROM inscripciones
    WHERE id_evento = v_evento_id 
      AND id_participante = NEW.id_participante
      AND estado_inscripcion = 'confirmada';
    
    IF v_inscrito = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El participante no está inscrito en este evento';
    END IF;
END$$
DELIMITER ;

-- Trigger 8: Update inscription status on payment approval
DELIMITER $$
CREATE TRIGGER trg_actualizar_inscripcion_pago
AFTER UPDATE ON pagos
FOR EACH ROW
BEGIN
    IF NEW.estado_pago = 'aprobado' AND OLD.estado_pago != 'aprobado' THEN
        UPDATE inscripciones 
        SET estado_inscripcion = 'confirmada'
        WHERE id_inscripcion = NEW.id_inscripcion;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- VIEWS (Vistas)
-- =====================================================

-- View 1: Event summary with statistics
CREATE OR REPLACE VIEW vista_eventos_resumen AS
SELECT 
    e.id_evento,
    e.nombre_evento,
    e.fecha_inicio,
    e.fecha_fin,
    e.lugar,
    e.capacidad_maxima,
    e.estado,
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

-- View 2: Participants with their activity
CREATE OR REPLACE VIEW vista_participantes_actividad AS
SELECT 
    p.id_participante,
    p.dni,
    p.nombres,
    p.apellidos,
    p.email,
    p.institucion,
    p.tipo_participante,
    fn_total_eventos_participante(p.id_participante) as eventos_inscritos,
    COUNT(DISTINCT a.id_asistencia) as sesiones_asistidas,
    COUNT(DISTINCT c.id_certificado) as certificados_obtenidos
FROM participantes p
LEFT JOIN inscripciones i ON p.id_participante = i.id_participante
LEFT JOIN asistencias a ON p.id_participante = a.id_participante
LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
GROUP BY p.id_participante;

-- View 3: Payments pending approval
CREATE OR REPLACE VIEW vista_pagos_pendientes AS
SELECT 
    pag.id_pago,
    pag.monto,
    pag.fecha_pago,
    pag.metodo_pago,
    p.nombres,
    p.apellidos,
    p.email,
    e.nombre_evento,
    DATEDIFF(CURDATE(), pag.fecha_pago) as dias_pendiente
FROM pagos pag
INNER JOIN inscripciones i ON pag.id_inscripcion = i.id_inscripcion
INNER JOIN participantes p ON i.id_participante = p.id_participante
INNER JOIN eventos e ON i.id_evento = e.id_evento
WHERE pag.estado_pago = 'pendiente'
ORDER BY pag.fecha_pago;

-- View 4: Certificates eligible
CREATE OR REPLACE VIEW vista_certificados_pendientes AS
SELECT 
    i.id_inscripcion,
    p.nombres,
    p.apellidos,
    p.email,
    e.nombre_evento,
    fn_porcentaje_asistencia_participante(p.id_participante, e.id_evento) as porcentaje_asistencia,
    fn_califica_certificado(p.id_participante, e.id_evento) as califica
FROM inscripciones i
INNER JOIN participantes p ON i.id_participante = p.id_participante
INNER JOIN eventos e ON i.id_evento = e.id_evento
WHERE i.estado_inscripcion = 'confirmada'
  AND NOT EXISTS (SELECT 1 FROM certificados WHERE id_inscripcion = i.id_inscripcion)
HAVING califica = 1;

COMMIT;
