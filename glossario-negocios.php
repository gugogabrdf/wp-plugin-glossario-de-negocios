<?php
/**
 * Plugin Name: Glossário de Negócios
 * Description: Plugin para gerenciar um glossário de negócios com shortcodes para exibição [glossario_lista], cadastro/gestão [glossario_cadastro] e métricas [glossario_dashboard].
 * Shortcodes: [glossario_lista] exibe a lista com filtros e paginação. [glossario_cadastro] exibe abas de cadastro, categorias, termos e dashboard. [glossario_dashboard] exibe o painel de estatísticas em tempo real.
 * Desativar: Adicione define('GS_GLOSSARIO_DISABLED', true); ao wp-config.php para desabilitar o plugin.
 * Version: 2.3.17
 * Author: Gustavo Silva
 * Collaborator IA: Antigravity (Google DeepMind Team) & MiMoCode (Xiaomi MiMo Team)
 * Text Domain: glossario-negocios
 * Domain Path: /languages
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Versão do plugin
if (!defined('GS_VERSAO')) {
    define('GS_VERSAO', '2.3.17');
}

// Constante para desabilitar o plugin em caso de emergência
// Adicione ao wp-config.php: define('GS_GLOSSARIO_DISABLED', true);
if (defined('GS_GLOSSARIO_DISABLED') && GS_GLOSSARIO_DISABLED === true) {
    return;
}

/**
 * Carrega a internacionalização do plugin
 */
function gs_carregar_textdomain() {
    load_plugin_textdomain(
        'glossario-negocios',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'gs_carregar_textdomain', 1);

/**
 * Registra o tipo de post personalizado "Glossário de Negócios"
 */
function gs_registrar_post_type() {
    try {
        $labels = array(
            'name'               => __('Glossário de Negócios', 'glossario-negocios'),
            'singular_name'      => __('Termo', 'glossario-negocios'),
            'menu_name'          => __('Glossário de Negócios', 'glossario-negocios'),
            'add_new'            => __('Adicionar Novo', 'glossario-negocios'),
            'add_new_item'       => __('Adicionar Novo Termo', 'glossario-negocios'),
            'edit_item'          => __('Editar Termo', 'glossario-negocios'),
            'new_item'           => __('Novo Termo', 'glossario-negocios'),
            'view_item'          => __('Ver Termo', 'glossario-negocios'),
            'search_items'       => __('Buscar Termos', 'glossario-negocios'),
            'not_found'          => __('Nenhum termo encontrado', 'glossario-negocios'),
            'not_found_in_trash' => __('Nenhum termo encontrado na lixeira', 'glossario-negocios'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'glossario'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-book-alt',
            'supports'            => array('title', 'editor'),
        );

        register_post_type('glossario', $args);
    } catch (Throwable $e) {
        error_log('Erro ao registrar post type glossario: ' . $e->getMessage());
    }
}
add_action('init', 'gs_registrar_post_type');

/**
 * Registra a taxonomia "Categoria do Glossário de Negócios"
 */
function gs_registrar_taxonomia() {
    try {
        $labels = array(
            'name'              => __('Categorias do Glossário de Negócios', 'glossario-negocios'),
            'singular_name'     => __('Categoria', 'glossario-negocios'),
            'search_items'      => __('Buscar Categorias', 'glossario-negocios'),
            'all_items'         => __('Todas as Categorias', 'glossario-negocios'),
            'parent_item'       => __('Categoria Pai', 'glossario-negocios'),
            'parent_item_colon' => __('Categoria Pai:', 'glossario-negocios'),
            'edit_item'         => __('Editar Categoria', 'glossario-negocios'),
            'update_item'       => __('Atualizar Categoria', 'glossario-negocios'),
            'add_new_item'      => __('Adicionar Nova Categoria', 'glossario-negocios'),
            'new_item_name'     => __('Nova Categoria', 'glossario-negocios'),
            'menu_name'         => __('Categorias', 'glossario-negocios'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'categoria-glossario'),
        );

        register_taxonomy('categoria_glossario', array('glossario'), $args);
    } catch (Throwable $e) {
        error_log('Erro ao registrar taxonomia categoria_glossario: ' . $e->getMessage());
    }
}
add_action('init', 'gs_registrar_taxonomia');

/**
 * Garante que termos da taxonomia categoria_glossario sejam sempre criados em minúsculo
 */
function gs_forcar_categoria_minuscula($term, $taxonomy) {
    if ($taxonomy === 'categoria_glossario' && is_string($term)) {
        return mb_strtolower(trim($term), 'UTF-8');
    }
    return $term;
}
add_filter('pre_insert_term', 'gs_forcar_categoria_minuscula', 10, 2);

/**
 * Garante que termos da taxonomia categoria_glossario sejam sempre atualizados em minúsculo
 */
function gs_forcar_categoria_update_minuscula($data, $term_id, $taxonomy, $args) {
    if ($taxonomy === 'categoria_glossario' && isset($data['name']) && is_string($data['name'])) {
        $data['name'] = mb_strtolower(trim($data['name']), 'UTF-8');
    }
    return $data;
}
add_filter('wp_update_term_data', 'gs_forcar_categoria_update_minuscula', 10, 4);

/**
 * Adiciona meta box para campo de referência
 */
function gs_adicionar_meta_boxes() {
    try {
        add_meta_box(
            'gs_referencia',
            __('Referência', 'glossario-negocios'),
            'gs_meta_box_referencia',
            'glossario',
            'normal',
            'default'
        );
    } catch (Throwable $e) {
        error_log('Erro ao adicionar meta box: ' . $e->getMessage());
    }
}
add_action('add_meta_boxes', 'gs_adicionar_meta_boxes');

/**
 * Callback para o meta box de referência
 */
function gs_meta_box_referencia($post) {
    try {
        // Adicionar nonce para verificação
        wp_nonce_field('gs_salvar_meta_box', 'gs_meta_box_nonce');

        // Obter valor atual
        $valor = get_post_meta($post->ID, 'referencia', true);

        // Campo para URL
        echo '<label for="gs_referencia_field">' . esc_html__('URL de Referência:', 'glossario-negocios') . '</label>';
        echo '<input type="url" id="gs_referencia_field" name="gs_referencia_field" value="' . esc_attr($valor) . '" style="width:100%" />';
        echo '<p class="description">' . esc_html__('Informe a URL de referência para este termo (opcional).', 'glossario-negocios') . '</p>';
    } catch (Throwable $e) {
        error_log('Erro ao renderizar meta box: ' . $e->getMessage());
        echo '<p>' . esc_html__('Erro ao carregar campos.', 'glossario-negocios') . '</p>';
    }
}

/**
 * Salva os dados do meta box
 */
function gs_salvar_meta_box($post_id) {
    try {
        // Verificar se é um salvamento automático
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar nonce
        if (!isset($_POST['gs_meta_box_nonce']) || !wp_verify_nonce($_POST['gs_meta_box_nonce'], 'gs_salvar_meta_box')) {
            return;
        }

        // Verificar permissões
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Salvar dados
        if (isset($_POST['gs_referencia_field'])) {
            $valor = esc_url_raw($_POST['gs_referencia_field']);
            update_post_meta($post_id, 'referencia', $valor);
        }
    } catch (Throwable $e) {
        error_log('Erro ao salvar meta box: ' . $e->getMessage());
    }
}
add_action('save_post', 'gs_salvar_meta_box');

/**
 * Inclui arquivos necessários
 */
function gs_incluir_arquivos() {
    try {
        $plugin_path = plugin_dir_path(__FILE__);
        $arquivos = array(
            'includes/shortcode-lista.php',
            'includes/shortcode-cadastro.php',
            'includes/csv-handler.php',
            'includes/scripts.php',
            'includes/admin-detalhes.php',
            'includes/dashboard.php',
        );

        foreach ($arquivos as $arquivo) {
            $caminho_completo = $plugin_path . $arquivo;
            if (file_exists($caminho_completo)) {
                require_once $caminho_completo;
            } else {
                error_log('Arquivo do plugin não encontrado: ' . $arquivo);
            }
        }
    } catch (Throwable $e) {
        error_log('Erro ao incluir arquivos do plugin: ' . $e->getMessage());
    }
}
add_action('plugins_loaded', 'gs_incluir_arquivos');

/**
 * Helper para logar erro completo e retornar resposta JSON
 * Admin recebe detalhes (file, line, trace) — outros recebem mensagem genérica
 */
function gs_log_and_respond_error($e, $context = '') {
    $log_msg = sprintf('[GS] %s: %s in %s on line %d', $context, $e->getMessage(), $e->getFile(), $e->getLine());
    error_log($log_msg);
    error_log('[GS] Trace: ' . $e->getTraceAsString());

    if (current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
            'context' => $context,
        ));
    } else {
        wp_send_json_error(esc_html__('Erro interno. Verifique wp-content/debug.log para detalhes.', 'glossario-negocios'));
    }
    die();
}

/**
 * Verifica se um termo com o mesmo título já existe em uma categoria específica
 * Comparação insensível a maiúsculas/minúsculas e espaços extras
 *
 * @param string $termo_titulo Título do termo
 * @param int $categoria_id ID da categoria (termo_id)
 * @param int $excluir_post_id ID do post a desconsiderar (em edições)
 * @return bool True se já existir, False se livre
 */
function gs_termo_existe_na_categoria($termo_titulo, $categoria_id, $excluir_post_id = 0) {
    try {
        $termo_normalizado = trim(mb_strtolower($termo_titulo, 'UTF-8'));
        $categoria_id = (int) $categoria_id;

        if (empty($termo_normalizado) || empty($categoria_id)) {
            return false;
        }

        $args = array(
            'post_type'              => 'glossario',
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'tax_query'              => array(
                array(
                    'taxonomy'         => 'categoria_glossario',
                    'field'            => 'term_id',
                    'terms'            => $categoria_id,
                    'include_children' => false,
                ),
            ),
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if (!empty($excluir_post_id)) {
            $args['post__not_in'] = array((int) $excluir_post_id);
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $titulo_existente = trim(mb_strtolower(get_the_title($post_id), 'UTF-8'));
                if ($titulo_existente === $termo_normalizado) {
                    return true;
                }
            }
        }

        return false;
    } catch (Throwable $e) {
        error_log('Erro ao verificar duplicidade de termo na categoria: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ações na ativação do plugin
 */
function gs_ativar_plugin() {
    try {
        // Criar diretórios para assets se não existirem
        $upload_dir = wp_upload_dir();
        $glossario_dir = $upload_dir['basedir'] . '/glossario-csv';

        if (!file_exists($glossario_dir)) {
            wp_mkdir_p($glossario_dir);

            // Criar arquivo .htaccess para proteger o diretório
            $htaccess_content = "# Proteger diretório\nDeny from all";
            file_put_contents($glossario_dir . '/.htaccess', $htaccess_content);
        }
    } catch (Throwable $e) {
        error_log('Erro ao ativar plugin: ' . $e->getMessage());
    }
}
register_activation_hook(__FILE__, 'gs_ativar_plugin');
