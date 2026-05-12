<?php
// 1. Conexión a la base de datos
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Verificar que venga un ID válido
if(!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id_ticket = $_GET['id'];

// 2. Obtener datos del ticket y el nombre del solicitante
$query_ticket = "SELECT s.*, sol.nombre as nombre_solicitante 
                 FROM solicitud s 
                 JOIN solicitante sol ON s.ci = sol.ci 
                 WHERE s.id = :id";
$stmt_ticket = $db->prepare($query_ticket);
$stmt_ticket->bindParam(':id', $id_ticket);
$stmt_ticket->execute();
$ticket = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

// 3. Obtener lista de técnicos activos (usando tu tabla 'especialista')
$query_tec = "SELECT id, especialista FROM especialista";
$stmt_tec = $db->prepare($query_tec);
$stmt_tec->execute();
$tecnicos = $stmt_tec->fetchAll(PDO::FETCH_ASSOC);

// 4. Procesar la asignación cuando se envíe el formulario
if ($_POST) {
    $id_tecnico_elegido = $_POST['id_tecnico'];
    $nuevo_estado = 'En Proceso';
    
    // Actualizamos la solicitud
    $sql_update = "UPDATE solicitud SET id_especialista = :id_tec, estatus = :estatus WHERE id = :id";
    $stmt_up = $db->prepare($sql_update);
    $stmt_up->bindParam(':id_tec', $id_tecnico_elegido);
    $stmt_up->bindParam(':estatus', $nuevo_estado);
    $stmt_up->bindParam(':id', $id_ticket);
    
    if ($stmt_up->execute()) {
        // Redirigir al index con un mensaje de éxito
        echo "<script>alert('Técnico asignado exitosamente'); window.location='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignación de Especialista - FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .header-fondas { background: white; border-bottom: 5px solid #2e7d32; padding: 20px 0; text-align: center; }
        .logo-principal { height: 100px; width: auto; display: inline-block; }
        .sistema-badge { background-color: #2e7d32; color: white; padding: 5px 15px; border-radius: 50px; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">
    <header class="header-fondas">
        <div class="container">
            <img src="img/logo3.png" alt="Logo FONDAS" class="logo-principal">
        </div>
    </header>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white p-3 text-center">
                        <h5 class="mb-0">GESTIÓN DE ASIGNACIÓN (Ticket #<?php echo $ticket['id']; ?>)</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <p class="mb-1 text-muted">Solicitante:</p>
                                <h6 class="fw-bold"><?php echo $ticket['nombre_solicitante']; ?></h6>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <p class="mb-1 text-muted">Fecha Reporte:</p>
                                <h6 class="fw-bold"><?php echo $ticket['fechainicial']; ?></h6>
                            </div>
                        </div>

                        <div class="alert alert-secondary border-0">
                            <strong>Descripción del Problema:</strong><br>
                            <?php echo $ticket['descripcion']; ?>
                        </div>

                        <form method="POST" class="mt-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Elegir Técnico Encargado:</label>
                                <select name="id_tecnico" class="form-select form-select-lg border-danger" required>
                                    <option value="">-- Seleccione un especialista activo --</option>
                                    <?php foreach($tecnicos as $tec): ?>
                                        <option value="<?php echo $tec['id']; ?>"><?php echo $tec['nombre']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger btn-lg">FINALIZAR ASIGNACIÓN</button>
                                <a href="index.php" class="btn btn-outline-secondary">Volver al Listado</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>