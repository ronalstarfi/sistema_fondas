<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'especialista';
$_SESSION['nombre'] = 'Prueba';
$_GET['frecuencia'] = 'mensual';
$_GET['desde'] = date('Y-m-01');
$_GET['hasta'] = date('Y-m-t');
require 'views/generate_reporte_pdf.php';
?>