<?php
/**
 * Shortcode [glossario_lista]
 * Lista os termos do glossário de negócios com filtros e paginação server-side de alta performance
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza o shortcode [glossario_lista]
 */
function gs_shortcode_lista() {
    try {
        if (function_exists('gs_enfileirar_assets')) {
            gs_enfileirar_assets();
        }

        ob_start();

        // Consulta inicial controlada de termos (apenas a primeira página para alta performance)
        $itens_iniciais = 10;
        $args_iniciais = array(
            'post_type'      => 'glossario',
            'post_status'    => 'publish',
            'posts_per_page' => $itens_iniciais,
            'paged'          => 1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $termos = new WP_Query($args_iniciais);
        $total_itens = (int) $termos->found_posts;
        $total_paginas = $total_itens > 0 ? (int) ceil($total_itens / $itens_iniciais) : 1;
        ?>

        <div class="gs-glossario-container"
             data-total-itens="<?php echo esc_attr($total_itens); ?>"
             data-total-paginas="<?php echo esc_attr($total_paginas); ?>"
             data-pagina-atual="1"
             data-itens-por-pagina="<?php echo esc_attr($itens_iniciais); ?>">

            <!-- Filtros -->
            <div class="gs-filtros">
                <div class="gs-busca-wrapper">
                    <span class="gs-busca-icone" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" id="gs-busca" placeholder="<?php echo esc_attr__('Buscar termo ou definição...', 'glossario-negocios'); ?>" aria-label="<?php echo esc_attr__('Buscar termo no glossário de negócios', 'glossario-negocios'); ?>" autocomplete="off" />
                </div>

                <?php
                // Dropdown de categorias
                $categorias = get_terms(array(
                    'taxonomy'   => 'categoria_glossario',
                    'hide_empty' => false,
                ));

                if (!empty($categorias) && !is_wp_error($categorias)) {
                    echo '<select id="gs-filtro-categoria" aria-label="' . esc_attr__('Filtrar por categoria', 'glossario-negocios') . '">';
                    echo '<option value="">' . esc_html__('todas as categorias', 'glossario-negocios') . '</option>';

                    foreach ($categorias as $categoria) {
                        echo '<option value="' . esc_attr($categoria->slug) . '">' . esc_html(mb_strtolower($categoria->name, 'UTF-8')) . '</option>';
                    }

                    echo '</select>';
                }
                ?>

                <div class="gs-paginacao-info">
                    <label for="gs-itens-por-pagina"><?php echo esc_html__('Mostrar:', 'glossario-negocios'); ?></label>
                    <select id="gs-itens-por-pagina" aria-label="<?php echo esc_attr__('Quantidade de itens por página', 'glossario-negocios'); ?>">
                        <option value="10"><?php echo esc_html__('10 por página', 'glossario-negocios'); ?></option>
                        <option value="20"><?php echo esc_html__('20 por página', 'glossario-negocios'); ?></option>
                        <option value="100"><?php echo esc_html__('100 por página', 'glossario-negocios'); ?></option>
                        <option value="500"><?php echo esc_html__('500 por página', 'glossario-negocios'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Índice A-Z -->
            <nav class="gs-indice" aria-label="<?php echo esc_attr__('Índice alfabético', 'glossario-negocios'); ?>">
                <button type="button" data-letra="todos" class="gs-indice-item ativo"><?php echo esc_html__('Todos', 'glossario-negocios'); ?></button>
                <?php
                // Gerar índice A-Z
                foreach (range('A', 'Z') as $letra) {
                    echo '<button type="button" data-letra="' . strtolower($letra) . '" class="gs-indice-item">' . $letra . '</button>';
                }
                ?>
            </nav>

            <!-- Tabela de termos -->
            <div class="gs-tabela-wrapper" <?php echo ($total_itens === 0) ? 'style="display: none;"' : ''; ?>>
                <table class="gs-tabela">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 28%;"><?php echo esc_html__('Termo', 'glossario-negocios'); ?></th>
                            <th scope="col"><?php echo esc_html__('Definição', 'glossario-negocios'); ?></th>
                            <th scope="col" style="width: 22%;"><?php echo esc_html__('Categoria', 'glossario-negocios'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="gs-termos-lista">
                        <?php
                        if ($termos->have_posts()) {
                            while ($termos->have_posts()) {
                                $termos->the_post();

                                // Obter categorias
                                $categorias_termo = get_the_terms(get_the_ID(), 'categoria_glossario');
                                $categorias_html = '';

                                if (!empty($categorias_termo) && !is_wp_error($categorias_termo)) {
                                    foreach ($categorias_termo as $cat) {
                                        $categorias_html .= '<span class="gs-badge">' . esc_html(mb_strtolower($cat->name, 'UTF-8')) . '</span>';
                                    }
                                } else {
                                    $categorias_html = '<span class="gs-badge gs-badge-muted">' . esc_html__('sem categoria', 'glossario-negocios') . '</span>';
                                }
                                ?>
                                <tr class="gs-termo" data-id="<?php the_ID(); ?>">
                                    <td class="gs-termo-col-titulo">
                                        <strong><?php the_title(); ?></strong>
                                    </td>
                                    <td class="gs-termo-col-conteudo">
                                        <div class="gs-definicao-texto"><?php the_content(); ?></div>
                                    </td>
                                    <td class="gs-termo-col-categoria">
                                        <div class="gs-badges-wrapper"><?php echo $categorias_html; ?></div>
                                    </td>
                                </tr>
                                <?php
                            }
                            wp_reset_postdata();
                        } else {
                            ?>
                            <tr class="gs-sem-termos-tr">
                                <td colspan="3" class="gs-sem-resultados-linha">
                                    <div class="gs-empty-state">
                                        <span class="dashicons dashicons-book"></span>
                                        <p><?php echo esc_html__('Nenhum termo cadastrado no glossário de negócios até o momento.', 'glossario-negocios'); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div id="gs-paginacao" class="gs-paginacao" role="navigation" aria-label="<?php echo esc_attr__('Paginação do Glossário de Negócios', 'glossario-negocios'); ?>"></div>

            <!-- Mensagem de nenhum resultado encontrado (inicialmente oculta ou exibida se vazio) -->
            <div id="gs-sem-resultados" class="gs-sem-resultados" <?php echo ($total_itens > 0) ? 'style="display: none;"' : ''; ?>>
                <div class="gs-empty-state">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                    <h4><?php echo esc_html__('Nenhum termo encontrado', 'glossario-negocios'); ?></h4>
                    <p><?php echo esc_html__('Tente ajustar os termos de busca ou selecionar outra categoria.', 'glossario-negocios'); ?></p>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    } catch (Throwable $e) {
        error_log('Erro no shortcode glossario_lista: ' . $e->getMessage());
        return '<p class="gs-erro">' . esc_html__('Erro ao carregar o glossário de negócios. Por favor, tente novamente mais tarde.', 'glossario-negocios') . '</p>';
    }
}

/**
 * Endpoint AJAX para busca, filtragem e paginação server-side do shortcode [glossario_lista]
 */
function gs_ajax_consultar_termos_lista() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gs_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Erro de segurança. Token inválido.', 'glossario-negocios')));
            die();
        }

        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $itens_raw = isset($_POST['itens_por_pagina']) ? sanitize_text_field(wp_unslash($_POST['itens_por_pagina'])) : '10';

        $itens_permitidos = array(10, 20, 100, 500);
        if ($itens_raw === 'todos') {
            $posts_per_page = 500; // Compatibilidade legada
        } else {
            $posts_per_page = in_array((int)$itens_raw, $itens_permitidos, true) ? (int)$itens_raw : 10;
        }

        $args = array(
            'post_type'      => 'glossario',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $pagina,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Filtro de busca textual
        if (!empty($_POST['busca'])) {
            $args['s'] = sanitize_text_field(wp_unslash($_POST['busca']));
        }

        // Filtro de categoria
        if (!empty($_POST['categoria'])) {
            $categoria_slug = sanitize_title(wp_unslash($_POST['categoria']));
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria_glossario',
                    'field'    => 'slug',
                    'terms'    => $categoria_slug,
                ),
            );
        }

        // Filtro alfabético A-Z com suporte a acentuação em português
        $letra = isset($_POST['letra']) ? sanitize_text_field(wp_unslash($_POST['letra'])) : 'todos';
        $posts_where_filter = null;

        if (!empty($letra) && $letra !== 'todos' && preg_match('/^[a-z0-9]$/i', $letra)) {
            $letra_char = strtolower($letra);
            $variantes = array($letra_char);
            $mapa_acentos = array(
                'a' => array('á', 'à', 'ã', 'â'),
                'e' => array('é', 'ê'),
                'i' => array('í'),
                'o' => array('ó', 'ô', 'õ'),
                'u' => array('ú', 'ü'),
                'c' => array('ç'),
            );
            if (isset($mapa_acentos[$letra_char])) {
                $variantes = array_merge($variantes, $mapa_acentos[$letra_char]);
            }

            $posts_where_filter = function($where, $query) use ($variantes) {
                global $wpdb;
                if ($query->get('gs_filtro_letra')) {
                    $condicoes = array();
                    foreach ($variantes as $v) {
                        $condicoes[] = $wpdb->prepare("LOWER({$wpdb->posts}.post_title) LIKE %s", $wpdb->esc_like($v) . '%');
                    }
                    if (!empty($condicoes)) {
                        $where .= " AND (" . implode(' OR ', $condicoes) . ")";
                    }
                }
                return $where;
            };

            $args['gs_filtro_letra'] = true;
            add_filter('posts_where', $posts_where_filter, 10, 2);
        }

        $query = new WP_Query($args);

        if ($posts_where_filter !== null) {
            remove_filter('posts_where', $posts_where_filter, 10);
        }

        $total_itens = (int) $query->found_posts;
        $total_paginas = $posts_per_page > 0 ? (int) ceil($total_itens / $posts_per_page) : 1;

        $termos_lista = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $categorias_termo = get_the_terms(get_the_ID(), 'categoria_glossario');
                $cats = array();
                if (!empty($categorias_termo) && !is_wp_error($categorias_termo)) {
                    foreach ($categorias_termo as $c) {
                        $cats[] = array(
                            'name' => mb_strtolower($c->name, 'UTF-8'),
                            'slug' => $c->slug,
                        );
                    }
                }

                $conteudo = get_the_content();
                // Sanitização e formatação de parágrafos segura
                $conteudo_formatado = wp_kses_post(wpautop($conteudo));

                $termos_lista[] = array(
                    'id'         => get_the_ID(),
                    'titulo'     => get_the_title(),
                    'definicao'  => $conteudo_formatado,
                    'categorias' => $cats,
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'termos'           => $termos_lista,
            'total_itens'      => $total_itens,
            'total_paginas'    => $total_paginas,
            'pagina_atual'     => $pagina,
            'itens_por_pagina' => $itens_raw,
        ));
        die();
    } catch (Throwable $e) {
        gs_log_and_respond_error($e, __('Consultar termos da lista', 'glossario-negocios'));
    }
}

/**
 * Registra o shortcode e os endpoints AJAX
 */
function gs_registrar_shortcode_lista() {
    add_shortcode('glossario_lista', 'gs_shortcode_lista');
    add_action('wp_ajax_gs_consultar_termos_lista', 'gs_ajax_consultar_termos_lista');
    add_action('wp_ajax_nopriv_gs_consultar_termos_lista', 'gs_ajax_consultar_termos_lista');
}
add_action('init', 'gs_registrar_shortcode_lista');

