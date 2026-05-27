USE sistema_fondas;

-- Triggers for solicitud
DROP TRIGGER IF EXISTS trg_solicitud_insert;
DROP TRIGGER IF EXISTS trg_solicitud_update;
DROP TRIGGER IF EXISTS trg_solicitud_delete;

-- Triggers for especialista
DROP TRIGGER IF EXISTS trg_especialista_insert;
DROP TRIGGER IF EXISTS trg_especialista_update;
DROP TRIGGER IF EXISTS trg_especialista_delete;

-- Triggers for solicitante
DROP TRIGGER IF EXISTS trg_solicitante_insert;
DROP TRIGGER IF EXISTS trg_solicitante_update;
DROP TRIGGER IF EXISTS trg_solicitante_delete;

DELIMITER //

-- ==========================================
-- TRIGGERS PARA TABLA: SOLICITUD
-- ==========================================

CREATE TRIGGER trg_solicitud_insert AFTER INSERT ON solicitud FOR EACH ROW
BEGIN
    -- Auditoría Global
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Creación de Ticket (Nivel BD)', CONCAT('Ticket #', NEW.id, ' registrado en BD. Estatus: ', NEW.estatus), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
    
    -- Historial de Tickets (auditoria_solicitudes)
    INSERT INTO auditoria_solicitudes (id_solicitud, estatus_anterior, estatus_nuevo, usuario_que_cambio, cedula_usuario, rol_usuario, direccion_ip, user_agent) 
    VALUES (NEW.id, 'N/A', NEW.estatus, IFNULL(@app_user, USER()), IFNULL(@app_cedula, 'N/A'), IFNULL(@app_rol, 'DB/Sistema'), IFNULL(@app_ip, 'N/A'), IFNULL(@app_ua, 'DB Trigger'));
END//

CREATE TRIGGER trg_solicitud_update AFTER UPDATE ON solicitud FOR EACH ROW
BEGIN
    DECLARE cambios TEXT DEFAULT '';
    
    IF OLD.estatus != NEW.estatus THEN
        SET cambios = CONCAT(cambios, 'Estatus: ', OLD.estatus, ' -> ', NEW.estatus, '. ');
    END IF;
    IF IFNULL(OLD.especialista_id, 0) != IFNULL(NEW.especialista_id, 0) THEN
        SET cambios = CONCAT(cambios, 'Especialista ID: ', IFNULL(OLD.especialista_id, 'Ninguno'), ' -> ', IFNULL(NEW.especialista_id, 'Ninguno'), '. ');
    END IF;
    IF IFNULL(OLD.area_problema, '') != IFNULL(NEW.area_problema, '') THEN
        SET cambios = CONCAT(cambios, 'Área: ', IFNULL(OLD.area_problema, 'N/A'), ' -> ', IFNULL(NEW.area_problema, 'N/A'), '. ');
    END IF;
    IF IFNULL(OLD.descripcion, '') != IFNULL(NEW.descripcion, '') THEN
        SET cambios = CONCAT(cambios, 'Descripción modificada. ');
    END IF;
    
    IF cambios != '' THEN
        -- Auditoría Global
        INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
        VALUES ('Actualización de Ticket (Nivel BD)', CONCAT('Ticket #', OLD.id, ' modificado. Detalles: ', cambios), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
        
        -- Historial de Tickets (auditoria_solicitudes)
        INSERT INTO auditoria_solicitudes (id_solicitud, estatus_anterior, estatus_nuevo, usuario_que_cambio, cedula_usuario, rol_usuario, direccion_ip, user_agent) 
        VALUES (OLD.id, OLD.estatus, NEW.estatus, IFNULL(@app_user, USER()), IFNULL(@app_cedula, 'N/A'), IFNULL(@app_rol, 'DB/Sistema'), IFNULL(@app_ip, 'N/A'), IFNULL(@app_ua, CONCAT('DB Trigger: ', cambios)));
    END IF;
END//

CREATE TRIGGER trg_solicitud_delete AFTER DELETE ON solicitud FOR EACH ROW
BEGIN
    -- Auditoría Global
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Eliminación de Ticket (Nivel BD)', CONCAT('Ticket #', OLD.id, ' fue ELIMINADO de la base de datos.'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
    
    -- Historial de Tickets (auditoria_solicitudes)
    INSERT INTO auditoria_solicitudes (id_solicitud, estatus_anterior, estatus_nuevo, usuario_que_cambio, cedula_usuario, rol_usuario, direccion_ip, user_agent) 
    VALUES (OLD.id, OLD.estatus, 'ELIMINADO', IFNULL(@app_user, USER()), IFNULL(@app_cedula, 'N/A'), IFNULL(@app_rol, 'DB/Sistema'), IFNULL(@app_ip, 'N/A'), IFNULL(@app_ua, 'DB Trigger - Eliminación'));
END//

-- ==========================================
-- TRIGGERS PARA TABLA: ESPECIALISTA
-- ==========================================

CREATE TRIGGER trg_especialista_insert AFTER INSERT ON especialista FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Nuevo Especialista (Nivel BD)', CONCAT('Especialista creado: ', NEW.especialista, ' (CI: ', NEW.ci, ')'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

CREATE TRIGGER trg_especialista_update AFTER UPDATE ON especialista FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Actualización de Especialista (Nivel BD)', CONCAT('Datos modificados para el especialista CI: ', OLD.ci), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

CREATE TRIGGER trg_especialista_delete AFTER DELETE ON especialista FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Eliminación de Especialista (Nivel BD)', CONCAT('Especialista ELIMINADO: ', OLD.especialista, ' (CI: ', OLD.ci, ')'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

-- ==========================================
-- TRIGGERS PARA TABLA: SOLICITANTE
-- ==========================================

CREATE TRIGGER trg_solicitante_insert AFTER INSERT ON solicitante FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Nuevo Solicitante (Nivel BD)', CONCAT('Solicitante creado: ', NEW.nombre, ' (CI: ', NEW.ci, ')'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

CREATE TRIGGER trg_solicitante_update AFTER UPDATE ON solicitante FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Actualización de Solicitante (Nivel BD)', CONCAT('Datos modificados para el solicitante CI: ', OLD.ci), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

CREATE TRIGGER trg_solicitante_delete AFTER DELETE ON solicitante FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Eliminación de Solicitante (Nivel BD)', CONCAT('Solicitante ELIMINADO: ', OLD.nombre, ' (CI: ', OLD.ci, ')'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

DELIMITER ;
