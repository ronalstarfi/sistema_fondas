<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) { echo "NO_CONN\n"; exit(1); }
$rows = $db->query('SELECT id, especialista, area_especifica, rol, disponibilidad, tickets_activos FROM especialista ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo implode(' | ', [$r['id'], $r['especialista'], $r['area_especifica'], $r['rol'], $r['disponibilidad'], $r['tickets_activos']]) . PHP_EOL;
}
