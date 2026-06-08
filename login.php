<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="es" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - FONDAS</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; overflow: hidden; }

        
        /* Fondo con imagen logo5.png */
        .bg {
            background-image: url("img/logo4.png");
            height: 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-right: 8%;
            position: relative;
        }

        .bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(0.7px);
            z-index: 0;
        }

        .login-box {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.90);
            width: 360px;
            padding: 40px 30px;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(33, 45, 69, 0.10), 0 0 0 1px rgba(46, 125, 50, 0.08);
            text-align: center;
            border: 1px solid rgba(46, 125, 50, 0.35);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .login-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 55px rgba(33, 45, 69, 0.14), 0 0 25px rgba(46, 125, 50, 0.18);
        }

        /* Logo institucional (logo1.png) destacado */
        .logo-top { 
            width: 160px; /* Tamaño grande solicitado */
            height: auto;
            margin-bottom: 30px; 
            filter: drop-shadow(0 0 12px rgba(255, 255, 255, 1)); /* Brillo blanco para resaltar */
        }

        select, input {
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 15px;
            transition: 0.3s;
        }

        .input-group {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        
        .input-group select {
            width: 25%;
            margin: 0;
            cursor: pointer;
            text-align: center;
        }
        
        .input-group input {
            width: 75%;
            margin: 0;
        }

        /* Enfoque en Verde Institucional */
        input:focus, select:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 8px rgba(46, 125, 50, 0.3);
        }

        /* Botón con el verde de FONDAS */
        .btn-ingresar {
            background-color: #2e7d32; 
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
            margin-top: 18px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 10px 18px rgba(46, 125, 50, 0.18);
        }

        .btn-ingresar:hover {
            background-color: #259143;
            transform: translateY(-2px);
        }

        /* Botón de Registro */
        .btn-registrar {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #d1d1d1;
            padding: 14px;
            width: 100%;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
            margin-top: 12px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
            display: inline-block;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-registrar:hover {
            background-color: #e2e2e2;
            transform: translateY(-2px);
        }

        .links {
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        /* Links en verde para combinar */
        .links a { 
            color: #2e7d32; 
            text-decoration: none; 
            font-weight: bold;
        }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="bg">
        <div class="login-box">
            <img src="img/logo1.png" alt="Logo FONDAS" class="logo-top">

            <?php if (isset($_GET['error'])): ?>
                <?php 
                    $mensaje = "";
                    if ($_GET['error'] == 'no_registrado') {
                        $mensaje = "Cédula no registrada. Por favor regístrese.";
                    } elseif ($_GET['error'] == 'sin_clave') {
                        $mensaje = "Aún no ha creado su contraseña. Vaya a Registrarse.";
                    } elseif ($_GET['error'] == 'clave_incorrecta') {
                        $mensaje = "Contraseña incorrecta.";
                    } elseif ($_GET['error'] == '1') {
                        $mensaje = "Cédula o contraseña incorrecta.";
                    }
                ?>
                <?php if ($mensaje !== ""): ?>
                    <div style="background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center; font-weight: bold;">
                        ⚠️ <?php echo $mensaje; ?>
                    </div>
                    <script>
                        // Limpiar la URL para que si el usuario recarga la página, el error no vuelva a aparecer
                        if (window.history.replaceState) {
                            window.history.replaceState(null, null, window.location.pathname);
                        }
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <form action="validar_login.php" method="POST">
                <div class="input-group">
                    <select name="tipo_doc" required translate="no">
                        <option value="V" selected>V</option>
                        <option value="E">E</option>
                        <option value="J">J</option>
                    </select>
                    <input type="text" name="ci" placeholder="Cédula"
                           inputmode="numeric" maxlength="8"
                           pattern="[0-9]{1,8}"
                           title="Ingrese solo números (máximo 8 dígitos)"
                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 8);"
                           required>
                </div>

                <input type="password" name="password" placeholder="Contraseña" required>

                <div class="links">
                    <a href="gestion_clave.php" style="color: #0d6efd;">¿Olvidó su contraseña?</a>
                </div>

                <button type="submit" class="btn-ingresar">INGRESAR AL SISTEMA</button>
                <a href="registro_usuario.php" class="btn-registrar">REGISTRARSE</a>
            </form>
        </div>
    </div>

</body>
</html>