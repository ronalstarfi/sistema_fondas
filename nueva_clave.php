<?php session_start(); if(!isset($_SESSION['temp_ci'])) header("Location: login.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SICEU - Nueva Clave</title>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .bg { background-image: url("img/logo5.png"); height: 100%; background-position: center; background-size: cover; display: flex; justify-content: center; align-items: center; }
        .box { background: white; width: 380px; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .btn-verde { background: #2e7d32; color: white; border: none; padding: 14px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        h3 { color: #2e7d32; }
    </style>
</head>
<body>
    <div class="bg">
        <div class="box">
            <img src="img/logo1.png" style="width: 140px; margin-bottom: 20px;">
            <h3>Crear Contraseña</h3>
            <p style="font-size: 13px; color: #666;">Use letras, números y símbolos para su seguridad.</p>
            <form action="guardar_clave.php" method="POST" onsubmit="return validar();">
                <input type="password" name="p1" id="p1" placeholder="Contraseña Nueva" required minlength="8">
                <input type="password" name="p2" id="p2" placeholder="Confirmar Contraseña" required>
                <button type="submit" class="btn-verde">FINALIZAR ACTIVACIÓN</button>
            </form>
        </div>
    </div>
    <script>
        function validar() {
            if(document.getElementById('p1').value !== document.getElementById('p2').value) {
                alert("Las contraseñas no coinciden"); return false;
            }
            return true;
        }
    </script>
</body>
</html>