<?php
session_start();
require_once 'config/database.php';

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

        $stmt_inc = $db->prepare("UPDATE especialista SET tickets_activos = tickets_activos + 1 WHERE id = :id");
        $stmt_inc->bindParam(':id', $esp_id);
        $stmt_inc->execute();

        header("Location: ver_tickets.php");
        exit();
    }
}

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

$openCount = 0;
if ($rol_sesion !== 'Solicitante') {
    $countSql = "SELECT COUNT(*) AS total FROM solicitud WHERE estatus = 'ABIERTO'";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute();
    $openCount = (int) $countStmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Tickets - FONDAS</title>
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
        .btn-regresar { background: linear-gradient(135deg, #2e7d32, #27ae60); color: white; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; box-shadow: 0 8px 16px rgba(46,125,50,0.15); transition: transform 0.18s ease, background 0.18s ease; border: 1px solid #1b5e20; }
        .btn-regresar:hover { background: linear-gradient(135deg, #23903a, #1d7d31); transform: translateY(-2px); }
        .nombre-solicitante { color: #2e7d32; font-weight: bold; display: block; }
        .info-secundaria { color: #777; font-size: 0.82em; display: block; margin-top: 3px; }
        .link-id { color: #0288d1; font-weight: bold; text-decoration: none; border: 1px solid #0288d1; padding: 3px 10px; border-radius: 4px; display: inline-block; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            max-width: 100%;
            margin: 0;
        }

        .cintillo-container {
            background: white;
            padding: 0;
            border-radius: 0;
            text-align: center;
            text-align: center;
        }

        .cintillo {
            width: 100%;
            max-height: 140px;
            object-fit: contain;
            display: block;
        }

        .container {
            background: white;
            padding: 25px 40px;
            border-radius: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        h2 {
            color: #1b5e20;
            margin: 0;
            font-size: 24px;
        }

        .user-info {
            background: #f1f8e9;
            padding: 10px 15px;
            border-radius: 5px;
            border-left: 5px solid #2e7d32;
            font-size: 0.9em;
            margin-top: 10px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th {
            background-color: #2e7d32;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .abierto {
            background: #fffde7;
            color: #fbc02d;
            border-color: #fbc02d;
        }

        .proceso {
            background: #e1f5fe;
            color: #0288d1;
            border-color: #0288d1;
        }

        .urgente {
            background: #ffebee;
            color: #d32f2f;
            border-color: #d32f2f;
        }

        .cerrado {
            background: #f5f5f5;
            color: #9e9e9e;
            border-color: #bdbdbd;
        }

        .btn-aceptar {
            background: #0288d1;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 5px;
            font-weight: bold;
        }

        .btn-regresar {
            background: #2e7d32;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .nombre-solicitante {
            color: #2e7d32;
            font-weight: bold;
            display: block;
        }

        .info-secundaria {
            color: #777;
            font-size: 0.82em;
            display: block;
            margin-top: 3px;
        }

        .link-id {
            color: #0288d1;
            font-weight: bold;
            text-decoration: none;
            border: 1px solid #0288d1;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
        }

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
        .btn-new { background: linear-gradient(135deg, #2e7d32, #27ae60); color: white; padding: 14px 26px; border-radius: 10px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 10px; font-size: 0.95rem; box-shadow: 0 10px 20px rgba(46, 125, 50, 0.18); transition: transform 0.2s ease, background 0.2s ease; border: 1px solid #1b5e20; }
        .btn-new:hover { background: linear-gradient(135deg, #23903a, #1d7d31); transform: translateY(-2px); }
        .btn-new::before { content: '+'; font-size: 1.2rem; line-height: 1; }
        .btn-back::before { content: '\2190'; margin-right: 8px; font-size: 1.05rem; display: inline-block; }

        .new-ticket-alert { position: sticky; top: 0; z-index: 1000; margin-bottom: 15px; display: flex; justify-content: center; }
        .new-ticket-alert-content { width: 100%; max-width: 1200px; background: linear-gradient(135deg, #fff3e0, #ffe0b2); border: 2px solid #ffb74d; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 30px rgba(0,0,0,0.08); font-size: 1.05rem; color: #5d4037; animation: pulseAlert 1.2s ease-in-out infinite alternate; gap: 12px; }
        .new-ticket-alert-content .alert-left { display:flex; align-items:center; gap:12px; }
        .bell-icon { width:44px; height:44px; flex:0 0 44px; display:flex; align-items:center; justify-content:center; border-radius:50%; background: radial-gradient(circle at 30% 30%, #fff9e6, #fff3e0); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        .bell-icon svg { width:26px; height:26px; fill:#f57c00; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.12)); }
        .bell-icon.ring svg { animation: ringBell 1s ease-in-out; }
        @keyframes ringBell { 0% { transform: rotate(0deg); } 25% { transform: rotate(-12deg); } 50% { transform: rotate(10deg); } 75% { transform: rotate(-6deg); } 100% { transform: rotate(0deg); } }
        .notification-permission-alert { position: sticky; top: 0; z-index: 1000; margin-bottom: 15px; display: flex; justify-content: center; }
        .notification-permission-content { width: 100%; max-width: 1200px; background: #e3f2fd; border: 2px solid #90caf9; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 20px rgba(0,0,0,0.08); font-size: 1rem; color: #0d47a1; }
        .notification-permission-content span { display: inline-block; margin-right: 12px; }
        .new-ticket-alert-content strong { font-size: 1.1rem; }
        @keyframes pulseAlert { from { transform: translateY(0); } to { transform: translateY(-4px); } }
        
        .highlight-row { animation: highlightTicket 3s ease forwards; }
        @keyframes highlightTicket { from { background-color: rgba(255,243,224,0.95); } to { background-color: transparent; } }
        
        /* Estilos de Paginación e Info (Inferior) */
        .bottom-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .dataTables_wrapper .dataTables_info {
            margin: 0 !important;
            color: #666;
            font-size: 0.9em;
            font-style: italic;
            clear: none !important;
            padding: 0 !important;
        }

        .dataTables_wrapper .dataTables_paginate {
            margin: 0 !important;
            float: none !important;
            text-align: right !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 14px !important;
            margin-left: 4px;
            border: 1px solid #e0e0e0 !important;
            border-radius: 6px;
            cursor: pointer;
            color: #555 !important;
            text-decoration: none;
            background: white !important;
            font-size: 0.9em;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f8e9 !important;
            border-color: #2e7d32 !important;
            color: #2e7d32 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #2e7d32 !important;
            color: white !important;
            border-color: #2e7d32 !important;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .date-filters {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            .table-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box-wrapper {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-bottom: 10px;
            }
            .container {
                padding: 15px;
            }
            .btn-regresar {
                width: 100%;
                text-align: center;
            }
            #custom-dt-search {
                width: 100%;
            }
        }
    </style>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
</head>
<body>
    <div class="wrapper">
        <div class="cintillo-container">
            <img src="img/logo3.png" alt="Cintillo FONDAS" class="cintillo">
        </div>
        <nav style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; background-color: #2e7d32; color: white; padding: 12px 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
            <div style="font-weight: bold; font-size: 1.1rem; display: flex; align-items: center;">
                <span style="margin-right: 8px;">📋</span> PANEL DE GESTIÓN DE ESPECIALISTAS
            </div>
            <div style="display: flex; align-items: center; font-size: 0.9em; flex-wrap: wrap; gap: 15px;">
                <?php if ($rol_sesion !== 'Solicitante'): ?>
                    <a href="registro.php" style="background: linear-gradient(135deg, #43a047, #2e7d32); color: white; padding: 6px 15px; border-radius: 50px; text-decoration: none; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid white;">+ NUEVA SOLICITUD</a>
                <?php endif; ?>
                <span style="background: white; color: #333; padding: 6px 15px; border-radius: 50px; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> | <?php echo htmlspecialchars($info_perfil); ?>
                </span>
                <a href="<?php echo ($rol_sesion === 'Solicitante') ? 'index_solicitante.php' : 'views/home_especialista.php'; ?>" style="color: white; text-decoration: none; border: 1px solid white; padding: 6px 15px; border-radius: 50px; font-weight: bold;">
                    Volver
                </a>
            </div>
        </nav>

        <div class="container">
            <div id="newTicketAlert" class="new-ticket-alert" style="display:none;">
                <div class="new-ticket-alert-content">
                    <div class="alert-left">
                        <div class="bell-icon" id="bellIcon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 3.17 10.5 4v.68C7.63 5.36 6 7.92 6 11v5l-1.99 2h15.98L18 16z"/></svg>
                        </div>
                        <div>
                            <strong id="newTicketTitle">¡Nuevo ticket disponible!</strong>
                            <div id="newTicketCountText">Hay nuevos tickets abiertos.</div>
                        </div>
                    </div>
                    <div>
                        <button id="btnShowTickets" class="btn-aceptar">Ver ahora</button>
                    </div>
                </div>
            </div>
            <div id="notificationPermissionAlert" class="notification-permission-alert" style="display:none;">
                <div class="notification-permission-content">
                    <span id="notificationMessage">Para recibir alertas instantáneas de nuevos tickets, habilite las notificaciones del navegador. Si ya rechazó, revise la configuración de notificaciones de su navegador y vuelva a esta página.</span>
                    <button id="btnEnableNotifications" class="btn-aceptar">Activar notificaciones</button>
                </div>
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
            <div style="overflow-x: auto; width: 100%;">
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

            function playBeep() {
                // Intenta usar WebAudio para un timbre claro y potente. Si falla, usa el <audio> de fallback.
                var played = false;
                try {
                    var AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx) {
                        var ctx = new AudioCtx();
                        var master = ctx.createGain();
                        master.gain.setValueAtTime(0.7, ctx.currentTime);
                        master.connect(ctx.destination);

                        var freqs = [880, 660, 990];
                        freqs.forEach(function(f, i) {
                            var osc = ctx.createOscillator();
                            var g = ctx.createGain();
                            osc.type = (i === 1) ? 'triangle' : 'sine';
                            osc.frequency.setValueAtTime(f, ctx.currentTime);
                            g.gain.setValueAtTime(0.001, ctx.currentTime);
                            g.gain.linearRampToValueAtTime(0.9, ctx.currentTime + 0.008);
                            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28 + i * 0.04);
                            osc.connect(g); g.connect(master);
                            osc.start(ctx.currentTime + i * 0.03);
                            osc.stop(ctx.currentTime + 0.28 + i * 0.03);
                        });

                        var bell = document.getElementById('bellIcon');
                        if (bell) { bell.classList.add('ring'); setTimeout(function(){ bell.classList.remove('ring'); }, 1000); }

                        setTimeout(function(){ if (ctx && ctx.close) ctx.close(); }, 1400);
                        played = true;
                    }
                } catch (e) {
                    console.warn('WebAudio failed, falling back to audio element', e);
                }

                if (!played) {
                    var fallback = document.getElementById('notifAudio');
                    if (fallback) {
                        try {
                            fallback.currentTime = 0;
                            fallback.volume = 0.9;
                            fallback.play().catch(function(err){ console.warn('Audio fallback play failed', err); });
                            var bell = document.getElementById('bellIcon');
                            if (bell) { bell.classList.add('ring'); setTimeout(function(){ bell.classList.remove('ring'); }, 1000); }
                        } catch (e) {
                            console.error('Fallback audio error', e);
                        }
                    }
                }
            }

            function requestNotificationPermission() {
                if (!('Notification' in window)) return;
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(function(permission) {
                        console.log('Permiso de notificaciones:', permission);
                        updateNotificationBanner();
                    });
                } else {
                    updateNotificationBanner();
                }
            }

            function updateNotificationBanner() {
                var banner = $('#notificationPermissionAlert');
                var message = $('#notificationMessage');
                var button = $('#btnEnableNotifications');
                if (!('Notification' in window)) {
                    banner.hide();
                    return;
                }
                if (Notification.permission === 'denied') {
                    message.text('Las notificaciones están bloqueadas. Abra la configuración de su navegador para permitirlas y luego vuelva a esta página.');
                    button.text('Revisar configuración');
                    banner.show();
                } else {
                    banner.hide();
                }
            }

            function showBrowserNotification(diff) {
                if (!('Notification' in window) || Notification.permission !== 'granted') return;
                var title = diff === 1 ? 'Nuevo ticket en FONDAS' : diff + ' nuevos tickets en FONDAS';
                var body = diff === 1 ? '1 ticket nuevo abierto. Haz clic para revisar.' : 'Hay ' + diff + ' tickets nuevos. Haz clic para revisar.';
                var options = {
                    body: body,
                    icon: 'img/logo3.png',
                    tag: 'fondas-new-ticket',
                    renotify: true,
                    requireInteraction: true
                };
                try {
                    var notification = new Notification(title, options);
                    notification.onclick = function () {
                        window.focus();
                        // Si estamos en otra pestaña, abrir la página de tickets
                        try { window.location.href = 'ver_tickets.php'; } catch (e) { console.warn(e); }
                        this.close();
                    };
                } catch (e) {
                    console.warn('Notification show failed', e);
                }
                // Feedback háptico si está disponible
                if (navigator.vibrate) {
                    navigator.vibrate([200, 100, 200]);
                }
            }

            function showNewTicketAlert(diff) {
                var alertBox = $('#newTicketAlert');
                var title = diff === 1 ? '¡Nuevo ticket disponible!' : '¡' + diff + ' nuevos tickets!';
                $('#newTicketTitle').text(title);
                $('#newTicketCountText').text(diff === 1 ? 'Hay 1 nuevo ticket abierto.' : 'Hay ' + diff + ' nuevos tickets abiertos.');
                alertBox.show();
                playBeep();
                showBrowserNotification(diff);
                // Mantener visible más tiempo si hay varios tickets
                var timeout = (diff >= 5) ? 20000 : 12000;
                setTimeout(function(){ alertBox.hide(); }, timeout);
            }

            function highlightNewRows() {
                $('#tablaTickets tbody tr').each(function() {
                    var statusText = $(this).find('td:nth-child(5) .status-pill').text().toUpperCase();
                    if (statusText.indexOf('ABIERTO') !== -1 || statusText.indexOf('URGENTE') !== -1) {
                        $(this).addClass('highlight-row');
                    }
                });
            }

            $('#btnShowTickets').on('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                highlightNewRows();
            });

            $('#btnEnableNotifications').on('click', function() {
                requestNotificationPermission();
            });

            if ('Notification' in window) {
                requestNotificationPermission();
                updateNotificationBanner();
            }

            var lastOpenCount = <?php echo $openCount; ?>;
            function checkForNewTickets() {
                fetch('ajax/check_new_tickets.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && typeof data.open === 'number') {
                        var currentOpen = data.open;
                        if (currentOpen > lastOpenCount) {
                            showNewTicketAlert(currentOpen - lastOpenCount);
                            lastOpenCount = currentOpen;
                        } else {
                            lastOpenCount = currentOpen;
                        }
                    }
                }).catch(function(){ /* Ignorar errores temporales */ });
            }

            setInterval(checkForNewTickets, 12000);
        });
    </script>
        <audio id="notifAudio" preload="auto">
            <source src="assets/notification_fallback.mp3" type="audio/mpeg">
            <source src="assets/notification_fallback.ogg" type="audio/ogg">
        </audio>
</body>
</html>