<?php
/**
 * FONDAS - Helper de Metadatos (Versión Minimalista)
 * Proporciona soporte para los meta tags básicos y el nombre del sistema.
 */

if (!function_exists('render_metadata')) {
    function render_metadata($title) {
        echo '<!-- Metadatos Básicos -->';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . $title . ' - FONDAS</title>';
        echo '<link rel="icon" href="img/logo3.png" type="image/png">';
    }
}

// Definición de constantes básicas si no existen
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SISTEMA FONDAS');
}
