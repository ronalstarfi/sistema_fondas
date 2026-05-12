<?php
$db = new PDO('mysql:host=localhost;dbname=sistema_fondas', 'root', 'PARALELEPIPEDO3312'); 
$triggers = $db->query("SHOW TRIGGERS LIKE 'solicitud'")->fetchAll(PDO::FETCH_ASSOC);
print_r($triggers);
?>
