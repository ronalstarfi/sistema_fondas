<?php
/**
 * models/Metadata.php
 * Modelo para gestionar metadatos dinámicos en la base de datos.
 */

class Metadata {
    private $db;
    private $table = "sistema_metadata";

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Crea la tabla de metadatos si no existe.
     */
    public function setupTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL, -- Ej: 'ticket', 'usuario', 'sistema'
            entity_id INT DEFAULT NULL,      -- ID del registro relacionado
            meta_key VARCHAR(100) NOT NULL,  -- Nombre del metadato
            meta_value TEXT,                 -- Valor del metadato
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_meta (entity_type, entity_id, meta_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        return $this->db->exec($sql);
    }

    /**
     * Guarda o actualiza un metadato.
     */
    public function set($entity_type, $entity_id, $key, $value) {
        $sql = "INSERT INTO {$this->table} (entity_type, entity_id, meta_key, meta_value) 
                VALUES (:type, :id, :key, :value)
                ON DUPLICATE KEY UPDATE meta_value = :value";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':type' => $entity_type,
            ':id' => $entity_id,
            ':key' => $key,
            ':value' => $value
        ]);
    }

    /**
     * Obtiene un metadato.
     */
    public function get($entity_type, $entity_id, $key) {
        $sql = "SELECT meta_value FROM {$this->table} 
                WHERE entity_type = :type AND entity_id = :id AND meta_key = :key LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':type' => $entity_type,
            ':id' => $entity_id,
            ':key' => $key
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['meta_value'] : null;
    }
}
?>
