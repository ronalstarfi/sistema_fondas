<?php
session_start();

// 1. SEGURIDAD
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'especialista') {
    header("Location: ../login.php"); 
    exit();
}

// 2. CONEXIÓN
include("../config/database.php");
$database = new Database(); 
$db = $database->getConnection(); 

// 3. CAPTURA DE FILTROS AVANZADOS
$fecha_desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-01'); // Por defecto, inicio de mes
$fecha_hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t'); // Por defecto, fin de mes
$especialista_filtro = isset($_GET['especialista']) ? $_GET['especialista'] : '';
$estatus_filtro = isset($_GET['estatus']) ? $_GET['estatus'] : ''; 

// Obtener lista de especialistas para el select
$stmt_all_esp = $db->query("SELECT id, especialista FROM especialista ORDER BY especialista ASC");
$especialistas_list = $stmt_all_esp->fetchAll(PDO::FETCH_ASSOC);

// --- FUNCIÓN PARA DETALLES DE TARJETAS (Ahora soporta rango de fechas, especialista y estatus) ---
function obtenerDataDetalle($db, $desde, $hasta, $esp, $est, $extra = "") {
    $sql = "SELECT s.id, s.descripcion, s.fechainicial, s.estatus, sol.nombre AS nombre_persona,
                sol.ubicacion AS departamento_origen, e.especialista AS tecnico_nombre
            FROM solicitud s
            LEFT JOIN solicitante sol ON s.ci = sol.ci
            LEFT JOIN especialista e ON s.especialista_id = e.id
            WHERE DATE(s.fechainicial) BETWEEN :desde AND :hasta ";
            
    if (!empty($esp)) {
        $sql .= " AND s.especialista_id = :esp ";
    }
    if (!empty($est)) {
        $sql .= " AND s.estatus = :est ";
    }
    
    $sql .= " $extra ORDER BY s.fechainicial DESC";
    $stmt = $db->prepare($sql);
    $params = ['desde' => $desde, 'hasta' => $hasta];
    if (!empty($esp)) { $params['esp'] = $esp; }
    if (!empty($est)) { $params['est'] = $est; }
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$detalle_todos = obtenerDataDetalle($db, $fecha_desde, $fecha_hasta, $especialista_filtro, $estatus_filtro);
$detalle_cerrados = obtenerDataDetalle($db, $fecha_desde, $fecha_hasta, $especialista_filtro, $estatus_filtro, "AND s.estatus = 'Cerrado'");
$detalle_pendientes = obtenerDataDetalle($db, $fecha_desde, $fecha_hasta, $especialista_filtro, $estatus_filtro, "AND s.estatus != 'Cerrado'");

// 4. CONSULTA PARA INDICADORES (TARJETAS) EXACTOS AL FILTRO
$sql_ind = "SELECT COUNT(*) as total_mes,
                SUM(CASE WHEN estatus = 'Cerrado' THEN 1 ELSE 0 END) as cerrados_mes,
                SUM(CASE WHEN estatus != 'Cerrado' THEN 1 ELSE 0 END) as pendientes_mes
            FROM solicitud WHERE DATE(fechainicial) BETWEEN :desde AND :hasta";
$params_grafica = ['desde' => $fecha_desde, 'hasta' => $fecha_hasta];

if (!empty($especialista_filtro)) {
    $sql_ind .= " AND especialista_id = :esp";
    $params_grafica['esp'] = $especialista_filtro;
}
if (!empty($estatus_filtro)) {
    $sql_ind .= " AND estatus = :est";
    $params_grafica['est'] = $estatus_filtro;
}
$stmt_ind = $db->prepare($sql_ind);
$stmt_ind->execute($params_grafica);
$res_mes = $stmt_ind->fetch(PDO::FETCH_ASSOC);

// 5. LÓGICA DINÁMICA DE TIEMPO EXACTA A LOS FILTROS
$condicion_tiempo = "DATE(s.fechainicial) BETWEEN :desde AND :hasta";
if (!empty($especialista_filtro)) {
    $condicion_tiempo .= " AND s.especialista_id = :esp";
}
if (!empty($estatus_filtro)) {
    $condicion_tiempo .= " AND s.estatus = :est";
}

$labels_dinamicos = [];
$valores_dinamicos = [];

$sql_grafica = "SELECT DATE_FORMAT(s.fechainicial, '%d/%m/%Y') as periodo, COUNT(*) as cantidad 
                FROM solicitud s WHERE $condicion_tiempo GROUP BY DATE(s.fechainicial) ORDER BY MIN(s.fechainicial) ASC";

// Ejecutar Flujo de Incidencias
$stmt_g = $db->prepare($sql_grafica);
$stmt_g->execute($params_grafica);
foreach($stmt_g->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $labels_dinamicos[] = $row['periodo'];
    $valores_dinamicos[] = $row['cantidad'];
}

// Ejecutar Rendimiento Especialista (Con exactitud de filtros)
$sql_esp = "SELECT e.especialista, COUNT(*) as total FROM solicitud s
            INNER JOIN especialista e ON s.especialista_id = e.id
            WHERE $condicion_tiempo 
            GROUP BY e.especialista ORDER BY total DESC";
$stmt_esp = $db->prepare($sql_esp);
$stmt_esp->execute($params_grafica);
$res_esp = $stmt_esp->fetchAll(PDO::FETCH_ASSOC);

$nombres_esp = []; $cant_esp = [];
foreach($res_esp as $row) { 
    $nombres_esp[] = $row['especialista']; 
    $cant_esp[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Gestión - FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --verde-fondas: #1d5733; --verde-claro: #27ae60; --azul-fondas: #2980b9; --bg-principal: #f8fafc; }
        body { background-color: var(--bg-principal); font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        .header-moderno { background: #fff; padding: 15px 40px; border-radius: 0 0 20px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .cintillo-verde { background: linear-gradient(90deg, var(--verde-fondas), var(--verde-claro)); color: white; padding: 15px 40px; font-weight: 600; border-radius: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        
        .card-stats { border: none; border-radius: 16px; color: white; padding: 25px; transition: transform 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .card-stats:hover { transform: translateY(-8px); }
        .bg-gradient-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-gradient-green { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .bg-gradient-orange { background: linear-gradient(135deg, #e67e22, #d35400); }

        .chart-box { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #edf2f7; height: 100%; }
        .filtro-panel { background: #fff; padding: 20px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        
        .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .cerrado { background: #dcfce7; color: #166534; }
        .abierto { background: #fef9c3; color: #854d0e; }
        
        .btn-check:checked + .btn-outline-success { background-color: var(--verde-fondas); color: white; }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="header-moderno text-center shadow-sm">
        <img src="../img/logo3.png" style="height: 65px;" alt="FONDAS">
    </div>

    <div class="cintillo-verde shadow">
        <span><i class="fas fa-chart-line me-2"></i>SISTEMA DE GESTIÓN DE INCIDENCIAS - PANEL DE CONTROL</span>
        <div>
            <span class="me-3 small">Bienvenido, <strong><?php echo explode(' ', $_SESSION['nombre'])[0]; ?></strong></span>
            <a href="home_especialista.php" class="btn btn-sm btn-light rounded-circle"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <div class="filtro-panel shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="fw-bold text-muted small">Desde:</label>
                <input type="date" name="desde" value="<?php echo htmlspecialchars($fecha_desde); ?>" class="form-control bg-light border-0 shadow-sm">
            </div>
            <div class="col-md-2">
                <label class="fw-bold text-muted small">Hasta:</label>
                <input type="date" name="hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>" class="form-control bg-light border-0 shadow-sm">
            </div>
            <div class="col-md-3">
                <label class="fw-bold text-muted small">Especialista:</label>
                <select name="especialista" class="form-select bg-light border-0 shadow-sm">
                    <option value="">Todos los especialistas</option>
                    <?php foreach($especialistas_list as $esp_item): ?>
                        <option value="<?php echo htmlspecialchars($esp_item['id']); ?>" <?php echo ($especialista_filtro == $esp_item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($esp_item['especialista']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-bold text-muted small">Estatus:</label>
                <select name="estatus" class="form-select bg-light border-0 shadow-sm">
                    <option value="">Todos los estatus</option>
                    <option value="Abierto" <?php echo ($estatus_filtro == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                    <option value="En Proceso" <?php echo ($estatus_filtro == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="Cerrado" <?php echo ($estatus_filtro == 'Cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100 rounded-pill shadow-sm fw-bold">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-stats bg-gradient-blue" data-bs-toggle="modal" data-bs-target="#mTotal">
                <div class="d-flex justify-content-between">
                    <div><h6 class="text-uppercase opacity-75 small">Total Incidencias</h6><h2 class="fw-bold"><?php echo $res_mes['total_mes'] ?? 0; ?></h2></div>
                    <i class="fas fa-clipboard-list fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stats bg-gradient-green" data-bs-toggle="modal" data-bs-target="#mCerrados">
                <div class="d-flex justify-content-between">
                    <div><h6 class="text-uppercase opacity-75 small">Casos Resueltos</h6><h2 class="fw-bold"><?php echo $res_mes['cerrados_mes'] ?? 0; ?></h2></div>
                    <i class="fas fa-check-circle fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stats bg-gradient-orange" data-bs-toggle="modal" data-bs-target="#mPendientes">
                <div class="d-flex justify-content-between">
                    <div><h6 class="text-uppercase opacity-75 small">Casos Pendientes</h6><h2 class="fw-bold"><?php echo $res_mes['pendientes_mes'] ?? 0; ?></h2></div>
                    <i class="fas fa-clock fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="chart-box">
                <h6 class="fw-bold text-dark mb-4"><i class="fas fa-chart-area me-2 text-success"></i>FLUJO DE INCIDENCIAS (Periodo Seleccionado)</h6>
                <div style="height: 380px;"><canvas id="chartFlujo"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-box">
                <h6 class="fw-bold text-dark mb-4"><i class="fas fa-user-tie me-2 text-primary"></i>RENDIMIENTO POR ESPECIALISTA</h6>
                <div style="height: 380px;"><canvas id="chartEsp"></canvas></div>
            </div>
        </div>
    </div>

    <!-- HISTORIAL DE TICKETS (NUEVO) -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="chart-box">
                <h6 class="fw-bold text-dark mb-4"><i class="fas fa-list me-2 text-warning"></i>HISTORIAL DE TICKETS FILTRADOS</h6>
                <table id="tablaHistorial" class="table table-hover align-middle mb-0 w-100">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Solicitante</th>
                            <th>Descripción</th>
                            <th>Técnico</th>
                            <th>Fecha</th>
                            <th class="pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalle_todos as $r): 
                            $st = (strtoupper($r['estatus']) == 'CERRADO') ? 'cerrado' : 'abierto'; 
                            if(strtoupper($r['estatus']) == 'EN PROCESO') $st = 'abierto'; // usar clase abierta para en proceso temporal
                            $estatus_texto = $r['estatus'];
                            $badge_bg = 'bg-warning text-dark';
                            if(strtoupper($estatus_texto) == 'CERRADO') $badge_bg = 'bg-success';
                            if(strtoupper($estatus_texto) == 'EN PROCESO') $badge_bg = 'bg-info text-white';
                        ?>
                        <tr>
                            <td class="ps-4">#<?php echo $r['id']; ?></td>
                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($r['nombre_persona']); ?></span></td>
                            <td class="small text-muted"><?php echo htmlspecialchars(substr($r['descripcion'], 0, 80)) . '...'; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['tecnico_nombre'] ?? 'Sin asignar'); ?></span></td>
                            <td class="small"><?php echo date('d/m/Y h:i A', strtotime($r['fechainicial'])); ?></td>
                            <td class="pe-4"><span class="badge <?php echo $badge_bg; ?>"><?php echo $estatus_texto; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
$modales = [ ['id' => 'mTotal', 'titulo' => 'Reporte Histórico Total', 'datos' => $detalle_todos, 'color' => 'dark'],
             ['id' => 'mCerrados', 'titulo' => 'Reporte de Casos Finalizados', 'datos' => $detalle_cerrados, 'color' => 'success'],
             ['id' => 'mPendientes', 'titulo' => 'Reporte de Casos en Espera', 'datos' => $detalle_pendientes, 'color' => 'warning'] ];
foreach($modales as $m): ?>
<div class="modal fade" id="<?php echo $m['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-<?php echo $m['color']; ?> text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i><?php echo $m['titulo']; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light"><tr><th class="ps-4">ID</th><th>Solicitante</th><th>Descripción</th><th>Técnico</th><th>Fecha</th><th class="pe-4">Estado</th></tr></thead>
                    <tbody>
                        <?php foreach($m['datos'] as $r): $st = (strtoupper($r['estatus']) == 'CERRADO') ? 'cerrado' : 'abierto'; ?>
                        <tr><td class="ps-4">#<?php echo $r['id']; ?></td>
                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($r['nombre_persona']); ?></span></td>
                            <td class="small text-muted"><?php echo htmlspecialchars(substr($r['descripcion'], 0, 50)) . '...'; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['tecnico_nombre'] ?? 'Sin asignar'); ?></span></td>
                            <td class="small"><?php echo date('d/m/Y', strtotime($r['fechainicial'])); ?></td>
                            <td class="pe-4"><span class="status-pill <?php echo $st; ?>"><?php echo $r['estatus']; ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    $('#tablaHistorial').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "pageLength": 10,
        "ordering": false
    });
});

// Configuración de Gráfica de Flujo (Verde Institucional)
new Chart(document.getElementById('chartFlujo'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_dinamicos); ?>,
        datasets: [{ 
            label: 'Incidencias', 
            data: <?php echo json_encode($valores_dinamicos); ?>, 
            borderColor: '#1d5733', 
            backgroundColor: 'rgba(29, 87, 51, 0.1)', 
            fill: true, 
            tension: 0.4,
            pointBackgroundColor: '#27ae60',
            pointRadius: 6,
            borderWidth: 3
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
    }
});

// Configuración de Gráfica de Especialistas (Azul/Verde)
new Chart(document.getElementById('chartEsp'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nombres_esp); ?>,
        datasets: [{ 
            label: 'Resueltos', 
            data: <?php echo json_encode($cant_esp); ?>, 
            backgroundColor: '#2980b9',
            hoverBackgroundColor: '#1d5733',
            borderRadius: 8
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        indexAxis: 'y', // Barras horizontales para mejor lectura de nombres
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true }, y: { grid: { display: false } } }
    }
});
</script>
</body>
</html>