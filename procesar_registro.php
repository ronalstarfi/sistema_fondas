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

        // 1. Asignación automática: elegir el técnico activo con menor carga en el área correspondiente.
        // No se asignan tickets automáticamente a Gerente, Coordinadora ni Analista.
        $allowedAreas = [
            'Soporte' => 'Soporte',
            'Infraestructura' => 'Infraestructura',
            'Desarrollo' => 'Desarrollo',
            'Impresoras y Toner' => 'Impresoras y Toner',
            'SIGA' => 'SIGA'
        ];

        if (!isset($allowedAreas[$area])) {
            throw new Exception('Área de problema no válida para la asignación automática.');
        }

        if ($area === 'SIGA') {
            $query_tec = "SELECT id FROM especialista WHERE especialista = 'Benjamin Acevedo' AND disponibilidad = 'Activo' LIMIT 1";
            $stmt_tec = $db->prepare($query_tec);
        } else {
            $query_tec = "SELECT id FROM especialista WHERE area_especifica = :area AND rol = 'Tecnico' AND disponibilidad = 'Activo' ORDER BY tickets_activos ASC, id ASC LIMIT 1";
            $stmt_tec = $db->prepare($query_tec);
            $stmt_tec->bindParam(':area', $area);
        }
        $stmt_tec->execute();
        $tecnico = $stmt_tec->fetch(PDO::FETCH_ASSOC);
        $tecnico_id = $tecnico ? $tecnico['id'] : null;
        // 1. Si el ticket lo genera un especialista, se asigna a sí mismo; en caso contrario, buscamos el técnico con menos carga en el área seleccionada
        if ($esEspecialista && isset($_SESSION['user_id'])) {
            $tecnico_id = $_SESSION['user_id'];
        } else {
            $query_tec = "SELECT id FROM especialista WHERE area_especifica = :area ORDER BY id ASC LIMIT 1";
            $stmt_tec = $db->prepare($query_tec);
            $stmt_tec->bindParam(':area', $area);
            $stmt_tec->execute();
            $tecnico = $stmt_tec->fetch(PDO::FETCH_ASSOC);
            $tecnico_id = $tecnico ? $tecnico['id'] : null;
        }

        // 2. Insertamos la solicitud
        $sql = "INSERT INTO solicitud (ci, tsolicitud, descripcion, estatus, fechainicial, especialista_id) 
                VALUES (:ci, :tipo, :desc, 'ABIERTO', :fecha, :tec_id)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':tipo', $id_tipo);
        $stmt->bindParam(':desc', $descripcion);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':tec_id', $tecnico_id);
        $stmt->execute();

        if ($tecnico_id) {
            $stmt_inc = $db->prepare("UPDATE especialista SET tickets_activos = tickets_activos + 1 WHERE id = :id");
            $stmt_inc->bindParam(':id', $tecnico_id);
            $stmt_inc->execute();
        }

        // 3. Obtenemos el ID del ticket recién creado[cite: 2]
        $id_ticket_nuevo = $db->lastInsertId();

        // 4. Buscamos el nombre de la marca[cite: 2]
        $query_m = "SELECT marca FROM marca WHERE id = :id_m LIMIT 1";
        $stmt_m = $db->prepare($query_m);
        $stmt_m->bindParam(':id_m', $id_marca_seleccionada);
        $stmt_m->execute();
        $marca_info = $stmt_m->fetch(PDO::FETCH_ASSOC);
        $nombre_marca = $marca_info['marca'] ?? 'Generica';

        // 5. Insertamos la relación en la tabla marca[cite: 2]
        $sql_vincular = "INSERT INTO marca (id, marca) VALUES (:id_t, :nombre_m)";
        $stmt_v = $db->prepare($sql_vincular);
        $stmt_v->bindParam(':id_t', $id_ticket_nuevo);
        $stmt_v->bindParam(':nombre_m', $nombre_marca);
        $stmt_v->execute();

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