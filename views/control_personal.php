<?php
session_start(); 

// SEGURIDAD
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../config/database.php"); 
$database = new Database();
$db = $database->getConnection();

$id_logueado = $_SESSION['user_id']; 
$rol_logueado = $_SESSION['rol']; 
$nombre_usuario_logueado = $_SESSION['nombre'];

// CONSULTA DE ESPECIALISTAS SEGÚN ROL
if ($rol_logueado == 'Gerente' || $rol_logueado == 'Coordinadora' || $rol_logueado == 'Jefe') {
    $query = "SELECT * FROM especialista ORDER BY especialista ASC";
    $stmt = $db->prepare($query);
} else {
    $query = "SELECT * FROM especialista WHERE id = :id_user LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_user', $id_logueado, PDO::PARAM_INT);
}
$stmt->execute();
$especialistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// LÓGICA PARA ESTADÍSTICAS Y LISTADO DE MODALES
$conteo_estatus = [];
$lista_activos = [];
$lista_ausentes = [];

foreach ($especialistas as $e) {
    $estatus = $e['disponibilidad'] ?? 'Inactivo';
    $conteo_estatus[$estatus] = ($conteo_estatus[$estatus] ?? 0) + 1;
    
    $datos_p = [
        'nombre' => $e['especialista'],
        'area' => $e['area_especifica'],
        'estatus' => $estatus
    ];
    if ($estatus === 'Activo') {
        $lista_activos[] = $datos_p;
    } else {
        $lista_ausentes[] = $datos_p;
    }
}

// CONSULTA DE HISTORIAL (Solo para el Modal del Jefe)
$sql_historial = "SELECT h.*, e.especialista, h.fecha_inicio, h.fecha_fin_permiso FROM asistencia_historico h JOIN especialista e ON h.id_especialista = e.id ORDER BY h.created_at DESC LIMIT 15";
$stmt_h = $db->prepare($sql_historial);
$stmt_h->execute();
$historial_asistencia = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Personal | FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --verde-fondas: #1d5733; 
            --azul-fondas: #0d6efd; 
            --morado-fondas: #6610f2;
            --amarillo-fondas: #ffc107;
            --bg-gris: #d6d8db; 
        }
        body { background-color: var(--bg-gris); font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        
        .cintillo-fondas { background-color: #2e7d32; color: white; padding: 12px 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .sidebar-stats { background: white; border-right: 1px solid #dee2e6; min-height: calc(100vh - 70px); padding: 25px; }
        
        .stat-card { 
            background: white; border-radius: 15px; padding: 18px; margin-bottom: 20px; 
            border: 1px solid #edf2f7; border-left: 6px solid var(--verde-fondas); 
            cursor: pointer; transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateX(8px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); background-color: #fafafa; }
        .card-ausente { border-left-color: #ffc107; }
        .card-historial { border-left-color: var(--azul-fondas); }

        /* Mejora: Tarjetas de Personal con franja superior */
        .user-card { border-radius: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; border-top: 8px solid #e0e0e0; }
        .user-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
        
        /* Colores de franja por área */
        .franja-soporte { border-top-color: #28a745 !important; }
        .franja-desarrollo { border-top-color: var(--azul-fondas) !important; }
        .franja-infraestructura { border-top-color: var(--morado-fondas) !important; }
        .franja-gerencia { border-top-color: var(--amarillo-fondas) !important; }

        /* Mejora: Contenedor de Foto */
        .photo-wrapper { width: 100px; height: 100px; margin: 0 auto 15px; position: relative; }
        .user-photo { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .no-photo-icon { font-size: 80px; color: #f0f0f0; }
        
        .status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { border-radius: 20px 20px 0 0; }
    </style>
</head>
<body>

<div class="bg-white text-center shadow-sm" style="padding:0; overflow:hidden;">
    <img src="../img/logo3.png" style="width:100%; max-height:140px; object-fit:contain; display:block;" alt="FONDAS">
</div>

<nav class="cintillo-fondas d-flex justify-content-between align-items-center">
    <div class="fw-bold"><i class="fas fa-users-cog me-2"></i>SISTEMA DE CONTROL DE ASISTENCIA</div>
    <div class="small">
        <span class="me-3">Usuario: <strong><?php echo $nombre_usuario_logueado; ?></strong></span>
        <a href="home_especialista.php" class="text-white text-decoration-none border px-3 py-1 rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 sidebar-stats shadow-sm">
            <h6 class="text-muted fw-bold mb-4 px-2">RESUMEN OPERATIVO</h6>
            
            <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modalActivos">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold uppercase">EN LÍNEA</div>
                        <div class="h2 fw-bold text-success mb-0"><?php echo $conteo_estatus['Activo'] ?? 0; ?></div>
                    </div>
                    <i class="fas fa-user-check fa-2x text-success opacity-50"></i>
                </div>
            </div>

            <div class="stat-card card-ausente" data-bs-toggle="modal" data-bs-target="#modalAusentes">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold uppercase">AUSENCIAS</div>
                        <div class="h2 fw-bold text-warning mb-0"><?php echo (count($especialistas) - ($conteo_estatus['Activo'] ?? 0)); ?></div>
                    </div>
                    <i class="fas fa-user-clock fa-2x text-warning opacity-50"></i>
                </div>
            </div>

            <?php if ($rol_logueado == 'Jefe'): ?>
            <div class="stat-card card-historial mt-4" data-bs-toggle="modal" data-bs-target="#modalHistorial">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold uppercase">BITÁCORA</div>
                        <div class="fw-bold text-primary">Movimientos Recientes</div>
                        <small class="text-primary d-block mt-1">Ver detalles <i class="fas fa-chevron-right ms-1"></i></small>
                    </div>
                    <i class="fas fa-history fa-2x text-primary opacity-50"></i>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <main class="col-md-9 p-4 p-lg-5">
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach($especialistas as $row): 
                    $id = $row['id'];
                    $estado = $row['disponibilidad'] ?? 'Sin Registro';
                    $area_raw = strtoupper($row['area_especifica'] ?? '');
                    
                    // Lógica para asignar clase de color de franja por área
                    $clase_area = '';
                    if (strpos($area_raw, 'SOPORTE') !== false) $clase_area = 'franja-soporte';
                    elseif (strpos($area_raw, 'DESARROLLO') !== false) $clase_area = 'franja-desarrollo';
                    elseif (strpos($area_raw, 'INFRAESTRUCTURA') !== false) $clase_area = 'franja-infraestructura';
                    elseif ($row['rol'] !== 'Tecnico') $clase_area = 'franja-gerencia';

                    $color_estado = ($estado == 'Activo') ? '#28a745' : (($estado == 'Inactivo') ? '#dc3545' : '#ffc107');
                    
                    // Ruta de la foto
                    $ruta_foto = "../img/usuarios/" . $id . ".jpg";
                ?>
                <div class="col">
                    <div class="card h-100 user-card shadow-sm <?php echo $clase_area; ?>">
                        <div class="card-body text-center p-4">
                            <div class="text-end mb-2">
                                <span class="status-dot" style="background-color: <?php echo $color_estado; ?>;"></span>
                                <small class="fw-bold text-muted"><?php echo $estado; ?></small>
                            </div>
                            
                            <div class="photo-wrapper">
                                <?php if(file_exists($ruta_foto)): ?>
                                    <img src="<?php echo $ruta_foto; ?>" class="user-photo" alt="Perfil">
                                <?php else: ?>
                                    <i class="fas fa-user-circle no-photo-icon"></i>
                                <?php endif; ?>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?php echo $row['especialista']; ?></h5>
                            <p class="badge bg-light text-secondary border mb-4"><?php echo $row['area_especifica']; ?></p>
                            
                            <button class="btn btn-dark w-100 rounded-pill fw-bold shadow-sm py-2" 
                                    data-bs-toggle="modal" data-bs-target="#gestionar<?php echo $id; ?>">
                                <i class="fas fa-edit me-2"></i>Actualizar Estado
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="modalActivos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i>Personal Activo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush">
                    <?php if(!empty($lista_activos)): foreach($lista_activos as $a): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div><strong><?php echo $a['nombre']; ?></strong><br><small class="text-muted"><?php echo $a['area']; ?></small></div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3">Activo</span>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="p-4 text-center text-muted">No hay personal activo.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAusentes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Personal Ausente / Otros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush">
                    <?php if(!empty($lista_ausentes)): foreach($lista_ausentes as $aus): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div><strong><?php echo $aus['nombre']; ?></strong><br><small class="text-muted"><?php echo $aus['area']; ?></small></div>
                            <span class="badge bg-warning-subtle text-dark rounded-pill px-3"><?php echo $aus['estatus']; ?></span>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="p-4 text-center text-muted">No hay ausencias hoy.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-2xl">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i>Bitácora de Entradas y Salidas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase fw-bold text-muted">
                                <th class="ps-4">Especialista</th>
                                <th>Fecha / Hora</th>
                                <th>Movimiento</th>
                                <th>Motivo / Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_asistencia as $h): ?>
                            <tr>
                                <td class="ps-4"><strong><?php echo $h['especialista']; ?></strong></td>
                                <td>
    <?php if (!empty($h['fecha_inicio']) && $h['fecha_inicio'] != '0000-00-00'): ?>
        <div class="small text-primary fw-bold">
            <i class="fas fa-calendar-alt me-1"></i>
            Desde: <?php echo date('d/m/Y', strtotime($h['fecha_inicio'])); ?>
        </div>
        <div class="small text-danger fw-bold">
            <i class="fas fa-calendar-check me-1"></i>
            Hasta: <?php echo date('d/m/Y', strtotime($h['fecha_fin_permiso'])); ?>
        </div>
    <?php else: ?>
        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></div>
    <?php endif; ?>
    <div class="small text-muted"><?php echo date('h:i A', strtotime($h['hora'])); ?></div>
</td>
                                <td>
                                    <span class="badge rounded-pill <?php echo ($h['tipo_movimiento']=='Activo'?'bg-success':'bg-danger'); ?> px-3">
                                        <?php echo $h['tipo_movimiento']; ?>
                                    </span>
                                </td>
                                <td class="small text-secondary"><?php echo $h['detalle'] ?: '---'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php foreach($especialistas as $row): $id = $row['id']; ?>
<div class="modal fade" id="gestionar<?php echo $id; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0"><h5 class="modal-title">Actualizar Mi Estatus</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <form action="actualizar_estado.php" method="POST">
                    <input type="hidden" name="id_especialista" value="<?php echo $id; ?>">
                    <div class="mb-4 text-center">
                        <p class="text-muted small">Seleccione su disponibilidad actual para el sistema.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estatus:</label>
                        <select class="form-select border-2" name="disponibilidad" id="sel_<?php echo $id; ?>" onchange="toggleCampos(<?php echo $id; ?>)">
                            <option value="Activo" <?php if($row['disponibilidad']=='Activo') echo 'selected'; ?>>Marcar Entrada (Activo)</option>
                            <option value="Inactivo" <?php if($row['disponibilidad']=='Inactivo') echo 'selected'; ?>>Marcar Salida (Inactivo)</option>
                            <option value="Reposo Médico">Reposo Médico</option>
                            <option value="Vacaciones">Vacaciones</option>
                            <option value="Permiso Institucional">Permiso Institucional</option>
                        </select>
                    </div>
                    <div id="extra_fechas_<?php echo $id; ?>" class="row g-2 mb-3" style="display: none;">
                        <div class="col-6"><label class="small fw-bold">Desde:</label><input type="date" class="form-control" name="fecha_desde"></div>
                        <div class="col-6"><label class="small fw-bold">Hasta:</label><input type="date" class="form-control" name="fecha_hasta"></div>
                    </div>
                    <div id="extra_desc_<?php echo $id; ?>" class="mb-4">
                        <label class="form-label fw-bold small">Detalles Adicionales:</label>
                        <textarea class="form-control" name="motivo_permiso" rows="2" placeholder="Ej: Motivo de la salida o reposo..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow">Confirmar y Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function toggleCampos(id) {
    const val = document.getElementById('sel_' + id).value;
    const conFechas = ['Reposo Médico', 'Vacaciones', 'Permiso Institucional'];
    document.getElementById('extra_fechas_' + id).style.display = conFechas.includes(val) ? 'flex' : 'none';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>