<?php
/**
 * Shortcode para cadastro de termos do glossário de negócios
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o shortcode [glossario_cadastro]
 */
function gs_registrar_shortcode_cadastro() {
    add_shortcode('glossario_cadastro', 'gs_shortcode_cadastro');

    // Registrar AJAX para salvar termo
    add_action('wp_ajax_gs_salvar_termo', 'gs_ajax_salvar_termo');
    add_action('wp_ajax_nopriv_gs_salvar_termo', 'gs_ajax_salvar_termo');

    // Registrar AJAX para gerenciar categorias
    add_action('wp_ajax_gs_listar_categorias', 'gs_ajax_listar_categorias');
    add_action('wp_ajax_gs_salvar_categoria', 'gs_ajax_salvar_categoria');
    add_action('wp_ajax_gs_editar_categoria', 'gs_ajax_editar_categoria');
    add_action('wp_ajax_gs_excluir_categoria', 'gs_ajax_excluir_categoria');

    // Registrar AJAX para gerenciar termos
    add_action('wp_ajax_gs_listar_termos_gerenciar', 'gs_ajax_listar_termos_gerenciar');
    add_action('wp_ajax_gs_editar_termo', 'gs_ajax_editar_termo');
    add_action('wp_ajax_gs_excluir_termo', 'gs_ajax_excluir_termo');
}
add_action('init', 'gs_registrar_shortcode_cadastro');

/**
 * Callback para o shortcode [glossario_cadastro]
 */
function gs_shortcode_cadastro($atts) {
    try {
        // Extrair atributos
        $atts = shortcode_atts(array(
            'titulo' => 'Cadastrar Novo Termo',
            'mostrar_importacao' => 'sim', // sim ou nao
            'mostrar_exportacao' => 'sim', // sim ou nao
        ), $atts, 'glossario_cadastro');

        // Converter atributos para booleanos
        $mostrar_importacao = ($atts['mostrar_importacao'] === 'sim');
        $mostrar_exportacao = ($atts['mostrar_exportacao'] === 'sim');

        // Obter categorias
        $categorias = get_terms(array(
            'taxonomy' => 'categoria_glossario',
            'hide_empty' => false,
        ));

        if (function_exists('gs_enfileirar_assets')) {
            gs_enfileirar_assets();
        }

        // Iniciar buffer de saída
        ob_start();

        // Verificar se o usuário pode publicar posts
        if (!current_user_can('publish_posts') && !current_user_can('publish_pages')) {
            return '<p class="gs-erro">' . esc_html__('Você não tem permissão para adicionar termos ao glossário de negócios.', 'glossario-negocios') . '</p>';
        }

        ?>

        <div class="gs-form-container">
            <div class="gs-form-header">
                <h2><?php echo esc_html($atts['titulo']); ?></h2>
                <span class="gs-form-subtitulo"><?php echo esc_html__('Gerenciamento completo de termos, categorias e dados do glossário de negócios', 'glossario-negocios'); ?></span>
            </div>

            <!-- Mensagem de resposta (inicialmente oculta) -->
            <div id="gs-mensagem" class="gs-mensagem" style="display: none;"></div>

            <!-- Abas de navegação segmentadas -->
            <div class="gs-tabs-nav" role="tablist" aria-label="<?php echo esc_attr__('Navegação do formulário', 'glossario-negocios'); ?>">
                <button type="button" role="tab" id="gs-tab-cadastro" class="gs-tab active" data-tab="gs-aba-cadastro" aria-selected="true" aria-controls="gs-aba-cadastro" tabindex="0"><?php echo esc_html__('Cadastro Manual', 'glossario-negocios'); ?></button>
                <button type="button" role="tab" id="gs-tab-categorias" class="gs-tab" data-tab="gs-aba-categorias" aria-selected="false" aria-controls="gs-aba-categorias" tabindex="-1"><?php echo esc_html__('Categorias', 'glossario-negocios'); ?></button>
                <button type="button" role="tab" id="gs-tab-termos" class="gs-tab" data-tab="gs-aba-termos" aria-selected="false" aria-controls="gs-aba-termos" tabindex="-1"><?php echo esc_html__('Gerenciar Termos', 'glossario-negocios'); ?></button>
                <?php if ($mostrar_importacao): ?>
                <button type="button" role="tab" id="gs-tab-importacao" class="gs-tab" data-tab="gs-aba-importacao" aria-selected="false" aria-controls="gs-aba-importacao" tabindex="-1"><?php echo esc_html__('Importar CSV', 'glossario-negocios'); ?></button>
                <?php endif; ?>
                <?php if ($mostrar_exportacao): ?>
                <button type="button" role="tab" id="gs-tab-exportacao" class="gs-tab" data-tab="gs-aba-exportacao" aria-selected="false" aria-controls="gs-aba-exportacao" tabindex="-1"><?php echo esc_html__('Exportar CSV', 'glossario-negocios'); ?></button>
                <?php endif; ?>
                <button type="button" role="tab" id="gs-tab-dashboard" class="gs-tab" data-tab="gs-aba-dashboard" aria-selected="false" aria-controls="gs-aba-dashboard" tabindex="-1"><?php echo esc_html__('Dashboard', 'glossario-negocios'); ?></button>
                <button type="button" role="tab" id="gs-tab-detalhes" class="gs-tab gs-tab-detalhes" data-tab="gs-aba-detalhes" aria-selected="false" aria-controls="gs-aba-detalhes" tabindex="-1"><?php echo esc_html__('Instruções & Detalhes', 'glossario-negocios'); ?></button>
            </div>

            <!-- Aba: Cadastro Manual -->
            <div id="gs-aba-cadastro" class="gs-tab-content active" role="tabpanel" aria-labelledby="gs-tab-cadastro" tabindex="0">
                <form id="gs-cadastro-form">
                    <!-- Campo: Título -->
                    <div class="gs-form-group">
                        <label for="gs-titulo"><?php echo esc_html__('Termo:', 'glossario-negocios'); ?> <span class="obrigatorio">*</span></label>
                        <input type="text" id="gs-titulo" name="termo" required>
                    </div>

                    <!-- Campo: Definição -->
                    <div class="gs-form-group">
                        <label for="gs-definicao"><?php echo esc_html__('Definição:', 'glossario-negocios'); ?> <span class="obrigatorio">*</span></label>
                        <textarea id="gs-definicao" name="definicao" required></textarea>
                    </div>

                    <!-- Campo: Categorias (múltiplas) -->
                    <div class="gs-form-group">
                        <label for="gs-categorias"><?php echo esc_html__('Categorias:', 'glossario-negocios'); ?> <span class="obrigatorio">*</span></label>
                        <select id="gs-categorias" name="categorias[]" multiple size="4" required>
                            <?php
                            if (!empty($categorias) && !is_wp_error($categorias)) {
                                foreach ($categorias as $categoria) {
                                    echo '<option value="' . esc_attr($categoria->term_id) . '">' . esc_html(mb_strtolower($categoria->name, 'UTF-8')) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <small><?php echo esc_html__('Selecione ao menos uma categoria. Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplas.', 'glossario-negocios'); ?></small>
                    </div>

                    <!-- Botão de envio -->
                    <div class="gs-form-group">
                        <button type="submit" class="gs-form-submit">
                            <?php echo esc_html__('Adicionar Termo', 'glossario-negocios'); ?>
                            <span id="gs-carregando" class="gs-carregando" style="display: none;"></span>
                        </button>
                    </div>

                    <?php wp_nonce_field('gs_cadastro_nonce', 'gs_nonce'); ?>
                </form>
            </div>

            <!-- Aba: Gerenciar Categorias -->
            <div id="gs-aba-categorias" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-categorias" tabindex="0">
                <h3><?php echo esc_html__('Gerenciar Categorias', 'glossario-negocios'); ?></h3>

                <form id="gs-categoria-form" class="gs-form-inline">
                    <div class="gs-form-group">
                        <input type="text" id="gs-nova-categoria" placeholder="<?php echo esc_attr__('Nova categoria...', 'glossario-negocios'); ?>" required>
                    </div>
                    <div class="gs-form-group">
                        <button type="submit" class="gs-form-submit">
                            <?php echo esc_html__('Adicionar', 'glossario-negocios'); ?>
                            <span id="gs-carregando-categoria" class="gs-carregando" style="display: none;"></span>
                        </button>
                    </div>
                    <?php wp_nonce_field('gs_cadastro_nonce', 'gs_nonce_categoria'); ?>
                </form>

                <div id="gs-lista-categorias" class="gs-lista-gerenciar">
                    <!-- Lista de categorias carregada via AJAX -->
                </div>
            </div>

            <!-- Aba: Gerenciar Termos -->
            <div id="gs-aba-termos" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-termos" tabindex="0">
                <h3><?php echo esc_html__('Gerenciar Termos', 'glossario-negocios'); ?></h3>

                <div class="gs-form-group">
                    <input type="text" id="gs-busca-termos" placeholder="<?php echo esc_attr__('Buscar termo para gerenciar...', 'glossario-negocios'); ?>">
                </div>

                <div id="gs-lista-termos-gerenciar" class="gs-lista-gerenciar">
                    <!-- Lista de termos carregada via AJAX -->
                </div>
            </div>

            <?php if ($mostrar_importacao): ?>
            <!-- Aba: Importação CSV -->
            <div id="gs-aba-importacao" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-importacao" tabindex="0">
                <h3><?php echo esc_html__('Importar Termos via CSV', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Faça upload de um arquivo CSV com os termos do glossário de negócios. O arquivo deve conter as colunas: termo, definicao, categoria (e opcionalmente referencia).', 'glossario-negocios'); ?></p>

                <form id="gs-importacao-form" enctype="multipart/form-data">
                    <div class="gs-form-group">
                        <label for="gs-csv-file"><?php echo esc_html__('Arquivo CSV:', 'glossario-negocios'); ?> <span class="obrigatorio">*</span></label>
                        <input type="file" id="gs-csv-file" name="csv_file" accept=".csv" required>
                        <small><?php echo esc_html__('Tamanho máximo: 5MB. Apenas arquivos CSV são aceitos.', 'glossario-negocios'); ?></small>
                    </div>

                    <div class="gs-form-group">
                        <button type="submit" class="gs-form-submit">
                            <?php echo esc_html__('Importar Termos', 'glossario-negocios'); ?>
                            <span id="gs-carregando-import" class="gs-carregando" style="display: none;"></span>
                        </button>
                    </div>

                    <?php wp_nonce_field('gs_csv_nonce', 'gs_import_nonce'); ?>
                </form>

                <div class="gs-form-group">
                    <h4><?php echo esc_html__('Exemplo de formato CSV:', 'glossario-negocios'); ?></h4>
                    <div class="gs-csv-exemplo">
                        <code>termo,definicao,categoria</code><br>
                        <code>api,Interface de Programação de Aplicações,tecnologia</code><br>
                        <code>html,Linguagem de Marcação,web</code>
                    </div>
                </div>

                <div class="gs-form-group">
                    <a href="#" id="gs-btn-download-modelo" class="gs-btn-modelo">
                        <?php echo esc_html__('Baixar Modelo CSV', 'glossario-negocios'); ?>
                    </a>
                    <small><?php echo esc_html__('Baixe o modelo preenchido para usar como base na importação.', 'glossario-negocios'); ?></small>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_exportacao): ?>
            <!-- Aba: Exportação CSV -->
            <div id="gs-aba-exportacao" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-exportacao" tabindex="0">
                <h3><?php echo esc_html__('Exportar Termos para CSV', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Baixe todos os termos do glossário de negócios em formato CSV para backup ou uso em outras aplicações.', 'glossario-negocios'); ?></p>

                <div class="gs-form-group">
                    <button id="gs-btn-exportar" class="gs-btn-export">
                        <?php echo esc_html__('Baixar CSV', 'glossario-negocios'); ?>
                        <span id="gs-carregando-export" class="gs-carregando" style="display: none;"></span>
                    </button>
                </div>

                <div class="gs-form-group">
                    <small><?php echo esc_html__('O arquivo CSV conterá todas as informações dos termos cadastrados, incluindo título, definição, categoria e referência.', 'glossario-negocios'); ?></small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Aba: Instruções & Detalhes -->
            <div id="gs-aba-detalhes" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-detalhes" tabindex="0">
                <div class="gs-detalhes-painel">
                    <h3><?php echo esc_html__('Como Usar o Glossário de Negócios', 'glossario-negocios'); ?></h3>
                    <p><?php echo esc_html__('Este painel permite gerenciar todos os aspectos do glossário de negócios diretamente pela página, sem necessidade de navegar pelo painel administrativo.', 'glossario-negocios'); ?></p>
                    
                    <div class="gs-detalhes-grid">
                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('1. Cadastro Manual', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Preencha o termo e a definição completa. Você pode associar o termo a uma ou mais categorias segurando a tecla Ctrl (Windows) ou Cmd (Mac).', 'glossario-negocios'); ?></p>
                        </div>

                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('2. Categorias', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Crie novas categorias para agrupar conceitos relacionados. Na aba Categorias você pode renomeá-las ou excluí-las caso não tenham termos vinculados.', 'glossario-negocios'); ?></p>
                        </div>

                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('3. Gestão de Termos', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Pesquise qualquer termo já cadastrado na aba "Gerenciar Termos" para editar rapidamente sua definição e categorias ou excluí-lo do catálogo.', 'glossario-negocios'); ?></p>
                        </div>

                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('4. Importação e Exportação', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Para importações em lote, baixe o Modelo CSV na aba correspondente, preencha suas colunas (termo,definicao,categoria) e faça o upload seguro.', 'glossario-negocios'); ?></p>
                        </div>

                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('5. Dashboard de Estatísticas', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Acompanhe KPIs em tempo real, ritmo de cadastros por período filtrado, distribuição percentual por categoria e cache otimizado.', 'glossario-negocios'); ?></p>
                        </div>

                        <div class="gs-detalhes-bloco">
                            <h4><?php echo esc_html__('6. Acessibilidade por Teclado', 'glossario-negocios'); ?></h4>
                            <p><?php echo esc_html__('Navegue entre as abas utilizando as setas direcionais (← / →), Home e End do teclado com suporte a leitores de tela conforme WCAG 2.1 AA.', 'glossario-negocios'); ?></p>
                        </div>
                    </div>

                    <div class="gs-detalhes-dica">
                        <strong><?php echo esc_html__('Dica para exibição pública:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Utilize o shortcode [glossario_lista] em qualquer página ou post para exibir a tabela com busca acento-insensível, paginação server-side (10, 20, 100 ou 500 por página) e índice alfabético aos visitantes.', 'glossario-negocios'); ?>
                    </div>
                </div>
            </div>

            <!-- Aba: Dashboard de Estatísticas -->
            <div id="gs-aba-dashboard" class="gs-tab-content" role="tabpanel" aria-labelledby="gs-tab-dashboard" tabindex="0">
                <?php
                if (function_exists('gs_renderizar_dashboard_html')) {
                    echo gs_renderizar_dashboard_html('cadastro');
                }
                ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    } catch (Throwable $e) {
        error_log('Erro no shortcode glossario_cadastro: ' . $e->getMessage());
        return '<p class="gs-erro">' . esc_html__('Erro ao carregar o formulário. Por favor, tente novamente mais tarde.', 'glossario-negocios') . '</p>';
    }
}

/**
 * Callback AJAX para salvar termo
 */
function gs_ajax_salvar_termo() {
    try {
        // Verificar nonce para segurança
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança. Tente novamente.', 'glossario-negocios'));
            die();
        }

        // Verificar permissões
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(__('Você não tem permissão para adicionar termos.', 'glossario-negocios'));
            die();
        }

        // Validar dados obrigatórios
        if (empty($_POST['termo']) || empty($_POST['definicao'])) {
            wp_send_json_error(__('Termo e definição são obrigatórios.', 'glossario-negocios'));
            die();
        }

        // Sanitizar dados
        $termo = sanitize_text_field($_POST['termo']);
        $definicao = wp_kses_post($_POST['definicao']);

        // Processar múltiplas categorias (obrigatórias)
        $categorias = array();
        if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $cat_id) {
                $cid = intval($cat_id);
                if ($cid > 0) {
                    $categorias[] = $cid;
                }
            }
        }

        if (empty($categorias)) {
            wp_send_json_error(__('A seleção de pelo menos uma categoria é obrigatória.', 'glossario-negocios'));
            die();
        }

        // Verificar duplicidade por categoria
        $categorias_livres = array();
        $categorias_conflitantes_nomes = array();

        foreach ($categorias as $cat_id) {
            $cat_obj = get_term($cat_id, 'categoria_glossario');
            $cat_nome = ($cat_obj && !is_wp_error($cat_obj)) ? $cat_obj->name : "ID #$cat_id";

            if (function_exists('gs_termo_existe_na_categoria') && gs_termo_existe_na_categoria($termo, $cat_id)) {
                $categorias_conflitantes_nomes[] = $cat_nome;
            } else {
                $categorias_livres[] = $cat_id;
            }
        }

        // Se o termo já existe em TODAS as categorias selecionadas
        if (empty($categorias_livres)) {
            wp_send_json_error(sprintf(
                /* translators: 1: nome do termo, 2: categorias conflitantes */
                __('O termo "%1$s" já está cadastrado na(s) categoria(s): %2$s.', 'glossario-negocios'),
                esc_html($termo),
                implode(', ', $categorias_conflitantes_nomes)
            ));
            die();
        }

        // Criar o post com as categorias livres
        $post_data = array(
            'post_title'   => $termo,
            'post_content' => $definicao,
            'post_status'  => 'publish',
            'post_type'    => 'glossario',
            'post_author'  => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(__('Erro ao salvar o termo. Tente novamente.', 'glossario-negocios'));
            die();
        }

        // Vincular as categorias livres
        wp_set_post_terms($post_id, $categorias_livres, 'categoria_glossario');

        // Mensagem com aviso se houve categorias conflitantes (Comportamento B)
        if (!empty($categorias_conflitantes_nomes)) {
            $mensagem_sucesso = sprintf(
                /* translators: %s: categorias duplicadas */
                __('Termo adicionado com sucesso! Observação: o termo já existia na(s) categoria(s) "%s" e não foi duplicado nela(s).', 'glossario-negocios'),
                implode(', ', $categorias_conflitantes_nomes)
            );
        } else {
            $mensagem_sucesso = __('Termo adicionado com sucesso!', 'glossario-negocios');
        }

        wp_send_json_success(array(
            'message' => $mensagem_sucesso,
            'post_id' => $post_id
        ));

        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Salvar termo', 'glossario-negocios'));
    }
}

/**
 * Listar categorias via AJAX
 */
function gs_ajax_listar_categorias() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        $categorias = get_terms(array(
            'taxonomy' => 'categoria_glossario',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        if (empty($categorias) || is_wp_error($categorias)) {
            wp_send_json_success(array('categorias' => array()));
            die();
        }

        $lista = array();
        foreach ($categorias as $cat) {
            $lista[] = array(
                'id' => $cat->term_id,
                'name' => mb_strtolower($cat->name, 'UTF-8'),
                'slug' => $cat->slug,
                'count' => $cat->count,
            );
        }

        wp_send_json_success(array('categorias' => $lista));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Listar categorias', 'glossario-negocios'));
    }
}

/**
 * Salvar nova categoria via AJAX
 */
function gs_ajax_salvar_categoria() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Você não tem permissão para gerenciar categorias.', 'glossario-negocios'));
            die();
        }

        if (empty($_POST['nome'])) {
            wp_send_json_error(__('Nome da categoria é obrigatório.', 'glossario-negocios'));
            die();
        }

        $nome = mb_strtolower(sanitize_text_field($_POST['nome']), 'UTF-8');

        // Verificar se já existe
        $existe = term_exists($nome, 'categoria_glossario');
        if ($existe) {
            wp_send_json_error(__('Esta categoria já existe.', 'glossario-negocios'));
            die();
        }

        $resultado = wp_insert_term($nome, 'categoria_glossario');

        if (is_wp_error($resultado)) {
            wp_send_json_error(__('Erro ao criar categoria: ', 'glossario-negocios') . $resultado->get_error_message());
            die();
        }

        wp_send_json_success(array(
            'message' => __('Categoria criada com sucesso!', 'glossario-negocios'),
            'id' => $resultado['term_id'],
            'nome' => $nome,
        ));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Salvar categoria', 'glossario-negocios'));
    }
}

/**
 * Editar categoria via AJAX
 */
function gs_ajax_editar_categoria() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Você não tem permissão para gerenciar categorias.', 'glossario-negocios'));
            die();
        }

        if (empty($_POST['id']) || empty($_POST['nome'])) {
            wp_send_json_error(__('ID e nome da categoria são obrigatórios.', 'glossario-negocios'));
            die();
        }

        $id = intval($_POST['id']);
        $novo_nome = mb_strtolower(sanitize_text_field($_POST['nome']), 'UTF-8');

        // Verificar se já existe outro com esse nome
        $existe = term_exists($novo_nome, 'categoria_glossario');
        if ($existe && intval($existe['term_id']) !== $id) {
            wp_send_json_error(__('Já existe outra categoria com este nome.', 'glossario-negocios'));
            die();
        }

        $resultado = wp_update_term($id, 'categoria_glossario', array('name' => $novo_nome));

        if (is_wp_error($resultado)) {
            wp_send_json_error(__('Erro ao editar categoria: ', 'glossario-negocios') . $resultado->get_error_message());
            die();
        }

        wp_send_json_success(array(
            'message' => __('Categoria editada com sucesso!', 'glossario-negocios'),
            'id' => $id,
            'nome' => $novo_nome,
        ));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Editar categoria', 'glossario-negocios'));
    }
}

/**
 * Excluir categoria via AJAX
 */
function gs_ajax_excluir_categoria() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Você não tem permissão para gerenciar categorias.', 'glossario-negocios'));
            die();
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(__('ID da categoria é obrigatório.', 'glossario-negocios'));
            die();
        }

        $id = intval($_POST['id']);

        // Verificar se há termos usando esta categoria
        $termo = get_term($id, 'categoria_glossario');
        if ($termo && $termo->count > 0) {
            wp_send_json_error(sprintf(
                /* translators: %d: quantidade de termos vinculados */
                __('Não é possível excluir. Esta categoria possui %d termo(s) vinculado(s).', 'glossario-negocios'),
                $termo->count
            ));
            die();
        }

        $resultado = wp_delete_term($id, 'categoria_glossario');

        if (is_wp_error($resultado)) {
            wp_send_json_error(__('Erro ao excluir categoria.', 'glossario-negocios'));
            die();
        }

        wp_send_json_success(array('message' => __('Categoria excluída com sucesso!', 'glossario-negocios')));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Excluir categoria', 'glossario-negocios'));
    }
}

/**
 * Listar termos para gerenciamento via AJAX
 */
function gs_ajax_listar_termos_gerenciar() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        $args = array(
            'post_type' => 'glossario',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $termos = new WP_Query($args);
        $lista = array();

        if ($termos->have_posts()) {
            while ($termos->have_posts()) {
                $termos->the_post();

                $categorias_termo = get_the_terms(get_the_ID(), 'categoria_glossario');
                $categorias_nomes = array();
                if (!empty($categorias_termo) && !is_wp_error($categorias_termo)) {
                    foreach ($categorias_termo as $cat) {
                        $categorias_nomes[] = mb_strtolower($cat->name, 'UTF-8');
                    }
                }

                $lista[] = array(
                    'id' => get_the_ID(),
                    'titulo' => get_the_title(),
                    'definicao' => wp_strip_all_tags(get_the_content()),
                    'categorias' => !empty($categorias_nomes) ? implode(', ', $categorias_nomes) : __('sem categoria', 'glossario-negocios'),
                    'referencia' => get_post_meta(get_the_ID(), 'referencia', true),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array('termos' => $lista));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Listar termos', 'glossario-negocios'));
    }
}

/**
 * Editar termo via AJAX
 */
function gs_ajax_editar_termo() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(__('Você não tem permissão para editar termos.', 'glossario-negocios'));
            die();
        }

        if (empty($_POST['id']) || empty($_POST['termo']) || empty($_POST['definicao'])) {
            wp_send_json_error(__('ID, termo e definição são obrigatórios.', 'glossario-negocios'));
            die();
        }

        $id = intval($_POST['id']);
        $termo = sanitize_text_field($_POST['termo']);
        $definicao = wp_kses_post($_POST['definicao']);

        // Processar múltiplas categorias (obrigatórias)
        $categorias = array();
        if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $cat_id) {
                $cid = intval($cat_id);
                if ($cid > 0) {
                    $categorias[] = $cid;
                }
            }
        }

        if (empty($categorias)) {
            wp_send_json_error(__('A seleção de pelo menos uma categoria é obrigatória.', 'glossario-negocios'));
            die();
        }

        // Verificar se o post existe
        $post_existe = get_post($id);
        if (!$post_existe || $post_existe->post_type !== 'glossario') {
            wp_send_json_error(__('Termo não encontrado.', 'glossario-negocios'));
            die();
        }

        // Verificar duplicidade por categoria (ignorando o post atual)
        $categorias_livres = array();
        $categorias_conflitantes_nomes = array();

        foreach ($categorias as $cat_id) {
            $cat_obj = get_term($cat_id, 'categoria_glossario');
            $cat_nome = ($cat_obj && !is_wp_error($cat_obj)) ? $cat_obj->name : "ID #$cat_id";

            if (function_exists('gs_termo_existe_na_categoria') && gs_termo_existe_na_categoria($termo, $cat_id, $id)) {
                $categorias_conflitantes_nomes[] = $cat_nome;
            } else {
                $categorias_livres[] = $cat_id;
            }
        }

        if (empty($categorias_livres)) {
            wp_send_json_error(sprintf(
                /* translators: 1: nome do termo, 2: categorias conflitantes */
                __('Já existe outro termo com o nome "%1$s" na(s) categoria(s): %2$s.', 'glossario-negocios'),
                esc_html($termo),
                implode(', ', $categorias_conflitantes_nomes)
            ));
            die();
        }

        $post_data = array(
            'ID'           => $id,
            'post_title'   => $termo,
            'post_content' => $definicao,
        );

        $resultado = wp_update_post($post_data);

        if (is_wp_error($resultado)) {
            wp_send_json_error(__('Erro ao editar termo.', 'glossario-negocios'));
            die();
        }

        // Atualizar categorias com as livres
        wp_set_post_terms($id, $categorias_livres, 'categoria_glossario');

        if (!empty($categorias_conflitantes_nomes)) {
            $msg = sprintf(
                /* translators: %s: categorias duplicadas */
                __('Termo editado com sucesso! Observação: o termo já existia na(s) categoria(s) "%s" e não foi vinculado a ela(s).', 'glossario-negocios'),
                implode(', ', $categorias_conflitantes_nomes)
            );
        } else {
            $msg = __('Termo editado com sucesso!', 'glossario-negocios');
        }

        wp_send_json_success(array('message' => $msg));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Editar termo', 'glossario-negocios'));
    }
}

/**
 * Excluir termo via AJAX
 */
function gs_ajax_excluir_termo() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_cadastro_nonce')) {
            wp_send_json_error(__('Erro de segurança.', 'glossario-negocios'));
            die();
        }

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(__('Você não tem permissão para excluir termos.', 'glossario-negocios'));
            die();
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(__('ID do termo é obrigatório.', 'glossario-negocios'));
            die();
        }

        $id = intval($_POST['id']);

        // Verificar se o post existe
        $post_existe = get_post($id);
        if (!$post_existe || $post_existe->post_type !== 'glossario') {
            wp_send_json_error(__('Termo não encontrado.', 'glossario-negocios'));
            die();
        }

        // Mover para lixeira
        $resultado = wp_delete_post($id, true);

        if (!$resultado) {
            wp_send_json_error(__('Erro ao excluir termo.', 'glossario-negocios'));
            die();
        }

        wp_send_json_success(array('message' => __('Termo excluído com sucesso!', 'glossario-negocios')));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Excluir termo', 'glossario-negocios'));
    }
}
