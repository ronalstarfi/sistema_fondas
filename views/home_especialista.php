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

        .wrapper {
            max-width: 1100px;
            margin: 20px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            border-radius: 10px;
        .banner-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: white;
            border-radius: 0;
            overflow: hidden;
            background: white;
            display: flex;
            flex-direction: column;
            /* Hacer que la caja blanca ocupe casi toda la altura de la ventana */
            min-height: calc(100vh - 120px);
        }

        .cintillo-container, .banner-container {
            width: 100%;
            margin: 0;
            background: white;
            overflow: hidden;
        }

        .cintillo-container img, .banner-container img {
            width: 100%;
            max-height: 140px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .navbar {
            background-color: #2e7d32;
            color: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            max-height: 140px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .header-info {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background-color: #2e7d32;
            color: white;
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0;
            box-sizing: border-box;
            font-size: 14px;
        }

        .navbar .brand { font-weight: 700; text-transform: uppercase; }
        .container {
            background: white;
            padding: 40px 30px;
            width: 100%;
            box-sizing: border-box;
            min-height: 420px;
            /* Permitir que el contenido central crezca y empuje el footer al final */
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .user-dropdown { position: relative; display: inline-block; }
        .user-dropdown-btn { 
            background: transparent; color: white; border: none; cursor: pointer; 
            font-size: 14px; font-family: inherit; display: flex; align-items: center; gap: 8px;
        }
        .user-dropdown-btn:focus { outline: none; }
        .dropdown-arrow { font-size: 10px; }
        .user-dropdown-content {
            display: none; position: absolute; right: 0; top: 100%;
            background-color: #fff; min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 6px; overflow: hidden; z-index: 100;
        }
        .user-dropdown:hover .user-dropdown-content { display: block; }
        .user-dropdown-content a {
            color: #333 !important; padding: 12px 16px; text-decoration: none;
            display: block; background: transparent; font-weight: normal; border-radius: 0;
        }
        .user-dropdown-content a:hover { background-color: #f1f8e9; color: #2e7d32 !important; }
        .user-dropdown-content .logout-link { color: #d32f2f !important; font-weight: bold; border-top: 1px solid #eee; }
        .user-dropdown-content .logout-link:hover { background-color: #ffebee; color: #b71c1c !important; }

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
            /* Subir los módulos: alineamos al inicio en vertical */
            align-items: flex-start;
            padding-top: 40px; /* pequeño espacio superior para separar del título */
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
            /* Empuja el footer hacia el fondo de la caja blanca */
            margin-top: auto;
            background: transparent;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="cintillo-container">
            <img src="../img/logo3.png" alt="Banner FONDAS">
        </div>

        <div class="navbar">
            <div class="brand">Sistema de Gestión de Incidencias</div>
            <div class="user-dropdown">
                <button class="user-dropdown-btn">
                    <span>Bienvenido(a): <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></span>
                    <span style="font-size: 10px; margin-left:8px;">▼</span>
                </button>
                <div class="user-dropdown-content">
                    <a href="../logout.php" class="logout-link">Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="panel-title">PANEL DE CONTROL <?php echo htmlspecialchars(strtoupper($titulo_dinamico)); ?></h2>

            <main>
                <div class="icon-container">
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
            }
            .user-dropdown:hover .user-dropdown-content { display: block; }
            .user-dropdown-content a {
                color: #333 !important; padding: 12px 16px; text-decoration: none;
                display: block; background: transparent; font-weight: normal; border-radius: 0;
            }
            .user-dropdown-content a:hover { background-color: #f1f8e9; color: #2e7d32 !important; }
            .user-dropdown-content .logout-link { color: #d32f2f !important; font-weight: bold; border-top: 1px solid #eee; }
            .user-dropdown-content .logout-link:hover { background-color: #ffebee; color: #b71c1c !important; }
        </style>
        <div class="user-dropdown">
            <button class="user-dropdown-btn">
                <span>Bienvenido(a): <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></span>
                <span style="font-size: 10px;">▼</span>
            </button>
            <div class="user-dropdown-content">
                <a href="../logout.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>
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
                        <p>AUDITORÍA FORENSE</p>
                    </a>
                </div>
            <?php endif; ?>
                </div> <!-- .icon-container -->
            </main>
        </div> <!-- .container -->
    </div> <!-- .wrapper -->

    <footer style="background: transparent; padding: 18px 30px; text-align:center; font-size:11px; color:#666; margin-top:20px;">
        <p style="margin:0;">Fondo para el Desarrollo Agrario Socialista (FONDAS) - Venezuela</p>
        <p style="margin:0;">Desarrollado por el Departamento de Tecnología e Información © 2026</p>
    </footer>

</body>
</html>