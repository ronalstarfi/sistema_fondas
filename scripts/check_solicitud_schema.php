<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo "NO_CONN\n";
    exit(1);
}
$rows = $db->query('SHOW COLUMNS FROM solicitud')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['Field'] . ' ' . $r['Type'] . ' ' . $r['Null'] . ' ' . $r['Key'] . ' ' . $r['Extra'] . PHP_EOL;
}
