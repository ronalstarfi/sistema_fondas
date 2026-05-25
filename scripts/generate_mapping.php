<?php
// scripts/generate_mapping.php
// Uso: php scripts/generate_mapping.php
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "No se pudo conectar a la base de datos\n";
    exit(1);
}

// Áreas conocidas en el sistema
$areas = [
    'Soporte',
    'Infraestructura',
    'Desarrollo',
    'Impresoras y Toner',
    'SIGA'
];

// Palabras clave por área (puedes ajustar)
$keywords = [
    'Soporte' => ['pc','laptop','desktop','workstation','notebook','router','switch','estación','monitor'],
    'Infraestructura' => ['servidor','router','switch','firewall','nas','cable','rack'],
    'Desarrollo' => ['pc','laptop','servidor','estación','desarrollo','IDE','software'],
    'Impresoras y Toner' => ['impresora','multifuncional','plotter','toner','cartucho'],
    'SIGA' => ['siga','sistema','software','aplicación']
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

function fetchAllTipos($db){
    $stmt = $db->query('SELECT id, tipo FROM tipo ORDER BY tipo ASC');
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function fetchAllMarcas($db){
    $stmt = $db->query('SELECT id, marca FROM marca ORDER BY marca ASC');
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$tipos = fetchAllTipos($db);
$marcas = fetchAllMarcas($db);

$result = [];

foreach ($areas as $area) {
    $kws = $keywords[$area] ?? [];
    $matchedTipos = [];
    $matchedMarcas = [];

    foreach ($tipos as $t) {
        $label = strtolower($t['tipo']);
        foreach ($kws as $kw) {
            if (strpos($label, strtolower($kw)) !== false) {
                $matchedTipos[] = $t;
                break;
            }
        }
    }

    foreach ($marcas as $m) {
        $label = strtolower($m['marca']);
        foreach ($kws as $kw) {
            if (strpos($label, strtolower($kw)) !== false) {
                $matchedMarcas[] = $m;
                break;
            }
        }
    }

    // Si no hay matches, puedes tomar un fallback de los primeros N
    if (empty($matchedTipos)) {
        $matchedTipos = array_slice($tipos, 0, 6);
    }
    if (empty($matchedMarcas)) {
        $matchedMarcas = array_slice($marcas, 0, 8);
    }

    // Dedupe marcas por nombre (mantener la de menor id para consistencia)
    $uniqueMarcas = [];
    foreach ($matchedMarcas as $m) {
        $k = mb_strtolower(trim($m['marca']));
        if (!isset($uniqueMarcas[$k]) || $m['id'] < $uniqueMarcas[$k]['id']) {
            $uniqueMarcas[$k] = $m;
        }
    }
    $matchedMarcas = array_values($uniqueMarcas);

    // Ordenar marcas por preferencia si hay prioridad definida para el área
    $matchedMarcas = sortByPreference($matchedMarcas, $brandPriority[$area] ?? [], 'marca');

    // Dedupe tipos por nombre (mantener la de menor id)
    $uniqueTipos = [];
    foreach ($matchedTipos as $t) {
        $k = mb_strtolower(trim($t['tipo']));
        if (!isset($uniqueTipos[$k]) || $t['id'] < $uniqueTipos[$k]['id']) {
            $uniqueTipos[$k] = $t;
        }
    }
    $matchedTipos = array_values($uniqueTipos);

    $result[$area] = [
        'types' => $matchedTipos,
        'brands' => $matchedMarcas
    ];
}

// Imprimir el resultado en formato legible y JSON
echo "Propuesta de mapeo automática por área:\n\n";
foreach ($result as $area => $data) {
    echo "AREA: $area\n";
    echo "  TIPOS:\n";
    foreach ($data['types'] as $t) {
        echo "    - ({$t['id']}) {$t['tipo']}\n";
    }
    echo "  MARCAS:\n";
    foreach ($data['brands'] as $m) {
        echo "    - ({$m['id']}) {$m['marca']}\n";
    }
    echo "\n";
}

file_put_contents(__DIR__ . '/mapping_suggested.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Se guardó la propuesta en scripts/mapping_suggested.json\n";
