<?php
/**
 * Funções para importação e exportação de termos via CSV
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe para gerenciar importação e exportação de termos via CSV
 */
class GS_CSV_Handler {

    // Tamanho máximo do arquivo (5MB)
    const MAX_FILE_SIZE = 5242880;

    // Tipos MIME permitidos
    const ALLOWED_MIME_TYPES = array(
        'text/csv',
        'text/plain',
        'application/csv',
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.msexcel',
    );

    /**
     * Inicializa hooks e ações
     */
    public static function init() {
        // Registrar AJAX para importação
        add_action('wp_ajax_gs_importar_csv', array(__CLASS__, 'ajax_importar_csv'));

        // Registrar AJAX para exportação
        add_action('wp_ajax_gs_exportar_csv', array(__CLASS__, 'ajax_exportar_csv'));
        add_action('wp_ajax_nopriv_gs_exportar_csv', array(__CLASS__, 'ajax_exportar_csv'));

        // Registrar AJAX para download do modelo CSV
        add_action('wp_ajax_gs_download_modelo_csv', array(__CLASS__, 'ajax_download_modelo_csv'));
    }

    /**
     * Callback para importação via AJAX
     */
    public static function ajax_importar_csv() {
        try {
            // Verificar nonce para segurança
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_csv_nonce')) {
                wp_send_json_error(__('Erro de segurança. Tente novamente.', 'glossario-negocios'));
                die();
            }

            // Verificar permissões
            if (!current_user_can('publish_posts')) {
                wp_send_json_error(__('Você não tem permissão para importar termos.', 'glossario-negocios'));
                die();
            }

            // Verificar se o arquivo foi enviado
            if (!isset($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
                wp_send_json_error(__('Nenhum arquivo foi enviado.', 'glossario-negocios'));
                die();
            }

            $file = $_FILES['csv_file'];

            // Validar tamanho do arquivo
            if ($file['size'] > self::MAX_FILE_SIZE) {
                wp_send_json_error(__('O arquivo é muito grande. O tamanho máximo permitido é 5MB.', 'glossario-negocios'));
                die();
            }

            // Validar tipo MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);

            if (!in_array($mime_type, self::ALLOWED_MIME_TYPES)) {
                wp_send_json_error(__('Tipo de arquivo inválido. Apenas arquivos CSV são permitidos.', 'glossario-negocios'));
                die();
            }

            // Processar importação
            $resultado = self::processar_importacao($file['tmp_name']);

            if (is_wp_error($resultado)) {
                wp_send_json_error($resultado->get_error_message());
            } else {
                $mensagem = sprintf(
                    /* translators: %d: quantidade de termos importados */
                    __('Importação concluída! %d termo(s) importado(s).', 'glossario-negocios'),
                    $resultado['importados']
                );
                if (!empty($resultado['ignorados'])) {
                    $mensagem .= sprintf(
                        /* translators: %d: quantidade de termos ignorados */
                        __(' %d termo(s) ignorado(s).', 'glossario-negocios'),
                        $resultado['ignorados']
                    );
                }

                wp_send_json_success(array(
                    'message'            => $mensagem,
                    'stats'              => $resultado,
                    'detalhes_ignorados' => !empty($resultado['detalhes_ignorados']) ? $resultado['detalhes_ignorados'] : array()
                ));
            }

            die();
        } catch (Throwable $e) {
            gs_log_and_respond_error($e, __('Importação CSV', 'glossario-negocios'));
        }
    }

    /**
     * Processa a importação do arquivo CSV
     *
     * @param string $file_path Caminho do arquivo temporário
     * @return array|WP_Error Resultado da importação ou objeto de erro
     */
    private static function processar_importacao($file_path) {
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return new WP_Error('arquivo_invalido', __('Não foi possível ler o arquivo.', 'glossario-negocios'));
        }

        // Detectar e pular BOM UTF-8 se presente
        $bom = fread($handle, 3);
        $tem_bom = ($bom === "\xEF\xBB\xBF");
        if (!$tem_bom) {
            rewind($handle);
        }

        // Ler primeira linha para detectar separador
        $primeira_linha = fgets($handle);
        if ($primeira_linha === false) {
            fclose($handle);
            return new WP_Error('arquivo_invalido', __('Arquivo CSV vazio.', 'glossario-negocios'));
        }

        $separador = ',';
        if (substr_count($primeira_linha, ';') > substr_count($primeira_linha, ',')) {
            $separador = ';';
        }

        // Voltar para logo após o BOM (ou início se não houver BOM)
        if ($tem_bom) {
            fseek($handle, 3);
        } else {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, $separador);

        // Limpar campos do cabeçalho (remover espaços, aspas, BOM)
        $header = array_map(function($col) {
            $col = trim($col);
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            $col = trim($col, '"\'');
            return strtolower($col);
        }, $header);

        // Validar cabeçalho (colunas obrigatórias)
        $colunas_obrigatorias = array('termo', 'definicao', 'categoria');
        $colunas_faltantes = array_diff($colunas_obrigatorias, $header);

        if (!empty($colunas_faltantes)) {
            fclose($handle);
            return new WP_Error(
                'cabecalho_invalido',
                __('O arquivo CSV não contém todas as colunas necessárias. Faltando: ', 'glossario-negocios') . implode(', ', $colunas_faltantes)
            );
        }

        // Mapear índices das colunas (referencia é opcional)
        $colunas_esperadas = array('termo', 'definicao', 'categoria', 'referencia');
        $indices = array();
        foreach ($colunas_esperadas as $coluna) {
            $indice = array_search(strtolower($coluna), array_map('strtolower', $header));
            $indices[$coluna] = $indice !== false ? $indice : null;
        }

        // Estatísticas
        $stats = array(
            'total'              => 0,
            'importados'         => 0,
            'ignorados'          => 0,
            'erros'              => 0,
            'detalhes_ignorados' => array(),
        );

        // Processar linhas
        $linha = 2; // Começar da linha 2 (após o cabeçalho)
        while (($data = fgetcsv($handle, 0, $separador)) !== false) {
            $stats['total']++;

            $termo = !empty($data[$indices['termo']]) ? sanitize_text_field($data[$indices['termo']]) : '';
            $definicao = !empty($data[$indices['definicao']]) ? wp_kses_post($data[$indices['definicao']]) : '';
            $categoria_nome = !empty($data[$indices['categoria']]) ? sanitize_text_field($data[$indices['categoria']]) : '';
            $referencia = !empty($data[$indices['referencia']]) ? esc_url_raw($data[$indices['referencia']]) : '';

            // Validar dados mínimos obrigatórios
            if (empty($termo) || empty($definicao)) {
                $stats['ignorados']++;
                $stats['detalhes_ignorados'][] = sprintf(
                    /* translators: %d: número da linha */
                    __('Linha %d: Termo ou definição não preenchidos.', 'glossario-negocios'),
                    $linha
                );
                $linha++;
                continue;
            }

            // Validar categoria obrigatória
            if (empty($categoria_nome)) {
                $stats['ignorados']++;
                $stats['detalhes_ignorados'][] = sprintf(
                    /* translators: 1: número da linha, 2: nome do termo */
                    __('Linha %1$d: O termo "%2$s" foi ignorado pois a categoria é obrigatória.', 'glossario-negocios'),
                    $linha,
                    esc_html($termo)
                );
                $linha++;
                continue;
            }

            // Converter categoria para minúsculas com suporte UTF-8
            $categoria_nome = mb_strtolower($categoria_nome, 'UTF-8');

            // Obter ou criar a categoria
            $categoria_id = null;
            $categorias_existentes = get_terms(array(
                'taxonomy'   => 'categoria_glossario',
                'name'       => $categoria_nome,
                'hide_empty' => false,
            ));

            if (!empty($categorias_existentes) && !is_wp_error($categorias_existentes)) {
                $categoria_id = $categorias_existentes[0]->term_id;
            } else {
                $nova_cat = wp_insert_term($categoria_nome, 'categoria_glossario');
                if (!is_wp_error($nova_cat)) {
                    $categoria_id = is_array($nova_cat) ? $nova_cat['term_id'] : (int) $nova_cat;
                }
            }

            if (!$categoria_id) {
                $stats['erros']++;
                $stats['detalhes_ignorados'][] = sprintf(
                    /* translators: 1: número da linha, 2: nome da categoria, 3: nome do termo */
                    __('Linha %1$d: Erro ao registrar a categoria "%2$s" para o termo "%3$s".', 'glossario-negocios'),
                    $linha,
                    esc_html($categoria_nome),
                    esc_html($termo)
                );
                $linha++;
                continue;
            }

            // Verificar se o termo já existe especificamente nesta categoria
            if (function_exists('gs_termo_existe_na_categoria') && gs_termo_existe_na_categoria($termo, $categoria_id)) {
                $stats['ignorados']++;
                $stats['detalhes_ignorados'][] = sprintf(
                    /* translators: 1: número da linha, 2: nome do termo, 3: nome da categoria */
                    __('Linha %1$d: O termo "%2$s" já está cadastrado na categoria "%3$s".', 'glossario-negocios'),
                    $linha,
                    esc_html($termo),
                    esc_html($categoria_nome)
                );
                $linha++;
                continue;
            }

            // Criar post
            $post_data = array(
                'post_title'   => $termo,
                'post_content' => $definicao,
                'post_status'  => 'publish',
                'post_type'    => 'glossario',
            );

            // Inserir post
            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $stats['erros']++;
                $linha++;
                continue;
            }

            // Vincular à categoria
            wp_set_object_terms($post_id, (int) $categoria_id, 'categoria_glossario');

            // Salvar referência se fornecida
            if (!empty($referencia)) {
                update_post_meta($post_id, 'referencia', $referencia);
            }

            $stats['importados']++;
            $linha++;
        }

        fclose($handle);
        return $stats;
    }

    /**
     * Callback para exportação via AJAX
     */
    public static function ajax_exportar_csv() {
        try {
            // Verificar nonce para segurança (aceita GET ou POST)
            $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
            if (empty($nonce) || !wp_verify_nonce($nonce, 'gs_csv_nonce')) {
                wp_send_json_error(__('Erro de segurança. Tente novamente.', 'glossario-negocios'));
                die();
            }

            // Gerar arquivo CSV
            self::gerar_arquivo_csv();

            die();
        } catch (Throwable $e) {
            gs_log_and_respond_error($e, __('Exportação CSV', 'glossario-negocios'));
        }
    }

    /**
     * Gera e força o download do arquivo CSV com todos os termos
     */
    private static function gerar_arquivo_csv() {
        // Configurar cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="glossario-negocios-' . date('Y-m-d') . '.csv"');

        // Criar output para PHP
        $output = fopen('php://output', 'w');

        // Adicionar BOM para UTF-8
        fputs($output, "\xEF\xBB\xBF");

        // Escrever cabeçalho
        fputcsv($output, array('Termo', 'Definicao', 'Categoria', 'Referencia'));

        // Buscar todos os termos
        $args = array(
            'post_type' => 'glossario',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $termos = new WP_Query($args);

        if ($termos->have_posts()) {
            while ($termos->have_posts()) {
                $termos->the_post();

                // Obter categorias
                $categorias_termo = get_the_terms(get_the_ID(), 'categoria_glossario');
                $categorias_nomes = array();

                if (!empty($categorias_termo) && !is_wp_error($categorias_termo)) {
                    foreach ($categorias_termo as $cat) {
                        $categorias_nomes[] = $cat->name;
                    }
                }

                $categoria_str = implode(', ', $categorias_nomes);

                // Obter referência
                $referencia = get_post_meta(get_the_ID(), 'referencia', true);

                // Escrever linha
                fputcsv($output, array(
                    get_the_title(),
                    wp_strip_all_tags(get_the_content()),
                    $categoria_str,
                    $referencia
                ));
            }
            wp_reset_postdata();
        }

        fclose($output);
        exit;
    }

    /**
     * Callback para download do modelo CSV via AJAX
     */
    public static function ajax_download_modelo_csv() {
        try {
            // Verificar nonce para segurança
            if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'gs_csv_nonce')) {
                wp_die(esc_html__('Erro de segurança.', 'glossario-negocios'));
            }

            // Configurar cabeçalhos para download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="modelo-glossario-de-negocios.csv"');

            // Criar output para PHP
            $output = fopen('php://output', 'w');

            // Adicionar BOM para UTF-8
            fputs($output, "\xEF\xBB\xBF");

            // Escrever cabeçalho
            fputcsv($output, array('termo', 'definicao', 'categoria', 'referencia'));

            // Escrever exemplos
            fputcsv($output, array('api', 'Interface de Programação de Aplicações', 'tecnologia', 'https://exemplo.com/api'));
            fputcsv($output, array('html', 'Linguagem de Marcação para Web', 'web', 'https://exemplo.com/html'));

            fclose($output);
            exit;
        } catch (Throwable $e) {
            error_log('Erro ao gerar modelo CSV: ' . $e->getMessage());
            wp_die(esc_html__('Erro ao gerar modelo CSV.', 'glossario-negocios'));
        }
    }
}

// Inicializar a classe
GS_CSV_Handler::init();
