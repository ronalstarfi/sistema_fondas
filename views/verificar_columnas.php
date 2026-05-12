<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "<h2>Verificando Estructura de la Tabla: especialista</h2>";
    
    // Consultamos la estructura real de la tabla
    $query = $db->query("DESCRIBE especialista");
    $columnas = $query->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: sans-serif;'>";
    echo "<tr style='background: #eee;'><th>Campo (Columna)</th><th>Tipo de Dato</th><th>Nulo</th><th>Por Defecto</th></tr>";

    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Verificando Estructura de la Tabla: asistencia_historico</h3>";
    $query_h = $db->query("DESCRIBE asistencia_historico");
    $columnas_h = $query_h->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: sans-serif;'>";
    echo "<tr style='background: #eee;'><th>Campo (Columna)</th><th>Tipo de Dato</th></tr>";
    foreach ($columnas_h as $col) {
        echo "<tr><td><strong>" . $col['Field'] . "</strong></td><td>" . $col['Type'] . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div style='color:red;'>Error de conexión: " . $e->getMessage() . "</div>";
}
?>