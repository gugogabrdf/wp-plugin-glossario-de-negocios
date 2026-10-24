<?php
/**
 * Módulo de Dashboard e Estatísticas do Glossário de Negócios
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adiciona o submenu do Dashboard no menu do Glossário de Negócios
 */
function gs_adicionar_submenu_dashboard() {
    try {
        add_submenu_page(
            'edit.php?post_type=glossario',
            __('Dashboard do Glossário de Negócios', 'glossario-negocios'),
            __('Dashboard', 'glossario-negocios'),
            'edit_posts',
            'glossario-dashboard',
            'gs_renderizar_admin_dashboard'
        );
    } catch (Throwable $e) {
        error_log('Erro ao adicionar submenu de dashboard: ' . $e->getMessage());
    }
}
add_action('admin_menu', 'gs_adicionar_submenu_dashboard');

/**
 * Normaliza datas enviadas em formato DD/MM/YYYY ou YYYY-MM-DD para YYYY-MM-DD
 */
function gs_normalizar_data_br($data_str) {
    $data_str = trim($data_str);
    if (empty($data_str)) {
        return null;
    }

    // Se já estiver em formato YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_str)) {
        return $data_str;
    }

    // Se estiver no formato brasileiro DD/MM/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data_str, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }

    $time = strtotime($data_str);
    return $time ? date('Y-m-d', $time) : null;
}

/**
 * Calcula todas as estatísticas do Glossário de Negócios (com cache via Transients API)
 *
 * @param string $data_inicio Data de início (opcional)
 * @param string $data_fim Data de fim (opcional)
 * @param bool $forcar_atualizacao Se verdadeiro, ignora o cache e recalcula
 * @return array|false
 */
function gs_obter_estatisticas_glossario($data_inicio = '', $data_fim = '', $forcar_atualizacao = false) {
    try {
        // Gerenciamento de Cache via Transients API (10 minutos)
        $versao_cache = (int) get_option('gs_stats_cache_version', 1);
        $cache_key = (empty($data_inicio) && empty($data_fim))
            ? 'gs_stats_padrao_v' . $versao_cache
            : 'gs_stats_' . $versao_cache . '_' . md5($data_inicio . '|' . $data_fim);

        if (!$forcar_atualizacao) {
            $cached = get_transient($cache_key);
            if (false !== $cached && is_array($cached)) {
                $cached['em_cache'] = true;
                return $cached;
            }
        }

        // Total de termos
        $total_termos = wp_count_posts('glossario')->publish;

        // Total de categorias
        $categorias = get_terms(array(
            'taxonomy'   => 'categoria_glossario',
            'hide_empty' => false,
        ));
        $total_categorias = !is_wp_error($categorias) ? count($categorias) : 0;

        // Categoria com mais termos e lista ordenada
        $categoria_top = null;
        $categorias_distribuicao = array();

        if (!empty($categorias) && !is_wp_error($categorias)) {
            // Ordenar categorias pela quantidade de termos (decrescente)
            usort($categorias, function($a, $b) {
                return $b->count - $a->count;
            });

            $categoria_top = $categorias[0];

            foreach ($categorias as $cat) {
                $percentual = $total_termos > 0 ? round(($cat->count / $total_termos) * 100, 1) : 0;
                $categorias_distribuicao[] = array(
                    'id'         => $cat->term_id,
                    'nome'       => mb_strtolower($cat->name, 'UTF-8'),
                    'slug'       => $cat->slug,
                    'count'      => $cat->count,
                    'percentual' => $percentual,
                );
            }
        }

        // Média de termos por categoria
        $media_por_cat = $total_categorias > 0 ? round($total_termos / $total_categorias, 1) : 0;

        // Período de consulta para o contador dinâmico
        $hoje_str = date('Y-m-d');
        $inicio_sql = gs_normalizar_data_br($data_inicio);
        $fim_sql = gs_normalizar_data_br($data_fim);

        // Se não houver período específico, o padrão é últimos 30 dias
        if (!$inicio_sql && !$fim_sql) {
            $inicio_sql = date('Y-m-d', strtotime('-30 days'));
            $fim_sql = $hoje_str;
            $rotulo_periodo = 'Últimos 30 dias';
        } else {
            $inicio_br = $inicio_sql ? date('d/m/Y', strtotime($inicio_sql)) : 'Início';
            $fim_br = $fim_sql ? date('d/m/Y', strtotime($fim_sql)) : date('d/m/Y');
            $rotulo_periodo = $inicio_br . ' até ' . $fim_br;
        }

        // Query de termos no período selecionado
        $date_query = array();
        if ($inicio_sql) {
            $date_query['after'] = $inicio_sql . ' 00:00:00';
            $date_query['inclusive'] = true;
        }
        if ($fim_sql) {
            $date_query['before'] = $fim_sql . ' 23:59:59';
            $date_query['inclusive'] = true;
        }

        $args_periodo = array(
            'post_type'              => 'glossario',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if (!empty($date_query)) {
            $args_periodo['date_query'] = array($date_query);
        }

        $query_periodo = new WP_Query($args_periodo);
        $cadastros_periodo = $query_periodo->post_count;

        // Contagens rápidas de atalhos
        $hoje_count = (new WP_Query(array(
            'post_type' => 'glossario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => array(array('after' => $hoje_str . ' 00:00:00', 'inclusive' => true)),
            'no_found_rows' => true,
        )))->post_count;

        $semana_count = (new WP_Query(array(
            'post_type' => 'glossario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => array(array('after' => date('Y-m-d', strtotime('-7 days')) . ' 00:00:00', 'inclusive' => true)),
            'no_found_rows' => true,
        )))->post_count;

        $mes_count = (new WP_Query(array(
            'post_type' => 'glossario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => array(array('after' => date('Y-m-01') . ' 00:00:00', 'inclusive' => true)),
            'no_found_rows' => true,
        )))->post_count;

        $ano_count = (new WP_Query(array(
            'post_type' => 'glossario',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => array(array('after' => date('Y-01-01') . ' 00:00:00', 'inclusive' => true)),
            'no_found_rows' => true,
        )))->post_count;

        // Termos mais recentes
        $termos_recentes = array();
        $query_recentes = new WP_Query(array(
            'post_type'      => 'glossario',
            'post_status'    => 'publish',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        if ($query_recentes->have_posts()) {
            while ($query_recentes->have_posts()) {
                $query_recentes->the_post();
                $cats = get_the_terms(get_the_ID(), 'categoria_glossario');
                $cats_nomes = array();
                if (!empty($cats) && !is_wp_error($cats)) {
                    foreach ($cats as $c) {
                        $cats_nomes[] = mb_strtolower($c->name, 'UTF-8');
                    }
                }

                $termos_recentes[] = array(
                    'id'         => get_the_ID(),
                    'titulo'     => get_the_title(),
                    'data_br'    => get_the_date('d/m/Y \à\s H:i'),
                    'categorias' => !empty($cats_nomes) ? implode(', ', $cats_nomes) : 'sem categoria',
                );
            }
            wp_reset_postdata();
        }

        $resultado = array(
            'total_termos'       => (int) $total_termos,
            'total_categorias'   => (int) $total_categorias,
            'media_por_cat'      => $media_por_cat,
            'categoria_top'      => $categoria_top ? array('nome' => mb_strtolower($categoria_top->name, 'UTF-8'), 'count' => $categoria_top->count) : null,
            'cadastros_periodo'  => (int) $cadastros_periodo,
            'rotulo_periodo'     => $rotulo_periodo,
            'hoje_count'         => (int) $hoje_count,
            'semana_count'       => (int) $semana_count,
            'mes_count'          => (int) $mes_count,
            'ano_count'          => (int) $ano_count,
            'categorias'         => $categorias_distribuicao,
            'termos_recentes'    => $termos_recentes,
            'data_inicio_br'     => $inicio_sql ? date('d/m/Y', strtotime($inicio_sql)) : '',
            'data_fim_br'        => $fim_sql ? date('d/m/Y', strtotime($fim_sql)) : '',
            'em_cache'           => false,
        );

        // Salvar em cache pelo período de 10 minutos (600 segundos)
        $tempo_expiracao = defined('MINUTE_IN_SECONDS') ? 10 * MINUTE_IN_SECONDS : 600;
        set_transient($cache_key, $resultado, $tempo_expiracao);

        return $resultado;
    } catch (Throwable $e) {
        error_log('Erro ao calcular estatísticas do glossário de negócios: ' . $e->getMessage());
        return false;
    }
}

/**
 * Renderiza o HTML do Dashboard (reutilizado no Admin, Shortcode e Aba)
 */
function gs_renderizar_dashboard_html($contexto = 'admin') {
    $stats = gs_obter_estatisticas_glossario();
    if (!$stats) {
        return '<p class="gs-erro">' . esc_html__('Não foi possível carregar as estatísticas no momento.', 'glossario-negocios') . '</p>';
    }

    ob_start();
    ?>
    <div class="gs-dashboard-container <?php echo esc_attr('gs-contexto-' . $contexto); ?>" id="gs-dashboard-root">
        <!-- Barra Superior de Filtros de Período -->
        <div class="gs-dashboard-filtros-bar">
            <div class="gs-dashboard-atalhos">
                <span class="gs-atalhos-label"><?php echo esc_html__('Período:', 'glossario-negocios'); ?></span>
                <button type="button" class="gs-btn-periodo" data-periodo="hoje"><?php echo esc_html__('Hoje', 'glossario-negocios'); ?></button>
                <button type="button" class="gs-btn-periodo" data-periodo="semana"><?php echo esc_html__('Últimos 7 dias', 'glossario-negocios'); ?></button>
                <button type="button" class="gs-btn-periodo ativo" data-periodo="mes"><?php echo esc_html__('Últimos 30 dias', 'glossario-negocios'); ?></button>
                <button type="button" class="gs-btn-periodo" data-periodo="ano"><?php echo esc_html__('Este Ano', 'glossario-negocios'); ?></button>
                <button type="button" class="gs-btn-periodo" data-periodo="tudo"><?php echo esc_html__('Histórico Completo', 'glossario-negocios'); ?></button>
            </div>

            <!-- Filtro de Datas Personalizadas com padrão brasileiro DD/MM/YYYY -->
            <form id="gs-form-filtro-datas" class="gs-form-filtro-datas">
                <div class="gs-campo-data-inline">
                    <label for="gs-data-inicio"><?php echo esc_html__('De:', 'glossario-negocios'); ?></label>
                    <input type="text" id="gs-data-inicio" name="data_inicio" placeholder="<?php echo esc_attr__('DD/MM/AAAA', 'glossario-negocios'); ?>" maxlength="10" autocomplete="off">
                </div>
                <div class="gs-campo-data-inline">
                    <label for="gs-data-fim"><?php echo esc_html__('Até:', 'glossario-negocios'); ?></label>
                    <input type="text" id="gs-data-fim" name="data_fim" placeholder="<?php echo esc_attr__('DD/MM/AAAA', 'glossario-negocios'); ?>" maxlength="10" autocomplete="off">
                </div>
                <button type="submit" class="gs-btn-filtrar-datas">
                    <?php echo esc_html__('Filtrar', 'glossario-negocios'); ?>
                    <span class="gs-dashboard-carregando" style="display: none;"></span>
                </button>
            </form>
        </div>

        <!-- Grid de Cards de KPI -->
        <div class="gs-dashboard-kpis">
            <!-- KPI: Total de Termos -->
            <div class="gs-kpi-card">
                <span class="gs-kpi-titulo"><?php echo esc_html__('Total de Termos', 'glossario-negocios'); ?></span>
                <div class="gs-kpi-valor" id="gs-kpi-total-termos"><?php echo esc_html($stats['total_termos']); ?></div>
                <span class="gs-kpi-subtexto"><?php echo esc_html__('Termos publicados', 'glossario-negocios'); ?></span>
            </div>

            <!-- KPI: Total de Categorias -->
            <div class="gs-kpi-card">
                <span class="gs-kpi-titulo"><?php echo esc_html__('Total de Categorias', 'glossario-negocios'); ?></span>
                <div class="gs-kpi-valor" id="gs-kpi-total-categorias"><?php echo esc_html($stats['total_categorias']); ?></div>
                <span class="gs-kpi-subtexto"><?php echo esc_html__('Categorias ativas', 'glossario-negocios'); ?></span>
            </div>

            <!-- KPI: Registros no Período -->
            <div class="gs-kpi-card gs-kpi-destaque">
                <span class="gs-kpi-titulo"><?php echo esc_html__('Cadastros no Período', 'glossario-negocios'); ?></span>
                <div class="gs-kpi-valor" id="gs-kpi-periodo-valor"><?php echo esc_html($stats['cadastros_periodo']); ?></div>
                <span class="gs-kpi-subtexto" id="gs-kpi-periodo-rotulo"><?php echo esc_html($stats['rotulo_periodo']); ?></span>
            </div>

            <!-- KPI: Média por Categoria -->
            <div class="gs-kpi-card">
                <span class="gs-kpi-titulo"><?php echo esc_html__('Média por Categoria', 'glossario-negocios'); ?></span>
                <div class="gs-kpi-valor" id="gs-kpi-media"><?php echo esc_html($stats['media_por_cat']); ?></div>
                <span class="gs-kpi-subtexto"><?php echo esc_html__('Termos por categoria', 'glossario-negocios'); ?></span>
            </div>

            <!-- KPI: Categoria com Mais Termos -->
            <div class="gs-kpi-card">
                <span class="gs-kpi-titulo"><?php echo esc_html__('Categoria com Mais Termos', 'glossario-negocios'); ?></span>
                <div class="gs-kpi-valor gs-kpi-valor-texto" id="gs-kpi-top-cat">
                    <?php echo $stats['categoria_top'] ? esc_html($stats['categoria_top']['nome']) : '—'; ?>
                </div>
                <span class="gs-kpi-subtexto" id="gs-kpi-top-cat-sub">
                    <?php echo $stats['categoria_top'] ? esc_html(sprintf(_n('%d termo', '%d termos', $stats['categoria_top']['count'], 'glossario-negocios'), $stats['categoria_top']['count'])) : esc_html__('Nenhuma categoria', 'glossario-negocios'); ?>
                </span>
            </div>
        </div>

        <!-- Seção de Duas Colunas: Distribuição por Categoria & Termos Recentes -->
        <div class="gs-dashboard-grid-detalhes">
            <!-- Coluna 1: Distribuição por Categorias -->
            <div class="gs-dashboard-painel">
                <div class="gs-painel-header">
                    <h3><?php echo esc_html__('Distribuição por Categorias', 'glossario-negocios'); ?></h3>
                    <span class="gs-painel-meta"><?php echo esc_html(sprintf(_n('%d categoria', '%d categorias', $stats['total_categorias'], 'glossario-negocios'), $stats['total_categorias'])); ?></span>
                </div>

                <div class="gs-categorias-distribuicao" id="gs-lista-categorias-distribuicao">
                    <?php if (!empty($stats['categorias'])): ?>
                        <?php foreach ($stats['categorias'] as $cat): ?>
                            <div class="gs-cat-progresso-item">
                                <div class="gs-cat-info-linha">
                                    <span class="gs-cat-nome"><?php echo esc_html($cat['nome']); ?></span>
                                    <span class="gs-cat-qtd">
                                        <strong><?php echo esc_html($cat['count']); ?></strong> <?php echo esc_html(_n('termo', 'termos', $cat['count'], 'glossario-negocios')); ?>
                                        <small>(<?php echo esc_html($cat['percentual']); ?>%)</small>
                                    </span>
                                </div>
                                <div class="gs-barra-trilha">
                                    <div class="gs-barra-preenchimento" style="width: <?php echo esc_attr($cat['percentual']); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="gs-empty-state"><p><?php echo esc_html__('Nenhuma categoria encontrada.', 'glossario-negocios'); ?></p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna 2: Termos Cadastrados Recentemente -->
            <div class="gs-dashboard-painel">
                <div class="gs-painel-header">
                    <h3><?php echo esc_html__('Termos Recentes', 'glossario-negocios'); ?></h3>
                    <span class="gs-painel-meta"><?php echo esc_html__('Últimos registros', 'glossario-negocios'); ?></span>
                </div>

                <div class="gs-termos-recentes-lista" id="gs-lista-termos-recentes">
                    <?php if (!empty($stats['termos_recentes'])): ?>
                        <table class="gs-tabela-compacta">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Termo', 'glossario-negocios'); ?></th>
                                    <th><?php echo esc_html__('Categoria', 'glossario-negocios'); ?></th>
                                    <th><?php echo esc_html__('Data de Cadastro', 'glossario-negocios'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['termos_recentes'] as $recente): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($recente['titulo']); ?></strong></td>
                                        <td><span class="gs-badge"><?php echo esc_html($recente['categorias']); ?></span></td>
                                        <td class="gs-data-col"><?php echo esc_html($recente['data_br']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="gs-empty-state"><p><?php echo esc_html__('Nenhum termo cadastrado recentemente.', 'glossario-negocios'); ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renderiza a página administrativa do Dashboard
 */
function gs_renderizar_admin_dashboard() {
    if (function_exists('gs_enfileirar_assets')) {
        gs_enfileirar_assets();
    }
    ?>
    <div class="wrap gs-admin-dashboard-wrap">
        <div class="gs-admin-header">
            <div class="gs-admin-badge">v<?php echo defined('GS_VERSAO') ? GS_VERSAO : '2.3.17'; ?></div>
            <h1><?php echo esc_html__('Dashboard & Métricas do Glossário de Negócios', 'glossario-negocios'); ?></h1>
            <p class="gs-admin-subtitle"><?php echo esc_html__('Visão consolidada do volume de termos, ritmo de cadastros por período e distribuição por categorias.', 'glossario-negocios'); ?></p>
        </div>

        <?php echo gs_renderizar_dashboard_html('admin'); ?>
    </div>
    <?php
}

/**
 * Shortcode [glossario_dashboard]
 */
function gs_shortcode_dashboard() {
    try {
        if (function_exists('gs_enfileirar_assets')) {
            gs_enfileirar_assets();
        }

        return gs_renderizar_dashboard_html('shortcode');
    } catch (Throwable $e) {
        error_log('Erro no shortcode glossario_dashboard: ' . $e->getMessage());
        return '<p class="gs-erro">' . esc_html__('Erro ao carregar o dashboard. Tente novamente mais tarde.', 'glossario-negocios') . '</p>';
    }
}
add_shortcode('glossario_dashboard', 'gs_shortcode_dashboard');

/**
 * Endpoint AJAX para filtrar métricas do Dashboard por período
 */
function gs_ajax_filtrar_dashboard() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_ajax_nonce')) {
            wp_send_json_error(__('Erro de segurança. Atualize a página e tente novamente.', 'glossario-negocios'));
            die();
        }

        $periodo = isset($_POST['periodo']) ? sanitize_text_field($_POST['periodo']) : '';
        $data_inicio = isset($_POST['data_inicio']) ? sanitize_text_field($_POST['data_inicio']) : '';
        $data_fim = isset($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) : '';

        // Definir datas com base no atalho
        if ($periodo === 'hoje') {
            $data_inicio = date('Y-m-d');
            $data_fim = date('Y-m-d');
            $rotulo = sprintf(
                /* translators: %s: data de hoje formatada */
                __('Hoje (%s)', 'glossario-negocios'),
                date('d/m/Y')
            );
        } elseif ($periodo === 'semana') {
            $data_inicio = date('Y-m-d', strtotime('-7 days'));
            $data_fim = date('Y-m-d');
            $rotulo = __('Últimos 7 dias', 'glossario-negocios');
        } elseif ($periodo === 'mes') {
            $data_inicio = date('Y-m-d', strtotime('-30 days'));
            $data_fim = date('Y-m-d');
            $rotulo = __('Últimos 30 dias', 'glossario-negocios');
        } elseif ($periodo === 'ano') {
            $data_inicio = date('Y-01-01');
            $data_fim = date('Y-m-d');
            $rotulo = sprintf(
                /* translators: %s: ano corrente */
                __('Este Ano (%s)', 'glossario-negocios'),
                date('Y')
            );
        } elseif ($periodo === 'tudo') {
            $data_inicio = '';
            $data_fim = '';
            $rotulo = __('Histórico Completo', 'glossario-negocios');
        } else {
            $ini_br = $data_inicio ? date('d/m/Y', strtotime(gs_normalizar_data_br($data_inicio))) : __('Início', 'glossario-negocios');
            $fim_br = $data_fim ? date('d/m/Y', strtotime(gs_normalizar_data_br($data_fim))) : date('d/m/Y');
            $rotulo = sprintf(
                /* translators: 1: data inicial, 2: data final */
                __('%1$s até %2$s', 'glossario-negocios'),
                $ini_br,
                $fim_br
            );
        }

        $forcar = !empty($_POST['forcar_atualizacao']);
        $stats = gs_obter_estatisticas_glossario($data_inicio, $data_fim, $forcar);

        if (!$stats) {
            wp_send_json_error(__('Erro ao calcular estatísticas.', 'glossario-negocios'));
            die();
        }

        $stats['rotulo_periodo'] = $rotulo;

        wp_send_json_success($stats);
        die();
    } catch (Throwable $e) {
        if (function_exists('gs_log_and_respond_error')) {
            gs_log_and_respond_error($e, __('Filtrar dashboard', 'glossario-negocios'));
        } else {
            wp_send_json_error(__('Erro interno.', 'glossario-negocios'));
            die();
        }
    }
}
add_action('wp_ajax_gs_filtrar_dashboard', 'gs_ajax_filtrar_dashboard');
add_action('wp_ajax_nopriv_gs_filtrar_dashboard', 'gs_ajax_filtrar_dashboard');

/**
 * Invalida o cache de métricas e estatísticas do Glossário (Transients API)
 * Disparado ao criar, salvar, mover para lixeira, restaurar ou excluir termos ou categorias.
 *
 * @param int|null $post_id ID do post (se disparado por hook de post)
 */
function gs_limpar_cache_estatisticas($post_id = null) {
    try {
        // Se chamado por hook genérico de post, certificar-se de que é do tipo 'glossario'
        if ($post_id && is_numeric($post_id)) {
            $post = get_post($post_id);
            if ($post && $post->post_type !== 'glossario') {
                return;
            }
        }

        // Deleta os transients diretos do dashboard padrão
        delete_transient('gs_dashboard_metricas');
        delete_transient('gs_dashboard_metricas_padrao');

        // Incrementa a versão de cache invalidando todos os transients particionados
        $versao_atual = (int) get_option('gs_stats_cache_version', 1);
        update_option('gs_stats_cache_version', $versao_atual + 1);
    } catch (Throwable $e) {
        error_log('Erro ao limpar cache de estatísticas do glossário: ' . $e->getMessage());
    }
}
add_action('save_post_glossario', 'gs_limpar_cache_estatisticas');
add_action('deleted_post', 'gs_limpar_cache_estatisticas');
add_action('trashed_post', 'gs_limpar_cache_estatisticas');
add_action('untrashed_post', 'gs_limpar_cache_estatisticas');
add_action('created_categoria_glossario', 'gs_limpar_cache_estatisticas');
add_action('edited_categoria_glossario', 'gs_limpar_cache_estatisticas');
add_action('delete_categoria_glossario', 'gs_limpar_cache_estatisticas');
