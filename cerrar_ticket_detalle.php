<?php
session_start();
require_once 'config/database.php';

// 1. Verificación de Seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id_ticket = $_GET['id'] ?? null;
$rol = $_SESSION['rol'] ?? 'Invitado';

// 2. Obtener datos del ticket y técnicos activos
if ($id_ticket) {
    $sql = "SELECT s.*, sol.nombre AS solicitante, sol.ubicacion, e.especialista AS tecnico_nombre 
            FROM solicitud s 
            LEFT JOIN solicitante sol ON s.ci = sol.ci 
            LEFT JOIN especialista e ON s.especialista_id = e.id
            WHERE s.id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // Solo técnicos activos para cerrar
    $tecnicos = $db->query("SELECT id, especialista FROM especialista WHERE disponibilidad = 'Activo'")->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Procesar el Cierre
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_cierre'])) {
    $nuevo_tecnico_id = $_POST['nuevo_tecnico_id'];
    $antiguo_tecnico_id = $ticket['especialista_id'];
    $observaciones = $_POST['observaciones'];

    try {
        $db->beginTransaction();

        // Liberar al técnico anterior si existía
        if ($antiguo_tecnico_id) {
            $db->prepare("UPDATE especialista SET tickets_activos = GREATEST(0, tickets_activos - 1) WHERE id = :id")
               ->execute([':id' => $antiguo_tecnico_id]);
        }

        $sql_final = "UPDATE solicitud SET 
                        estatus = 'CERRADO', 
                        fechafinal = NOW(), 
                        especialista_id = :esp_id, 
                        descripcion = CONCAT(descripcion, ' | SOLUCIÓN: ', :obs) 
                      WHERE id = :id";
        
        $db->prepare($sql_final)->execute([
            ':esp_id' => $nuevo_tecnico_id, 
            ':obs' => $observaciones, 
            ':id' => $id_ticket
        ]);

        $db->commit();
        
        // MENSAJE DE ÉXITO CON TU DISEÑO ORIGINAL
        ?>
        <div style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #d1e2d4; font-family: 'Segoe UI', sans-serif; position: fixed; top: 0; left: 0; width: 100%; z-index: 9999;">
            <div style="background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 450px; width: 90%;">
                <div style="width: 80px; height: 80px; background-color: #f0f9f1; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; border: 2px solid #e1f0e4;">
                            <span style="color: #28a745; font-size: 40px;">✔</span>
                        </div>
                <h1 style="color: #333; margin-bottom: 10px; font-size: 24px;">¡Ticket #<?php echo $id_ticket; ?> Finalizado!</h1>
                <p style="color: #666; margin-bottom: 30px; line-height: 1.5;">
                    La incidencia ha sido cerrada y la solución técnica se ha registrado exitosamente.
                </p>
                <a href="ver_tickets.php" class="btn-new btn-back">Volver a la Lista</a>
            </div>
        </div>
        <?php
        exit(); 

    } catch (Exception $e) {
        $db->rollBack();
        echo "<div style='color:red; padding:20px;'>Error al procesar: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Ticket - FONDAS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header-cintillo { background-color: white; padding: 0; display: block; border-bottom: 4px solid #2e7d32; }
        .header-cintillo img { width: 100%; height: 95px; object-fit: fill; display: block; margin: 0; }
        .main-content { display: flex; justify-content: center; padding: 40px 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 600px; overflow: hidden; }
        .card-header { background: #2e7d32; color: white; padding: 15px; text-align: center; font-weight: bold; text-transform: uppercase; }
        .card-body { padding: 25px; }
        .info-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #eee; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #444; }
        select, textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 20px; box-sizing: border-box; font-family: inherit; }
        .btn-finalizar { background: #2e7d32; color: white; border: none; padding: 15px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; text-transform: uppercase; transition: 0.3s; }
        .btn-finalizar:hover { background: #1b5e20; }
        .btn-volver { display: block; text-align: center; margin-top: 15px; color: #2e7d32; text-decoration: none; padding: 10px; border: 1px solid #2e7d32; border-radius: 5px; font-weight: bold; }
        .btn-new { background: linear-gradient(135deg, #2e7d32, #27ae60); color: white; padding: 12px 22px; border-radius: 10px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; box-shadow: 0 10px 20px rgba(46,125,50,0.18); transition: transform 0.2s ease, background 0.2s ease; border: 1px solid #1b5e20; }
        .btn-new:hover { background: linear-gradient(135deg, #23903a, #1d7d31); transform: translateY(-2px); }
        .btn-back::before { content: '\2190'; margin-right: 8px; font-size: 1.05rem; display: inline-block; }
        /* Ajustes específicos para el botón de volver: tamaño reducido y centrado */
        .btn-back {
            display: block;
            margin: 15px auto 0;
            color: #2e7d32;
            background: transparent;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.95rem;
            text-decoration: none;
            text-align: center;
            box-shadow: none;
            border: 1px solid #2e7d32;
            width: auto;
            max-width: 220px;
        }
        .loc-highlight { color: #d32f2f; font-weight: bold; }
    </style>
</head>
<body>

    <header class="header-cintillo">
        <img src="img/logo3.png" alt="FONDAS">
    </header>

    <div class="main-content">
        <div class="card">
            <div class="card-header">
                Gestión de Cierre (Ticket #<?php echo $id_ticket; ?>)
            </div>
            
            <div class="card-body">
                <div class="info-section">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span><strong>Solicitante:</strong><br><?php echo htmlspecialchars($ticket['solicitante'] ?? 'N/A'); ?></span>
                        <span style="text-align: right;"><strong>Fecha Reporte:</strong><br><?php echo date('d/m/Y H:i', strtotime($ticket['fechainicial'])); ?></span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Ubicación / Departamento:</strong><br>
                        <span class="loc-highlight"><?php echo htmlspecialchars($ticket['ubicacion'] ?? 'No definida'); ?></span>
                    </div>
                    <label>Falla Reportada:</label>
                    <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($ticket['descripcion']); ?></p>
                </div>

                <form method="POST">
                    <label>Técnico que cierra el ticket:</label>
                    <select name="nuevo_tecnico_id" required>
                        <?php foreach ($tecnicos as $tec): ?>
                            <option value="<?php echo $tec['id']; ?>" <?php echo ($tec['id'] == $ticket['especialista_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tec['especialista']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Observaciones / Solución:</label>
                    <textarea name="observaciones" rows="4" placeholder="Indique las acciones tomadas..." required></textarea>

                    <button type="submit" name="confirmar_cierre" class="btn-finalizar">Confirmar y Cerrar Ticket</button>
                    <a href="ver_tickets.php" class="btn-new btn-back">Cancelar y Volver</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>