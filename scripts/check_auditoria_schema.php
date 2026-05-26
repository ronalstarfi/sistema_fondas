<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo "NO_CONN\n";
    exit(1);
}
$tables = [
    'auditoria_solicitudes',
    'auditoria_general',
];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $rows = $db->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['Field'] . ' ' . $r['Type'] . ' ' . $r['Null'] . ' ' . $r['Key'] . ' ' . $r['Extra'] . PHP_EOL;
    }
    echo PHP_EOL;
}
