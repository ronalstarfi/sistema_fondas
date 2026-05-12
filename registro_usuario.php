<?php
require_once 'config/database.php';

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Consultar CNE
    if ($_POST['action'] === 'consultar_cne') {
        header('Content-Type: application/json');
        $cedula = strip_tags($_POST['cedula'] ?? '');
        
        if (empty($cedula)) {
            echo json_encode(['status' => 'error', 'message' => 'Cédula vacía']);
            exit;
        }

        try {
            // Verificar si ya está registrado en FONDAS
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT ci, nombre, password FROM solicitante WHERE ci = :ci LIMIT 1");
            $stmt->execute([':ci' => $cedula]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                if (!empty($existente['password'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Esta cédula ya está registrada y posee una contraseña. Inicia sesión o recupera tu clave.']);
                    exit;
                } else {
                    // Ya existe pero no tiene clave, le permitimos completar el registro usando su nombre guardado
                    echo json_encode(['status' => 'success', 'data' => ['nombre' => $existente['nombre']]]);
                    exit;
                }
            }

            // Consultar a Starfi para la API del CNE
            $config = null;
            try {
                $con_starfi = @mysqli_connect("192.168.0.71", "starfi_v2_user", md5("PARALELEPIPEDO3312"), "starfi");
                if (!$con_starfi) {
                    $con_starfi = @mysqli_connect("127.0.0.1", "root", "", "starfi");
                }
                if ($con_starfi) {
                    $stmt_conf = @mysqli_query($con_starfi, "SELECT * FROM api_nacional_config ORDER BY id DESC LIMIT 1");
                    if ($stmt_conf) {
                        $config = mysqli_fetch_assoc($stmt_conf);
                    }
                }
            } catch (Exception $e) {
                // Ignorar error de base de datos de configuración y usar fallback
            }
            
            // Si la tabla no existe o falla, usar el Fallback Nativo de Starfi 2.0
            $base_url = 'https://api.cedula.com.ve/api/v1';
            $app_id = '788';
            $token = 'a500e36df22b222af802f945455569da';
            
            if ($config) {
                $base_url = rtrim(trim($config['api_url']), '/');
                $app_id = urlencode($config['app_id']);
                $token = urlencode($config['api_token']);
            }
            
            $url = $base_url . "?app_id=$app_id&token=$token&nacionalidad=V&cedula=" . intval($cedula);

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
                        
                        echo json_encode(['status' => 'success', 'data' => ['nombre' => $nombre_completo]]);
                        exit;
                    }
                }
            echo json_encode(['status' => 'error', 'message' => 'No se encontró en el CNE o la API está caída.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error interno al consultar CNE: ' . $e->getMessage()]);
        }
        exit;
    }

    // 2. Registrar Usuario
    if ($_POST['action'] === 'registrar') {
        header('Content-Type: application/json');
        $cedula = $_POST['cedula'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $ubicacion = $_POST['ubicacion'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_conf = $_POST['password_conf'] ?? '';

        if (empty($cedula) || empty($nombre) || empty($ubicacion) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        if ($password !== $password_conf) {
            echo json_encode(['status' => 'error', 'message' => 'Las contraseñas no coinciden.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Verificamos si existe para hacer UPDATE o INSERT
            $stmt_chk = $db->prepare("SELECT ci, password FROM solicitante WHERE ci = :ci LIMIT 1");
            $stmt_chk->execute([':ci' => $cedula]);
            $solicitante = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if ($solicitante) {
                if (!empty($solicitante['password'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Esta cédula ya posee una contraseña activa.']);
                    exit;
                }
                // Actualizar usuario existente sin clave
                $stmt = $db->prepare("UPDATE solicitante SET nombre = :nom, ubicacion = :ub, password = :pass WHERE ci = :ci");
                $stmt->execute([
                    ':ci' => $cedula,
                    ':nom' => $nombre,
                    ':ub' => $ubicacion,
                    ':pass' => $hash
                ]);
            } else {
                // Insertar nuevo usuario
                $stmt = $db->prepare("INSERT INTO solicitante (ci, nombre, ubicacion, password) VALUES (:ci, :nom, :ub, :pass)");
                $stmt->execute([
                    ':ci' => $cedula,
                    ':nom' => $nombre,
                    ':ub' => $ubicacion,
                    ':pass' => $hash
                ]);
            }
            
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar: La cédula podría estar ya registrada.']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <title>Registro de Usuario - FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 para Alertas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background-image: url("img/logo4.png");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
        }
        .registro-box {
            background-color: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
        }
        .logo-top {
            width: 150px;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-green {
            background-color: #2e7d32;
            color: white;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-green:hover {
            background-color: #1b5e20;
            color: white;
            transform: translateY(-2px);
        }
        .spinner-border {
            width: 1rem;
            height: 1rem;
            display: none;
        }
    </style>
</head>
<body>

<div class="registro-box">
    <img src="img/logo1.png" alt="Logo FONDAS" class="logo-top">
    <h4 class="text-center mb-4" style="color: #2e7d32; font-weight: bold;">Crear Cuenta</h4>

    <form id="formRegistro" onsubmit="registrarUsuario(event)">
        <!-- Búsqueda de Cédula -->
        <label class="form-label fw-bold small text-muted">Cédula de Identidad</label>
        <div class="input-group mb-3">
            <input type="number" id="cedula" class="form-control" placeholder="Ej: 12345678" required>
            <button class="btn btn-outline-secondary" type="button" id="btnCNE" onclick="consultarCNE()">
                <span class="spinner-border spinner-border-sm me-1" id="spinnerCNE"></span>
                Buscar en CNE
            </button>
        </div>

        <!-- Campos Auto-completados -->
        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Nombre Completo</label>
            <input type="text" id="nombre" class="form-control bg-light" readonly required placeholder="Se autocompletará con el CNE">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Gerencia / Ubicación</label>
            <input type="text" id="ubicacion" class="form-control" required placeholder="Ej: Gerencia de Tecnología">
        </div>

        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label fw-bold small text-muted">Contraseña</label>
                <input type="password" id="password" class="form-control" required>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label fw-bold small text-muted">Confirmar</label>
                <input type="password" id="password_conf" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-green w-100 py-2 mt-2" id="btnSubmit" disabled>REGISTRARME</button>
        
        <div class="text-center mt-3">
            <a href="login.php" onclick="confirmarVolver(event)" class="text-decoration-none" style="color: #6c757d; font-size: 14px;">← Volver al Login</a>
        </div>
    </form>
</div>

<script>
let statePushed = false;

// Escuchar cambios en el formulario para activar la protección
document.getElementById('formRegistro').addEventListener('input', function() {
    if (!statePushed) {
        window.history.pushState({preventBack: true}, null, window.location.href);
        statePushed = true;
    }
});

// Interceptar el botón "Atrás" del navegador
window.onpopstate = function(e) {
    if (statePushed) {
        // Volvemos a empujar el estado para contrarrestar el retroceso
        window.history.pushState({preventBack: true}, null, window.location.href);
        
        Swal.fire({
            title: '¿Está seguro de volver?',
            text: "Hay datos sin guardar, si sale perderá su progreso.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                statePushed = false; // Quitar la traba
                window.history.back(); // Ejecutar salida real
            }
        });
    }
};

// Interceptar recarga de página o cierre de pestaña (usa alerta nativa del navegador por seguridad de los navegadores)
window.addEventListener('beforeunload', function (e) {
    const ci = document.getElementById('cedula').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    const ubicacion = document.getElementById('ubicacion').value.trim();
    const pass = document.getElementById('password').value;

    if (ci || nombre || ubicacion || pass) {
        e.preventDefault();
        e.returnValue = ''; // Requerido en navegadores modernos
    }
});

function confirmarVolver(e) {
    const ci = document.getElementById('cedula').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    const ubicacion = document.getElementById('ubicacion').value.trim();
    const pass = document.getElementById('password').value;
    
    // Si hay algún dato escrito, preguntamos
    if (ci || nombre || ubicacion || pass) {
        e.preventDefault();
        Swal.fire({
            title: '¿Está seguro de volver?',
            text: "Hay datos sin guardar, si sale perderá su progreso.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, volver al login',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.onpopstate = null; // Quitar listener para evitar doble alerta
                window.location.href = 'login.php';
            }
        });
    }
}

function consultarCNE() {
    const ci = document.getElementById('cedula').value.trim();
    if (!ci) {
        Swal.fire('Atención', 'Ingrese una cédula primero.', 'warning');
        return;
    }

    const btn = document.getElementById('btnCNE');
    const spinner = document.getElementById('spinnerCNE');
    btn.disabled = true;
    spinner.style.display = 'inline-block';

    const fd = new FormData();
    fd.append('action', 'consultar_cne');
    fd.append('cedula', ci);

    fetch('registro_usuario.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        spinner.style.display = 'none';

        if (data.status === 'success') {
            document.getElementById('nombre').value = data.data.nombre;
            document.getElementById('nombre').readOnly = true;
            document.getElementById('nombre').classList.add('bg-light');
            document.getElementById('btnSubmit').disabled = false;
            Swal.fire({
                icon: 'success',
                title: 'Verificado',
                text: 'Identidad validada por el CNE.',
                timer: 2000,
                showConfirmButton: false
            });
            document.getElementById('ubicacion').focus();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Aviso del CNE',
                text: 'La API del CNE no responde o la cédula no se encontró. Por favor, escriba su nombre completo manualmente.',
                confirmButtonColor: '#2e7d32'
            });
            document.getElementById('nombre').readOnly = false;
            document.getElementById('nombre').classList.remove('bg-light');
            document.getElementById('nombre').focus();
            document.getElementById('btnSubmit').disabled = false;
        }
    })
    .catch(err => {
        btn.disabled = false;
        spinner.style.display = 'none';
        Swal.fire({
            icon: 'warning',
            title: 'Sin Conexión al CNE',
            text: 'No pudimos conectar con el CNE. Por favor, escriba su nombre manualmente.',
            confirmButtonColor: '#2e7d32'
        });
        document.getElementById('nombre').readOnly = false;
        document.getElementById('nombre').classList.remove('bg-light');
        document.getElementById('nombre').focus();
        document.getElementById('btnSubmit').disabled = false;
    });
}

function registrarUsuario(e) {
    e.preventDefault();
    const ci = document.getElementById('cedula').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    const ubicacion = document.getElementById('ubicacion').value.trim();
    const pass = document.getElementById('password').value;
    const passConf = document.getElementById('password_conf').value;

    if (pass !== passConf) {
        Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'registrar');
    fd.append('cedula', ci);
    fd.append('nombre', nombre);
    fd.append('ubicacion', ubicacion);
    fd.append('password', pass);
    fd.append('password_conf', passConf);

    document.getElementById('btnSubmit').disabled = true;

    fetch('registro_usuario.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: 'Tu cuenta ha sido creada. Ahora puedes iniciar sesión.',
                confirmButtonColor: '#2e7d32'
            }).then(() => {
                window.location.href = 'login.php';
            });
        } else {
            document.getElementById('btnSubmit').disabled = false;
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        document.getElementById('btnSubmit').disabled = false;
        Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
    });
}
</script>
</body>
</html>
