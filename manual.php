<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$rol_sesion = $_SESSION['rol'];
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$v_param = ($rol_sesion === 'Solicitante') ? 'ext' : 'int';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - FONDAS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: white; margin: 0; padding: 0; height: 100vh; overflow: hidden; }
        .wrapper { width: 100%; max-width: 100%; margin: 0; height: 100vh; display: flex; flex-direction: column; }
        .cintillo-container { background: white; padding: 0; text-align: center; }
        .cintillo { width: 100%; height: 95px; object-fit: fill; display: block; margin: 0; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; background-color: #2e7d32; color: white; padding: 12px 30px; }
        .container { background: white; padding: 0; flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: none; flex: 1; display: block; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="cintillo-container">
            <img src="img/logo3.png" alt="Cintillo FONDAS" class="cintillo">
        </div>
        <div class="nav-bar">
            <div style="font-weight: bold; font-size: 1.1rem; display: flex; align-items: center;">
                <span style="margin-right: 8px;">📖</span> MANUAL DE USUARIO
            </div>
            <div style="display: flex; align-items: center; font-size: 0.9em; flex-wrap: wrap; gap: 15px;">
                <span style="background: white; color: #333; padding: 6px 15px; border-radius: 50px; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </span>
                <a href="<?php echo ($rol_sesion === 'Solicitante') ? 'index_solicitante.php' : 'views/home_especialista.php'; ?>" style="color: white; text-decoration: none; border: 1px solid white; padding: 6px 15px; border-radius: 50px; font-weight: bold;">
                    Volver
                </a>
            </div>
        </div>
        <div class="container">
            <iframe src="manual_usuario_fondas.html?v=<?php echo $v_param; ?>&embed=true&t=<?php echo time(); ?>"></iframe>
        </div>
    </div>
</body>
</html>
