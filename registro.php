<?php
session_start();
// 1. Conexión a la base de datos
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Inicialización de variables
$solicitante = null;
$cedula_buscada = "";
$rol = $_SESSION['rol'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
$esEspecialista = ($userType === 'especialista' || $rol === 'Especialista');

// 2. Si el usuario está autenticado, cargamos su información directamente
if (isset($_SESSION['user_id'])) {
    // Si es especialista, tomamos los datos de la tabla especialista
    if ($esEspecialista) {
        $query = "SELECT ci, especialista AS nombre, area_especifica AS ubicacion FROM especialista WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $solicitante = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Si es solicitante, la sesión guarda su CI en user_id
        $ci_sesion = $_SESSION['user_id'];
        $query = "SELECT ci, nombre, ubicacion FROM solicitante WHERE ci = :ci LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ci', $ci_sesion);
        $stmt->execute();
        $solicitante = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// 3. Lógica para buscar el solicitante cuando no es especialista
if (!$esEspecialista && isset($_POST['buscar_cedula'])) {
    $cedula_buscada = $_POST['ci_busqueda'];

    if ($db) {
        // Se usa 'ubicacion' para la gerencia
        $query = "SELECT ci, nombre, ubicacion FROM solicitante WHERE ci = :ci LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ci', $cedula_buscada);
        $stmt->execute();
        $solicitante = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    var area = document.getElementById('area_problema');
                    var tipo = document.getElementById('id_tipo');
                    var marca = document.getElementById('id_marca');

                    function loadOptions(areaVal){
                        if(!areaVal) return;
                        fetch('ajax/get_options.php', {
                            method: 'POST',
                            headers: {'Content-Type':'application/x-www-form-urlencoded'},
                            body: 'area=' + encodeURIComponent(areaVal)
                        }).then(function(resp){
                            return resp.json();
                        }).then(function(data){
                            if(data.types){
                                tipo.innerHTML = '<option value="">Seleccione...</option>';
                                data.types.forEach(function(t){
                                    var o = document.createElement('option'); o.value = t.id; o.textContent = t.tipo; tipo.appendChild(o);
                                });
                            }
                            if(data.brands){
                                marca.innerHTML = '<option value="">Seleccione...</option>';
                                data.brands.forEach(function(m){
                                    var o = document.createElement('option'); o.value = m.id; o.textContent = m.marca; marca.appendChild(o);
                                });
                            }
                        }).catch(function(err){
                            console.error('Error cargando opciones:', err);
                        });
                    }

                    if(area){
                        area.addEventListener('change', function(e){ loadOptions(e.target.value); });
                        // Si ya hay un área seleccionada al cargar la página, cargar opciones
                        if(area.value) loadOptions(area.value);
                    }
                });
                </script>
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
                        <?php if (!$esEspecialista): ?>
                        <form method="POST" class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Cédula del Solicitante</label>
                                <input type="number" name="ci_busqueda" class="form-control form-control-lg" placeholder="Ej: 14407683" value="<?php echo htmlspecialchars($cedula_buscada); ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="buscar_cedula" class="btn btn-primary btn-lg w-100" style="background-color: #0d6efd;">Verificar</button>
                            </div>
                        </form>
                        <?php endif; ?>

                    <hr>

                    <?php if ($solicitante): ?>
                        <div class="alert alert-success py-2">
                            ✓ <?php echo $esEspecialista ? 'Generando ticket como especialista:' : 'Trabajador verificado:'; ?> <?php echo htmlspecialchars($solicitante['nombre']); ?>.
                        </div>

                        <form action="procesar_registro.php" method="POST" class="row g-3">
                            <input type="hidden" name="ci" value="<?php echo htmlspecialchars($solicitante['ci']); ?>">

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Cédula del Solicitante</label>
                                <input type="text" class="form-control bg-light" readonly value="<?php echo htmlspecialchars($solicitante['ci']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nombre</label>
                                <input type="text" class="form-control bg-light" readonly value="<?php echo htmlspecialchars($solicitante['nombre']); ?>">
                            </div>
      
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Gerencia / Ubicación</label>
                                <input type="text" class="form-control bg-light" readonly value="<?php echo htmlspecialchars($solicitante['ubicacion']); ?>">
                            </div>

                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">Área del Requerimiento (Asignación Automática)</label>
                                <select id="area_problema" name="area_problema" class="form-select border-primary" required>
                                    <option value="">Seleccione el área del problema...</option>
                                    <option value="Soporte">Soporte Técnico</option>
                                    <option value="Infraestructura">Infraestructura</option>
                                    <option value="Desarrollo">Desarrollo</option>
                                    <option value="Impresoras y Toner">Impresoras y Tóner (Asignado a Oswaldo M.)</option>
                                    <option value="SIGA">SIGA (Asignar a Benjamin Acevedo)</option>
                                </select>
                                <div class="form-text">El sistema asigna automáticamente el ticket al técnico activo con la menor carga en el área seleccionada.</div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-bold">Tipo de Equipo</label>
                                <select id="id_tipo" name="id_tipo" class="form-select" required>
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
                                <select id="id_marca" name="id_marca" class="form-select" required>
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
                    <?php elseif ($esEspecialista && !isset($solicitante)): ?>
                        <div class="alert alert-danger">No se encontró la información del especialista en sesión. Verifique su usuario y vuelva a iniciar sesión.</div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>