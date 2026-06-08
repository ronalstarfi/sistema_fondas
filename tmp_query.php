<?php
$db = new PDO("mysql:host=localhost;dbname=sistema_fondas;charset=utf8mb4","root","Day123*/*");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach($db->query("SELECT ci,nombre,password FROM solicitante LIMIT 5") as $row) {
    echo "S:" . json_encode($row) . "\n";
}
foreach($db->query("SELECT ci,especialista,rol,password FROM especialista LIMIT 5") as $row) {
    echo "E:" . json_encode($row) . "\n";
}
?>
