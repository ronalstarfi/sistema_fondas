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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Auditoría Forense - FONDAS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .wrapper { max-width: 1400px; margin: auto; }
        .cintillo-container { background: white; padding: 10px; border-radius: 10px 10px 0 0; text-align: center; border-bottom: 4px solid #b71c1c; }
        .cintillo { max-width: 100%; height: auto; }
        .container { background: white; padding: 25px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { color: #b71c1c; margin: 0; font-size: 24px; }
        .user-info { background: #fbe9e7; padding: 10px 15px; border-radius: 5px; border-left: 5px solid #b71c1c; font-size: 0.9em; margin-top: 10px; display: inline-block; color: #d84315; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th { background-color: #37474f; color: white; padding: 12px; text-align: left; font-size: 0.85em; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 13px; }
        .status-pill { padding: 4px 12px; border-radius: 15px; font-weight: bold; font-size: 11px; text-transform: uppercase; border: 1px solid; display: inline-block; min-width: 80px; text-align: center; }
        .abierto { background: #fffde7; color: #fbc02d; border-color: #fbc02d; }
        .proceso { background: #e1f5fe; color: #0288d1; border-color: #0288d1; }
        .cerrado { background: #f5f5f5; color: #9e9e9e; border-color: #bdbdbd; }
        .btn-regresar { background: #37474f; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; }
        .info-secundaria { color: #777; font-size: 0.82em; display: block; margin-top: 3px; }
        .ip-address { font-family: monospace; background: #eceff1; padding: 2px 5px; border-radius: 3px; border: 1px solid #cfd8dc; color: #37474f; }
        
        /* Toolbar */
        .table-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #eceff1; padding: 15px 20px; border-radius: 8px; border: 1px solid #cfd8dc; margin-bottom: 20px; gap: 15px; }
        .search-box-wrapper { display: flex; align-items: center; gap: 8px; }
        .dataTables_wrapper .dataTables_filter { display: none; }
        .search-box-wrapper input { border: 2px solid #546e7a; border-radius: 20px; padding: 6px 15px; outline: none; font-family: inherit; width: 300px; }
        .btn-search { background: #455a64; color: white; border: none; padding: 7px 18px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-search:hover { background: #263238; }

        .bottom-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 14px !important; margin-left: 4px; border: 1px solid #e0e0e0 !important; border-radius: 6px; cursor: pointer; color: #555 !important; text-decoration: none; background: white !important; font-size: 0.9em; transition: all 0.2s; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #37474f !important; color: white !important; border-color: #37474f !important; font-weight: bold; }
    </style>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
</head>
<body>
    <div class="wrapper">
        <div class="cintillo-container">
            <img src="img/logo3.png" alt="Cintillo FONDAS" class="cintillo">
        </div>

        <div class="container">
            <div class="header-top">
                <div>
                    <h2>Bitácora de Auditoría Forense</h2>
                    <div class="user-info">
                        <strong>AUTORIDAD:</strong> <?php echo htmlspecialchars($nombre_usuario); ?> (<?php echo htmlspecialchars($rol_sesion); ?>)
                    </div>
                </div>
                <a href="views/home_especialista.php" class="btn-regresar">Volver al Menú Principal</a>
            </div>

            <div class="table-toolbar">
                <div class="search-box-wrapper">
                    <input type="search" id="dt-custom-search" placeholder="Buscar por usuario, IP, dispositivo o Ticket...">
                    <button id="btn-custom-search" class="btn-search">Buscar Registros</button>
                </div>
            </div>
            
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
                            if ($log['estatus_anterior'] === 'CERRADO') $clase_ant = 'cerrado';
                            elseif ($log['estatus_anterior'] === 'EN PROCESO') $clase_ant = 'proceso';

                            $clase_nue = 'abierto'; 
                            if ($log['estatus_nuevo'] === 'CERRADO') $clase_nue = 'cerrado';
                            elseif ($log['estatus_nuevo'] === 'EN PROCESO') $clase_nue = 'proceso';
                        ?>
                        <tr>
                            <td style="color: #455a64; font-weight: bold;"><?php echo date('d/m/Y - h:i A', strtotime($log['fecha_movimiento'])); ?></td>
                            <td>
                                Cambio de estado:<br>
                                <span class="status-pill <?php echo $clase_ant; ?>"><?php echo htmlspecialchars($log['estatus_anterior'] ?? 'N/A'); ?></span>
                                ➔ 
                                <span class="status-pill <?php echo $clase_nue; ?>"><?php echo htmlspecialchars($log['estatus_nuevo'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['usuario_que_cambio'] ?? 'Desconocido'); ?></strong>
                                <span class="info-secundaria">Cédula: <?php echo htmlspecialchars($log['cedula_usuario'] ?? 'N/A'); ?></span>
                                <span class="info-secundaria">Rol: <?php echo htmlspecialchars($log['rol_usuario'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="ip-address">IP: <?php echo htmlspecialchars($log['direccion_ip'] ?? '127.0.0.1'); ?></span>
                                <span class="info-secundaria" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                    <?php echo htmlspecialchars($log['user_agent'] ?? 'Dispositivo Desconocido'); ?>
                                </span>
                            </td>
                            <td>
                                <strong>Ticket #<?php echo htmlspecialchars($log['id_solicitud']); ?></strong>
                                <span class="info-secundaria"><?php echo htmlspecialchars($log['detalle_ticket'] ?? 'Sin detalle'); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
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
        });
    </script>
</body>
</html>
