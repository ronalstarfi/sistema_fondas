<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'especialista') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

include("../config/database.php");
$database = new Database();
$db = $database->getConnection();

function calcularPeriodo($frecuencia) {
    $hoy = new DateTime();
    $desde = clone $hoy;
    $hasta = clone $hoy;

    switch ($frecuencia) {
        case 'diario':
            break;
        case 'semanal':
            $desde->modify('-6 days');
            break;
        case 'mensual':
            $desde->modify('first day of this month');
            $hasta->modify('last day of this month');
            break;
        case 'trimestral':
            $desde->modify('first day of this month')->modify('-2 months');
            $hasta->modify('last day of this month');
            break;
        case 'anual':
            $desde->setDate((int) $hoy->format('Y'), 1, 1);
            $hasta->setDate((int) $hoy->format('Y'), 12, 31);
            break;
        default:
            $desde->modify('first day of this month');
            $hasta->modify('last day of this month');
            break;
    }

    return [
        'desde' => $desde->format('Y-m-d'),
        'hasta' => $hasta->format('Y-m-d')
    ];
}

$frecuencia = isset($_GET['frecuencia']) ? $_GET['frecuencia'] : 'mensual';
$desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : null;
$hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : null;
$especialista = isset($_GET['especialista']) ? $_GET['especialista'] : '';
$estatus = isset($_GET['estatus']) ? $_GET['estatus'] : '';

if (!$desde || !$hasta) {
    $periodo = calcularPeriodo($frecuencia);
    $desde = $periodo['desde'];
    $hasta = $periodo['hasta'];
}

$especialista_label = 'Todos los especialistas';
if (!empty($especialista)) {
    $stmt = $db->prepare("SELECT especialista FROM especialista WHERE id = :id");
    $stmt->execute(['id' => $especialista]);
    $especialista_label = $stmt->fetchColumn() ?: $especialista_label;
}

$estatus_label = 'Todos los estatus';
if (!empty($estatus)) {
    $estatus_label = $estatus;
}

$params = ['desde' => $desde, 'hasta' => $hasta];
$where = "WHERE DATE(s.fechainicial) BETWEEN :desde AND :hasta";
if (!empty($especialista)) {
    $where .= " AND s.especialista_id = :especialista";
    $params['especialista'] = $especialista;
}
if (!empty($estatus)) {
    $where .= " AND s.estatus = :estatus";
    $params['estatus'] = $estatus;
}

$sql_resumen = "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN s.estatus = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
                        SUM(CASE WHEN s.estatus != 'Cerrado' THEN 1 ELSE 0 END) AS pendientes
                FROM solicitud s $where";
$stmt = $db->prepare($sql_resumen);
$stmt->execute($params);
$resumen = $stmt->fetch(PDO::FETCH_ASSOC);

$sql_grafica = "SELECT DATE_FORMAT(s.fechainicial, '%d/%m/%Y') AS periodo,
                        COUNT(*) AS cantidad
                FROM solicitud s $where
                GROUP BY DATE_FORMAT(s.fechainicial, '%d/%m/%Y')
                ORDER BY MIN(s.fechainicial) ASC";
$stmt = $db->prepare($sql_grafica);
$stmt->execute($params);
$grafica = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_esp = "SELECT e.especialista, COUNT(*) AS total
            FROM solicitud s
            INNER JOIN especialista e ON s.especialista_id = e.id
            $where
            GROUP BY e.especialista
            ORDER BY total DESC";
$stmt = $db->prepare($sql_esp);
$stmt->execute($params);
$especialistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_departamentos = "SELECT COALESCE(sol.ubicacion, 'Sin departamento') AS departamento, COUNT(*) AS total
                     FROM solicitud s
                     LEFT JOIN solicitante sol ON s.ci = sol.ci
                     $where
                     GROUP BY departamento
                     ORDER BY total DESC";
$stmt = $db->prepare($sql_departamentos);
$stmt->execute($params);
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_tickets = "SELECT s.id, s.descripcion, s.estatus, COALESCE(sol.ubicacion, 'Sin departamento') AS departamento, e.especialista
                FROM solicitud s
                LEFT JOIN solicitante sol ON s.ci = sol.ci
                LEFT JOIN especialista e ON s.especialista_id = e.id
                $where
                ORDER BY s.fechainicial DESC";
$stmt = $db->prepare($sql_tickets);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

function normalizarTexto($texto) {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $texto)), 'UTF-8');
}

$categorias_def = [
    'Mantenimiento y Reparación de Impresoras' => ['impresora', 'impresoras', 'tóner', 'toner', 'fusor', 'rodillo', 'cartucho', 'papel', 'impresión'],
    'Soporte de Hardware y Periféricos' => ['monitor', 'monitores', 'teclado', 'mouse', 'ratón', 'bios', 'placa', 'cpu', 'memoria', 'disco', 'fuente', 'conector', 'cable', 'tarjeta', 'periférico', 'periferico'],
    'Software y Configuración' => ['software', 'sistema', 'configuración', 'configuracion', 'instalación', 'instalacion', 'driver', 'controlador', 'red', 'internet', 'vpn', 'correo', 'actualización', 'actualizacion', 'hora', 'fecha'],
];

$categorias = [];
foreach ($categorias_def as $nombre => $palabras) {
    $categorias[$nombre] = [
        'total' => 0,
        'ejemplos' => []
    ];
}
$categorias['Otros'] = ['total' => 0, 'ejemplos' => []];

$descripcion_map = [];
$observaciones = [];
$recomendaciones = [];
$pendientes = [];
$sin_asignar = [];

foreach ($tickets as $ticket) {
    $texto_normalizado = normalizarTexto($ticket['descripcion'] ?? '');
    if (!empty($texto_normalizado)) {
        $descripcion_map[$texto_normalizado][] = $ticket;
    }
    if (strtoupper($ticket['estatus']) !== 'CERRADO') {
        $pendientes[] = $ticket;
    }
    if (empty($ticket['especialista'])) {
        $sin_asignar[] = $ticket;
    }

    $clasificado = false;
    foreach ($categorias_def as $nombre => $palabras) {
        foreach ($palabras as $palabra) {
            if ($palabra !== '' && mb_strpos($texto_normalizado, $palabra) !== false) {
                $categorias[$nombre]['total']++;
                if (count($categorias[$nombre]['ejemplos']) < 3) {
                    $categorias[$nombre]['ejemplos'][] = $ticket['descripcion'];
                }
                $clasificado = true;
                break 2;
            }
        }
    }
    if (!$clasificado) {
        $categorias['Otros']['total']++;
        if (count($categorias['Otros']['ejemplos']) < 3) {
            $categorias['Otros']['ejemplos'][] = $ticket['descripcion'];
        }
    }
}

$duplicados = [];
$tickets_sin_descripcion = 0;
$sin_descripcion_ids = [];
foreach ($tickets as $ticket) {
    $texto_normalizado = normalizarTexto($ticket['descripcion'] ?? '');
    if ($texto_normalizado === '') {
        $tickets_sin_descripcion++;
        $sin_descripcion_ids[] = $ticket['id'];
    }
    if (!empty($texto_normalizado)) {
        $descripcion_map[$texto_normalizado][] = $ticket;
    }
    if (strtoupper($ticket['estatus']) !== 'CERRADO') {
        $pendientes[] = $ticket;
    }
    if (empty($ticket['especialista'])) {
        $sin_asignar[] = $ticket;
    }

    $clasificado = false;
    foreach ($categorias_def as $nombre => $palabras) {
        foreach ($palabras as $palabra) {
            if ($palabra !== '' && mb_strpos($texto_normalizado, $palabra) !== false) {
                $categorias[$nombre]['total']++;
                if (count($categorias[$nombre]['ejemplos']) < 3) {
                    $categorias[$nombre]['ejemplos'][] = $ticket['descripcion'];
                }
                $clasificado = true;
                break 2;
            }
        }
    }
    if (!$clasificado) {
        $categorias['Otros']['total']++;
        if (count($categorias['Otros']['ejemplos']) < 3) {
            $categorias['Otros']['ejemplos'][] = $ticket['descripcion'];
        }
    }
}

foreach ($descripcion_map as $texto => $items) {
    if (count($items) > 1) {
        $ids = array_map(function($item) { return $item['id']; }, $items);
        $duplicados[] = [
            'descripcion' => $items[0]['descripcion'],
            'cantidad' => count($items),
            'tickets' => $ids
        ];
    }
}

foreach ($departamentos as &$depto) {
    $depto['principales'] = [];
}
unset($depto);

$problemas_por_depto = [];
foreach ($tickets as $ticket) {
    $nombre = $ticket['departamento'];
    if (!isset($problemas_por_depto[$nombre])) {
        $problemas_por_depto[$nombre] = [];
    }
    if (count($problemas_por_depto[$nombre]) < 2 && !empty($ticket['descripcion'])) {
        $problemas_por_depto[$nombre][] = $ticket['descripcion'];
    }
}

foreach ($departamentos as &$depto) {
    $nombre = $depto['departamento'];
    if (!empty($problemas_por_depto[$nombre])) {
        $depto['principales'] = array_slice(array_unique($problemas_por_depto[$nombre]), 0, 2);
    }
}
unset($depto);

$sql_personal = "SELECT COALESCE(e.especialista, 'Sin asignar') AS especialista,
                        COUNT(*) AS total,
                        SUM(CASE WHEN s.estatus = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
                        SUM(CASE WHEN s.estatus != 'Cerrado' THEN 1 ELSE 0 END) AS pendientes
                FROM solicitud s
                LEFT JOIN especialista e ON s.especialista_id = e.id
                $where
                GROUP BY especialista
                ORDER BY total DESC";
$stmt = $db->prepare($sql_personal);
$stmt->execute($params);
$control_personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicados) > 0) {
    $observaciones[] = 'Se detectaron tickets duplicados con contenido similar, lo cual sugiere revisar la consolidación de solicitudes antes de reabrir nuevos casos.';
}
if (count($pendientes) > 0) {
    $observaciones[] = 'Existen tickets pendientes de cierre técnico que requieren seguimiento para completar la atención.';
}
if (count($sin_asignar) > 0) {
    $observaciones[] = 'Algunos tickets aún no tienen técnico asignado y deben ser derivados a especialistas disponibles.';
}
if ($tickets_sin_descripcion > 0) {
    $observaciones[] = 'Se identificaron solicitudes sin descripción clara, lo que dificulta la clasificación y el registro técnico.';
}
if (empty($observaciones)) {
    $observaciones[] = 'No se reportan observaciones críticas adicionales en el período seleccionado.';
}

$recomendaciones[] = 'Adquirir repuestos de impresión y consumibles si las incidencias de impresoras y tóner son recurrentes.';
$recomendaciones[] = 'Mantener un stock básico de pilas, fusores y kits de mantenimiento para disminuir tiempos de respuesta en hardware.';
$recomendaciones[] = 'Promover acciones de soporte de primer nivel para reducir la carga de incidencias básicas de software y configuración.';
if (count($pendientes) > 0) {
    $recomendaciones[] = 'Reforzar el cierre técnico de tickets pendientes y documentar la solución para evitar reaperturas.';
}
if (count($duplicados) > 0) {
    $recomendaciones[] = 'Estandarizar la gestión de tickets repetidos y mejorar el seguimiento de solicitudes duplicadas.';
}

$user_name = $_SESSION['nombre'] ?? 'Especialista';
$user_rol = $_SESSION['rol'] ?? '';

if (stripos($user_rol, 'coordin') !== false || stripos($user_rol, 'gerente') !== false || stripos($user_rol, 'jefe') !== false) {
    $user_name = 'ING. LUIS A. RAMÍREZ';
    $usuario_titulo = 'GERENTE OFICINA DE TECNOLOGÍA DE LA INFORMACIÓN Y LA COMUNICACIÓN';
} else {
    $usuario_titulo = $user_rol;
}

$user_name = mb_strtoupper(trim($user_name), 'UTF-8');
$total_tickets = (int) ($resumen['total'] ?? 0);
$estadisticas = [
    'total' => $total_tickets,
    'resueltos' => (int) ($resumen['cerrados'] ?? 0),
    'pendientes' => (int) ($resumen['pendientes'] ?? 0),
    'porcentaje_resueltos' => $total_tickets ? round((($resumen['cerrados'] ?? 0) * 100) / $total_tickets, 1) : 0,
    'porcentaje_pendientes' => $total_tickets ? round((($resumen['pendientes'] ?? 0) * 100) / $total_tickets, 1) : 0,
    'tickets_duplicados' => count($duplicados),
    'tickets_sin_descripcion' => $tickets_sin_descripcion,
    'especialistas_activos' => count($control_personal),
    'departamentos_activos' => count($departamentos)
];

function obtenerMesesEnEspanol($fechaDesde, $fechaHasta) {
    $meses = [
        '01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril','05' => 'Mayo','06' => 'Junio',
        '07' => 'Julio','08' => 'Agosto','09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'
    ];
    $desde = new DateTime($fechaDesde);
    $hasta = new DateTime($fechaHasta);

    if ($desde->format('Y') === $hasta->format('Y') && $desde->format('m') === $hasta->format('m')) {
        return $meses[$desde->format('m')] . ' ' . $desde->format('Y');
    }
    return $desde->format('d/m/Y') . ' - ' . $hasta->format('d/m/Y');
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'periodo' => date('d/m/Y', strtotime($desde)) . ' - ' . date('d/m/Y', strtotime($hasta)),
    'periodo_label' => obtenerMesesEnEspanol($desde, $hasta),
    'frecuencia' => $frecuencia,
    'desde' => $desde,
    'hasta' => $hasta,
    'especialista_label' => $especialista_label,
    'estatus_label' => $estatus_label,
    'resumen' => [
        'total' => (int) ($resumen['total'] ?? 0),
        'cerrados' => (int) ($resumen['cerrados'] ?? 0),
        'pendientes' => (int) ($resumen['pendientes'] ?? 0)
    ],
    'estadisticas' => $estadisticas,
    'grafica' => $grafica,
    'especialistas' => $especialistas,
    'control_personal' => $control_personal,
    'departamentos' => $departamentos,
    'categorias' => (function($categoriasMap) {
        $result = [];
        foreach ($categoriasMap as $nombre => $item) {
            $result[] = [
                'categoria' => $nombre,
                'total' => $item['total'],
                'ejemplos' => $item['ejemplos']
            ];
        }
        return $result;
    })($categorias),
    'observaciones' => $observaciones,
    'tickets_repetidos' => $duplicados,
    'tickets_vacios' => [
        'cantidad' => $tickets_sin_descripcion,
        'ids' => $sin_descripcion_ids
    ],
    'recomendaciones' => $recomendaciones,
    'usuario_nombre' => $user_name,
    'usuario_rol' => $user_rol,
    'usuario_titulo' => $usuario_titulo
], JSON_UNESCAPED_UNICODE);
