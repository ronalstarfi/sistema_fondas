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
        if ($est === 'URGENTE') {
            $sql .= " AND s.estatus = 'ABIERTO' AND s.fechainicial <= DATE_SUB(NOW(), INTERVAL 1 HOUR) ";
        } else {
            $sql .= " AND s.estatus = :est ";
        }
    }
    
    $sql .= " $extra ORDER BY s.fechainicial DESC";
    $stmt = $db->prepare($sql);
    $params = ['desde' => $desde, 'hasta' => $hasta];
    if (!empty($esp)) { $params['esp'] = $esp; }
    if (!empty($est) && $est !== 'URGENTE') { $params['est'] = $est; }
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
    if ($estatus_filtro === 'URGENTE') {
        $sql_ind .= " AND estatus = 'ABIERTO' AND fechainicial <= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    } else {
        $sql_ind .= " AND estatus = :est";
        $params_grafica['est'] = $estatus_filtro;
    }
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
    if ($estatus_filtro === 'URGENTE') {
        $condicion_tiempo .= " AND s.estatus = 'ABIERTO' AND s.fechainicial <= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    } else {
        $condicion_tiempo .= " AND s.estatus = :est";
        $params_grafica['est'] = $estatus_filtro;
    }
}

$labels_dinamicos = [];
$valores_dinamicos = [];
$status_order = ['Asignado', 'En Proceso', 'Urgente', 'Cerrado'];
$status_counts = array_fill_keys($status_order, 0);

$sql_grafica = "SELECT 
                    SUM(CASE WHEN s.estatus = 'ABIERTO' AND s.fechainicial <= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS urgente,
                    SUM(CASE WHEN s.estatus = 'ABIERTO' AND s.fechainicial > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS asignado,
                    SUM(CASE WHEN s.estatus = 'EN PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
                    SUM(CASE WHEN s.estatus = 'CERRADO' THEN 1 ELSE 0 END) AS cerrado
                FROM solicitud s WHERE $condicion_tiempo";

// Ejecutar Flujo de Incidencias por estatus
$stmt_g = $db->prepare($sql_grafica);
$stmt_g->execute($params_grafica);
$row = $stmt_g->fetch(PDO::FETCH_ASSOC);
$status_counts['Urgente'] = (int) ($row['urgente'] ?? 0);
$status_counts['Asignado'] = (int) ($row['asignado'] ?? 0);
$status_counts['En Proceso'] = (int) ($row['en_proceso'] ?? 0);
$status_counts['Cerrado'] = (int) ($row['cerrado'] ?? 0);

foreach ($status_counts as $label => $value) {
    $labels_dinamicos[] = $label;
    $valores_dinamicos[] = $value;
}

// Ejecutar Rendimiento Especialista (Con exactitud de filtros)
$sql_esp = "SELECT e.especialista, COUNT(*) as total FROM solicitud s
            INNER JOIN especialista e ON s.especialista_id = e.id
            WHERE $condicion_tiempo 
            GROUP BY e.especialista ORDER BY total DESC";
$stmt_esp = $db->prepare($sql_esp);
$stmt_esp->execute($params_grafica);
$res_esp = $stmt_esp->fetchAll(PDO::FETCH_ASSOC);

$nombres_esp = []; $cant_esp = []; $cant_esp_total = 0;
foreach($res_esp as $row) {
    $cant_esp_total += (int) $row['total'];
}

$porc_esp = [];
foreach($res_esp as $row) {
    $nombres_esp[] = $row['especialista'];
    $cant_esp[] = (int) $row['total'];
    $porc_esp[] = $cant_esp_total ? round((($row['total'] * 100) / $cant_esp_total), 1) : 0;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --verde-fondas: #1d5733; --verde-claro: #27ae60; --azul-fondas: #2980b9; --bg-principal: #f8fafc; }
        body { background-color: var(--bg-principal); font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        .header-moderno { background: #fff; padding: 15px 40px; border-radius: 0; margin: 0; width: 100%; max-width: 100%; }
        .cintillo-verde { background: #2e7d32; color: white; padding: 15px 40px; font-weight: 600; border-radius: 0; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .card-stats { border: none; border-radius: 16px; color: white; padding: 25px; transition: transform 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .card-stats:hover { transform: translateY(-8px); }
        .bg-gradient-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-gradient-green { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .bg-gradient-orange { background: linear-gradient(135deg, #e67e22, #d35400); }

        .chart-box { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #edf2f7; height: 100%; }
        .chart-toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 18px; }
        .chart-type-btn { min-width: 42px; }
        .chart-type-btn.active { background-color: var(--verde-fondas); color: white; border-color: var(--verde-fondas); }
        .filtro-panel { background: #fff; padding: 20px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        
        .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .cerrado { background: #dcfce7; color: #166534; }
        .abierto { background: #fef9c3; color: #854d0e; }
        
        .btn-check:checked + .btn-outline-success { background-color: var(--verde-fondas); color: white; }
    </style>
</head>
<body>

<div class="header-moderno text-center" style="padding:0; overflow:hidden;">
    <img src="../img/logo3.png" style="width:100%; height:95px; object-fit:fill; display:block;" alt="FONDAS">
</div>
<div class="cintillo-verde shadow">
    <span><i class="fas fa-chart-line me-2"></i>SISTEMA DE GESTIÓN DE INCIDENCIAS - PANEL DE CONTROL</span>
    <div>
        <span class="me-3 small">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></span>
        <a href="home_especialista.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>
</div>
<div class="container-fluid px-4">

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
                    <option value="ABIERTO" <?php echo ($estatus_filtro == 'ABIERTO') ? 'selected' : ''; ?>>Asignado</option>
                    <option value="En Proceso" <?php echo ($estatus_filtro == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="URGENTE" <?php echo ($estatus_filtro == 'URGENTE') ? 'selected' : ''; ?>>Urgente</option>
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
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-chart-area me-2 text-success"></i>FLUJO DE INCIDENCIAS (Periodo Seleccionado)</h6>
                    <div class="chart-toolbar">
                        <button type="button" class="btn btn-outline-secondary btn-sm chart-type-btn" data-chart="line" title="Gráfico de línea"><i class="fas fa-chart-line"></i></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm chart-type-btn active" data-chart="bar" title="Gráfico de columnas"><i class="fas fa-chart-column"></i></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm chart-type-btn" data-chart="pie" title="Gráfico de pastel"><i class="fas fa-chart-pie"></i></button>
                        <button type="button" id="btnDescargarPdf" class="btn btn-success btn-sm">Descargar Informe PDF</button>
                    </div>
                </div>
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
                            if(strtoupper($r['estatus']) == 'EN PROCESO') $st = 'abierto'; 
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

const flujoLabels = <?php echo json_encode($labels_dinamicos); ?>;
const flujoValues = <?php echo json_encode($valores_dinamicos); ?>;
const especialistaCounts = <?php echo json_encode($cant_esp); ?>;
const especialistaPercent = <?php echo json_encode($porc_esp); ?>;
const flujoStatusColors = {
    'Asignado': '#f59e0b',
    'En Proceso': '#0dcaf0',
    'Urgente': '#dc2626',
    'Cerrado': '#198754'
};

async function descargarInformePDF() {
    try {
    const desde = document.querySelector('input[name="desde"]').value;
    const hasta = document.querySelector('input[name="hasta"]').value;
    const especialista = document.querySelector('select[name="especialista"]').value;
    const estatus = document.querySelector('select[name="estatus"]').value;

    if (!desde || !hasta) {
        alert('Seleccione un rango de fechas válido antes de descargar el informe.');
        return;
    }

    console.log('Generando PDF con parámetros:', { desde, hasta, especialista, estatus });

    const url = new URL('generate_reporte_pdf.php', window.location.href);
    url.searchParams.set('desde', desde);
    url.searchParams.set('hasta', hasta);
    if (especialista) url.searchParams.set('especialista', especialista);
    if (estatus) url.searchParams.set('estatus', estatus);

    console.log('URL del endpoint:', url.toString());

    const response = await fetch(url.toString());
    console.log('Respuesta del servidor:', response.status);

    if (!response.ok) {
        const errorText = await response.text();
        console.error('Error del servidor:', errorText);
        alert('Error al generar el informe: ' + response.status + ' - ' + errorText);
        return;
    }

    const data = await response.json();
    console.log('Datos recibidos:', data);
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 85; // 3 cm en puntos (1 cm ≈ 28.35 pt)
    const maxWidth = pageWidth - margin * 2;
    const maxHeaderFooterHeight = margin - 10;

    let y = margin;
    let headerHeight = 0;
    let footerHeight = 0;
    let headerImage = null;
    let footerImage = null;

    const loadImageDataUrl = (url) => new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            canvas.getContext('2d').drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => reject(new Error('No se pudo cargar la imagen: ' + url));
        img.src = url;
    });

    const getImageData = async (url) => {
        const dataUrl = await loadImageDataUrl(url);
        const img = new Image();
        img.src = dataUrl;
        await new Promise((resolve) => img.onload = resolve);
        let width = Math.min(maxWidth, img.width);
        let height = (img.height * width) / img.width;
        if (height > maxHeaderFooterHeight) {
            height = maxHeaderFooterHeight;
            width = (img.width * height) / img.height;
        }
        return { dataUrl, width, height };
    };

    const drawHeader = (header) => {
        if (!header) return;
        doc.addImage(header.dataUrl, 'PNG', margin, margin - header.height, header.width, header.height);
    };

    // SOLUCIÓN: Cambiado para que el cintillo inferior se pegue al ras del borde físico inferior (pageHeight - header.height)
    const drawFooter = (footer) => {
        if (!footer) return;
        doc.addImage(footer.dataUrl, 'PNG', margin, pageHeight - footer.height, footer.width, footer.height);
    };

    const ensureLines = (text, width) => doc.splitTextToSize(text || '', width);

    const addText = (text, options = {}) => {
        const lines = ensureLines(text, maxWidth);
        const lineHeight = options.lineHeight || 16;
        lines.forEach((line) => {
            if (y > pageHeight - margin - lineHeight) {
                drawFooter(footerImage);
                doc.addPage();
                drawHeader(headerImage);
                y = margin;
                doc.setFontSize(options.fontSize || 10);
            }
            doc.text(line, margin, y);
            y += lineHeight;
        });
    };

    const addSectionTitle = (title) => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        addText(title, { lineHeight: 18 });
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        y += 4;
    };

    const addBullet = (text) => {
        addText(`• ${text}`, { lineHeight: 16 });
    };

    const addKeyValue = (label, value) => {
        addText(`${label}: ${value ?? 'N/A'}`, { lineHeight: 16 });
    };

    const addChartCapture = async () => {
        const chartElement = document.getElementById('chartFlujo');
        if (!chartElement || typeof html2canvas === 'undefined') return;
        try {
            const canvas = await html2canvas(chartElement, { backgroundColor: '#ffffff' });
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = maxWidth;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            if (y + imgHeight > pageHeight - margin) {
                drawFooter(footerImage);
                doc.addPage();
                drawHeader(headerImage);
                y = margin;
            }
            doc.addImage(imgData, 'PNG', margin, y, imgWidth, imgHeight);
            y += imgHeight + 18;
        } catch (err) {
            console.warn('No se pudo capturar el gráfico:', err);
        }
    };

    const addTable = (headers, rows, opts = {}) => {
        const colCount = headers.length;
        const colGap = opts.colGap || 8;
        const colWidths = opts.colWidths || Array.from({ length: colCount }, (_, i) => (maxWidth - (colCount - 1) * colGap) / colCount);
        const lineHeight = opts.lineHeight || 14;
        const cellPadding = opts.cellPadding || 6;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);

        const headerHeights = headers.map((h, i) => {
            const lines = doc.splitTextToSize(h || '', colWidths[i] - cellPadding * 2);
            return lines.length * lineHeight + cellPadding * 2;
        });
        const headerHeight = Math.max(...headerHeights);

        if (y + headerHeight > pageHeight - margin) {
            drawFooter(footerImage);
            doc.addPage();
            drawHeader(headerImage);
            y = margin;
        }

        let x = margin;
        headers.forEach((h, i) => {
            doc.setDrawColor(200);
            doc.rect(x, y, colWidths[i], headerHeight);
            const lines = doc.splitTextToSize(h || '', colWidths[i] - cellPadding * 2);
            doc.text(lines, x + cellPadding, y + cellPadding + lineHeight - 4);
            x += colWidths[i] + colGap;
        });
        y += headerHeight + 4;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        rows.forEach((row) => {
            const cellLines = row.map((cell, i) => doc.splitTextToSize(String(cell || ''), colWidths[i] - cellPadding * 2));
            const rowHeight = Math.max(...cellLines.map(lines => lines.length)) * lineHeight + cellPadding * 2;
            if (y + rowHeight > pageHeight - margin) {
                drawFooter(footerImage);
                doc.addPage();
                drawHeader(headerImage);
                y = margin;
            }
            let cx = margin;
            cellLines.forEach((lines, i) => {
                doc.setDrawColor(220);
                doc.rect(cx, y, colWidths[i], rowHeight);
                doc.text(lines, cx + cellPadding, y + cellPadding + lineHeight - 4);
                cx += colWidths[i] + colGap;
            });
            y += rowHeight + 4;
        });
    };

    const createCoverPage = (titulo, oficina, periodoTexto, usuarioNombre, usuarioTitulo) => {
        drawHeader(headerImage);
        drawFooter(footerImage);

        const centerX = pageWidth / 2;
        const titleY = pageHeight / 3;
        const rightX = pageWidth - margin;
        const bottomY = pageHeight - footerHeight - 35; // Corregido para que no pise el cintillo

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(26);
        const titleLines = doc.splitTextToSize(titulo, maxWidth);
        doc.text(titleLines, centerX, titleY, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(16);
        const officeLines = doc.splitTextToSize(oficina, maxWidth);
        doc.text(officeLines, centerX, titleY + 40, { align: 'center' });

        doc.setFontSize(12);
        doc.text(`Periodo del informe: ${periodoTexto}`, rightX, bottomY, { align: 'right' });
        doc.text(`Fecha de generación: ${new Date().toLocaleDateString('es-ES')}`, rightX, bottomY + 16, { align: 'right' });
        doc.addPage();
    };

    const headerPath = '../img/cintillo_superior.png';
    const footerPath = '../img/cintillo_inferior.png';
    try {
        headerImage = await getImageData(headerPath);
        footerImage = await getImageData(footerPath);
        headerHeight = headerImage.height + 10;
        footerHeight = footerImage.height + 10;
    } catch (err) {
        console.warn('No se pudo cargar imagen de encabezado o pie:', err);
    }

    const periodoTexto = data.periodo_label || data.periodo || `${desde} - ${hasta}`;
    const especialistaTexto = data.especialista_label || 'Todos los especialistas';
    const estatusTexto = data.estatus_label || 'Todos los estatus';
    const usuarioNombre = (data.usuario_nombre || 'Especialista').toUpperCase();
    const usuarioTitulo = (data.usuario_titulo || 'Especialista').toUpperCase();

    createCoverPage('INFORME DE GESTIÓN', 'OFICINA DE TECNOLOGÍA DE LA INFORMACIÓN Y COMUNICACIÓN (OTIC)', periodoTexto, usuarioNombre, usuarioTitulo);

    try {
        drawHeader(headerImage);
    } catch (err) {
        console.warn('No se pudo dibujar encabezado en página de contenido:', err);
    }
    y = margin + headerHeight;

    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('1. Resumen Ejecutivo', margin, y);
    y += 22;
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    addKeyValue('Periodo', periodoTexto);
    addKeyValue('Frecuencia', 'Personalizado');
    addKeyValue('Especialista', especialistaTexto);
    addKeyValue('Estatus', estatusTexto);
    y += 8;
    addText(`Durante el periodo ${periodoTexto}, se gestionaron ${data.resumen?.total ?? 0} tickets de servicio. Este informe presenta el estado de la gestión, los principales departamentos, la categorización de incidencias y las recomendaciones operativas.`);
    addBullet(`Total de tickets: ${data.resumen?.total ?? 0}`);
    addBullet(`Casos resueltos: ${data.resumen?.cerrados ?? 0}`);
    addBullet(`Casos pendientes: ${data.resumen?.pendientes ?? 0}`);
    y += 8;

    await addChartCapture();

    addSectionTitle('2. Análisis por Departamento');
    if (!data.departamentos || data.departamentos.length === 0) {
        addText('No hay datos de departamentos para el periodo seleccionado.');
    } else {
        const headersDept = ['Departamento', 'Total', 'Incidencias principales'];
        const rowsDept = data.departamentos.map(item => [item.departamento || 'Sin departamento', item.total ?? 0, (item.principales || []).join('; ')]);
        addTable(headersDept, rowsDept, { colWidths: [maxWidth * 0.35, maxWidth * 0.12, maxWidth * 0.53] });
    }
    y += 8;

    addSectionTitle('3. Categorización de Incidencias');
    if (!data.categorias || data.categorias.length === 0) {
        addText('No hay datos de categorías para el periodo seleccionado.');
    } else {
        const headersCat = ['Categoría', 'Total', 'Ejemplos'];
        const rowsCat = data.categorias.map(item => [item.categoria || 'Sin categoría', item.total ?? 0, (item.ejemplos || []).join('; ')]);
        addTable(headersCat, rowsCat, { colWidths: [maxWidth * 0.38, maxWidth * 0.12, maxWidth * 0.5] });
    }
    y += 8;

    addSectionTitle('4. Detalle por Área');
    const categoriaImpresoras = data.categorias?.find(item => item.categoria === 'Mantenimiento y Reparación de Impresoras');
    const categoriaHardware = data.categorias?.find(item => item.categoria === 'Soporte de Hardware y Periféricos');
    const categoriaSoftware = data.categorias?.find(item => item.categoria === 'Software y Configuración');

    addText(`Mantenimiento y Reparación de Impresoras: Total de casos: ${categoriaImpresoras ? categoriaImpresoras.total : 0}`);
    if (categoriaImpresoras?.ejemplos?.length > 0) {
        addText(`Incidencias principales: ${categoriaImpresoras.ejemplos.join('; ')}`);
    }
    y += 6;

    addText(`Soporte de Hardware y Periféricos: Total de casos: ${categoriaHardware ? categoriaHardware.total : 0}`);
    if (categoriaHardware?.ejemplos?.length > 0) {
        addText(`Incidencias principales: ${categoriaHardware.ejemplos.join('; ')}`);
    }
    y += 6;

    addText(`Software y Configuración: Total de casos: ${categoriaSoftware ? categoriaSoftware.total : 0}`);
    if (categoriaSoftware?.ejemplos?.length > 0) {
        addText(`Incidencias principales: ${categoriaSoftware.ejemplos.join('; ')}`);
    }
    y += 8;

    addSectionTitle('5. Tickets Repetidos y Vacíos');
    const repetidos = data.tickets_repetidos || [];
    const vacios = data.tickets_vacios?.cantidad ?? 0;
    if (repetidos.length === 0 && vacios === 0) {
        addText('No se registraron tickets duplicados ni vacíos en el período seleccionado.');
    } else {
        if (repetidos.length > 0) {
            const headersDup = ['Descripción', 'Cantidad', 'Tickets (IDs)'];
            const rowsDup = repetidos.map(item => [item.descripcion || 'Sin descripción', item.cantidad ?? 0, (item.tickets || []).join(', ')]);
            addTable(headersDup, rowsDup, { colWidths: [maxWidth * 0.6, maxWidth * 0.12, maxWidth * 0.28] });
        }
        if (vacios > 0) {
            const idsVacios = (data.tickets_vacios?.ids || []).join(', ');
            const headersEmpty = ['Descripción', 'Cantidad', 'IDs'];
            const rowsEmpty = [['Tickets sin descripción', vacios, idsVacios]];
            addTable(headersEmpty, rowsEmpty, { colWidths: [maxWidth * 0.5, maxWidth * 0.15, maxWidth * 0.35] });
        }
    }
    y += 8;

    addSectionTitle('6. Control de Personal');
    if (!data.control_personal || data.control_personal.length === 0) {
        addText('No hay control de personal disponible para el período seleccionado.');
    } else {
        const headersPersonal = ['Especialista', 'Total', 'Cerrados', 'Pendientes'];
        const rowsPersonal = data.control_personal.map(item => [item.especialista || 'Sin asignar', item.total ?? 0, item.cerrados ?? 0, item.pendientes ?? 0]);
        addTable(headersPersonal, rowsPersonal, { colWidths: [maxWidth * 0.45, maxWidth * 0.18, maxWidth * 0.18, maxWidth * 0.19] });
    }
    y += 8;

    addSectionTitle('7. Observaciones y Recomendaciones');
    if (!data.observaciones || data.observaciones.length === 0) {
        addText('No se detectaron observaciones críticas en el período.');
    } else {
        data.observaciones.forEach((item) => addBullet(item));
    }
    y += 8;
    if (!data.recomendaciones || data.recomendaciones.length === 0) {
        addText('No hay recomendaciones generadas para el periodo seleccionado.');
    } else {
        data.recomendaciones.forEach((item, index) => addBullet(`${index + 1}. ${item}`));
    }
    y += 12;

    addSectionTitle('8. Estadísticas Generales');
    const statsHeaders = ['Métrica', 'Valor'];
    const statsRows = [
        ['Total de incidencias', data.estadisticas?.total ?? 0],
        ['Porcentaje resueltos', `${data.estadisticas?.porcentaje_resueltos ?? 0}%`],
        ['Porcentaje pendientes', `${data.estadisticas?.porcentaje_pendientes ?? 0}%`],
        ['Tickets duplicados', data.estadisticas?.tickets_duplicados ?? 0],
        ['Tickets sin descripción', data.estadisticas?.tickets_sin_descripcion ?? 0],
        ['Especialistas activos', data.estadisticas?.especialistas_activos ?? 0],
        ['Departamentos activos', data.estadisticas?.departamentos_activos ?? 0]
    ];
    addTable(statsHeaders, statsRows, { colWidths: [maxWidth * 0.6, maxWidth * 0.4] });
    y += 12;

    if (y > pageHeight - margin - 120) {
        drawFooter(footerImage);
        doc.addPage();
        drawHeader(headerImage);
        y = margin + headerHeight;
    }

    const signatureWidth = maxWidth * 0.6;
    const signatureX = margin + (maxWidth - signatureWidth) / 2;
    const lineY = y + 20;
    const nameY = lineY + 18;
    const titleY = nameY + 18;

    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(1);
    doc.line(signatureX, lineY, signatureX + signatureWidth, lineY);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text(usuarioNombre, pageWidth / 2, nameY, { align: 'center' });

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    const titleText = usuarioTitulo ? usuarioTitulo.toUpperCase() : 'GERENTE OFICINA DE TECNOLOGÍA DE LA INFORMACIÓN Y LA COMUNICACIÓN';
    doc.text(titleText, pageWidth / 2, titleY, { align: 'center' });
    y = titleY + 30;

    const fileName = `informe_gestion_${desde}_a_${hasta}.pdf`;
    console.log('Guardando PDF:', fileName);
    drawFooter(footerImage);
    doc.save(fileName);
    } catch (error) {
        console.error('Error al generar el PDF:', error);
        alert('Error al generar el informe. Revisa la consola para más detalles.');
    }
}

document.getElementById('btnDescargarPdf').addEventListener('click', descargarInformePDF);

function getColorPalette(count) {
    const palette = ['#2f80ed', '#eb5757', '#f2c94c', '#27ae60', '#56ccf2', '#9b51e0'];
    return Array.from({ length: count }, (_, i) => palette[i % palette.length]);
}

function createFlujoConfig(type) {
    const shared = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
    };

    const backgroundColors = flujoLabels.map(label => flujoStatusColors[label] || '#2f80ed');

    if (type === 'pie') {
        return {
            type: 'pie',
            data: {
                labels: flujoLabels,
                datasets: [{
                    data: flujoValues,
                    backgroundColor: backgroundColors,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } }
            }
        };
    }

    return {
        type: type === 'line' ? 'line' : 'bar',
        data: {
            labels: flujoLabels,
            datasets: [{
                label: 'Incidencias',
                data: flujoValues,
                borderColor: type === 'line' ? '#1d5733' : backgroundColors,
                backgroundColor: type === 'line' ? 'rgba(29,87,51,0.2)' : backgroundColors,
                fill: type === 'line',
                tension: type === 'line' ? 0.4 : 0,
                pointBackgroundColor: '#1d5733',
                pointRadius: type === 'line' ? 6 : 0,
                borderWidth: 3,
                borderRadius: 12,
                borderSkipped: false
            }]
        },
        options: { ...shared }
    };
}

let chartFlujo = new Chart(document.getElementById('chartFlujo'), createFlujoConfig('bar'));

document.querySelectorAll('.chart-type-btn').forEach(button => {
    button.addEventListener('click', () => {
        const chartType = button.dataset.chart;
        document.querySelectorAll('.chart-type-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        chartFlujo.destroy();
        chartFlujo = new Chart(document.getElementById('chartFlujo'), createFlujoConfig(chartType));
    });
});

new Chart(document.getElementById('chartEsp'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nombres_esp); ?>,
        datasets: [{ 
            label: 'Porcentaje de tickets', 
            data: <?php echo json_encode($porc_esp); ?>, 
            backgroundColor: '#2980b9',
            hoverBackgroundColor: '#1d5733',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        indexAxis: 'y', 
        plugins: { 
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const index = context.dataIndex;
                        const count = especialistaCounts[index] || 0;
                        return `${context.dataset.label}: ${context.parsed.x}% (${count} tickets)`;
                    }
                }
            }
        },
        scales: { 
            x: { beginAtZero: true, ticks: { callback: value => `${value}%` } }, 
            y: { grid: { display: false } } 
        }
    }
});
</script>
</body>
</html>