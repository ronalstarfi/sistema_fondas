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

$areaMap = [
    'Soporte' => 'Soporte Técnico',
    'Soporte Técnico' => 'Soporte Técnico',
    'Infraestructura' => 'Infraestructura',
    'Desarrollo' => 'Desarrollo',
    'Impresoras y Toner' => 'Impresoras y Toner',
    'SIGA' => 'Analista Funcional'
];

$ticketArea = trim($ticket['area_problema'] ?? '');
$areaEspecialidad = $areaMap[$ticketArea] ?? null;

// 3. Obtener lista de técnicos activos en el área correspondiente
if ($areaEspecialidad) {
    $query_tec = "SELECT id, especialista FROM especialista WHERE area_especifica = :area AND rol = 'Tecnico' AND disponibilidad = 'Activo' ORDER BY tickets_activos ASC, id ASC";
    $stmt_tec = $db->prepare($query_tec);
    $stmt_tec->bindParam(':area', $areaEspecialidad);
    $stmt_tec->execute();
    $tecnicos = $stmt_tec->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tecnicos)) {
        $query_tec = "SELECT id, especialista FROM especialista WHERE area_especifica = :area AND rol = 'Tecnico' ORDER BY tickets_activos ASC, id ASC";
        $stmt_tec = $db->prepare($query_tec);
        $stmt_tec->bindParam(':area', $areaEspecialidad);
        $stmt_tec->execute();
        $tecnicos = $stmt_tec->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $query_tec = "SELECT id, especialista FROM especialista WHERE rol = 'Tecnico' AND disponibilidad = 'Activo' ORDER BY tickets_activos ASC, id ASC";
    $stmt_tec = $db->prepare($query_tec);
    $stmt_tec->execute();
    $tecnicos = $stmt_tec->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Procesar la asignación cuando se envíe el formulario
if ($_POST) {
    $id_tecnico_elegido = $_POST['id_tecnico'] ?? null;
    $nuevo_estado = 'En Proceso';
    $antiguo_tecnico_id = $ticket['especialista_id'];

    if (empty($id_tecnico_elegido)) {
        $errorAsignacion = 'Debe seleccionar un técnico válido antes de asignar el ticket.';
    } else {
        // Actualizamos la solicitud
        $sql_update = "UPDATE solicitud SET id_especialista = :id_tec, estatus = :estatus WHERE id = :id";
        $stmt_up = $db->prepare($sql_update);
        $stmt_up->bindParam(':id_tec', $id_tecnico_elegido);
        $stmt_up->bindParam(':estatus', $nuevo_estado);
        $stmt_up->bindParam(':id', $id_ticket);

        if ($stmt_up->execute()) {
            if ($id_tecnico_elegido && $id_tecnico_elegido !== $antiguo_tecnico_id) {
                if ($antiguo_tecnico_id) {
                    $stmt_dec = $db->prepare("UPDATE especialista SET tickets_activos = GREATEST(0, tickets_activos - 1) WHERE id = :id");
                    $stmt_dec->bindParam(':id', $antiguo_tecnico_id);
                    $stmt_dec->execute();
                }
                $stmt_inc = $db->prepare("UPDATE especialista SET tickets_activos = tickets_activos + 1 WHERE id = :id");
                $stmt_inc->bindParam(':id', $id_tecnico_elegido);
                $stmt_inc->execute();
            }
            echo "<script>alert('Técnico asignado exitosamente'); window.location='index.php';</script>";
        } else {
            $errorAsignacion = 'Ocurrió un error al asignar el técnico, intente de nuevo.';
        }
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
        .btn-new { background: linear-gradient(135deg, #2e7d32, #27ae60); color: white; padding: 12px 22px; border-radius: 10px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; box-shadow: 0 10px 20px rgba(46,125,50,0.18); transition: transform 0.2s ease, background 0.2s ease; border: 1px solid #1b5e20; }
        .btn-new:hover { background: linear-gradient(135deg, #23903a, #1d7d31); transform: translateY(-2px); }
        .btn-back::before { content: '\2190'; margin-right: 8px; font-size: 1.05rem; display: inline-block; }
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
                        <?php if (!empty($errorAsignacion)): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorAsignacion); ?></div>
                        <?php endif; ?>
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
                                <label class="form-label fw-bold">Área del Ticket:</label>
                                <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($ticket['area_problema'] ?? 'Sin área definida'); ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Elegir Técnico Encargado:</label>
                                <select name="id_tecnico" class="form-select form-select-lg border-danger" required <?php echo empty($tecnicos) ? 'disabled' : ''; ?>>
                                    <option value=""><?php echo empty($tecnicos) ? '-- No hay técnicos disponibles para esta área --' : '-- Seleccione un especialista activo --'; ?></option>
                                    <?php foreach($tecnicos as $tec): ?>
                                        <option value="<?php echo $tec['id']; ?>"><?php echo $tec['especialista']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger btn-lg">FINALIZAR ASIGNACIÓN</button>
                                <a href="index.php" class="btn-new btn-back">Volver al Listado</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>