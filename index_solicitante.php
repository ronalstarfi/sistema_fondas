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
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        
        .wrapper {
            max-width: 1100px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .cintillo-container {
            background-color: white;
            text-align: center;
            line-height: 0;
        }
        .cintillo {
            width: 100%;
            height: auto;
            display: block;
        }

        .navbar {
            background-color: #2e7d32;
            color: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
        }
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
        .user-dropdown:hover .user-dropdown-content { display: block; }
        .user-dropdown-content a {
            color: #333 !important; padding: 12px 16px; text-decoration: none;
            display: block; background: transparent; font-weight: normal; border-radius: 0; text-align: left;
        }
        .user-dropdown-content a:hover { background-color: #f1f8e9; color: #2e7d32 !important; }
        .user-dropdown-content .logout-link { color: #d32f2f !important; font-weight: bold; border-top: 1px solid #eee; }
        .user-dropdown-content .logout-link:hover { background-color: #ffebee; color: #b71c1c !important; }

        .container { 
            background: white; 
            padding: 40px 25px; 
            width: 100%;
            box-sizing: border-box;
            min-height: 300px;
        }

        h2 { color: #1b5e20; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 15px; }

        .button-group {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        /* ESTILO DE LOS BOTONES PARA QUE PAREZCAN ENLACES CLICKEABLES */
        .btn {
            flex: 1;
            padding: 15px;
            text-align: center;
            text-decoration: none; /* Quita el subrayado azul */
            color: white;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1em;
            display: inline-block;
            transition: 0.3s;
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
                    <span>Bienvenido(a): <strong><?php echo htmlspecialchars(explode(' ', $nombre_usuario)[0]); ?></strong></span>
                    <span style="font-size: 10px;">▼</span>
                </button>
                <div class="user-dropdown-content">
                    <a href="logout.php" class="logout-link">Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <div class="container">
            <h2>Panel de Control - Solicitante</h2>
            <p>Hola <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>, has ingresado correctamente al sistema.</p>
            
            <p style="color: #666; margin-top: 20px;">¿Qué desea hacer hoy?</p>
            
            <div class="button-group">
    <!-- El enlace ahora apunta a registro.php que es la página de tu captura -->
    <a href="registro.php" class="btn btn-new">+ CREAR NUEVA SOLICITUD</a>
    
    <a href="ver_tickets.php" class="btn btn-view">CONSULTAR MIS TICKETS</a>
</div>
        </div>
    </div>

</body>
</html>