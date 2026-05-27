<?php
session_start();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$rol = $_SESSION['rol'] ?? '';
$esEspecialista = $rol === 'Especialista';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['ci'];
    $area = $_POST['area_problema'];
    $id_tipo = $_POST['id_tipo'];
    $id_marca_seleccionada = $_POST['id_marca']; 
    $descripcion = $_POST['descripcion'];
    $fecha = date('Y-m-d H:i:s');

    try {
        $db->beginTransaction();

        // 1. Asignación automática: elegir el técnico activo con menos tickets en el área correspondiente.
        // No se asignan tickets automáticamente a Gerente, Coordinadora ni Analista.
        $areaMap = [
            'Soporte' => 'Soporte Técnico',
            'Soporte Técnico' => 'Soporte Técnico',
            'Infraestructura' => 'Infraestructura',
            'Desarrollo' => 'Desarrollo',
            'Impresoras y Toner' => 'Impresoras y Toner',
            'SIGA' => 'Analista Funcional'
        ];

        if (!isset($areaMap[$area])) {
            throw new Exception('Área de problema no válida para la asignación automática.');
        }

        $areaEspecialidad = $areaMap[$area];

        // Buscar técnico activo con menor carga en el área de especialidad definida
        $query_tec = "SELECT id FROM especialista WHERE area_especifica = :area AND rol = 'Tecnico' AND disponibilidad = 'Activo' ORDER BY tickets_activos ASC, id ASC LIMIT 1";
        $stmt_tec = $db->prepare($query_tec);
        $stmt_tec->bindParam(':area', $areaEspecialidad);
        $stmt_tec->execute();
        $tecnico = $stmt_tec->fetch(PDO::FETCH_ASSOC);
        $tecnico_id = $tecnico ? $tecnico['id'] : null;

        // Si el ticket lo genera un especialista, puede ser asignado a sí mismo si pertenece a esa área y está activo
        if ($esEspecialista && isset($_SESSION['user_id'])) {
            $stmt_user_area = $db->prepare("SELECT area_especifica, disponibilidad FROM especialista WHERE id = :id LIMIT 1");
            $stmt_user_area->bindParam(':id', $_SESSION['user_id']);
            $stmt_user_area->execute();
            $userInfo = $stmt_user_area->fetch(PDO::FETCH_ASSOC);
            if ($userInfo && $userInfo['area_especifica'] === $areaEspecialidad && $userInfo['disponibilidad'] === 'Activo') {
                $tecnico_id = $_SESSION['user_id'];
            }
        }

        // 2. Insertamos la solicitud
        $sql = "INSERT INTO solicitud (ci, area_problema, tsolicitud, marca_id, descripcion, estatus, fechainicial, especialista_id) 
                VALUES (:ci, :area, :tipo, :marca_id, :desc, 'ABIERTO', :fecha, :tec_id)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':area', $area);
        $stmt->bindParam(':tipo', $id_tipo);
        $stmt->bindParam(':marca_id', $id_marca_seleccionada);
        $stmt->bindParam(':desc', $descripcion);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':tec_id', $tecnico_id);
        $stmt->execute();

        if ($tecnico_id) {
            $stmt_inc = $db->prepare("UPDATE especialista SET tickets_activos = tickets_activos + 1 WHERE id = :id");
            $stmt_inc->bindParam(':id', $tecnico_id);
            $stmt_inc->execute();
        }

        // 3. Obtenemos el ID del ticket recién creado
        $id_ticket_nuevo = $db->lastInsertId();

        // 4. Registrar en la bitácora de auditoría
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $usuario = $_SESSION['nombre'] ?? 'Solicitante Anónimo';
        $cedula = $_SESSION['user_id'] ?? $ci;
        $rol_usuario = $_SESSION['rol'] ?? 'Solicitante';

        $sql_audit = "INSERT INTO auditoria_solicitudes 
                     (id_solicitud, estatus_anterior, estatus_nuevo, usuario_que_cambio, cedula_usuario, rol_usuario, direccion_ip, user_agent) 
                     VALUES 
                     (:id_sol, 'N/A', 'ABIERTO', :usuario, :cedula, :rol, :ip, :ua)";
        
        $stmt_audit = $db->prepare($sql_audit);
        $stmt_audit->bindParam(':id_sol', $id_ticket_nuevo);
        $stmt_audit->bindParam(':usuario', $usuario);
        $stmt_audit->bindParam(':cedula', $cedula);
        $stmt_audit->bindParam(':rol', $rol_usuario);
        $stmt_audit->bindParam(':ip', $ip);
        $stmt_audit->bindParam(':ua', $user_agent);
        $stmt_audit->execute();

        $db->commit();

        // --- INICIO DE LA MEJORA VISUAL ---
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Éxito - FONDAS</title>
            <style>
                .mensaje-exito-fondo {
                    display: flex; justify-content: center; align-items: center; 
                    height: 100vh; background-color: #d1e2d4; 
                    font-family: 'Segoe UI', sans-serif; position: fixed; 
                    top: 0; left: 0; width: 100%; z-index: 9999;
                }
                .caja-mensaje {
                    background: white; padding: 40px; border-radius: 10px; 
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; 
                    max-width: 450px; width: 90%;
                }
                .icono-check {
                    width: 80px; height: 80px; background-color: #f0f9f1; 
                    border-radius: 50%; display: flex; justify-content: center; 
                    align-items: center; margin: 0 auto 20px; border: 2px solid #e1f0e4;
                }
                .btn-continuar {
                    background-color: #2e7d32; color: white; padding: 12px 30px; 
                    text-decoration: none; border-radius: 5px; font-weight: bold; 
                    display: inline-block; margin-top: 20px; transition: 0.3s;
                }
                .btn-continuar:hover { background-color: #1b5e20; }
            </style>
        </head>
        <body>
            <div class="mensaje-exito-fondo">
                <div class="caja-mensaje">
                    <div class="icono-check">
                        <span style="color: #28a745; font-size: 40px;">✔</span>
                    </div>
                    <h1 style="color: #333; margin-bottom: 10px; font-size: 24px;">¡Solicitud Registrada!</h1>
                    <p style="color: #666; margin-bottom: 20px; line-height: 1.5;">
                        Tu ticket <strong>#<?php echo $id_ticket_nuevo; ?></strong> ha sido generado con éxito en el sistema.
                    </p>
                    <a href="ver_tickets.php" class="btn-continuar">Ir a mis tickets</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit(); 
        // --- FIN DE LA MEJORA VISUAL ---

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error al registrar: " . $e->getMessage();
    }
}
?>