<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SICEU - Activación de Cuenta</title>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Segoe UI', sans-serif; overflow: hidden; }
        .bg { background-image: url("img/logo5.png"); height: 100%; background-position: center; background-size: cover; display: flex; justify-content: center; align-items: center; }
        .box { background: rgba(255, 255, 255, 0.95); width: 400px; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        h3 { color: #2e7d32; text-transform: uppercase; margin-bottom: 15px; }
        /* Estilo mejorado para las instrucciones */
        .instrucciones { font-size: 13px; color: #666; margin-bottom: 25px; line-height: 1.4; }
        .instrucciones strong { color: #2e7d32; }
        
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-verde { background: #2e7d32; color: white; border: none; padding: 14px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; transition: 0.3s; }
        .btn-verde:hover { background: #1b5e20; }
    </style>
</head>
<body>
    <div class="bg">
        <div class="box">
            <!-- Logo institucional -->
            <img src="img/logo1.png" style="width: 150px; margin-bottom: 20px;">
            
            <h3>Recuperar / Activar</h3>
            
            <div class="instrucciones">
                Ingrese su Cédula y su dato de validación:<br>
                <strong>(ID para Especialistas o Extensión para Solicitantes)</strong>
            </div>
            
            <form action="procesar_recuperacion.php" method="POST">
                <!-- Campo de Cédula -->
                <input type="text" name="ci" placeholder="Cédula de Identidad" pattern="[0-9]+" title="Solo números" required>
                
                <!-- Campo de Validación Ajustado -->
                <input type="text" name="verificacion" placeholder="ID o Extensión (Ej: 32 o 5006)" required>
                
                <button type="submit" class="btn-verde">VERIFICAR DATOS</button>
            </form>
            
            <a href="login.php" style="display:block; margin-top:20px; color:#2e7d32; font-size:13px; text-decoration:none; font-weight:500;">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>