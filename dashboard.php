<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Consultamos la tabla solicitud (usando los nombres que vimos en tu Workbench)
$query = "SELECT s.id, s.fechainicial, sol.nombre AS solicitante, s.estatus 
          FROM solicitud s 
          INNER JOIN solicitante sol ON s.ci = sol.ci 
          ORDER BY s.fechainicial DESC";
$stmt = $db->prepare($query);
$stmt->execute();

// Contamos cuántos incidentes hay para la tarjeta de arriba
$total_incidentes = $stmt->rowCount();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Incidentes - FONDAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .navbar { background-color: #2c3e50; }
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">FONDAS - Gestión de Incidentes</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-center p-3">
                <h3>Total Incidentes</h3>
                <p class="display-4 text-primary"><?php echo $total_incidentes; ?></p>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card p-4">
                <h4>Bienvenida al Sistema</h4>
                <p>Desde aquí podrás gestionar los reportes de incidencias, técnicos y especialistas.</p>
                <button class="btn btn-success btn-lg">Crear Nuevo Ticket</button>
            </div>
        </div>
    </div>

    <div class="card p-4 mt-2">
        <h4>Listado de Solicitudes Recientes</h4>
        <table class="table table-hover mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Nro Ticket</th>
                    <th>Fecha</th>
                    <th>Solicitante</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['fechainicial']; ?></td>
        <td><?php echo $row['solicitante']; ?></td>
        <td>
            <span class="badge <?php echo ($row['estatus'] == 'Abierto') ? 'bg-warning' : 'bg-success'; ?>">
                <?php echo $row['estatus']; ?>
            </span>
        </td>
        <td>
    <a href="asignar_ticket.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white">
        Asignar Técnico
    </a>
</td>
    </tr>
    <?php endwhile; ?>
</tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>