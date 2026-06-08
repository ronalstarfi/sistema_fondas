<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

$area = $_POST['area'] ?? '';

function getItemsFromDatabase(PDO $db, string $table, string $column, array $items): array
{
    $results = [];
    if (empty($items)) {
        return $results;
    }

    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $stmt = $db->prepare("SELECT id, $column FROM $table WHERE $column IN ($placeholders)");
    $stmt->execute($items);
    $existing = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existing[trim($row[$column])] = $row['id'];
    }

    foreach ($items as $item) {
        if (!isset($existing[$item])) {
            $insert = $db->prepare("INSERT INTO $table ($column) VALUES (:value)");
            $insert->execute([':value' => $item]);
            $existing[$item] = $db->lastInsertId();
        }
        $results[] = ['id' => $existing[$item], $column => $item];
    }

    return $results;
}

// Si el área seleccionada es SIGA, ambas listas usan las mismas opciones.
if ($area === 'SIGA') {
    $sigaOptions = [
        'Seguridad',
        'Contabilidad',
        'Tesorería y Banco',
        'Nómina y Personal',
        'Presupuesto',
        'Viáticos'
    ];

    $types = [];
    $brands = [];

    if ($db) {
        $placeholders = implode(',', array_fill(0, count($sigaOptions), '?'));

        $stmtTipo = $db->prepare("SELECT id, tipo FROM tipo WHERE tipo IN ($placeholders)");
        $stmtTipo->execute($sigaOptions);
        $existingTipos = [];
        foreach ($stmtTipo->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existingTipos[trim($row['tipo'])] = $row['id'];
        }

        foreach ($sigaOptions as $label) {
            if (!isset($existingTipos[$label])) {
                $insert = $db->prepare('INSERT INTO tipo (tipo) VALUES (:tipo)');
                $insert->execute([':tipo' => $label]);
                $existingTipos[$label] = $db->lastInsertId();
            }
            $types[] = ['id' => $existingTipos[$label], 'tipo' => $label];
        }

        $stmtMarca = $db->prepare("SELECT id, marca FROM marca WHERE marca IN ($placeholders)");
        $stmtMarca->execute($sigaOptions);
        $existingMarcas = [];
        foreach ($stmtMarca->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existingMarcas[trim($row['marca'])] = $row['id'];
        }

        foreach ($sigaOptions as $label) {
            if (!isset($existingMarcas[$label])) {
                $insert = $db->prepare('INSERT INTO marca (marca) VALUES (:marca)');
                $insert->execute([':marca' => $label]);
                $existingMarcas[$label] = $db->lastInsertId();
            }
            $brands[] = ['id' => $existingMarcas[$label], 'marca' => $label];
        }
    }

    echo json_encode(['types' => $types, 'brands' => $brands]);
    exit;
}

// Si el área seleccionada es Soporte, devolver todos los tipos y marcas válidos de TI.
if ($area === 'Soporte') {
    $supportTypes = [
        'CPU',
        'Laptop',
        'Monitor',
        'Teclado',
        'Mause',
        'Cámara Web',
        'Telefono',
        'Teléfono IP / Analógico',
        'Impresora',
        'Escáner',
        'Router',
        'Switch',
        'Cable de Red (Patch Cord)',
        'Punto de Red / Jack',
        'UPS / Protector de Voltaje',
        'Regulador',
        'Sistema Operativo',
        'Software Administrativo',
        'Correo Electrónico',
        'Ofimática',
        'CCTV / Cámaras',
        'Instalación / Mudanza',
        'Mantenimiento Preventivo'
    ];

    $supportBrands = [
        'HP',
        'Dell',
        'Lenovo',
        'Samsung',
        'Compaq',
        'AON',
        'Benq',
        'Sentey',
        'grandstream',
        'VIT',
        'Cisco',
        'Netgear'
    ];

    $types = getItemsFromDatabase($db, 'tipo', 'tipo', $supportTypes);
    $brands = getItemsFromDatabase($db, 'marca', 'marca', $supportBrands);

    echo json_encode(['types' => $types, 'brands' => $brands]);
    exit;
}

if ($area === 'Desarrollo') {
    $devTypes = [
        'PC',
        'Laptop',
        'Servidor',
        'Estación',
        'Sistema Operativo',
        'Software Administrativo',
        'Correo Electrónico',
        'Ofimática',
        'Bases de Datos',
        'IDE / Desarrollo',
        'Aplicación Web',
        'Aplicación Móvil',
        'Control de Versiones',
        'Integración Continua'
    ];

    $devBrands = [
        'HP',
        'Dell',
        'Lenovo',
        'Microsoft',
        'IBM',
        'Oracle',
        'Cisco',
        'AON',
        'Compaq',
        'Benq',
        'VIT'
    ];

    $types = getItemsFromDatabase($db, 'tipo', 'tipo', $devTypes);
    $brands = getItemsFromDatabase($db, 'marca', 'marca', $devBrands);

    echo json_encode(['types' => $types, 'brands' => $brands]);
    exit;
}

if ($area === 'Impresoras y Toner') {
    $printerTypes = [
        'Impresora',
        'Multifuncional',
        'Plotter',
        'Escáner',
        'Tóner',
        'Cartucho',
        'Mantenimiento de Impresora',
        'Reparación de Impresora',
        'Servicio de Impresora'
    ];

    $printerBrands = [
        'HP',
        'Canon',
        'Epson',
        'Brother',
        'Samsung',
        'Lexmark',
        'Sharp',
        'Ricoh',
        'Kyocera',
        'Xerox',
        'Konica Minolta',
        'Dell',
        'AON',
        'Benq',
        'Compaq'
    ];

    $types = getItemsFromDatabase($db, 'tipo', 'tipo', $printerTypes);
    $brands = getItemsFromDatabase($db, 'marca', 'marca', $printerBrands);

    echo json_encode(['types' => $types, 'brands' => $brands]);
    exit;
}

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
