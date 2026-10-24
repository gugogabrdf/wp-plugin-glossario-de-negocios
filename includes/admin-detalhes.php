<?php
/**
 * Página administrativa de Instruções e Detalhes do Glossário de Negócios
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o submenu de Instruções e Detalhes no menu do Glossário de Negócios
 */
function gs_adicionar_submenu_detalhes() {
    try {
        add_submenu_page(
            'edit.php?post_type=glossario',
            __('Instruções e Detalhes', 'glossario-negocios'),
            __('Como Funciona', 'glossario-negocios'),
            'edit_posts',
            'glossario-detalhes',
            'gs_renderizar_pagina_detalhes'
        );
    } catch (Throwable $e) {
        error_log('Erro ao adicionar submenu de detalhes: ' . $e->getMessage());
    }
}
add_action('admin_menu', 'gs_adicionar_submenu_detalhes');

/**
 * Renderiza a página administrativa de Detalhes
 */
function gs_renderizar_pagina_detalhes() {
    ?>
    <div class="wrap gs-admin-wrap">
        <div class="gs-admin-header">
            <div class="gs-admin-badge">v<?php echo defined('GS_VERSAO') ? GS_VERSAO : '2.3.17'; ?></div>
            <h1><?php echo esc_html__('Glossário de Negócios — Instruções & Detalhes', 'glossario-negocios'); ?></h1>
            <p class="gs-admin-subtitle"><?php echo esc_html__('Guia de funcionamento, shortcodes, estrutura de dados e boas práticas para gerenciar seu glossário de negócios com excelência.', 'glossario-negocios'); ?></p>
        </div>

        <div class="gs-admin-grid">
            <!-- Card 1: Shortcodes Disponíveis -->
            <div class="gs-admin-card">
                <div class="gs-card-icon"><span class="dashicons dashicons-shortcode"></span></div>
                <h2><?php echo esc_html__('Shortcodes de Exibição & Gestão', 'glossario-negocios'); ?></h2>
                <p><?php echo esc_html__('O plugin disponibiliza shortcodes que podem ser inseridos em qualquer página ou post:', 'glossario-negocios'); ?></p>
                
                <div class="gs-code-block">
                    <code>[glossario_lista]</code>
                </div>
                <ul class="gs-feature-list">
                    <li><?php echo esc_html__('Exibe a tabela interativa do glossário de negócios no front-end.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Busca instantânea e insensível a acentos (encontra "comunicação" digitando "comunicacao").', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Filtro alfabético A-Z com suporte acentual fonético em português (a letra "A" inclui Á, À, Ã, Â).', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Filtro por categorias com padronização universal em letras minúsculas.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Paginação server-side de alto desempenho ajustável (10, 20, 100 ou 500 termos por página).', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Feedback visual com Skeleton Loading e animação shimmer no carregamento assíncrono.', 'glossario-negocios'); ?></li>
                </ul>

                <hr class="gs-card-divider" />

                <div class="gs-code-block">
                    <code>[glossario_cadastro]</code>
                </div>
                <ul class="gs-feature-list">
                    <li><?php echo esc_html__('Painel completo com abas para Cadastro Manual, Categorias, Gerenciar Termos, Importar CSV, Exportar CSV, Dashboard e Instruções.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Navegação acessível por teclado nas abas com suporte a leitores de tela (WCAG 2.1 AA via setas direcionais e roving tabindex).', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Modais nativos in-app para confirmação de exclusão e avisos, sem travamento do navegador.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Parâmetros opcionais:', 'glossario-negocios'); ?></strong>
                        <ul style="margin-top: 5px; list-style: circle; padding-left: 20px;">
                            <li><code>titulo="Meu Título"</code> &mdash; <?php echo esc_html__('Personaliza o título do formulário.', 'glossario-negocios'); ?></li>
                            <li><code>mostrar_importacao="sim"</code> ou <code>"nao"</code> &mdash; <?php echo esc_html__('Habilita ou oculta a aba de importação CSV.', 'glossario-negocios'); ?></li>
                            <li><code>mostrar_exportacao="sim"</code> ou <code>"nao"</code> &mdash; <?php echo esc_html__('Habilita ou oculta a aba de exportação CSV.', 'glossario-negocios'); ?></li>
                        </ul>
                    </li>
                </ul>

                <hr class="gs-card-divider" />

                <div class="gs-code-block">
                    <code>[glossario_dashboard]</code>
                </div>
                <ul class="gs-feature-list">
                    <li><?php echo esc_html__('Exibe exclusivamente o Dashboard de Estatísticas e Métricas em qualquer página ou post.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Cards de KPIs (Total de Termos, Categorias, Média por Categoria, Top Categoria e Cadastros no Período).', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Filtros temporais inteligentes com atalhos rápidos (Hoje, 7 dias, 30 dias, Ano, Tudo) e intervalo personalizado DD/MM/AAAA.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Gráficos de distribuição percentual por categoria e listagem dos últimos termos cadastrados.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Cache de alto rendimento via Transients API (10 minutos) com invalidação automática em tempo real.', 'glossario-negocios'); ?></li>
                    <li><?php echo esc_html__('Disponível também no menu administrativo em Glossário de Negócios > Dashboard.', 'glossario-negocios'); ?></li>
                </ul>
            </div>

            <!-- Card 2: Como Cadastrar e Estrutura -->
            <div class="gs-admin-card">
                <div class="gs-card-icon"><span class="dashicons dashicons-edit"></span></div>
                <h2><?php echo esc_html__('Cadastro & Gerenciamento', 'glossario-negocios'); ?></h2>
                
                <h3><?php echo esc_html__('Campos do Termo', 'glossario-negocios'); ?></h3>
                <ul class="gs-feature-list">
                    <li><strong><?php echo esc_html__('Termo:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('O nome da palavra, conceito, sigla ou expressão a definir.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Definição:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('O significado completo do termo. Suporta parágrafos e formatação rica com sanitização segura.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Categorias (Obrigatório):', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Todo termo deve pertencer a pelo menos uma categoria. Permite seleção múltipla segurando Ctrl ou Cmd.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Padronização em Minúsculo:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('As categorias são tratadas e exibidas obrigatoriamente em minúsculas para consistência e organização visual.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Unicidade por Categoria:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('O mesmo termo é permitido em categorias distintas, evitando homônimos na mesma categoria.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Referência (Opcional):', 'glossario-negocios'); ?></strong> <?php echo esc_html__('URL externa de fonte ou consulta, acessível no painel administrativo e exportação CSV.', 'glossario-negocios'); ?></li>
                </ul>

                <h3><?php echo esc_html__('Permissões de Usuários', 'glossario-negocios'); ?></h3>
                <ul class="gs-feature-list">
                    <li><strong><?php echo esc_html__('Adicionar/Editar Termos:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Usuários com capacidade publish_posts (Autores, Editores e Administradores).', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Gerenciar Categorias:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Editores e Administradores (capacidade manage_categories).', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Visitantes / Não autenticados:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Visualizam a lista pública de termos com busca acento-insensível e paginação server-side.', 'glossario-negocios'); ?></li>
                </ul>
            </div>

            <!-- Card 3: Importação e Exportação CSV -->
            <div class="gs-admin-card">
                <div class="gs-card-icon"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                <h2><?php echo esc_html__('Importação & Exportação CSV', 'glossario-negocios'); ?></h2>
                <p><?php echo esc_html__('Importe ou faça backup de listas de termos com segurança e rapidez.', 'glossario-negocios'); ?></p>
                
                <h3><?php echo esc_html__('Estrutura do Arquivo CSV', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('O arquivo CSV deve conter um cabeçalho com as seguintes colunas:', 'glossario-negocios'); ?></p>
                <div class="gs-code-block">
                    <code>termo,definicao,categoria,referencia</code>
                </div>
                
                <ul class="gs-feature-list">
                    <li><strong><?php echo esc_html__('Separadores aceitos:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Vírgula (,) ou ponto e vírgula (;), detectados automaticamente.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Codificação:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('UTF-8 (com ou sem BOM).', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Coluna de referência:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Opcional. Se não informada, a coluna pode ficar vazia.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Validação & Relatório:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Categorias inexistentes são criadas automaticamente em minúsculas; termos duplicados na mesma categoria são prevenidos e reportados com linha e motivo.', 'glossario-negocios'); ?></li>
                    <li><strong><?php echo esc_html__('Modelo pronto:', 'glossario-negocios'); ?></strong> <?php echo esc_html__('Baixe o arquivo modelo na aba "Importar CSV" do painel de cadastro.', 'glossario-negocios'); ?></li>
                </ul>
            </div>

            <!-- Card 4: Segurança & Manutenção -->
            <div class="gs-admin-card">
                <div class="gs-card-icon"><span class="dashicons dashicons-shield"></span></div>
                <h2><?php echo esc_html__('Segurança & Solução de Problemas', 'glossario-negocios'); ?></h2>
                
                <h3><?php echo esc_html__('Isolamento de Erros & Proteção', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Todas as operações do plugin são encapsuladas em blocos de proteção (try-catch Throwable) para assegurar estabilidade contínua sem quebrar o WordPress.', 'glossario-negocios'); ?></p>
                
                <h3><?php echo esc_html__('Segurança CSRF & Sanitização XSS', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Todas as rotas AJAX exigem tokens de segurança criptográficos (wp_verify_nonce) e todas as saídas no front-end passam por higienização estrita com funções de escape do WordPress.', 'glossario-negocios'); ?></p>

                <h3><?php echo esc_html__('Desativação de Emergência', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Se precisar desativar as rotinas do plugin sem acessar o painel administrativo, adicione no wp-config.php:', 'glossario-negocios'); ?></p>
                <div class="gs-code-block">
                    <code>define('GS_GLOSSARIO_DISABLED', true);</code>
                </div>

                <h3><?php echo esc_html__('Diagnóstico & Logs', 'glossario-negocios'); ?></h3>
                <p><?php echo esc_html__('Administradores recebem mensagens detalhadas de erro via console e respostas JSON em caso de inconsistência, registradas também em debug.log.', 'glossario-negocios'); ?></p>
            </div>
        </div>
    </div>

    <style>
        .gs-admin-wrap {
            max-width: 1100px;
            margin: 25px 20px 40px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .gs-admin-header {
            background: #ffffff;
            padding: 28px 32px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .gs-admin-badge {
            position: absolute;
            top: 28px;
            right: 32px;
            background: #f1f5f9;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
        }
        .gs-admin-header h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
        }
        .gs-admin-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 15px;
            line-height: 1.5;
        }
        .gs-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 24px;
        }
        .gs-admin-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
        }
        .gs-card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 16px;
            color: #334155;
        }
        .gs-card-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .gs-admin-card h2 {
            margin: 0 0 14px 0;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }
        .gs-admin-card h3 {
            margin: 18px 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .gs-admin-card p {
            margin: 0 0 12px 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
        }
        .gs-feature-list {
            margin: 0 0 14px 0;
            padding-left: 18px;
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
        }
        .gs-feature-list li {
            margin-bottom: 6px;
        }
        .gs-code-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 10px 0 14px 0;
        }
        .gs-code-block code {
            background: transparent;
            color: #0f172a;
            font-size: 13px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 600;
        }
        .gs-card-divider {
            border: 0;
            border-top: 1px solid #f1f5f9;
            margin: 16px 0;
        }
        @media (max-width: 600px) {
            .gs-admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
}
