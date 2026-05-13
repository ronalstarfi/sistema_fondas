<?php
/**
 * scripts/init_metadata.php
 * Ejecuta la creación de la tabla de metadatos.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Metadata.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $metadata = new Metadata($db);
    echo "Iniciando configuración de tabla de metadatos...\n";
    
    if ($metadata->setupTable() !== false) {
        echo "¡Éxito! La tabla 'sistema_metadata' ha sido creada o ya existe.\n";
    } else {
        echo "Error al crear la tabla.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
