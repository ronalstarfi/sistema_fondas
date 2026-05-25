<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$rol_sesion = $_SESSION['rol'];
// Restringir el módulo solo al Jefe (máxima autoridad)
if ($rol_sesion !== 'Jefe') {
    die("Acceso Denegado. Solo la máxima autoridad puede ver la bitácora de auditoría.");
}

$database = new Database();
$db = $database->getConnection();
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';

// Consultar los registros de auditoría
$sql = "SELECT 
            a.id_auditoria,
            a.id_solicitud,
            a.estatus_anterior,
            a.estatus_nuevo,
            a.usuario_que_cambio,
            a.cedula_usuario,
            a.rol_usuario,
            a.direccion_ip,
            a.user_agent,
            a.fecha_movimiento,
            s.descripcion AS detalle_ticket
        FROM auditoria_solicitudes a
        LEFT JOIN solicitud s ON a.id_solicitud = s.id
        ORDER BY a.fecha_movimiento DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consultar los registros de auditoría general (Accesos y BD)
$sql_gen = "SELECT id, tipo_movimiento, descripcion, usuario, direccion_ip, fecha 
            FROM auditoria_general 
            ORDER BY fecha DESC";
$stmt_gen = $db->prepare($sql_gen);
$stmt_gen->execute();
$logs_gen = $stmt_gen->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Módulo de Auditoría Forense - FONDAS</title>
    <style>
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
            margin: 0 auto;
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
            color: #b71c1c;
            margin: 0;
            font-size: 24px;
        }

        .user-info {
            background: #fbe9e7;
            padding: 10px 15px;
            border-radius: 5px;
            border-left: 5px solid #b71c1c;
            font-size: 0.9em;
            margin-top: 10px;
            display: inline-block;
            color: #d84315;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th {
            background-color: #37474f;
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
            font-size: 13px;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid;
            display: inline-block;
            min-width: 80px;
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

        .cerrado {
            background: #f5f5f5;
            color: #9e9e9e;
            border-color: #bdbdbd;
        }

        .btn-regresar {
            background: #37474f;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .info-secundaria {
            color: #777;
            font-size: 0.82em;
            display: block;
            margin-top: 3px;
        }

        .ip-address {
            font-family: monospace;
            background: #eceff1;
            padding: 2px 5px;
            border-radius: 3px;
            border: 1px solid #cfd8dc;
            color: #37474f;
        }

        /* Toolbar */
        .table-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            background: #eceff1;
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid #cfd8dc;
            margin-bottom: 20px;
            gap: 15px;
        }

        .search-box-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .search-box-wrapper input {
            border: 2px solid #546e7a;
            border-radius: 20px;
            padding: 6px 15px;
            outline: none;
            font-family: inherit;
            width: 100%;
        }

        .btn-search {
            background: #455a64;
            color: white;
            border: none;
            padding: 7px 18px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-search:hover {
            background: #263238;
        }

        .date-filters {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-filters label {
            font-weight: bold;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .date-filters input[type="date"] {
            padding: 6px 12px;
            border: 1px solid #a5d6a7;
            border-radius: 6px;
            outline: none;
            color: #333;
            font-family: inherit;
        }

        .date-filters input[type="date"]:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 5px rgba(46, 125, 50, 0.3);
        }

        .btn-clear {
            background: #fff;
            border: 1px solid #ccc;
            padding: 6px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            color: #555;
            transition: 0.2s;
        }

        .btn-clear:hover {
            background: #e0e0e0;
        }

        .btn-tab {
            background: transparent;
            border: none;
            padding: 10px 20px;
            font-size: 1.1em;
            font-weight: bold;
            color: #777;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .btn-tab.active-tab {
            color: #2e7d32;
            border-bottom: 3px solid #2e7d32;
        }

        .btn-tab:hover:not(.active-tab) {
            color: #2e7d32;
            opacity: 0.8;
        }

        .bottom-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
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

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #37474f !important;
            color: white !important;
            border-color: #37474f !important;
            font-weight: bold;
        }

        /* Modal Estilos */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            width: 800px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #888;
        }

        .modal-close:hover {
            color: #d32f2f;
        }

        .modal-body p {
            margin: 8px 0;
            font-size: 0.95rem;
            color: #444;
        }

        .modal-body strong {
            color: #222;
        }
        
        .clickable-row {
            cursor: pointer;
            transition: background 0.2s;
        }
        .clickable-row:hover {
            background-color: #e8f5e9 !important;
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .btn-regresar {
                width: 100%;
                text-align: center;
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
                gap: 10px;
            }

            .search-box-wrapper input {
                width: 100%;
            }

            .container {
                padding: 15px;
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
                <span style="margin-right: 8px;">🏛️</span> BITÁCORA DE AUDITORÍA FORENSE
            </div>
            <div style="display: flex; align-items: center; font-size: 0.9em; flex-wrap: wrap; gap: 15px;">
                <span style="background: white; color: #333; padding: 6px 15px; border-radius: 50px; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </span>
                <a href="views/home_especialista.php" style="color: white; text-decoration: none; border: 1px solid white; padding: 6px 15px; border-radius: 50px; font-weight: bold;">
                    Volver
                </a>
            </div>
        </nav>

        <div class="container">

            <div style="display:flex; gap:15px; margin-bottom:20px; border-bottom:2px solid #ddd; padding-bottom:10px;">
                <button id="btnTabTickets" class="btn-tab active-tab">Historial de Tickets</button>
                <button id="btnTabGeneral" class="btn-tab">Auditoría Global (Accesos / BD)</button>
            </div>

            <!-- TABLA DE TICKETS -->
            <div id="tabTickets">
                <div class="table-toolbar">
                <div class="date-filters">
                    <?php $hoy = date('Y-m-d'); ?>
                    <label>Desde: <input type="date" id="min_date" value="<?php echo $hoy; ?>"></label>
                    <label>Hasta: <input type="date" id="max_date" value="<?php echo $hoy; ?>"></label>
                    <button id="clear_dates" class="btn-clear">Limpiar Filtro</button>
                </div>
                <div class="search-box-wrapper">
                    <input type="search" id="dt-custom-search"
                        placeholder="Buscar por usuario, IP, dispositivo o Ticket...">
                    <button id="btn-custom-search" class="btn-search">Buscar Registros</button>
                </div>
            </div>

            <div style="overflow-x: auto; width: 100%;">
                <table id="tablaAuditoria">
                    <thead>
                        <tr>
                            <th style="width: 140px;">FECHA Y HORA</th>
                            <th>ACCIÓN REALIZADA</th>
                            <th>USUARIO (AUTOR)</th>
                            <th>METADATOS DE RED</th>
                            <th>TICKET ASOCIADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $clase_ant = 'abierto';
                            if ($log['estatus_anterior'] === 'CERRADO')
                                $clase_ant = 'cerrado';
                            elseif ($log['estatus_anterior'] === 'EN PROCESO')
                                $clase_ant = 'proceso';

                            $clase_nue = 'abierto';
                            if ($log['estatus_nuevo'] === 'CERRADO')
                                $clase_nue = 'cerrado';
                            elseif ($log['estatus_nuevo'] === 'EN PROCESO')
                                $clase_nue = 'proceso';
                            ?>
                            <tr class="clickable-row" 
                                data-ticket="<?php echo htmlspecialchars($log['id_solicitud']); ?>"
                                data-detalle="<?php echo htmlspecialchars($log['detalle_ticket'] ?? 'Sin detalle'); ?>"
                                data-fecha="<?php echo date('d/m/Y - h:i A', strtotime($log['fecha_movimiento'])); ?>"
                                data-autor="<?php echo htmlspecialchars($log['usuario_que_cambio'] ?? 'Desconocido'); ?>"
                                data-ip="<?php echo htmlspecialchars($log['direccion_ip'] ?? '127.0.0.1'); ?>"
                                data-accion="<?php echo htmlspecialchars($log['estatus_anterior']) . ' ➔ ' . htmlspecialchars($log['estatus_nuevo']); ?>">
                                <td data-fecha="<?php echo date('Y-m-d', strtotime($log['fecha_movimiento'])); ?>" style="color: #455a64; font-weight: bold;">
                                    <?php echo date('d/m/Y - h:i A', strtotime($log['fecha_movimiento'])); ?></td>
                                <td>
                                    Cambio de estado:<br>
                                    <span
                                        class="status-pill <?php echo $clase_ant; ?>"><?php echo htmlspecialchars($log['estatus_anterior'] ?? 'N/A'); ?></span>
                                    ➔
                                    <span
                                        class="status-pill <?php echo $clase_nue; ?>"><?php echo htmlspecialchars($log['estatus_nuevo'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['usuario_que_cambio'] ?? 'Desconocido'); ?></strong>
                                    <span class="info-secundaria">Cédula:
                                        <?php echo htmlspecialchars($log['cedula_usuario'] ?? 'N/A'); ?></span>
                                    <span class="info-secundaria">Rol:
                                        <?php echo htmlspecialchars($log['rol_usuario'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="ip-address">IP:
                                        <?php echo htmlspecialchars($log['direccion_ip'] ?? '127.0.0.1'); ?></span>
                                    <span class="info-secundaria"
                                        style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                        <?php echo htmlspecialchars($log['user_agent'] ?? 'Dispositivo Desconocido'); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>Ticket #<?php echo htmlspecialchars($log['id_solicitud']); ?></strong>
                                    <span
                                        class="info-secundaria"><?php echo htmlspecialchars($log['detalle_ticket'] ?? 'Sin detalle'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div> <!-- End Tab Tickets -->

            <!-- TABLA GENERAL -->
            <div id="tabGeneral" style="display:none;">
                <div class="table-toolbar" style="margin-bottom:20px;">
                    <div class="date-filters">
                        <label>Desde: <input type="date" id="min_date_gen" value="<?php echo $hoy; ?>"></label>
                        <label>Hasta: <input type="date" id="max_date_gen" value="<?php echo $hoy; ?>"></label>
                        <button id="clear_dates_gen" class="btn-clear">Limpiar Filtro</button>
                    </div>
                    <div class="search-box-wrapper">
                        <input type="search" id="dt-gen-search" placeholder="Buscar movimientos globales, IPs, usuarios...">
                        <button id="btn-gen-search" class="btn-search">Buscar Registros</button>
                    </div>
                </div>
                <div style="overflow-x: auto; width: 100%;">
                    <table id="tablaGeneral">
                        <thead>
                            <tr>
                                <th style="width: 150px;">FECHA Y HORA</th>
                                <th>TIPO DE MOVIMIENTO</th>
                                <th>DESCRIPCIÓN</th>
                                <th>USUARIO</th>
                                <th>IP / ORIGEN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_gen as $g): ?>
                                <tr class="clickable-row-gen" style="cursor:pointer;"
                                    data-fecha="<?php echo date('d/m/Y - h:i A', strtotime($g['fecha'])); ?>"
                                    data-tipo="<?php echo htmlspecialchars($g['tipo_movimiento']); ?>"
                                    data-desc="<?php echo htmlspecialchars($g['descripcion']); ?>"
                                    data-usuario="<?php echo htmlspecialchars($g['usuario']); ?>"
                                    data-ip="<?php echo htmlspecialchars($g['direccion_ip']); ?>">
                                    <td data-fecha="<?php echo date('Y-m-d', strtotime($g['fecha'])); ?>" style="color: #455a64; font-weight: bold; transition: background 0.2s;"><?php echo date('d/m/Y - h:i A', strtotime($g['fecha'])); ?></td>
                                    <td>
                                        <span class="status-pill" style="background:#e3f2fd; color:#0288d1; border-color:#0288d1;">
                                            <?php echo htmlspecialchars($g['tipo_movimiento']); ?>
                                        </span>
                                    </td>
                                    <td style="color:#444;"><?php echo htmlspecialchars($g['descripcion']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($g['usuario']); ?></strong></td>
                                    <td><span class="ip-address"><?php echo htmlspecialchars($g['direccion_ip']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- End Tab General -->

        </div>
    </div>

    <!-- Modal para Auditoría Global -->
    <div id="globalModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:9999;">
        <div class="modal-box" style="background:#fff; width:90%; max-width:600px; border-radius:15px; padding:25px; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <span class="modal-close" id="closeGlobalModal" style="position:absolute; top:15px; right:20px; font-size:1.5rem; cursor:pointer; color:#888;">&times;</span>
            <div class="modal-header" style="border-bottom:2px solid #2e7d32; padding-bottom:10px; margin-bottom:20px;">
                <h2 style="margin:0; font-size:1.4rem; color:#2e7d32;"><i class="fas fa-info-circle me-2"></i> Detalle de Movimiento Global</h2>
            </div>
            <div class="modal-body">
                <div class="info-highlight" style="background-color: #e3f2fd; border-left: 5px solid #0288d1; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <p style="margin:5px 0;"><strong>Tipo de Movimiento:</strong> <span id="modGenTipo" class="status-pill" style="background:#0288d1; color:white; padding:4px 10px; border-radius:10px; font-size:0.8rem;"></span></p>
                    <p style="margin:5px 0;"><strong>Fecha y Hora:</strong> <span id="modGenFecha"></span></p>
                    <p style="margin:5px 0;"><strong>Usuario Responsable:</strong> <span id="modGenUsuario"></span></p>
                    <p style="margin:5px 0;"><strong>Dirección IP:</strong> <span id="modGenIp"></span></p>
                </div>
                
                <h4 style="color:#455a64; font-size:1.1rem; margin-bottom:10px;">Descripción Detallada</h4>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <p id="modGenDesc" style="margin: 0; color: #444; word-break: break-word; line-height:1.5;"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="closeModal">&times;</span>
            <div class="modal-header">
                <i class="fas fa-history me-2"></i> Historial Completo - Ticket #<span id="modalTicketId"></span>
            </div>
            <div class="modal-body">
                <div style="background:#e3f2fd; padding:15px; border-radius:6px; border-left:4px solid #0288d1; margin-bottom: 15px;">
                    <strong style="color:#0288d1; display:block; margin-bottom:10px; font-size:1.1em;"><i class="fas fa-info-circle me-1"></i> Información del Movimiento Seleccionado:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 0.95em; color: #333;">
                        <div style="flex: 1 1 45%;"><strong style="color:#0288d1;">Fecha:</strong> <span id="modalFecha"></span></div>
                        <div style="flex: 1 1 45%;"><strong style="color:#0288d1;">Autor:</strong> <span id="modalAutor"></span></div>
                        <div style="flex: 1 1 45%;"><strong style="color:#0288d1;">IP/Dispositivo:</strong> <span id="modalIp"></span></div>
                        <div style="flex: 1 1 45%;"><strong style="color:#0288d1;">Acción Realizada:</strong> <span id="modalAccion"></span></div>
                    </div>
                </div>

                <div style="background:#f9f9f9; padding:15px; border-radius:6px; border-left:4px solid #2e7d32; margin-bottom: 20px;">
                    <strong style="color:#2e7d32; display:block; margin-bottom:5px;">Detalle del Reporte Original:</strong>
                    <span id="modalDetalle" style="color:#444; font-size:0.95em;"></span>
                </div>
                
                <h4 style="color:#1b5e20; margin-bottom: 10px; font-size: 1.1em; border-bottom: 1px solid #ccc; padding-bottom: 5px;">Movimientos Registrados</h4>
                <div id="modalHistoryContainer">
                    <!-- Aquí se inyectará el historial en formato de tabla -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
    <script>
        // Exportar los logs a JS para el historial del modal
        var allLogs = <?php echo json_encode($logs); ?>;

        // Función personalizada de DataTables para filtrar por fechas usando el data-fecha del <td>
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                var minStr = $('#min_date').val();
                var maxStr = $('#max_date').val();

                var tr = settings.aoData[dataIndex].nTr;
                var dateStr = $(tr).find('td:first').data('fecha'); // obtenemos YYYY-MM-DD

                if (!dateStr) return true;

                var targetDate = new Date(dateStr + 'T00:00:00Z');

                if (minStr) {
                    var minDate = new Date(minStr + 'T00:00:00Z');
                    if (targetDate < minDate) return false;
                }
                if (maxStr) {
                    var maxDate = new Date(maxStr + 'T23:59:59Z');
                    if (targetDate > maxDate) return false;
                }
                return true;
            }
        );

        // Date range filter for General Table
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaGeneral') {
                    return true;
                }
                var minStr = $('#min_date_gen').val();
                var maxStr = $('#max_date_gen').val();

                var tr = settings.aoData[dataIndex].nTr;
                var dateStr = $(tr).find('td:first').data('fecha');

                if (!dateStr) return true;
                var targetDate = new Date(dateStr + 'T00:00:00Z');

                if (minStr) {
                    var minDate = new Date(minStr + 'T00:00:00Z');
                    if (targetDate < minDate) return false;
                }
                if (maxStr) {
                    var maxDate = new Date(maxStr + 'T23:59:59Z');
                    if (targetDate > maxDate) return false;
                }
                return true;
            }
        );

        $(document).ready(function () {
            var table = $('#tablaAuditoria').DataTable({
                "dom": 't<"bottom-controls"ip>',
                "language": {
                    "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 registros",
                    "sZeroRecords": "No hay movimientos registrados",
                    "oPaginate": { "sNext": "Siguiente", "sPrevious": "Anterior" }
                },
                "pageLength": 15,
                "ordering": false
            });

            $('#dt-custom-search').on('keypress', function (e) {
                if (e.which == 13) { table.search(this.value).draw(); }
            });

            $('#btn-custom-search').on('click', function (e) {
                e.preventDefault();
                table.search($('#dt-custom-search').val()).draw();
            });

            $('#min_date, #max_date').on('change', function () {
                table.draw();
            });

            $('#clear_dates').on('click', function () {
                $('#min_date').val('');
                $('#max_date').val('');
                $('#dt-custom-search').val('');
                table.search('').draw();
            });
            
            // Draw table on init to apply default today filter
            table.draw();

            // Lógica del Modal
            $('#tablaAuditoria tbody').on('click', '.clickable-row', function() {
                var ticket = $(this).data('ticket');
                var detalle = $(this).data('detalle');
                var fecha = $(this).data('fecha');
                var autor = $(this).data('autor');
                var ip = $(this).data('ip');
                var accion_ant = $(this).closest('tr').find('.status-pill').eq(0).prop('outerHTML');
                var accion_nue = $(this).closest('tr').find('.status-pill').eq(1).prop('outerHTML');
                var accion = accion_ant + ' ➔ ' + accion_nue;

                $('#modalTicketId').text(ticket);
                $('#modalDetalle').text(detalle);
                $('#modalFecha').text(fecha);
                $('#modalAutor').text(autor);
                $('#modalIp').text(ip);
                $('#modalAccion').html(accion);

                // Filtrar el historial completo de este ticket
                var ticketLogs = allLogs.filter(function(log) {
                    return log.id_solicitud == ticket;
                });

                // Construir la tabla de historial
                var historyHtml = '<table style="width:100%; border-collapse: collapse; font-size: 0.9em; text-align: left;">';
                historyHtml += '<thead style="background:#e8f5e9; border-bottom: 2px solid #2e7d32;">' +
                               '<tr>' +
                               '<th style="padding:10px;">Fecha y Hora</th>' +
                               '<th style="padding:10px;">Transición de Estado</th>' +
                               '<th style="padding:10px;">Usuario Responsable</th>' +
                               '<th style="padding:10px;">Dispositivo / IP</th>' +
                               '</tr></thead><tbody>';
                               
                ticketLogs.forEach(function(log) {
                    // Formatear estado para que se vea como en la tabla principal
                    var clase_ant = 'cerrado';
                    if (log.estatus_anterior === 'ABIERTO') clase_ant = 'abierto';
                    else if (log.estatus_anterior === 'EN PROCESO') clase_ant = 'proceso';
                    
                    var clase_nue = 'cerrado';
                    if (log.estatus_nuevo === 'ABIERTO') clase_nue = 'abierto';
                    else if (log.estatus_nuevo === 'EN PROCESO') clase_nue = 'proceso';

                    var badgeAnt = '<span class="status-pill ' + clase_ant + '">' + log.estatus_anterior + '</span>';
                    var badgeNue = '<span class="status-pill ' + clase_nue + '">' + log.estatus_nuevo + '</span>';

                    historyHtml += '<tr style="border-bottom: 1px solid #ddd;">' +
                                   '<td style="padding:10px; font-weight:bold; color:#455a64;">' + log.fecha_movimiento + '</td>' +
                                   '<td style="padding:10px;">' + badgeAnt + ' ➔ ' + badgeNue + '</td>' +
                                   '<td style="padding:10px;"><strong>' + (log.usuario_que_cambio || 'Desconocido') + '</strong><br><small style="color:#777;">Rol: ' + (log.rol_usuario || 'N/A') + '</small></td>' +
                                   '<td style="padding:10px;"><span class="ip-address">IP: ' + (log.direccion_ip || '127.0.0.1') + '</span></td>' +
                                   '</tr>';
                });
                historyHtml += '</tbody></table>';

                if(ticketLogs.length === 0) {
                    historyHtml = '<p style="padding:15px; text-align:center; color:#777;">No hay historial disponible para este ticket.</p>';
                }

                $('#modalHistoryContainer').html(historyHtml);

                $('#ticketModal').css('display', 'flex');
            });

            $('#closeModal').on('click', function() {
                $('#ticketModal').css('display', 'none');
            });

            $(window).on('click', function(e) {
                if ($(e.target).is('#ticketModal')) {
                    $('#ticketModal').css('display', 'none');
                }
            });

            // Lógica de Tabs
            $('#btnTabTickets').on('click', function() {
                $('.btn-tab').removeClass('active-tab');
                $(this).addClass('active-tab');
                $('#tabGeneral').hide();
                $('#tabTickets').fadeIn();
            });

            $('#btnTabGeneral').on('click', function() {
                $('.btn-tab').removeClass('active-tab');
                $(this).addClass('active-tab');
                $('#tabTickets').hide();
                $('#tabGeneral').fadeIn();
                tableGen.columns.adjust().draw(); // Re-ajustar datatables al mostrar
            });

            // Inicializar DataTable para Auditoría General
            var tableGen = $('#tablaGeneral').DataTable({
                "dom": 't<"bottom-controls"ip>',
                "language": {
                    "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 registros",
                    "sZeroRecords": "No hay movimientos registrados",
                    "oPaginate": { "sNext": "Siguiente", "sPrevious": "Anterior" }
                },
                "pageLength": 15,
                "ordering": false
            });

            $('#dt-gen-search').on('keypress', function (e) {
                if (e.which == 13) { tableGen.search(this.value).draw(); }
            });

            $('#btn-gen-search').on('click', function (e) {
                e.preventDefault();
                tableGen.search($('#dt-gen-search').val()).draw();
            });

            $('#min_date_gen, #max_date_gen').on('change', function () {
                tableGen.draw();
            });

            $('#clear_dates_gen').on('click', function () {
                $('#min_date_gen').val('');
                $('#max_date_gen').val('');
                $('#dt-gen-search').val('');
                tableGen.search('').draw();
            });

            // Draw table on init to apply default today filter
            tableGen.draw();

            // Lógica Modal Global
            $('#tablaGeneral tbody').on('click', '.clickable-row-gen', function() {
                $('#modGenFecha').text($(this).data('fecha'));
                $('#modGenTipo').text($(this).data('tipo'));
                $('#modGenUsuario').text($(this).data('usuario'));
                $('#modGenIp').text($(this).data('ip'));
                $('#modGenDesc').text($(this).data('desc'));
                $('#globalModal').css('display', 'flex');
            });

            $('#closeGlobalModal').on('click', function() {
                $('#globalModal').css('display', 'none');
            });

            $(window).on('click', function(e) {
                if ($(e.target).is('#globalModal')) {
                    $('#globalModal').css('display', 'none');
                }
            });
        });
    </script>
</body>

</html>