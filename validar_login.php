<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = (new Database())->getConnection();
    $user = $_POST['ci']; 
    $pass = $_POST['password'];

    // --- BUSQUEDA EN AMBAS TABLAS ---
    $stmt = $db->prepare("SELECT id, especialista, rol, password FROM especialista WHERE ci = :ci");
    $stmt->execute([':ci' => $user]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $db->prepare("SELECT ci, nombre, password FROM solicitante WHERE ci = :ci");
    $stmt2->execute([':ci' => $user]);
    $s = $stmt2->fetch(PDO::FETCH_ASSOC);

    // 1. Si no existe en ninguna parte
    if (!$u && !$s) {
        header("Location: login.php?error=no_registrado");
        exit();
    }

    // 2. Si es especialista
    if ($u) {
        if (password_verify($pass, $u['password'])) {
            $_SESSION['user_id'] = $u['id']; 
            $_SESSION['nombre'] = $u['especialista']; 
            $_SESSION['rol'] = $u['rol'];
            $_SESSION['user_type'] = 'especialista'; 
            header("Location: views/home_especialista.php"); 
            exit();
        }
    }

    // 3. Si es solicitante
    if ($s) {
        if (empty($s['password'])) {
            // Está en la base de datos pero nunca se ha registrado para crear contraseña
            header("Location: login.php?error=sin_clave");
            exit();
        }
        if (password_verify($pass, $s['password'])) {
            $_SESSION['user_id'] = $s['ci'];
            $_SESSION['nombre'] = $s['nombre'];
            $_SESSION['rol'] = 'Solicitante';
            $_SESSION['user_type'] = 'solicitante';
            header("Location: index_solicitante.php");
            exit();
        }
    }

    // 4. Si existe pero la contraseña no coincide
    header("Location: login.php?error=clave_incorrecta");
    exit();
}