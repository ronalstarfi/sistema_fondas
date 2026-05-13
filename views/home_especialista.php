<?php
session_start();

// 1. SEGURIDAD: Control de acceso para especialistas
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'especialista') {
    header("Location: ../login.php"); 
    exit();
}

// 2. CONEXIÓN
require_once '../config/database.php';
$db = (new Database())->getConnection();

// 3. LÓGICA DINÁMICA PARA EL TÍTULO
$nombre_usuario = $_SESSION['nombre'];
$titulo_dinamico = "ESPECIALISTA"; // Valor por defecto

if ($db) {
    // Buscamos el área específica (Gerente, Coordinadora, Desarrollo) en la tabla especialista
    $query = "SELECT area_especifica FROM especialista WHERE especialista = :nombre LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre_usuario);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['area_especifica'])) {
        $titulo_dinamico = $row['area_especifica'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Especialista - FONDAS</title>
    <style>
        /* Estilos originales preservados */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; 
        }

        body {
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
        }

        .banner-container {
            width: 90%;
            max-width: 950px;
            margin: 10px auto 0;
            background: white;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .banner-container img {
            width: 100%;
            height: auto;
            display: block;
        }

        .header-info {
            width: 90%;
            max-width: 950px;
            margin: 0 auto;
            background-color: #2e7d32;
            color: white;
            padding: 8px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 12px 12px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .header-info a {
            background-color: #d32f2f;
            color: white !important;
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }

        .panel-title {
            text-align: center;
            margin: 30px 0;
            color: #1b5e20;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: bold;
        }

        main {
            flex: 1; 
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .icon-container {
            display: flex;
            gap: 50px;
            justify-content: center;
            align-items: center;
        }

        .opcion-modulo {
            text-align: center;
            width: 180px;
            background: transparent;
            border: none;
            transition: transform 0.3s ease;
        }

        .opcion-modulo:hover {
            transform: scale(1.1);
        }

        .opcion-modulo img {
            width: 160px;
            height: auto;
            display: block;
            margin: 0 auto 15px;
            mix-blend-mode: multiply; 
        }

        .opcion-modulo p {
            font-weight: bold;
            font-size: 13px;
            margin: 0;
            color: #1b5e20;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        .opcion-modulo a { text-decoration: none; }

        footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 11px;
        }
    </style>
    <!-- SweetAlert2 para mensajes premium -->
    <script src="../assets/sweetalert2.all.min.js"></script>
</head>

<body>

    <div class="banner-container">
        <img src="../img/logo3.png" alt="Banner FONDAS">
    </div>

    <div class="header-info">
        <div><span>Sistema de Gestión de Incidencias</span></div>
        <style>
            .user-dropdown { position: relative; display: inline-block; margin-left: 10px; }
            .user-dropdown-btn { 
                background: transparent; color: white; border: none; cursor: pointer; 
                font-size: 14px; font-family: inherit; display: flex; align-items: center; gap: 8px;
            }
            .user-dropdown-btn:focus { outline: none; }
            .user-dropdown-content {
                display: none; position: absolute; right: 0; top: 100%;
                background-color: #fff; min-width: 160px;
                box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
                border-radius: 6px; overflow: hidden; z-index: 100;
                margin-top: 8px;
            }
            .user-dropdown-content.show { display: block; }

            .user-dropdown-content a {
                color: #333 !important; padding: 12px 16px; text-decoration: none;
                display: block; background: transparent; font-weight: normal; border-radius: 0;
            }
            .user-dropdown-content a:hover { background-color: #f1f8e9; color: #2e7d32 !important; }
            .user-dropdown-content .logout-link { color: #d32f2f !important; font-weight: bold; border-top: 1px solid #eee; }
            .user-dropdown-content .logout-link:hover { background-color: #ffebee; color: #b71c1c !important; }
            /* Puente invisible para evitar que se cierre el menú al mover el ratón */
            .user-dropdown-content::before {
                content: '';
                position: absolute;
                top: -15px;
                left: 0;
                width: 100%;
                height: 15px;
                background: transparent;
            }
        </style>

        <div class="user-dropdown">
            <button class="user-dropdown-btn" id="userMenuBtn">
                <span>Bienvenido(a): <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['nombre'])[0]); ?></strong></span>
                <span style="font-size: 10px;">▼</span>
            </button>
            <div class="user-dropdown-content" id="userMenuContent">
                <a href="#" class="logout-link" id="logoutBtn">Cerrar Sesión</a>
            </div>
        </div>
        <script>
            document.getElementById('userMenuBtn').addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('userMenuContent').classList.toggle('show');
            });

            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Cerrar Sesión?',
                    text: "Se guardará tu actividad y saldrás del sistema de forma segura.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2e7d32',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, Salir',
                    cancelButtonText: 'Cancelar',
                    background: '#ffffff',
                    color: '#1b5e20',
                    backdrop: `rgba(0,0,123,0.1)`
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../logout.php';
                    }
                });
            });

            window.addEventListener('click', function() {
                document.getElementById('userMenuContent').classList.remove('show');
            });
        </script>


    </div>

    <!-- TÍTULO DINÁMICO -->
    <h2 class="panel-title">PANEL DE CONTROL <?php echo htmlspecialchars(strtoupper($titulo_dinamico)); ?></h2>

    <main>
        <div class="icon-container">
            <!-- Módulo de Tickets: Visible para todos -->
            <div class="opcion-modulo">
                <a href="../ver_tickets.php">
                    <img src="../img/tickets1.png" alt="Tickets">
                    <p>GESTIÓN DE TICKETS</p>
                </a>
            </div>

            <!-- MEJORA: Control de Personal ahora disponible para todos los especialistas -->
            <div class="opcion-modulo">
                <a href="control_personal.php">
                    <img src="../img/personal1.png" alt="Personal">
                    <p>CONTROL DE PERSONAL</p>
                </a>
            </div>

            <!-- Bloque restringido: Solo para usuarios con rol 'Jefe' -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Jefe'): ?>
                <div class="opcion-modulo">
                    <a href="dashboard.php">
                        <img src="../img/estadisticas2.png" alt="Reportes">
                        <p>ESTADÍSTICAS</p>
                    </a>
                </div>

                <div class="opcion-modulo">
                    <a href="../auditoria_sistema.php">
                        <img src="../img/auditoria2.png" alt="Auditoría">
                        <p>AUDITORÍA</p>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>Fondo para el Desarrollo Agrario Socialista (FONDAS) - Venezuela</p>
        <p>Desarrollado por el Departamento de Tecnología e Información © 2026</p>
    </footer>

</body>
</html>