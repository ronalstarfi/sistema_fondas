<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['temp_ci'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Aquí tenemos la cédula que el usuario ingresó en el paso anterior
    $id_usuario = $_SESSION['temp_ci']; 
    $tipo = $_SESSION['user_type'];
    
    // Encriptamos la contraseña para que aparezca el código hash ($2y$10$) en la BD[cite: 2]
    $hash = password_hash($_POST['p1'], PASSWORD_DEFAULT); 

    // DETERMINAMOS LA TABLA SEGÚN EL TIPO[cite: 2]
    if ($tipo == 'especialista') {
        // MEJORA: Buscamos por 'ci' porque es el dato que tenemos en la sesión
        $sql = "UPDATE especialista SET password = :p WHERE ci = :id";
    } else {
        // MANTENEMOS IGUAL: Para solicitante ya funcionaba bien con 'ci'
        $sql = "UPDATE solicitante SET password = :p WHERE ci = :id";
    }
    
    $stmt = $db->prepare($sql);
    
    // Preparamos la respuesta visual con SweetAlert2[cite: 2]
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body style='font-family: sans-serif;'>";

    // Ejecutamos la actualización[cite: 2]
    if ($stmt->execute([':p' => $hash, ':id' => $id_usuario])) {
        // IMPORTANTE: Solo destruimos la sesión si la actualización fue real[cite: 2]
        session_destroy(); 
        echo "
        <script>
            Swal.fire({
                title: '¡Contraseña Guardada!',
                text: 'Tu clave ha sido actualizada exitosamente.',
                icon: 'success',
                confirmButtonColor: '#198754',
                confirmButtonText: 'Ir al Login',
                backdrop: `rgba(46, 125, 50, 0.2)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        </script>";
    } else {
        echo "
        <script>
            Swal.fire({
                title: 'Error de Sistema',
                text: 'No se pudo guardar en la base de datos.',
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Reintentar'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }

    echo "</body></html>";
}
?>