<?php
$pdo = new PDO('mysql:host=localhost;dbname=sistema_fondas;charset=utf8mb4','root','Day123*/*');
$desde = date('Y-m-01');
$hasta = date('Y-m-t');
$params = ['desde' => $desde, 'hasta' => $hasta];
$sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN estatus = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados, SUM(CASE WHEN estatus != 'Cerrado' THEN 1 ELSE 0 END) AS pendientes FROM solicitud WHERE DATE(fechainicial) BETWEEN :desde AND :hasta";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "RANGE={$desde} to {$hasta} -> total={$row['total']} cerrados={$row['cerrados']} pendientes={$row['pendientes']}\n";
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM solicitud WHERE DATE(fechainicial) BETWEEN :desde AND :hasta AND estatus = 'Cerrado'");
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Cerrado count={$row['total']}\n";
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM solicitud WHERE DATE(fechainicial) BETWEEN :desde AND :hasta AND estatus != 'Cerrado'");
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Not cerrado count={$row['total']}\n";
?>