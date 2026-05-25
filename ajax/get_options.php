<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

$area = $_POST['area'] ?? '';

// Mapeo básico área -> patrones de tipos/marcas. Ajusta según tu BD.
$mapping = [
    'Soporte' => [
        'types' => ['PC','Laptop','Computadora','Workstation','Router','Switch','Estación'],
        'brands' => ['HP','Dell','Lenovo','Acer','Asus']
    ],
    'Infraestructura' => [
        'types' => ['Servidor','Router','Switch','Firewall','NAS'],
        'brands' => ['Cisco','HP','Dell','Netgear']
    ],
    'Desarrollo' => [
        'types' => ['PC','Laptop','Servidor','Estación'],
        'brands' => ['HP','Dell','Lenovo']
    ],
    'Impresoras y Toner' => [
        'types' => ['Impresora','Multifuncional','Plotter'],
        'brands' => ['HP','Brother','Epson','Canon']
    ],
    'SIGA' => [
        'types' => ['Servidor','PC','Software'],
        'brands' => ['HP','Dell','IBM']
    ]
];

$brandPriority = [
    'Soporte' => ['Dell', 'HP', 'Lenovo', 'Compaq', 'Benq', 'AON'],
    'Infraestructura' => ['Cisco', 'Dell', 'HP', 'Netgear', 'Compaq', 'AON', 'Benq'],
    'Desarrollo' => ['Dell', 'HP', 'Lenovo', 'Compaq', 'AON', 'Benq'],
    'Impresoras y Toner' => ['HP', 'Canon', 'Epson', 'Brother', 'Dell', 'AON', 'Benq'],
    'SIGA' => ['IBM', 'Dell', 'HP', 'Compaq', 'AON']
];

function sortByPreference(array $items, array $priority, string $field): array
{
    $order = array_flip(array_map('mb_strtolower', $priority));
    usort($items, function ($a, $b) use ($order, $field) {
        $aKey = mb_strtolower($a[$field]);
        $bKey = mb_strtolower($b[$field]);
        $aPos = $order[$aKey] ?? 999;
        $bPos = $order[$bKey] ?? 999;
        if ($aPos === $bPos) {
            return $a[$field] <=> $b[$field];
        }
        return $aPos <=> $bPos;
    });
    return $items;
}

$types = [];
$brands = [];

try {
    if ($db) {
        // Si existe una propuesta generada previamente, usar IDs desde el archivo
        $suggestPath = __DIR__ . '/../scripts/mapping_suggested.json';
        if (file_exists($suggestPath)) {
            $json = json_decode(file_get_contents($suggestPath), true);
            if (isset($json[$area])) {
                $areaData = $json[$area];
                $typeIds = array_column($areaData['types'], 'id');
                $brandIds = array_column($areaData['brands'], 'id');

                if (!empty($typeIds)) {
                    $in = implode(',', array_map('intval', $typeIds));
                    $stmt = $db->query("SELECT id, tipo FROM tipo WHERE id IN ($in) ORDER BY tipo ASC");
                    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                if (!empty($brandIds)) {
                    $in2 = implode(',', array_map('intval', $brandIds));
                    $stmt = $db->query("SELECT id, marca FROM marca WHERE id IN ($in2) ORDER BY marca ASC");
                    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                // limpiar duplicados por nombre (case-insensitive)
                $cleanTypes = [];
                $seen = [];
                foreach ($types as $t) {
                    $key = mb_strtolower($t['tipo']);
                    if (!isset($seen[$key])) { $cleanTypes[] = $t; $seen[$key] = true; }
                }
                $cleanBrands = [];
                $seen = [];
                foreach ($brands as $b) {
                    $key = mb_strtolower($b['marca']);
                    if (!isset($seen[$key])) { $cleanBrands[] = $b; $seen[$key] = true; }
                }
                $cleanBrands = sortByPreference($cleanBrands, $brandPriority[$area] ?? [], 'marca');
                echo json_encode(['types' => $cleanTypes, 'brands' => $cleanBrands]);
                exit;
            }
        }
        // Buscar tipos basados en patrones si existen
        if (isset($mapping[$area]) && !empty($mapping[$area]['types'])) {
            $patterns = $mapping[$area]['types'];
            $where = [];
            $params = [];
            foreach ($patterns as $i => $p) {
                $where[] = "tipo LIKE :p$i";
                $params[":p$i"] = "%$p%";
            }
            $sql = 'SELECT id, tipo FROM tipo WHERE ' . implode(' OR ', $where) . ' ORDER BY tipo ASC';
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Si no se encontraron tipos filtrados, devolver todos como fallback
        if (empty($types)) {
            $stmt = $db->query('SELECT id, tipo FROM tipo ORDER BY tipo ASC');
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Buscar marcas basadas en mapping
        if (isset($mapping[$area]) && !empty($mapping[$area]['brands'])) {
            $patterns = $mapping[$area]['brands'];
            $where = [];
            $params = [];
            foreach ($patterns as $i => $p) {
                $where[] = "marca LIKE :m$i";
                $params[":m$i"] = "%$p%";
            }
            $sql = 'SELECT id, marca FROM marca WHERE ' . implode(' OR ', $where) . ' ORDER BY marca ASC';
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fallback: todas las marcas
        if (empty($brands)) {
            $stmt = $db->query('SELECT id, marca FROM marca ORDER BY marca ASC');
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// limpiar duplicados por nombre (case-insensitive) antes de devolver
$cleanTypes = [];
$seen = [];
foreach ($types as $t) {
    $key = mb_strtolower($t['tipo']);
    if (!isset($seen[$key])) { $cleanTypes[] = $t; $seen[$key] = true; }
}
$cleanBrands = [];
$seen = [];
foreach ($brands as $b) {
    $key = mb_strtolower($b['marca']);
    if (!isset($seen[$key])) { $cleanBrands[] = $b; $seen[$key] = true; }
}
$cleanBrands = sortByPreference($cleanBrands, $brandPriority[$area] ?? [], 'marca');

echo json_encode(['types' => $cleanTypes, 'brands' => $cleanBrands]);
