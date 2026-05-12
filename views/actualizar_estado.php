<?php
/**
 * SISTEMA FONDAS - Gestión de Estatus y Asistencia
 * Archivo: actualizar_estado.php
 */

// 1. Configuración de errores y zona horaria
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Caracas');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    // 2. Recepción de datos del formulario (Sincronizado con control_personal.php)
    $id = $_POST['id_especialista'] ?? null;
    $estado = $_POST['disponibilidad'] ?? null; 
    $motivo = $_POST['motivo_permiso'] ?? '';
    
    // Captura de rangos de fechas (Desde / Hasta)
    $f_inicio = !empty($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
    $f_fin = !empty($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;

    if ($id && $estado) {
        try {
            $db->beginTransaction();

            // 3. Lógica para la HORA DE SALIDA
            // Si NO es 'Activo' o 'Disponible', guardamos la hora actual. 
            // Si vuelve a estar Activo, limpiamos la columna (NULL).
            $sql_hora = ($estado !== 'Activo' && $estado !== 'Disponible') ? "NOW()" : "NULL";

            // 4. ACTUALIZAR TABLA ESPECIALISTA
            // Actualizamos visual (disponibilidad), técnico (estado_asistencia) y la hora.
            $sql_update = "UPDATE especialista SET 
                           disponibilidad = :est, 
                           estado_asistencia = :est, 
                           motivo_permiso = :mot,
                           hora_salida_permiso = $sql_hora 
                           WHERE id = :id";
            
            $stmt = $db->prepare($sql_update);
            $stmt->execute([
                ':est' => $estado,
                ':mot' => $motivo,
                ':id'  => $id
            ]);

            // 5. INSERTAR EN BITÁCORA (asistencia_historico)
            // Aquí guardamos el historial con el rango de fechas completo
            $sql_bitacora = "INSERT INTO asistencia_historico 
                             (id_especialista, tipo_movimiento, fecha, hora, fecha_inicio, fecha_fin_permiso, detalle) 
                             VALUES (:id, :mov, CURDATE(), CURTIME(), :f_ini, :f_fin, :det)";
            
            $stmt_b = $db->prepare($sql_bitacora);
            $stmt_b->execute([
                ':id'    => $id,
                ':mov'   => $estado,
                ':f_ini' => $f_inicio,
                ':f_fin' => $f_fin, // Se guarda en la nueva columna de la bitácora
                ':det'   => $motivo
            ]);

            $db->commit();
            
            // 6. Redirección con éxito
            header("Location: control_personal.php?status=success");
            exit();

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            die("Error crítico en la base de datos: " . $e->getMessage());
        }
    } else {
        die("Error: El sistema no recibió el ID o el Estatus correctamente.");
    }
} else {
    // Si intentan entrar al archivo directamente sin POST
    header("Location: control_personal.php");
    exit();
}