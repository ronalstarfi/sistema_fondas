<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo "NO_CONN\n";
    exit(1);
}
try {
    $stmt = $db->query("SHOW COLUMNS FROM solicitud LIKE 'area_problema'");
    if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "COLUMN_EXISTS\n";
    } else {
        $db->exec("ALTER TABLE solicitud ADD COLUMN area_problema VARCHAR(100) NULL AFTER ci");
        echo "ALTER_OK\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
