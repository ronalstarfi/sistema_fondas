<?php
/**
 * config/app_config.php
 * Archivo de configuración global para el Sistema FONDAS.
 * Centraliza metadatos y constantes del sistema.
 */

define('APP_NAME', 'FONDAS - Sistema de Gestión');
define('APP_VERSION', '2.1.0');
define('APP_AUTHOR', 'Equipo de Desarrollo STARFI');
define('INSTITUTION_NAME', 'Fondo para el Desarrollo Agrario Socialista');
define('APP_DESCRIPTION', 'Sistema Interno de Gestión de Tickets y Soporte Técnico - FONDAS');

// Rutas base (opcional, para facilitar enlaces)
define('BASE_URL', '/sistema_fondas/');

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Metadatos por defecto para SEO/Social
$default_metadata = [
    'charset' => 'UTF-8',
    'viewport' => 'width=device-width, initial-scale=1.0',
    'robots' => 'noindex, nofollow', // Sistema interno
    'google' => 'notranslate'
];
?>
