<?php
// 1. Conexión a la base de datos
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Inicialización de variables
$solicitante = null;
$cedula_buscada = "";

// 2. Lógica para buscar el solicitante
if (isset($_POST['buscar_cedula'])) {
    $cedula_buscada = $_POST['ci_busqueda'];

    if($db) {
        $query = "SELECT ci, nombre, ubicacion FROM solicitante WHERE ci = :ci LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ci', $cedula_buscada);
        $stmt->execute();
        $solicitante = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- INTEGRACIÓN CNE API (Extraída de Starfi 2.0) ---
        if (!$solicitante) {
            try {
                // Conectar a la BD de starfi para sacar la configuración de la API
                $con_starfi = new PDO("mysql:host=localhost;dbname=starfi", "root", "PARALELEPIPEDO3312");
                $con_starfi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt_conf = $con_starfi->query("SELECT * FROM api_nacional_config ORDER BY id DESC LIMIT 1");
                $config = $stmt_conf->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    $base_url = rtrim(trim($config['api_url']), '/');
                    $app_id = urlencode($config['app_id']);
                    $token = urlencode($config['api_token']);
                    $url = $base_url . "?app_id=$app_id&token=$token&nacionalidad=V&cedula=" . intval($cedula_buscada);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    $json_response = curl_exec($ch);
                    curl_close($ch);

                    if ($json_response) {
                        $api_data = json_decode($json_response, true);
                        if (isset($api_data['data']) && !empty($api_data['data']) && $api_data['data'] !== false) {
                            $d = $api_data['data'];
                            $nombre_parts = [];
                            if (!empty($d['primer_nombre'])) $nombre_parts[] = $d['primer_nombre'];
                            if (!empty($d['segundo_nombre'])) $nombre_parts[] = $d['segundo_nombre'];
                            if (!empty($d['primer_apellido'])) $nombre_parts[] = $d['primer_apellido'];
                            if (!empty($d['segundo_apellido'])) $nombre_parts[] = $d['segundo_apellido'];
                            
                            $nombre_completo = implode(' ', $nombre_parts);
                            
                            // Auto-registrar al solicitante en FONDAS
                            $ubicacion_default = 'Nuevo Ingreso (Por Asignar)';
                            $ins = $db->prepare("INSERT INTO solicitante (ci, nombre, ubicacion) VALUES (:ci, :nom, :ub)");
                            if ($ins->execute([':ci' => $cedula_buscada, ':nom' => $nombre_completo, ':ub' => $ubicacion_default])) {
                                $solicitante = [
                                    'ci' => $cedula_buscada,
                                    'nombre' => $nombre_completo,
                                    'ubicacion' => $ubicacion_default,
                                    'cne_api' => true
                                ];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Silencioso, si falla la API o la BD starfi, simplemente se muestra que no existe.
            }
        }
        // --- FIN INTEGRACIÓN CNE ---
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Ticket - FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .navbar { background-color: #2c3e50; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-label { color: #2c3e50; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-9"> <!-- Un poco más ancho para que luzca el cintillo -->
            
            <!-- Botón Regresar minimalista arriba de la tarjeta -->
            <div class="mb-2">
                <a href="javascript:history.back()" style="text-decoration: none; color: #666; font-weight: bold; font-size: 0.9em;">
                    ← REGRESAR AL PANEL
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0"> <!-- p-0 para que el logo toque los bordes si es necesario -->
                    
                    <!-- Cintillo Institucional centrado (Igual a ver_tickets.php) -->
                    <div class="text-center p-4">
                        <img src="img/logo3.png" alt="Cintillo FONDAS" style="max-width: 100%; height: auto;">
                    </div>

                    <!-- Título con la línea verde inferior -->
                    <div class="px-4">
                        <h4 style="color: #2e7d32; font-weight: bold; border-bottom: 2px solid #2e7d32; padding-bottom: 10px;">
                            Registrar Nueva Solicitud
                        </h4>
                    </div>

                    <div class="p-4">
                        <!-- Aquí empieza tu formulario de búsqueda de cédula -->
                        <form method="POST" class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Cédula del Solicitante</label>
                                <input type="number" name="ci_busqueda" class="form-control form-control-lg" placeholder="Ej: 14407683" value="<?php echo htmlspecialchars($cedula_buscada); ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="buscar_cedula" class="btn btn-primary btn-lg w-100" style="background-color: #0d6efd;">Verificar</button>
                            </div>
                        </form>

                    <hr>

                    <?php if ($solicitante): ?>
                        <?php if(isset($solicitante['cne_api'])): ?>
                            <div class="alert alert-info py-2">✓ Verificado por CNE y auto-registrado: <?php echo $solicitante['nombre']; ?>.</div>
                        <?php else: ?>
                            <div class="alert alert-success py-2">✓ Trabajador verificado: <?php echo $solicitante['nombre']; ?>.</div>
                        <?php endif; ?>

                        <form action="procesar_registro.php" method="POST" class="row g-3">
                            <input type="hidden" name="ci" value="<?php echo $solicitante['ci']; ?>">

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nombre</label>
                                <input type="text" class="form-control bg-light" readonly value="<?php echo $solicitante['nombre']; ?>">
                            </div>
      
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Gerencia / Ubicación</label>
                                <input type="text" class="form-control bg-light" readonly value="<?php echo $solicitante['ubicacion']; ?>">
                            </div>

                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">Área del Requerimiento (Asignación Automática)</label>
                                <select name="area_problema" class="form-select border-primary" required>
                                    <option value="">Seleccione el área del problema...</option>
                                    <option value="Soporte">Soporte Técnico</option>
                                    <option value="Infraestructura">Infraestructura</option>
                                    <option value="Desarrollo">Desarrollo</option>
                                    <option value="Impresoras y Toner">Impresoras y Tóner (Asignado a Oswaldo M.)</option>
                                </select>
                                <div class="form-text">El sistema buscará al técnico disponible con menos carga en el área seleccionada.</div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-bold">Tipo de Equipo</label>
                                <select name="id_tipo" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php
                                    $res_t = $db->query("SELECT id, tipo FROM tipo ORDER BY tipo ASC");
                                    if ($res_t) {
                                        while($t = $res_t->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="'.$t['id'].'">'.$t['tipo'].'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-bold">Marca</label>
                                <select name="id_marca" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php
                                    $res_m = $db->query("SELECT id, marca FROM marca ORDER BY marca ASC");
                                    if ($res_m) {
                                        while($m = $res_m->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="'.$m['id'].'">'.$m['marca'].'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold">Descripción de la Falla</label>
                                <textarea name="descripcion" class="form-control" rows="4" required placeholder="Describa el problema detalladamente..."></textarea>
                            </div>

                            <div class="col-12 mt-4">
    <button type="submit" class="btn btn-lg w-100 shadow" 
            style="background-color: #2e7d32; color: white; border: none; font-weight: bold; transition: 0.3s;">
        REGISTRAR TICKET
    </button>
</div>
                        </form>

                    <?php elseif (isset($_POST['buscar_cedula'])): ?>
                        <div class="alert alert-danger">La cédula no existe en la base de datos de FONDAS.</div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>