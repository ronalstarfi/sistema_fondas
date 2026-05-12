<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $documento = $_POST['ci']; // Aquí el usuario ingresa su Cédula
    $val = $_POST['verificacion']; // Aquí el especialista ingresará su ID numérico

    // --- LOGICA PARA ESPECIALISTAS ---
    // Buscamos coincidencia entre la columna 'ci' y la columna 'id'
    $sql_e = "SELECT ci FROM especialista WHERE ci = :ci AND id = :id";
    $stmt_e = $db->prepare($sql_e);
    $stmt_e->execute([':ci' => $documento, ':id' => $val]);

    if ($stmt_e->rowCount() > 0) {
        $_SESSION['temp_ci'] = $documento;
        $_SESSION['user_type'] = 'especialista';
        header("Location: nueva_clave.php");
        exit();
    }

    // --- LOGICA PARA SOLICITANTES (Se mantiene por Cédula y Extensión) ---
    // Esto asegura que no se dañe lo que ya funciona para ellos
    $sql_s = "SELECT ci FROM solicitante WHERE ci = :ci AND extension = :val";
    $stmt_s = $db->prepare($sql_s);
    $stmt_s->execute([':ci' => $documento, ':val' => $val]);

    if ($stmt_s->rowCount() > 0) {
        $_SESSION['temp_ci'] = $documento;
        $_SESSION['user_type'] = 'solicitante';
        header("Location: nueva_clave.php");
        exit();
    }

    echo "<script>alert('Datos incorrectos. Verifique su Cédula y su ID de validación.'); window.history.back();</script>";
}
?>