<?php
session_start();
require_once 'config/database.php';

// Verificación de sesión para asegurar que solo entren especialistas[cite: 3]
if (!isset($_SESSION['temp_ci']) || $_SESSION['user_type'] !== 'especialista') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Consulta Global: Trae todos los tickets sin filtrar por usuario
$sql = "SELECT 
            s.id, 
            sol.nombre AS solicitante, 
            sol.ubicacion AS ubicacion_solicitante, 
            s.descripcion, 
            s.estatus, 
            s.fechainicial, 
            e.especialista AS tecnico,
            e.area_especifica
        FROM solicitud s
        LEFT JOIN solicitante sol ON s.ci = sol.ci
        LEFT JOIN especialista e ON s.especialista_id = e.id
        ORDER BY s.fechainicial DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Global de Tickets - FONDAS</title>
    <!-- Bootstrap para un diseño moderno y responsivo[cite: 1] -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .card { border: none; border-radius: 12px; }
        .header-line { border-bottom: 2px solid #2e7d32; padding-bottom: 10px; color: #2e7d32; }
        th { background-color: #2e7d32 !important; color: white !important; text-transform: uppercase; font-size: 0.85em; }
        .status { padding: 5px 12px; border-radius: 15px; font-weight: bold; font-size: 0.8em; text-align: center; display: block; }
        .abierto { background-color: #fff3cd; color: #856404; }
        .enproceso { background-color: #e3f2fd; color: #1565c0; }
        .cerrado { background-color: #eeeeee; color: #616161; }
        .btn-atender { background-color: #198754; color: white; font-weight: bold; transition: 0.3s; }
        .btn-atender:hover { background-color: #146c43; color: white; transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="container-fluid mt-5 mb-5 px-4">
    <div class="card shadow-lg">
        <div class="card-body p-0">
            <!-- Cintillo Institucional centrado[cite: 1] -->
            <div class="text-center p-4 border-bottom bg-white rounded-top">
                <img src="img/logo3.png" alt="Logo FONDAS" style="max-width: 400px; height: auto;">
            </div>

            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="header-line fw-bold mb-0">Listado Maestro de Incidencias</h4>
                        <p class="text-muted small mb-0">Panel de monitoreo global para el equipo técnico</p>
                    </div>
                    <a href="index_especialista.php" class="btn btn-outline-secondary fw-bold px-4">
                        ← Volver al Inicio
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Solicitante / Ubicación</th>
                                <th>Falla Reportada</th>
                                <th>Técnico / Área</th>
                                <th class="text-center">Estatus</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td class="fw-bold text-success" style="font-size: 1.1em;">#<?php echo $t['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($t['solicitante'] ?? 'N/A'); ?></div>
                                    <small class="text-muted italic"><?php echo htmlspecialchars($t['ubicacion_solicitante'] ?? 'Sin ubicación'); ?></small>
                                </td>
                                <td style="max-width: 250px;">
                                    <div class="text-truncate" title="<?php echo htmlspecialchars($t['descripcion']); ?>">
                                        <?php echo htmlspecialchars($t['descripcion']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($t['tecnico']): ?>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($t['tecnico']); ?></div>
                                        <span class="badge bg-light text-success border border-success" style="font-size: 0.7em;">
                                            <?php echo htmlspecialchars($t['area_especifica']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold small italic">No asignado aún</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo strtolower(str_replace(' ', '', $t['estatus'])); ?>">
                                        <?php echo htmlspecialchars($t['estatus']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($t['fechainicial'])); ?><br>
                                        <?php echo date('H:i', strtotime($t['fechainicial'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <!-- Enlace al detalle para gestionar el cierre -->
                                    <a href="cerrar_ticket_detalle.php?id=<?php echo $t['id']; ?>" class="btn btn-atender btn-sm w-100 shadow-sm">
                                        GESTIONAR
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>