<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Solicitante - FONDAS</title>
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: auto;
        }

        body {
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
        }

        /* Caja principal: ahora ocupa todo el ancho para que el cintillo y la franja
           verde se vean a pantalla completa; si prefieres quitar la caja blanca,
           lo dejamos en blanco aquí y lo elimino en una próxima edición. */
        .wrapper {
            width: 100%;
            max-width: none;
            margin: 0;
            box-shadow: none;
            border-radius: 0;
            overflow: visible;
            background: white;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .cintillo-container {
            width: 100%;
            margin: 0;
            background: white;
            overflow: hidden;
        }

        .cintillo {
            width: 100%;
            height: 95px;
            object-fit: fill;
            display: block;
            margin: 0;
        }

        .navbar {
            background-color: #2e7d32;
            color: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }

        .navbar .brand { font-weight: 700; text-transform: uppercase; }

        .user-dropdown { position: relative; display: inline-block; }
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

        .container { 
            background: white; 
            padding: 40px 30px; 
            width: 100%;
            box-sizing: border-box;
            min-height: 420px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .panel-title {
            text-align: center;
            margin: 0;
            color: #1b5e20;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .intro-text {
            text-align: center;
            color: #666;
            margin: 18px auto 0;
            max-width: 720px;
            line-height: 1.6;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 40px;
        }

        .icon-container {
            display: flex;
            gap: 50px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .opcion-modulo {
            text-align: center;
            width: 180px;
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
            object-fit: contain;
        }

        .opcion-modulo p {
            font-weight: bold;
            font-size: 13px;
            margin: 0;
            color: #1b5e20;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        main a { text-decoration: none; color: inherit; }

        footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 11px;
            margin-top: auto;
            background: transparent;
        }
        .btn:hover { opacity: 0.8; transform: translateY(-2px); }
        
        /* Enlaces específicos corregidos según tus archivos */
        .btn-new { background-color: #2e7d32; }
        .btn-view { background-color: #1565c0; }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="cintillo-container">
            <img src="img/logo3.png" alt="Cintillo FONDAS" class="cintillo">
        </div>

        <div class="navbar">
            <span>    Sistema de Incidencias</span>
            <div class="user-dropdown">
                <button class="user-dropdown-btn">
                    <span>Bienvenido(a): <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></span>
                    <span style="font-size: 10px;">▼</span>
                </button>
                <div class="user-dropdown-content">
                    <a href="logout.php" class="logout-link">Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="panel-title">Panel de Control - Solicitante</h2>

            <main>
                <div class="icon-container">
                    <div class="opcion-modulo">
                        <a href="registro.php">
                            <img src="img/tickets1.png" alt="Crear Nueva Solicitud">
                            <p>Crear Nueva Solicitud</p>
                        </a>
                    </div>

                    <div class="opcion-modulo">
                        <a href="ver_tickets.php">
                            <img src="img/auditoria2.png" alt="Consultar mis Tickets">
                            <p>Consultar mis Tickets</p>
                        </a>
                    </div>

                    <div class="opcion-modulo">
                        <a href="manual.php">
                            <img src="img/manual_icon.png" alt="Manual de Usuario" style="width: 140px; height: auto; margin-bottom: 15px; display: block; mix-blend-mode: multiply;">
                            <p>MANUAL DE USUARIO</p>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer style="background: transparent; padding: 18px 30px; text-align:center; font-size:11px; color:#666; margin-top:20px;">
        <p style="margin:0;">Fondo para el Desarrollo Agrario Socialista (FONDAS) - Venezuela</p>
        <p style="margin:0;">Desarrollado por el Departamento de Tecnología e Información © 2026</p>
    </footer>

</body>
</html>