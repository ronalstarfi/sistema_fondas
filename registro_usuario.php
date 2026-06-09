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
            // VERIFICACIÓN ESTRICTA EN EL SISTEMA FONDAS
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT ci, nombre, password FROM solicitante WHERE ci = :ci LIMIT 1");
            $stmt->execute([':ci' => $cedula]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existente) {
                // Mensaje solicitado: No está en el sistema, no puede crear cuenta
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Esta cédula no se encuentra registrada en el sistema, por lo tanto no puede crear la cuenta. Por favor, diríjase a la oficina de tecnología.'
                ]);
                exit;
            }

            if (!empty($existente['password'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Esta cédula ya posee una cuenta activa. Por favor, inicie sesión.'
                ]);
                exit;
            }

            // Si llegamos aquí, existe en el sistema pero NO tiene clave (puede registrarse)
            echo json_encode([
                'status' => 'success', 
                'data' => ['nombre' => $existente['nombre']]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de validación en el sistema.']);
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

            $stmt_chk = $db->prepare("SELECT ci, password FROM solicitante WHERE ci = :ci LIMIT 1");
            $stmt_chk->execute([':ci' => $cedula]);
            $solicitante = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if ($solicitante) {
                if (!empty($solicitante['password'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Esta cédula ya posee una contraseña activa.']);
                    exit;
                }
                $stmt = $db->prepare("UPDATE solicitante SET nombre = :nom, ubicacion = :ub, password = :pass WHERE ci = :ci");
                $stmt->execute([':ci' => $cedula, ':nom' => $nombre, ':ub' => $ubicacion, ':pass' => $hash]);
            } else {
                $stmt = $db->prepare("INSERT INTO solicitante (ci, nombre, ubicacion, password) VALUES (:ci, :nom, :ub, :pass)");
                $stmt->execute([':ci' => $cedula, ':nom' => $nombre, ':ub' => $ubicacion, ':pass' => $hash]);
            }

            // Registrar en auditoría
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $sql_audit = "INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip) VALUES (?, ?, ?, ?)";
            $stmt_audit = $db->prepare($sql_audit);
            $stmt_audit->execute(['Registro de Cuenta', 'El usuario completó su registro de cuenta.', $nombre, $ip]);
            
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar.']);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
            font-family: 'Outfit', 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            overflow: hidden;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .registro-box {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.1));
            width: 100%;
            max-width: 500px;
            padding: 45px;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInUp 0.8s ease-out;
        }

        .logo-top {
            width: 160px;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.9));
        }

        .form-label {
            color: #ffffff !important;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: #ffffff !important;
            border-color: #2e7d32;
            box-shadow: 0 0 15px rgba(46, 125, 50, 0.4);
            transform: scale(1.01);
        }

        .btn-green {
            background: linear-gradient(90deg, #2e7d32, #1b5e20);
            color: white;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            padding: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(46, 125, 50, 0.3);
            transition: 0.3s;
        }

        .btn-green:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(46, 125, 50, 0.5);
            filter: brightness(1.1);
        }

        .btn-green:disabled { opacity: 0.6; cursor: not-allowed; }

        #btnCNE {
            border-radius: 0 10px 10px 0;
            background-color: #2e7d32;
            color: white;
            border: none;
            font-weight: bold;
            padding: 0 20px;
        }

        #btnCNE:hover { background-color: #1b5e20; }
        .spinner-border { width: 1rem; height: 1rem; display: none; }
    </style>
</head>
<body>

<div class="registro-box">
    <img src="img/logo1.png" alt="Logo FONDAS" class="logo-top">
    <h4 class="text-center mb-4" style="color: #2e7d32; font-weight: bold;">Crear Cuenta</h4>

    <form id="formRegistro" onsubmit="registrarUsuario(event)">
        <label class="form-label fw-bold">Cédula de Identidad</label>
        <div class="input-group mb-3">
            <input type="number" id="cedula" class="form-control" placeholder="Ej: 12345678" required>
            <button class="btn btn-outline-secondary" type="button" id="btnCNE" onclick="consultarCNE()" title="Buscar en CNE">
                <span class="spinner-border spinner-border-sm me-1" id="spinnerCNE"></span>
                <i class="bi bi-search"></i>
            </button>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Nombre</label>
            <input type="text" id="nombre" class="form-control bg-light" readonly required placeholder="Se autocompletará">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Gerencia / Ubicación</label>
            <input type="text" id="ubicacion" class="form-control" required placeholder="Ej: Gerencia de Tecnología">
        </div>

        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label fw-bold">Contraseña</label>
                <input type="password" id="password" class="form-control" required>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label fw-bold">Confirmar</label>
                <input type="password" id="password_conf" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-green w-100 py-2 mt-2" id="btnSubmit" disabled>REGISTRARME</button>
        
        <div class="text-center mt-3">
            <a href="login.php" onclick="confirmarVolver(event)" class="text-decoration-none" style="color: #ffffff; font-size: 14px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">← Volver al Login</a>
        </div>
    </form>
</div>

<script>
let statePushed = false;

document.getElementById('formRegistro').addEventListener('input', function() {
    if (!statePushed) {
        window.history.pushState({preventBack: true}, null, window.location.href);
        statePushed = true;
    }
});

window.onpopstate = function(e) {
    if (statePushed) {
        window.history.pushState({preventBack: true}, null, window.location.href);
        Swal.fire({
            title: '¿Está seguro de volver?',
            text: "Hay datos sin guardar.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, salir'
        }).then((result) => {
            if (result.isConfirmed) {
                statePushed = false;
                window.history.back();
            }
        });
    }
};

function confirmarVolver(e) {
    const ci = document.getElementById('cedula').value.trim();
    if (ci) {
        e.preventDefault();
        Swal.fire({
            title: '¿Está seguro?',
            text: "Perderá su progreso.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, volver'
        }).then((result) => {
            if (result.isConfirmed) {
                window.onpopstate = null;
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
            Swal.fire({ icon: 'success', title: 'Verificado', text: 'Identidad validada.', timer: 1500, showConfirmButton: false });
            document.getElementById('ubicacion').focus();
        } else {
            Swal.fire({ icon: 'warning', title: 'Aviso', text: data.message || 'API no disponible.', confirmButtonColor: '#2e7d32' });
            habilitarNombreManual();
        }
    })
    .catch(err => {
        btn.disabled = false;
        spinner.style.display = 'none';
        Swal.fire({ icon: 'warning', title: 'Aviso', text: 'Escribe tu nombre manualmente.', confirmButtonColor: '#2e7d32' });
        habilitarNombreManual();
    });
}

function habilitarNombreManual() {
    const nom = document.getElementById('nombre');
    nom.readOnly = false;
    nom.classList.remove('bg-light');
    nom.focus();
    document.getElementById('btnSubmit').disabled = false;
}

function registrarUsuario(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'registrar');
    fd.append('cedula', document.getElementById('cedula').value.trim());
    fd.append('nombre', document.getElementById('nombre').value.trim());
    fd.append('ubicacion', document.getElementById('ubicacion').value.trim());
    fd.append('password', document.getElementById('password').value);
    fd.append('password_conf', document.getElementById('password_conf').value);

    document.getElementById('btnSubmit').disabled = true;
    fetch('registro_usuario.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Registro Exitoso', confirmButtonColor: '#2e7d32' }).then(() => { window.location.href = 'login.php'; });
        } else {
            document.getElementById('btnSubmit').disabled = false;
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        document.getElementById('btnSubmit').disabled = false;
        Swal.fire('Error', 'Error al procesar.', 'error');
    });
}
</script>

<!-- Botón flotante de ayuda -->
<a href="manual_usuario_fondas.html?v=ext" style="position: fixed; bottom: 25px; right: 25px; background-color: #2e7d32; color: white; width: 55px; height: 55px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 26px; text-decoration: none; box-shadow: 0 4px 15px rgba(46, 125, 50, 0.4); z-index: 1000;" target="_blank" title="¿Necesitas ayuda? Ver Manual">
    <i class="bi bi-question-lg"></i>
</a>

</body>
</html>
