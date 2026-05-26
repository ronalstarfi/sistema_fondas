<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo "NO_CONN\n";
    exit(1);
}
try {
    $exists = false;
    $stmt = $db->query("SHOW COLUMNS FROM solicitud LIKE 'marca_id'");
    if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
        $exists = true;
    }
    if ($exists) {
        echo "COLUMN_EXISTS\n";
    } else {
        $db->exec('ALTER TABLE solicitud ADD COLUMN marca_id INT NULL AFTER tsolicitud');
        echo "ALTER OK\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
