<?php
/**
 * Scripts e estilos do plugin
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Função para garantir que os assets sejam enfileirados
 */
function gs_enfileirar_assets() {
    wp_enqueue_style('glossario-negocios-style');
    wp_enqueue_script('glossario-negocios-script');
}

/**
 * Registrar e enfileirar scripts e estilos
 */
function gs_enqueue_scripts() {
    $versao = defined('GS_VERSAO') ? GS_VERSAO : '2.3.17';
    $min    = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

    $plugin_dir = plugin_dir_path(dirname(__FILE__));
    $plugin_url = plugin_dir_url(dirname(__FILE__));

    // Determina o arquivo CSS com fallback seguro caso o minificado não exista
    $css_rel  = 'assets/css/glossario-negocios' . $min . '.css';
    $css_file = file_exists($plugin_dir . $css_rel) ? $css_rel : 'assets/css/glossario-negocios.css';

    // Determina o arquivo JS com fallback seguro caso o minificado não exista
    $js_rel  = 'assets/js/glossario-negocios' . $min . '.js';
    $js_file = file_exists($plugin_dir . $js_rel) ? $js_rel : 'assets/js/glossario-negocios.js';

    // Registrar estilos
    wp_register_style(
        'glossario-negocios-style',
        $plugin_url . $css_file,
        array(),
        $versao
    );

    // Registrar scripts
    wp_register_script(
        'glossario-negocios-script',
        $plugin_url . $js_file,
        array('jquery'),
        $versao,
        true
    );

    // Localizar script para AJAX
    wp_localize_script(
        'glossario-negocios-script',
        'gs_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gs_ajax_nonce')
        )
    );

    // Enfileirar automaticamente se os shortcodes estiverem no conteúdo
    global $post;
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'glossario_lista') ||
        has_shortcode($post->post_content, 'glossario_cadastro') ||
        has_shortcode($post->post_content, 'glossario_dashboard')
    )) {
        gs_enfileirar_assets();
    }
}
add_action('wp_enqueue_scripts', 'gs_enqueue_scripts');

/**
 * Enfileirar scripts e estilos nas páginas administrativas do glossário de negócios
 */
function gs_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'glossario') !== false) {
        gs_enqueue_scripts();
        gs_enfileirar_assets();
    }
}
add_action('admin_enqueue_scripts', 'gs_admin_enqueue_scripts');

