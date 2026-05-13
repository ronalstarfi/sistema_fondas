<?php
/**
 * includes/metadata_helper.php
 * Generador de etiquetas meta dinámicas.
 */

require_once __DIR__ . '/../config/app_config.php';

/**
 * Genera el bloque de metadatos para el <head> de la página.
 * 
 * @param string $page_title Título específico de la página.
 * @param array $custom_meta Metadatos adicionales o sobreescrituras.
 * @return string HTML con las etiquetas meta.
 */
function render_metadata($page_title = "", $custom_meta = []) {
    global $default_metadata;
    
    // Título unificado
    $full_title = $page_title ? "$page_title | " . APP_NAME : APP_NAME;
    
    // Mezclar metadatos por defecto con los personalizados
    $meta = array_merge($default_metadata, $custom_meta);
    
    $html = "\n    <!-- Metadatos Generados por MetaHandler -->\n";
    $html .= "    <meta charset=\"" . $meta['charset'] . "\">\n";
    $html .= "    <meta name=\"viewport\" content=\"" . $meta['viewport'] . "\">\n";
    $html .= "    <meta name=\"description\" content=\"" . (isset($meta['description']) ? $meta['description'] : APP_DESCRIPTION) . "\">\n";
    $html .= "    <meta name=\"author\" content=\"" . APP_AUTHOR . "\">\n";
    $html .= "    <meta name=\"google\" content=\"" . $meta['google'] . "\">\n";
    $html .= "    <meta name=\"robots\" content=\"" . $meta['robots'] . "\">\n";
    
    // Open Graph (para previsualizaciones si fuera necesario)
    $html .= "    <meta property=\"og:title\" content=\"$full_title\">\n";
    $html .= "    <meta property=\"og:description\" content=\"" . APP_DESCRIPTION . "\">\n";
    $html .= "    <meta property=\"og:site_name\" content=\"" . INSTITUTION_NAME . "\">\n";
    
    $html .= "    <title>" . htmlspecialchars($full_title) . "</title>\n";
    
    // Favicon
    $html .= "    <link rel=\"icon\" type=\"image/png\" href=\"img/favicon.png\">\n";
    
    return $html;
}
?>
