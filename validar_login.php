<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = (new Database())->getConnection();
    if (!$db) {
        echo "<script>alert('Error de conexión con la base de datos. Verifique las credenciales en config/database.php.'); window.history.back();</script>";
        exit();
    }

    $user = $_POST['ci']; 
    $pass = $_POST['password'];

    // --- BLOQUE 1: BUSQUEDA PARA ESPECIALISTAS ---
    $stmt = $db->prepare("SELECT id, especialista, rol, password FROM especialista WHERE ci = :ci");
    $stmt->execute([':ci' => $user]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, $u['password'])) {
        // Mantenemos TUS variables originales
        $_SESSION['user_id'] = $u['id']; 
        $_SESSION['nombre'] = $u['especialista']; 
        $_SESSION['rol'] = $u['rol'];
        $_SESSION['user_type'] = 'especialista'; 

        header("Location: views/home_especialista.php"); 
        exit();
    }

    // --- BLOQUE 2: BUSQUEDA PARA SOLICITANTES ---
    $stmt = $db->prepare("SELECT ci, nombre, password FROM solicitante WHERE ci = :ci");
    $stmt->execute([':ci' => $user]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($s && (empty($s['password']) || password_verify($pass, $s['password']))) {
        $_SESSION['user_id'] = $s['ci'];
        $_SESSION['nombre'] = $s['nombre'];
        $_SESSION['rol'] = 'Solicitante';
        $_SESSION['user_type'] = 'solicitante';
        
        header("Location: index_solicitante.php");
        exit();
    }

    echo "<script>alert('Cédula o contraseña incorrecta'); window.history.back();</script>";
}