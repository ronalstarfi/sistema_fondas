USE sistema_fondas;

DROP TRIGGER IF EXISTS trg_solicitud_insert;
DROP TRIGGER IF EXISTS trg_solicitud_update;
DROP TRIGGER IF EXISTS trg_solicitud_delete;

DELIMITER //

CREATE TRIGGER trg_solicitud_insert AFTER INSERT ON solicitud FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Creación de Ticket (Nivel BD)', CONCAT('Ticket #', NEW.id, ' registrado en BD. Estatus: ', NEW.estatus), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
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
    
    IF IFNULL(OLD.marca_id, 0) != IFNULL(NEW.marca_id, 0) THEN
        SET cambios = CONCAT(cambios, 'Marca ID: ', IFNULL(OLD.marca_id, 'N/A'), ' -> ', IFNULL(NEW.marca_id, 'N/A'), '. ');
    END IF;
    
    IF IFNULL(OLD.tsolicitud, 0) != IFNULL(NEW.tsolicitud, 0) THEN
        SET cambios = CONCAT(cambios, 'Tipo de Solicitud modificada. ');
    END IF;

    IF cambios != '' THEN
        INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
        VALUES ('Actualización de Ticket (Nivel BD)', CONCAT('Ticket #', OLD.id, ' modificado. Detalles: ', cambios), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
    END IF;
END//

CREATE TRIGGER trg_solicitud_delete AFTER DELETE ON solicitud FOR EACH ROW
BEGIN
    INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip)
    VALUES ('Eliminación Crítica (Nivel BD)', CONCAT('Ticket #', OLD.id, ' fue ELIMINADO de la base de datos.'), IFNULL(@app_user, USER()), IFNULL(@app_ip, 'N/A'));
END//

DELIMITER ;
