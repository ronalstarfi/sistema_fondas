<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM solicitud WHERE estatus = 'ABIERTO'");
    $stmt->execute();
    $count = (int) $stmt->fetchColumn();
    echo json_encode(['open' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar tickets', 'details' => $e->getMessage()]);
}
