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
                        <div class="alert alert-success py-2">✓ Trabajador verificado: <?php echo $solicitante['nombre']; ?>.</div>

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