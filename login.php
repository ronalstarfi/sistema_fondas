<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SICEU - Acceso al Sistema</title>
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
            justify-content: flex-end; /* Alineación a la derecha según el modelo */
            align-items: center;
            padding-right: 8%;
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.95);
            width: 380px;
            padding: 45px 35px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            text-align: center;
            backdrop-filter: blur(4px);
        }

        /* Logo institucional (logo1.png) destacado */
        .logo-top { 
            width: 160px; /* Tamaño grande solicitado */
            height: auto;
            margin-bottom: 30px; 
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
            padding: 15px;
            width: 100%;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            margin-top: 20px;
            border-radius: 5px;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-ingresar:hover { 
            background-color: #1b5e20; /* Tono más oscuro para el hover */
            transform: translateY(-2px);
        }

        .links {
            margin-top: 25px;
            font-size: 13px;
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

            <form action="validar_login.php" method="POST">
                <div class="input-group">
                    <select name="tipo_doc" required>
                        <option value="V" selected>V</option>
                        <option value="E">E</option>
                        <option value="J">J</option>
                    </select>
                    <input type="text" name="ci" placeholder="Cédula" 
                           pattern="[0-9]+" title="Ingrese solo números" required>
                </div>

                <input type="password" name="password" placeholder="Contraseña" required>

                <button type="submit" class="btn-ingresar">INGRESAR AL SISTEMA</button>
            </form>

            <div class="links">
                <a href="gestion_clave.php" style="color: #0d6efd;">¿Olvidó su contraseña?</a>
            </div>
        </div>
    </div>

</body>
</html>