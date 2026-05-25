<?php
/**
 * logout.php
 * Cierre de sesión con trazabilidad forense.
 */
session_start();

// 1. REGISTRO FORENSE (Si hay usuario)
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'models/Metadata.php';
    
    try {
        $db = (new Database())->getConnection();
        if ($db) {
            $metadata = new Metadata($db);
            // Registramos el evento de logout como un metadato temporal o log
            $metadata->set('usuario', $_SESSION['user_id'], 'last_logout_at', date('Y-m-d H:i:s'));
            $metadata->set('usuario', $_SESSION['user_id'], 'last_logout_ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            // --- AUDITORIA GENERAL ---
            $sql = "INSERT INTO auditoria_general (tipo_movimiento, descripcion, usuario, direccion_ip) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute(['Cierre de Sesión', 'El usuario ha cerrado su sesión correctamente', $_SESSION['nombre'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        }
    } catch (Exception $e) {
        // Fallback silencioso si falla el log
    }
}

// 2. Limpiar todas las variables de sesión
$_SESSION = array();

// 3. Borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión
session_destroy();

// 5. Redirigir
header("Location: login.php");
exit();
?>