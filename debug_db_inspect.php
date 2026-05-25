<?php
$pdo = new PDO('mysql:host=localhost;dbname=sistema_fondas;charset=utf8mb4','root','Day123*/*');
$stmt = $pdo->query('DESCRIBE solicitud');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
    echo $row['Field'].' '.$row['Type'].' '.($row['Null']??'').'\n';
}
$stmt2 = $pdo->query('SELECT COUNT(*) as total, MIN(fechainicial) as minfecha, MAX(fechainicial) as maxfecha FROM solicitud');
$row = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "TOTAL={$row['total']} MIN={$row['minfecha']} MAX={$row['maxfecha']}\n";
$stmt3 = $pdo->query("SELECT DISTINCT estatus FROM solicitud");
while($r = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    echo 'ESTATUS='.$r['estatus'].'\n';
}
$stmt4 = $pdo->query("SELECT COUNT(*) AS total_today FROM solicitud WHERE DATE(fechainicial)=CURDATE()");
$row2 = $stmt4->fetch(PDO::FETCH_ASSOC);
echo "TODAY={$row2['total_today']}\n";
?>