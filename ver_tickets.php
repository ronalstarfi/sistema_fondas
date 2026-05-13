<?php
session_start();
require_once 'config/database.php';
require_once 'includes/metadata_helper.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$rol_sesion = $_SESSION['rol']; 
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';

// --- LÓGICA DE ACEPTAR TICKET (Sin cambios) ---
if (isset($_POST['aceptar_id']) && ($rol_sesion === 'Tecnico' || $rol_sesion === 'Especialista')) {
    $id_ticket = $_POST['aceptar_id'];
    
    $query_esp = "SELECT id FROM especialista WHERE especialista = :nombre LIMIT 1";
    $stmt_esp = $db->prepare($query_esp);
    $stmt_esp->bindParam(':nombre', $nombre_usuario);
    $stmt_esp->execute();
    $especialista = $stmt_esp->fetch(PDO::FETCH_ASSOC);

    if ($especialista) {
        $esp_id = $especialista['id'];
        $update = "UPDATE solicitud SET estatus = 'EN PROCESO', especialista_id = :esp_id WHERE id = :id";
        $stmt_up = $db->prepare($update);
        $stmt_up->bindParam(':esp_id', $esp_id);
        $stmt_up->bindParam(':id', $id_ticket);
        $stmt_up->execute();
        
        // --- INSERCIÓN DE AUDITORÍA FORENSE ---
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $cedula = $_SESSION['user_id'] ?? 'N/A'; // en especialistas user_id es el id, pero no cedula, pero en solicitantes es la cedula. Wait, los especialistas usan 'user_id' = id interno
        
        // Vamos a sacar la cedula del especialista que aceptó
        $query_ci = "SELECT ci FROM especialista WHERE id = :id LIMIT 1";
        $stmt_ci = $db->prepare($query_ci);
        $stmt_ci->bindParam(':id', $esp_id);
        $stmt_ci->execute();
        $ci_esp = $stmt_ci->fetch(PDO::FETCH_ASSOC)['ci'] ?? 'N/A';

        $audit_sql = "INSERT INTO auditoria_solicitudes (id_solicitud, estatus_anterior, estatus_nuevo, usuario_que_cambio, cedula_usuario, rol_usuario, direccion_ip, user_agent) VALUES (:id_sol, 'ABIERTO', 'EN PROCESO', :nombre, :ci, :rol, :ip, :ua)";
        $stmt_audit = $db->prepare($audit_sql);
        $stmt_audit->execute([
            ':id_sol' => $id_ticket,
            ':nombre' => $nombre_usuario,
            ':ci' => $ci_esp,
            ':rol' => $rol_sesion,
            ':ip' => $ip,
            ':ua' => $ua
        ]);
        
        header("Location: ver_tickets.php");
        exit();
    }
}

// 1. Información del perfil (Sin cambios)
$info_perfil = "";
$ci_real = "";

if ($rol_sesion === 'Solicitante') {
    $query_u = "SELECT ci, ubicacion FROM solicitante WHERE nombre = :nombre LIMIT 1";
    $stmt_u = $db->prepare($query_u);
    $stmt_u->bindParam(':nombre', $nombre_usuario);
    $stmt_u->execute();
    $row = $stmt_u->fetch(PDO::FETCH_ASSOC);
    $info_perfil = "Ubicación: " . ($row['ubicacion'] ?? 'No definida');
    $ci_real = $row['ci'] ?? '';
} else {
    $query_e = "SELECT area_especifica FROM especialista WHERE especialista = :nombre LIMIT 1";
    $stmt_e = $db->prepare($query_e);
    $stmt_e->bindParam(':nombre', $nombre_usuario);
    $stmt_e->execute();
    $row = $stmt_e->fetch(PDO::FETCH_ASSOC);
    $info_perfil = "ÁREA: " . ($row['area_especifica'] ?? 'No definida');
}

// 2. CONSULTA SQL (Trae Tipo y Marca correctamente)
$sql = "SELECT 
            s.id, 
            s.descripcion, 
            s.estatus, 
            s.fechainicial, 
            t.tipo AS tipo_equipo, 
            m.marca AS marca_equipo, 
            e.especialista AS tecnico,
            sol.nombre AS nombre_persona,
            sol.ubicacion AS departamento_origen
        FROM solicitud s
        LEFT JOIN tipo t ON s.tsolicitud = t.id
        LEFT JOIN marca m ON s.id = m.id 
        LEFT JOIN especialista e ON s.especialista_id = e.id
        LEFT JOIN solicitante sol ON s.ci = sol.ci"; 

if ($rol_sesion === 'Solicitante') {
    $sql .= " WHERE s.ci = :ci";
}
$sql .= " ORDER BY CASE WHEN s.estatus = 'CERRADO' THEN 1 ELSE 0 END, s.fechainicial DESC";

$stmt = $db->prepare($sql);
if ($rol_sesion === 'Solicitante') { $stmt->bindParam(':ci', $ci_real); }
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php echo render_metadata("Gestión de Tickets"); ?>

    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .wrapper { max-width: 1350px; margin: auto; }
        .cintillo-container { background: white; padding: 10px; border-radius: 10px 10px 0 0; text-align: center; border-bottom: 4px solid #2e7d32; }
        .cintillo { max-width: 100%; height: auto; }
        .container { background: white; padding: 25px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { color: #1b5e20; margin: 0; font-size: 24px; }
        .user-info { background: #f1f8e9; padding: 10px 15px; border-radius: 5px; border-left: 5px solid #2e7d32; font-size: 0.9em; margin-top: 10px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th { background-color: #2e7d32; color: white; padding: 12px; text-align: left; font-size: 0.85em; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .status-pill { padding: 4px 12px; border-radius: 15px; font-weight: bold; font-size: 11px; text-transform: uppercase; border: 1px solid; display: inline-block; min-width: 90px; text-align: center; }
        .abierto { background: #fffde7; color: #fbc02d; border-color: #fbc02d; }
        .proceso { background: #e1f5fe; color: #0288d1; border-color: #0288d1; }
        .urgente { background: #ffebee; color: #d32f2f; border-color: #d32f2f; }
        .cerrado { background: #f5f5f5; color: #9e9e9e; border-color: #bdbdbd; }
        .btn-aceptar { background: #0288d1; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; margin-top: 5px; font-weight: bold; }
        .btn-regresar { background: #2e7d32; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; }
        .nombre-solicitante { color: #2e7d32; font-weight: bold; display: block; }
        .info-secundaria { color: #777; font-size: 0.82em; display: block; margin-top: 3px; }
        .link-id { color: #0288d1; font-weight: bold; text-decoration: none; border: 1px solid #0288d1; padding: 3px 10px; border-radius: 4px; display: inline-block; }
        
        /* Estilos Barra de Herramientas Unificada */
        .table-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #f1f8e9; padding: 15px 20px; border-radius: 8px; border: 1px solid #c8e6c9; margin-bottom: 20px; gap: 15px; }
        .date-filters { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .date-filters label { font-weight: bold; color: #2e7d32; display: flex; align-items: center; gap: 8px; margin: 0; }
        .date-filters input[type="date"] { padding: 6px 12px; border: 1px solid #a5d6a7; border-radius: 6px; outline: none; color: #333; font-family: inherit; }
        .date-filters input[type="date"]:focus { border-color: #2e7d32; box-shadow: 0 0 5px rgba(46,125,50,0.3); }
        .btn-clear { background: #fff; border: 1px solid #ccc; padding: 6px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; color: #555; transition: 0.2s; }
        .btn-clear:hover { background: #e0e0e0; }
        
        /* Ajustes Internos DataTables para la Toolbar */
        .dataTables_wrapper .dataTables_filter { margin: 0 !important; float: none !important; text-align: left !important; }
        .search-box-wrapper { display: flex; align-items: center; gap: 8px; }
        .dataTables_wrapper .dataTables_filter input { border: 2px solid #2e7d32; border-radius: 20px; padding: 6px 15px; outline: none; margin-left: 0; font-family: inherit; transition: box-shadow 0.3s; width: 250px; }
        .dataTables_wrapper .dataTables_filter input:focus { box-shadow: 0 0 8px rgba(46, 125, 50, 0.4); }
        .btn-search { background: #2e7d32; color: white; border: none; padding: 7px 18px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-search:hover { background: #1b5e20; transform: translateY(-1px); }
        
        /* Estilos de Paginación e Info (Inferior) */
        .bottom-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; flex-wrap: wrap; padding-top: 15px; border-top: 1px solid #eee; }
        .dataTables_wrapper .dataTables_info { margin: 0 !important; color: #666; font-size: 0.9em; font-style: italic; clear: none !important; padding: 0 !important; }
        .dataTables_wrapper .dataTables_paginate { margin: 0 !important; float: none !important; text-align: right !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 14px !important; margin-left: 4px; border: 1px solid #e0e0e0 !important; border-radius: 6px; cursor: pointer; color: #555 !important; text-decoration: none; background: white !important; font-size: 0.9em; transition: all 0.2s; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f1f8e9 !important; border-color: #2e7d32 !important; color: #2e7d32 !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: #2e7d32 !important; color: white !important; border-color: #2e7d32 !important; font-weight: bold; }
    </style>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
</head>
<body>
    <div class="wrapper">
        <div class="cintillo-container">
            <img src="img/logo3.png" alt="Cintillo <?php echo APP_NAME; ?>" class="cintillo">

        </div>

        <div class="container">
            <div class="header-top">
                <div>
                    <h2>Panel de Gestión de Especialistas</h2>
                    <div class="user-info">
                        Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> | <?php echo htmlspecialchars($info_perfil); ?>
                    </div>
                </div>
                <a href="<?php echo ($rol_sesion === 'Solicitante') ? 'index_solicitante.php' : 'views/home_especialista.php'; ?>" class="btn-regresar">Volver</a>
            </div>

            <!-- Toolbar Unificada (Fechas + Buscador) -->
            <div class="table-toolbar">
                <div class="date-filters">
                    <label>Desde: <input type="date" id="min_date"></label>
                    <label>Hasta: <input type="date" id="max_date"></label>
                    <button id="clear_dates" class="btn-clear">Limpiar Filtro</button>
                </div>
                <div id="custom-dt-search"></div>
            </div>
            
            <table id="tablaTickets">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>DETALLES DE LA FALLA</th>
                        <?php if ($rol_sesion !== 'Solicitante'): ?>
                            <th>SOLICITANTE / UBICACIÓN</th>
                        <?php endif; ?>
                        <th>TÉCNICO</th>
                        <th>ESTADO</th>
                        <th>FECHA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <?php 
                            $fecha_creacion = new DateTime($t['fechainicial']);
                            $ahora = new DateTime();
                            $dif = $ahora->diff($fecha_creacion);
                            $horas_pasadas = ($dif->days * 24) + $dif->h;
                            $estatus_raw = strtoupper(trim($t['estatus'] ?? ''));
                            
                            $clase = 'abierto'; $texto = $t['estatus'];
                            if ($estatus_raw === 'CERRADO') $clase = 'cerrado';
                            elseif ($estatus_raw === 'EN PROCESO') $clase = 'proceso';
                            elseif ($horas_pasadas >= 24 && $estatus_raw === 'ABIERTO') { $clase = 'urgente'; $texto = "URGENTE (" . $horas_pasadas . "H)"; }
                        ?>
                        <tr>
                            <td>
                                <!-- LÓGICA DE ENLACE SOLO PARA ESPECIALISTAS -->
                                <?php if ($rol_sesion !== 'Solicitante'): ?>
                                    <a href="cerrar_ticket_detalle.php?id=<?php echo $t['id']; ?>" class="link-id">
                                        #<?php echo $t['id']; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="link-id" style="border-color: #ccc; color: #666; cursor: default;">
                                        #<?php echo $t['id']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['descripcion']); ?></strong>
                                <span class="info-secundaria">
                                    Equipo: <?php echo htmlspecialchars($t['tipo_equipo'] ?? 'No indicado'); ?> 
                                    | Marca: <?php echo htmlspecialchars($t['marca_equipo'] ?? 'No registrada'); ?>
                                </span>
                            </td>
                            <?php if ($rol_sesion !== 'Solicitante'): ?>
                                <td>
                                    <span class="nombre-solicitante"><?php echo htmlspecialchars($t['nombre_persona'] ?? 'N/A'); ?></span>
                                    <span class="info-secundaria"><?php echo htmlspecialchars($t['departamento_origen'] ?? 'Sin Ubicación'); ?></span>
                                </td>
                            <?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($t['tecnico'] ?? 'Pendiente'); ?></strong></td>
                            <td style="text-align: center;">
                                <span class="status-pill <?php echo $clase; ?>"><?php echo htmlspecialchars($texto); ?></span>
                                <?php if ($estatus_raw === 'ABIERTO' && ($rol_sesion === 'Tecnico' || $rol_sesion === 'Especialista')): ?>
                                    <form method="POST" style="margin:0;"><input type="hidden" name="aceptar_id" value="<?php echo $t['id']; ?>"><button type="submit" class="btn-aceptar">ACEPTAR</button></form>
                                <?php endif; ?>
                            </td>
                            <td data-fecha="<?php echo date('Y-m-d', strtotime($t['fechainicial'])); ?>" style="font-size: 0.85em; color: #666;"><?php echo date('d/m/Y h:i A', strtotime($t['fechainicial'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
    <script>
        // Función personalizada de DataTables para filtrar por fechas usando el data-fecha del <td>
        $.fn.dataTable.ext.search.push(
            function( settings, data, dataIndex ) {
                var minStr = $('#min_date').val(); // formato YYYY-MM-DD
                var maxStr = $('#max_date').val();
                
                var tr = settings.aoData[dataIndex].nTr;
                var dateStr = $(tr).find('td:last').data('fecha'); // obtenemos YYYY-MM-DD

                if (!dateStr) return true;

                // Crear fechas UTC para evitar desfases de zona horaria
                var targetDate = new Date(dateStr + 'T00:00:00Z');
                
                if (minStr) {
                    var minDate = new Date(minStr + 'T00:00:00Z');
                    if (targetDate < minDate) return false;
                }
                if (maxStr) {
                    var maxDate = new Date(maxStr + 'T23:59:59Z'); // Final del día
                    if (targetDate > maxDate) return false;
                }
                return true;
            }
        );

        $(document).ready(function() {
            var table = $('#tablaTickets').DataTable({
                "dom": 't<"bottom-controls"ip>', // Quitamos l y f por defecto para inyectarlos manualmente
                "language": {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sEmptyTable":     "Ningún dato disponible en esta tabla",
                    "sInfo":           "Mostrando del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered":   "(filtrado de _MAX_ registros en total)",
                    "sSearch":         "",
                    "searchPlaceholder": "Buscar ticket, falla o técnico...",
                    "oPaginate": {
                        "sFirst":    "Primero",
                        "sLast":     "Último",
                        "sNext":     "Siguiente",
                        "sPrevious": "Anterior"
                    }
                },
                "pageLength": 10,
                "lengthChange": false,
                "ordering": false
            });

            // Recreamos los controles manualmente si usamos "dom" sin l y f, o podemos usar el dom standard y moverlos.
            // La forma más limpia es usar la API:
            $('#custom-dt-search').html('<div class="dataTables_filter search-box-wrapper"><input type="search" id="dt-custom-search" placeholder="Buscar ticket, falla o técnico..." aria-controls="tablaTickets"><button id="btn-custom-search" class="btn-search">Buscar</button></div>');
            
            // Búsqueda unificada: se activa al presionar Enter en el input
            $('#dt-custom-search').on('keypress', function (e) {
                if (e.which == 13) {
                    table.search(this.value).draw();
                }
            });
            
            // El botón 'Buscar' procesa la búsqueda amplia (código, persona, detalles) y evalúa las fechas
            $('#custom-dt-search').on('click', '#btn-custom-search', function (e) {
                e.preventDefault();
                table.search($('#dt-custom-search').val()).draw();
            });

            // Re-dibujar la tabla cuando cambian las fechas automáticamente para mayor comodidad
            $('#min_date, #max_date').on('change', function () {
                table.search($('#dt-custom-search').val()).draw();
            });

            // Limpiar filtro general (fechas y texto) y reiniciar tabla
            $('#clear_dates').on('click', function() {
                $('#min_date').val('');
                $('#max_date').val('');
                $('#dt-custom-search').val('');
                table.search('').draw();
            });
        });
    </script>
</body>
</html>