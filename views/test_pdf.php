<?php
// Script de prueba para el endpoint de PDF
echo "Probando endpoint generate_reporte_pdf.php...\n";

// Simular parámetros GET
$_GET['frecuencia'] = 'mensual';
$_GET['desde'] = date('Y-m-01');
$_GET['hasta'] = date('Y-m-t');

// Ejecutar el endpoint directamente
require_once("generate_reporte_pdf.php");
?>