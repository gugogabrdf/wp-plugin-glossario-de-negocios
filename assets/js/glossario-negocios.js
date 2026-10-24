/**
 * Scripts para o plugin Glossário de Negócios
 * Design neutro, adaptativo, acessível e refinado (v2.3.17)
 */

(function($) {
    'use strict';

    /**
     * Codifica caracteres especiais para entidades HTML seguras
     */
    function gsEscapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Normaliza strings para comparações insensíveis a acentos e maiúsculas
     */
    function gsNormalizarTexto(str) {
        if (!str) return '';
        return str
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    /**
     * Utilitário de Debounce para entradas do usuário
     */
    function gsDebounce(fn, delay) {
        let timer = null;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, delay);
        };
    }

    /**
     * Helper para logar erros do servidor no console
     * Admin recebe detalhes completos — outros recebem mensagem genérica
     */
    function gsLogServerError(contexto, data) {
        if (typeof data === 'object' && data !== null) {
            console.error('[GS] ' + contexto + ':', data.message);
            if (data.file) console.error('[GS] Arquivo:', data.file + ':' + data.line);
            if (data.trace) console.error('[GS] Trace:', data.trace);
            console.error('[GS] Log completo: wp-content/debug.log');
        } else {
            console.error('[GS] ' + contexto + ':', data);
            console.error('[GS] Log completo: wp-content/debug.log');
        }
    }

    /**
     * Modal de confirmação in-app (substitui o confirm nativo)
     */
    function gsConfirm(mensagem, onConfirm, titulo) {
        $('.gs-dialog-overlay').remove();
        titulo = titulo || 'Confirmar Ação';

        const dialog = $(
            '<div class="gs-modal-overlay gs-dialog-overlay" role="dialog" aria-modal="true">' +
                '<div class="gs-modal gs-dialog-modal">' +
                    '<h3></h3>' +
                    '<p class="gs-dialog-texto"></p>' +
                    '<div class="gs-botoes-modal">' +
                        '<button type="button" class="gs-btn-cancelar-modal gs-dialog-cancelar">Cancelar</button>' +
                        '<button type="button" class="gs-btn-excluir gs-dialog-confirmar">Confirmar</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        dialog.find('h3').text(titulo);
        dialog.find('.gs-dialog-texto').text(mensagem || '');

        $('body').append(dialog);

        dialog.find('.gs-dialog-cancelar').on('click', function() {
            dialog.fadeOut(150, function() { $(this).remove(); });
        });

        dialog.find('.gs-dialog-confirmar').on('click', function() {
            dialog.fadeOut(150, function() { $(this).remove(); });
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        // Fechar no clique fora do modal
        dialog.on('click', function(e) {
            if ($(e.target).is('.gs-dialog-overlay')) {
                dialog.fadeOut(150, function() { $(this).remove(); });
            }
        });
    }

    /**
     * Modal de aviso in-app (substitui o alert nativo)
     */
    function gsAlert(mensagem, titulo) {
        $('.gs-dialog-overlay').remove();
        titulo = titulo || 'Aviso';

        const dialog = $(
            '<div class="gs-modal-overlay gs-dialog-overlay" role="dialog" aria-modal="true">' +
                '<div class="gs-modal gs-dialog-modal">' +
                    '<h3></h3>' +
                    '<p class="gs-dialog-texto"></p>' +
                    '<div class="gs-botoes-modal">' +
                        '<button type="button" class="gs-form-submit gs-dialog-fechar">OK</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        dialog.find('h3').text(titulo);
        dialog.find('.gs-dialog-texto').text(mensagem || '');

        $('body').append(dialog);

        dialog.find('.gs-dialog-fechar').focus().on('click', function() {
            dialog.fadeOut(150, function() { $(this).remove(); });
        });

        dialog.on('click', function(e) {
            if ($(e.target).is('.gs-dialog-overlay')) {
                dialog.fadeOut(150, function() { $(this).remove(); });
            }
        });
    }

    // Expor globalmente para manter compatibilidade
    window.gsEscapeHtml = gsEscapeHtml;
    window.gsConfirm = gsConfirm;
    window.gsAlert = gsAlert;

    /**
     * Função global para alternar abas com suporte a WAI-ARIA
     */
    window.gsAbrirAba = function(evt, nomeAba) {
        $('.gs-tab-content').removeClass('active');
        $('.gs-tab')
            .removeClass('active')
            .attr('aria-selected', 'false')
            .attr('tabindex', '-1');

        $('#' + nomeAba).addClass('active');

        const $ativa = (evt && evt.currentTarget) ? $(evt.currentTarget) : $('[data-tab="' + nomeAba + '"]');
        $ativa
            .addClass('active')
            .attr('aria-selected', 'true')
            .attr('tabindex', '0');
    };

    /**
     * Inicialização no DOM Ready
     */
    $(document).ready(function() {
        // Inicializar lista pública
        if ($('.gs-glossario-container').length) {
            initGlossarioLista();
        }

        // Inicializar formulário de cadastro e abas
        if ($('.gs-form-container').length) {
            initGlossarioCadastro();
            initImportacaoCSV();
            initExportacaoCSV();
            initGerenciarCategorias();
            initGerenciarTermos();
            initDownloadModeloCSV();
            initNavegacaoAbas();
        }

        // Inicializar Dashboard se presente (admin, shortcode ou aba)
        if ($('.gs-dashboard-container').length) {
            initDashboard();
        }

        // Listener global para fechar modais com tecla Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('.gs-modal-overlay').fadeOut(150, function() {
                    $(this).remove();
                });
            }
        });
    });

    /**
     * Inicializa a navegação entre abas com suporte a teclado (WCAG / WAI-ARIA)
     */
    function initNavegacaoAbas() {
        // Clique nas abas
        $(document).on('click', '.gs-tab', function(e) {
            e.preventDefault();
            const targetId = $(this).data('tab');
            if (targetId) {
                window.gsAbrirAba(e, targetId);

                // Carregamento reativo com skeleton ao alternar para as abas de gerenciamento
                if (targetId === 'gs-aba-categorias' && !$('#gs-lista-categorias .gs-item-gerenciar:not(.gs-skeleton-item)').length) {
                    carregarCategorias();
                } else if (targetId === 'gs-aba-termos' && !$('#gs-lista-termos-gerenciar .gs-item-gerenciar:not(.gs-skeleton-item)').length) {
                    carregarTermosGerenciar();
                }
            }
        });

        // Navegação por setas direcionais e atalhos de teclado (WAI-ARIA Tabs Pattern)
        $(document).on('keydown', '.gs-tabs-nav .gs-tab', function(e) {
            const tabs = $(this).closest('.gs-tabs-nav').find('.gs-tab:visible');
            const currentIndex = tabs.index(this);
            let nextIndex = null;

            switch (e.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    nextIndex = (currentIndex + 1) % tabs.length;
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                    break;
                case 'Home':
                    e.preventDefault();
                    nextIndex = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    nextIndex = tabs.length - 1;
                    break;
                default:
                    return;
            }

            if (nextIndex !== null) {
                const nextTab = tabs.eq(nextIndex);
                nextTab.trigger('click').focus();
            }
        });
    }

    /**
     * Inicializa a lista pública de termos com filtros e paginação server-side
     */
    function initGlossarioLista() {
        const container = $('.gs-glossario-container');
        if (!container.length) return;

        const busca = $('#gs-busca');
        const filtroCategoria = $('#gs-filtro-categoria');
        const indiceLetras = $('.gs-indice-item');
        const semResultados = $('#gs-sem-resultados');
        const tabelaWrapper = $('.gs-tabela-wrapper');
        const tabelaBody = $('#gs-termos-lista');
        const itensPorPaginaSelect = $('#gs-itens-por-pagina');
        const paginacaoContainer = $('#gs-paginacao');

        // Estado ativo de filtros e paginação server-side
        let filtroAtivo = {
            busca: '',
            categoria: '',
            letra: 'todos',
            pagina: 1,
            itensPorPagina: '10'
        };

        // Valores iniciais extraídos dos data-attributes do container
        let totalItens = parseInt(container.data('totalItens'), 10) || 0;
        let totalPaginas = parseInt(container.data('totalPaginas'), 10) || 1;
        let requisicaoAtiva = null;

        // Renderizador dos botões e navegação da paginação
        function atualizarPaginacao(paginas, total, paginaAtual, itensPorPagina) {
            paginacaoContainer.empty();

            if (total <= 0) {
                return;
            }

            const itensNum = itensPorPagina === 'todos' ? total : parseInt(itensPorPagina, 10);
            if (paginas <= 1 && total <= itensNum) {
                const infoTexto = $('<span>', {
                    class: 'gs-paginacao-info-texto',
                    text: 'Mostrando ' + total + ' termo(s)'
                });
                paginacaoContainer.append(infoTexto);
                return;
            }

            // Botão Anterior
            const btnAnterior = $('<button>', {
                class: 'gs-pagina-btn' + (paginaAtual === 1 ? ' gs-pagina-disabled' : ''),
                text: '« Anterior',
                disabled: paginaAtual === 1,
                'aria-label': 'Página anterior'
            }).on('click', function(e) {
                e.preventDefault();
                if (paginaAtual > 1) {
                    mudarPagina(paginaAtual - 1);
                }
            });
            paginacaoContainer.append(btnAnterior);

            // Adiciona botão numérico de página
            function adicionarBtnPagina(num) {
                const btn = $('<button>', {
                    class: 'gs-pagina-btn' + (num === paginaAtual ? ' active' : ''),
                    text: num,
                    'aria-label': 'Página ' + num
                }).on('click', function(e) {
                    e.preventDefault();
                    if (num !== paginaAtual) {
                        mudarPagina(num);
                    }
                });
                paginacaoContainer.append(btn);
            }

            // Adiciona reticências (...)
            function adicionarEllipsis() {
                $('<span>', {
                    class: 'gs-pagina-ellipsis',
                    text: '…',
                    'aria-hidden': 'true'
                }).appendTo(paginacaoContainer);
            }

            // Algoritmo de paginação deslizante com reticências para milhares de páginas
            if (paginas <= 7) {
                for (let i = 1; i <= paginas; i++) {
                    adicionarBtnPagina(i);
                }
            } else {
                adicionarBtnPagina(1);

                if (paginaAtual > 4) {
                    adicionarEllipsis();
                }

                const inicio = Math.max(2, paginaAtual - 1);
                const fim = Math.min(paginas - 1, paginaAtual + 1);

                for (let i = inicio; i <= fim; i++) {
                    adicionarBtnPagina(i);
                }

                if (paginaAtual < paginas - 3) {
                    adicionarEllipsis();
                }

                adicionarBtnPagina(paginas);
            }

            // Botão Próximo
            const btnProximo = $('<button>', {
                class: 'gs-pagina-btn' + (paginaAtual === paginas ? ' gs-pagina-disabled' : ''),
                text: 'Próximo »',
                disabled: paginaAtual === paginas,
                'aria-label': 'Próxima página'
            }).on('click', function(e) {
                e.preventDefault();
                if (paginaAtual < paginas) {
                    mudarPagina(paginaAtual + 1);
                }
            });
            paginacaoContainer.append(btnProximo);

            // Texto informativo de resumo
            const inicioItem = (paginaAtual - 1) * itensNum + 1;
            const fimItem = Math.min(paginaAtual * itensNum, total);
            const info = $('<span>', {
                class: 'gs-paginacao-info-texto',
                text: 'Mostrando ' + inicioItem + '–' + fimItem + ' de ' + total + ' termos'
            });
            paginacaoContainer.append(info);
        }

        // Renderiza linhas skeleton com efeito shimmer durante carregamento da tabela
        function renderSkeletonTabela() {
            let html = '';
            for (let i = 0; i < 5; i++) {
                html += '<tr class="gs-skeleton-row" aria-hidden="true">' +
                    '<td><span class="gs-skeleton gs-skeleton-text" style="width: 70%;"></span></td>' +
                    '<td><span class="gs-skeleton gs-skeleton-text" style="width: 90%; margin-bottom: 6px;"></span><span class="gs-skeleton gs-skeleton-text" style="width: 50%;"></span></td>' +
                    '<td><span class="gs-skeleton gs-skeleton-badge" style="width: 80px;"></span></td>' +
                '</tr>';
            }
            return html;
        }

        // Renderiza termos retornados pelo servidor de forma segura no DOM
        function renderizarTermos(termos) {
            tabelaBody.empty();

            termos.forEach(function(termo) {
                const $tr = $('<tr>', { class: 'gs-termo', 'data-id': termo.id });

                const $tdTitulo = $('<td>', { class: 'gs-termo-col-titulo' }).appendTo($tr);
                $('<strong>', { text: termo.titulo }).appendTo($tdTitulo);

                const $tdConteudo = $('<td>', { class: 'gs-termo-col-conteudo' }).appendTo($tr);
                const $defDiv = $('<div>', { class: 'gs-definicao-texto' }).appendTo($tdConteudo);
                $defDiv.html(termo.definicao);

                const $tdCat = $('<td>', { class: 'gs-termo-col-categoria' }).appendTo($tr);
                const $badgesWrapper = $('<div>', { class: 'gs-badges-wrapper' }).appendTo($tdCat);

                if (termo.categorias && termo.categorias.length > 0) {
                    termo.categorias.forEach(function(cat) {
                        $('<span>', {
                            class: 'gs-badge',
                            text: (cat.name || '').toLowerCase()
                        }).appendTo($badgesWrapper);
                    });
                } else {
                    $('<span>', {
                        class: 'gs-badge gs-badge-muted',
                        text: 'sem categoria'
                    }).appendTo($badgesWrapper);
                }

                tabelaBody.append($tr);
            });
        }

        // Executa a requisição assíncrona para consultar termos no servidor
        function consultarServidor(rolarParaTabela) {
            // Cancelar requisição anterior se ainda estiver em andamento
            if (requisicaoAtiva && requisicaoAtiva.readyState !== 4) {
                requisicaoAtiva.abort();
            }

            // Exibir placeholder com efeito shimmer
            tabelaBody.html(renderSkeletonTabela());
            tabelaWrapper.show();
            semResultados.hide();

            requisicaoAtiva = $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gs_consultar_termos_lista',
                    nonce: gs_ajax.nonce,
                    busca: filtroAtivo.busca,
                    categoria: filtroAtivo.categoria,
                    letra: filtroAtivo.letra,
                    pagina: filtroAtivo.pagina,
                    itens_por_pagina: filtroAtivo.itensPorPagina
                },
                success: function(response) {
                    requisicaoAtiva = null;
                    if (response.success && response.data) {
                        const data = response.data;
                        totalItens = data.total_itens;
                        totalPaginas = data.total_paginas;

                        if (totalItens > 0 && data.termos && data.termos.length > 0) {
                            renderizarTermos(data.termos);
                            tabelaWrapper.show();
                            semResultados.hide();
                            paginacaoContainer.show();
                            atualizarPaginacao(totalPaginas, totalItens, filtroAtivo.pagina, filtroAtivo.itensPorPagina);
                        } else {
                            tabelaBody.empty();
                            tabelaWrapper.hide();
                            paginacaoContainer.hide();
                            semResultados.show();
                        }

                        if (rolarParaTabela) {
                            const offset = tabelaWrapper.offset();
                            if (offset && $(window).scrollTop() > offset.top - 80) {
                                $('html, body').animate({ scrollTop: offset.top - 80 }, 200);
                            }
                        }
                    } else {
                        tabelaBody.html('<tr><td colspan="3" class="gs-empty-state"><p>Erro ao carregar os termos.</p></td></tr>');
                    }
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    requisicaoAtiva = null;
                    tabelaBody.html('<tr><td colspan="3" class="gs-empty-state"><p>Erro de comunicação com o servidor.</p></td></tr>');
                }
            });
        }

        // Navegar para outra página
        function mudarPagina(novaPagina) {
            filtroAtivo.pagina = novaPagina;
            consultarServidor(true);
        }

        // Event listeners com Debounce para busca textual
        busca.on('input', gsDebounce(function() {
            filtroAtivo.busca = $.trim($(this).val());
            filtroAtivo.pagina = 1;
            consultarServidor(false);
        }, 250));

        // Filtro por categoria
        filtroCategoria.on('change', function() {
            filtroAtivo.categoria = $(this).val();
            filtroAtivo.pagina = 1;
            consultarServidor(false);
        });

        // Índice alfabético A-Z
        indiceLetras.on('click', function(e) {
            e.preventDefault();
            indiceLetras.removeClass('ativo');
            $(this).addClass('ativo');
            filtroAtivo.letra = $(this).data('letra') || 'todos';
            filtroAtivo.pagina = 1;
            consultarServidor(false);
        });

        // Seletor de itens por página
        itensPorPaginaSelect.on('change', function() {
            filtroAtivo.itensPorPagina = $(this).val();
            filtroAtivo.pagina = 1;
            consultarServidor(false);
        });

        // Inicializar controles de paginação na carga inicial sem requisição redundante
        atualizarPaginacao(totalPaginas, totalItens, 1, filtroAtivo.itensPorPagina);
    }

    /**
     * Inicializa a funcionalidade do formulário de cadastro
     */
    function initGlossarioCadastro() {
        const form = $('#gs-cadastro-form');
        const carregando = $('#gs-carregando');

        form.on('submit', function(e) {
            e.preventDefault();

            const campoTermo = $('#gs-titulo');
            const campoDefinicao = $('#gs-definicao');
            const campoCategorias = $('#gs-categorias');

            campoTermo.removeClass('gs-field-error');
            campoDefinicao.removeClass('gs-field-error');
            campoCategorias.removeClass('gs-field-error');

            const termo = campoTermo.val().trim();
            const definicao = campoDefinicao.val().trim();
            const categorias = campoCategorias.val();

            if (!termo) {
                campoTermo.addClass('gs-field-error').focus();
                mostrarMensagem('O campo Termo é obrigatório.', 'erro');
                return;
            }

            if (!definicao) {
                campoDefinicao.addClass('gs-field-error').focus();
                mostrarMensagem('O campo Definição é obrigatório.', 'erro');
                return;
            }

            if (!categorias || categorias.length === 0) {
                campoCategorias.addClass('gs-field-error').focus();
                mostrarMensagem('Selecione pelo menos uma categoria para o termo.', 'erro');
                return;
            }

            carregando.show();

            const formData = new FormData(this);
            formData.append('action', 'gs_salvar_termo');
            formData.append('nonce', $('#gs_nonce').val());

            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    carregando.hide();

                    if (response.success) {
                        mostrarMensagem(response.data.message, 'sucesso');
                        form[0].reset();
                    } else {
                        mostrarMensagem(typeof response.data === 'object' ? response.data.message : response.data, 'erro');
                        gsLogServerError('Cadastro', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    carregando.hide();
                    mostrarMensagem('Erro de conexão ao processar requisição.', 'erro');
                    console.error('[GS] Cadastro: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        });
    }

    /**
     * Inicializa importação CSV
     */
    function initImportacaoCSV() {
        const importForm = $('#gs-importacao-form');
        const carregandoImport = $('#gs-carregando-import');

        if (!importForm.length) return;

        importForm.on('submit', function(e) {
            e.preventDefault();

            const fileInput = $('#gs-csv-file');
            fileInput.removeClass('gs-field-error');

            const file = fileInput[0].files[0];
            if (!file) {
                fileInput.addClass('gs-field-error').focus();
                mostrarMensagem('Por favor, selecione um arquivo CSV.', 'erro');
                return;
            }

            if (file.size > 5242880) {
                fileInput.addClass('gs-field-error');
                mostrarMensagem('O arquivo é muito grande. O limite é 5MB.', 'erro');
                return;
            }

            if (!file.name.toLowerCase().endsWith('.csv')) {
                fileInput.addClass('gs-field-error');
                mostrarMensagem('Apenas arquivos no formato .csv são aceitos.', 'erro');
                return;
            }

            carregandoImport.show();

            const formData = new FormData();
            formData.append('action', 'gs_importar_csv');
            formData.append('csv_file', file);
            formData.append('nonce', $('#gs_import_nonce').val());

            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    carregandoImport.hide();

                    // Limpar relatório anterior se houver
                    $('#gs-import-detalhes-log').remove();

                    if (response.success) {
                        mostrarMensagem(response.data.message, 'sucesso');
                        importForm[0].reset();

                        // Se houver detalhes de termos ignorados/duplicados, exibir na tela de forma segura
                        if (response.data.detalhes_ignorados && response.data.detalhes_ignorados.length > 0) {
                            const $logContainer = $('<div>', {
                                id: 'gs-import-detalhes-log',
                                class: 'gs-csv-log-detalhes'
                            });
                            $('<h4>', {
                                text: 'Observações da Importação (' + response.data.detalhes_ignorados.length + ' item(ns)):'
                            }).appendTo($logContainer);

                            const $ul = $('<ul>').appendTo($logContainer);
                            response.data.detalhes_ignorados.forEach(function(item) {
                                $('<li>', { text: item }).appendTo($ul);
                            });

                            importForm.after($logContainer);
                        }
                    } else {
                        mostrarMensagem(typeof response.data === 'object' ? response.data.message : response.data, 'erro');
                        gsLogServerError('Importação CSV', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    carregandoImport.hide();
                    mostrarMensagem('Erro de conexão ao processar a importação.', 'erro');
                    console.error('[GS] Importação CSV: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        });
    }

    /**
     * Inicializa exportação CSV
     */
    function initExportacaoCSV() {
        const btnExportar = $('#gs-btn-exportar');
        const carregandoExport = $('#gs-carregando-export');

        if (!btnExportar.length) return;

        btnExportar.on('click', function(e) {
            e.preventDefault();
            carregandoExport.show();

            const form = $('<form>', {
                method: 'POST',
                action: gs_ajax.ajax_url
            }).css('display', 'none');

            $('<input>', { type: 'hidden', name: 'action', value: 'gs_exportar_csv' }).appendTo(form);
            $('<input>', { type: 'hidden', name: 'nonce', value: $('#gs_import_nonce').val() }).appendTo(form);

            form.appendTo('body').submit();

            setTimeout(function() {
                form.remove();
                carregandoExport.hide();
            }, 2000);
        });
    }

    /**
     * Inicializa download do modelo CSV
     */
    function initDownloadModeloCSV() {
        const btnModelo = $('#gs-btn-download-modelo');
        if (!btnModelo.length) return;

        btnModelo.on('click', function(e) {
            e.preventDefault();
            window.location.href = gs_ajax.ajax_url + '?action=gs_download_modelo_csv&nonce=' + $('#gs_import_nonce').val();
        });
    }

    /**
     * Inicializa gerenciamento de categorias
     */
    function initGerenciarCategorias() {
        const form = $('#gs-categoria-form');
        if (!form.length) return;

        carregarCategorias();

        form.on('submit', function(e) {
            e.preventDefault();

            const campoNovaCat = $('#gs-nova-categoria');
            campoNovaCat.removeClass('gs-field-error');

            const nome = campoNovaCat.val().trim().toLowerCase();
            if (!nome) {
                campoNovaCat.addClass('gs-field-error').focus();
                return;
            }

            const carregando = $('#gs-carregando-categoria');
            carregando.show();

            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gs_salvar_categoria',
                    nome: nome,
                    nonce: $('#gs_nonce_categoria').val()
                },
                success: function(response) {
                    carregando.hide();
                    if (response.success) {
                        campoNovaCat.val('');
                        mostrarMensagem(response.data.message, 'sucesso');
                        carregarCategorias();
                    } else {
                        mostrarMensagem(typeof response.data === 'object' ? response.data.message : response.data, 'erro');
                        gsLogServerError('Categoria', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    carregando.hide();
                    mostrarMensagem('Erro ao salvar categoria.', 'erro');
                    console.error('[GS] Categoria: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        });

        // Delegação de eventos para botões de editar e excluir categoria
        $(document).on('click', '.gs-btn-editar-cat', function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            editarCategoriaInline(id, nome);
        });

        $(document).on('click', '.gs-btn-excluir-cat', function() {
            const id = $(this).data('id');
            excluirCategoria(id);
        });
    }

    /**
     * Renderiza placeholders com efeito shimmer para carregamento de categorias
     */
    function renderSkeletonCategorias() {
        let html = '';
        const widths = [130, 180, 110, 160];
        for (let i = 0; i < 4; i++) {
            html += '<div class="gs-item-gerenciar gs-skeleton-item" aria-hidden="true">' +
                '<div class="gs-item-info">' +
                    '<span class="gs-skeleton gs-skeleton-text" style="width: ' + widths[i] + 'px;"></span>' +
                    '<span class="gs-skeleton gs-skeleton-badge" style="width: 75px; margin-top: 4px;"></span>' +
                '</div>' +
                '<div class="gs-botoes-acao">' +
                    '<span class="gs-skeleton gs-skeleton-btn" style="width: 48px;"></span>' +
                    '<span class="gs-skeleton gs-skeleton-btn" style="width: 48px;"></span>' +
                '</div>' +
            '</div>';
        }
        return html;
    }

    /**
     * Carrega a lista de categorias
     */
    function carregarCategorias() {
        const listaContainer = $('#gs-lista-categorias');
        if (!listaContainer.length) return;

        listaContainer.html(renderSkeletonCategorias());

        $.ajax({
            url: gs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gs_listar_categorias',
                nonce: $('#gs_nonce_categoria').val()
            },
            success: function(response) {
                listaContainer.empty();
                if (response.success) {
                    if (!response.data.categorias || response.data.categorias.length === 0) {
                        listaContainer.html('<div class="gs-empty-state"><p>Nenhuma categoria encontrada.</p></div>');
                        return;
                    }

                    response.data.categorias.forEach(function(cat) {
                        const catNomeLower = (cat.name || '').toLowerCase();
                        const catId = cat.id;
                        const catCount = parseInt(cat.count, 10) || 0;

                        const $item = $('<div>', {
                            class: 'gs-item-gerenciar',
                            'data-id': catId
                        });

                        const $info = $('<div>', { class: 'gs-item-info' }).appendTo($item);
                        $('<span>', { class: 'gs-item-nome', text: catNomeLower }).appendTo($info);
                        $('<span>', { class: 'gs-item-categoria', text: catCount + ' termo(s) vinculados' }).appendTo($info);

                        const $botoes = $('<div>', { class: 'gs-botoes-acao' }).appendTo($item);
                        $('<button>', {
                            type: 'button',
                            class: 'gs-btn-editar gs-btn-editar-cat',
                            'data-id': catId,
                            'data-nome': catNomeLower,
                            text: 'Editar'
                        }).appendTo($botoes);

                        $('<button>', {
                            type: 'button',
                            class: 'gs-btn-excluir gs-btn-excluir-cat',
                            'data-id': catId,
                            text: 'Excluir'
                        }).appendTo($botoes);

                        listaContainer.append($item);
                    });
                } else {
                    listaContainer.html('<div class="gs-empty-state"><p>Erro ao carregar categorias.</p></div>');
                }
            },
            error: function(xhr, status, error) {
                listaContainer.html('<div class="gs-empty-state"><p>Erro ao carregar categorias. Tente novamente.</p></div>');
                console.error('[GS] Listar categorias: Erro AJAX:', status, error);
            }
        });
    }

    /**
     * Edição inline de categoria
     */
    function editarCategoriaInline(id, nomeAtual) {
        const container = $('[data-id="' + id + '"]');
        const nomeSpan = container.find('.gs-item-nome');
        const botoesDiv = container.find('.gs-botoes-acao');

        const catNomeAtualLower = (nomeAtual || '').toLowerCase();

        nomeSpan.empty();
        $('<input>', {
            type: 'text',
            class: 'gs-input-editar',
            value: catNomeAtualLower
        }).appendTo(nomeSpan);

        botoesDiv.empty();
        $('<button>', {
            type: 'button',
            class: 'gs-btn-salvar gs-btn-salvar-cat',
            'data-id': id,
            text: 'Salvar'
        }).appendTo(botoesDiv);

        $('<button>', {
            type: 'button',
            class: 'gs-btn-cancelar gs-btn-cancelar-cat',
            'data-id': id,
            'data-nome': catNomeAtualLower,
            text: 'Cancelar'
        }).appendTo(botoesDiv);
    }

    // Salvar e Cancelar Edição Inline de Categoria
    $(document).on('click', '.gs-btn-salvar-cat', function() {
        const id = $(this).data('id');
        const container = $('[data-id="' + id + '"]');
        const novoNome = container.find('.gs-input-editar').val().trim().toLowerCase();

        if (!novoNome) {
            gsAlert('O nome da categoria não pode ficar vazio.');
            return;
        }

        $.ajax({
            url: gs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gs_editar_categoria',
                id: id,
                nome: novoNome,
                nonce: $('#gs_nonce_categoria').val()
            },
            success: function(response) {
                if (response.success) {
                    carregarCategorias();
                    mostrarMensagem(response.data.message, 'sucesso');
                } else {
                    gsAlert(typeof response.data === 'object' ? response.data.message : response.data);
                    gsLogServerError('Editar categoria', response.data);
                }
            },
            error: function(xhr, status, error) {
                gsAlert('Erro ao editar categoria.');
                console.error('[GS] Editar categoria: Erro AJAX:', status, error, xhr.responseText);
            }
        });
    });

    $(document).on('click', '.gs-btn-cancelar-cat', function() {
        carregarCategorias();
    });

    /**
     * Excluir categoria usando diálogo in-app
     */
    function excluirCategoria(id) {
        gsConfirm('Tem certeza que deseja excluir esta categoria? Ela só será removida se não possuir termos vinculados.', function() {
            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gs_excluir_categoria',
                    id: id,
                    nonce: $('#gs_nonce_categoria').val()
                },
                success: function(response) {
                    if (response.success) {
                        carregarCategorias();
                        mostrarMensagem(response.data.message, 'sucesso');
                    } else {
                        gsAlert(typeof response.data === 'object' ? response.data.message : response.data);
                        gsLogServerError('Excluir categoria', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    gsAlert('Erro ao excluir categoria.');
                    console.error('[GS] Excluir categoria: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        }, 'Excluir Categoria');
    }

    /**
     * Inicializa gerenciamento de termos
     */
    function initGerenciarTermos() {
        const listaContainer = $('#gs-lista-termos-gerenciar');
        const buscaTermos = $('#gs-busca-termos');

        if (!listaContainer.length) return;

        carregarTermosGerenciar();

        buscaTermos.on('input', gsDebounce(function() {
            const texto = gsNormalizarTexto($(this).val());
            $('.gs-item-termo-gerenciar').each(function() {
                const titulo = gsNormalizarTexto($(this).data('titulo'));
                if (titulo.includes(texto)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }, 150));

        // Delegação de eventos para editar e excluir termos
        $(document).on('click', '.gs-btn-editar-termo', function() {
            const id = $(this).data('id');
            editarTermo(id);
        });

        $(document).on('click', '.gs-btn-excluir-termo', function() {
            const id = $(this).data('id');
            excluirTermo(id);
        });
    }

    /**
     * Renderiza placeholders com efeito shimmer para carregamento de termos
     */
    function renderSkeletonTermos() {
        let html = '';
        const widths = [150, 210, 130, 190, 170];
        for (let i = 0; i < 5; i++) {
            html += '<div class="gs-item-gerenciar gs-item-termo-gerenciar gs-skeleton-item" aria-hidden="true">' +
                '<div class="gs-item-info">' +
                    '<span class="gs-skeleton gs-skeleton-text" style="width: ' + widths[i] + 'px;"></span>' +
                    '<span class="gs-skeleton gs-skeleton-badge" style="width: 85px; margin-top: 4px;"></span>' +
                '</div>' +
                '<div class="gs-botoes-acao">' +
                    '<span class="gs-skeleton gs-skeleton-btn" style="width: 48px;"></span>' +
                    '<span class="gs-skeleton gs-skeleton-btn" style="width: 48px;"></span>' +
                '</div>' +
            '</div>';
        }
        return html;
    }

    /**
     * Carrega lista de termos para gerenciamento
     */
    function carregarTermosGerenciar() {
        const listaContainer = $('#gs-lista-termos-gerenciar');
        if (!listaContainer.length) return;

        listaContainer.html(renderSkeletonTermos());

        $.ajax({
            url: gs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gs_listar_termos_gerenciar',
                nonce: $('#gs_nonce_categoria').val()
            },
            success: function(response) {
                listaContainer.empty();
                if (response.success) {
                    if (!response.data.termos || response.data.termos.length === 0) {
                        listaContainer.html('<div class="gs-empty-state"><p>Nenhum termo cadastrado ainda.</p></div>');
                        return;
                    }

                    response.data.termos.forEach(function(termo) {
                        const termoTitulo = termo.titulo || '';
                        const termoCats = ((termo.categorias || 'sem categoria').toString()).toLowerCase();
                        const termoId = termo.id;

                        const $item = $('<div>', {
                            class: 'gs-item-gerenciar gs-item-termo-gerenciar',
                            'data-titulo': termoTitulo,
                            'data-id': termoId
                        });

                        const $info = $('<div>', { class: 'gs-item-info' }).appendTo($item);
                        $('<strong>', { class: 'gs-item-titulo', text: termoTitulo }).appendTo($info);
                        $('<span>', { class: 'gs-item-categoria', text: termoCats }).appendTo($info);

                        const $botoes = $('<div>', { class: 'gs-botoes-acao' }).appendTo($item);
                        $('<button>', {
                            type: 'button',
                            class: 'gs-btn-editar gs-btn-editar-termo',
                            'data-id': termoId,
                            text: 'Editar'
                        }).appendTo($botoes);

                        $('<button>', {
                            type: 'button',
                            class: 'gs-btn-excluir gs-btn-excluir-termo',
                            'data-id': termoId,
                            text: 'Excluir'
                        }).appendTo($botoes);

                        listaContainer.append($item);
                    });
                } else {
                    listaContainer.html('<div class="gs-empty-state"><p>Erro ao carregar termos.</p></div>');
                }
            },
            error: function(xhr, status, error) {
                listaContainer.html('<div class="gs-empty-state"><p>Erro ao carregar termos. Tente novamente.</p></div>');
                console.error('[GS] Listar termos: Erro AJAX:', status, error);
            }
        });
    }

    /**
     * Editar termo (abre modal)
     */
    function editarTermo(id) {
        $.ajax({
            url: gs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gs_listar_termos_gerenciar',
                nonce: $('#gs_nonce_categoria').val()
            },
            success: function(response) {
                if (response.success) {
                    const termo = response.data.termos.find(function(t) { return t.id === id; });
                    if (termo) {
                        abrirModalEditarTermo(termo);
                    }
                }
            }
        });
    }

    /**
     * Abrir modal de edição de termo com transição suave
     */
    function abrirModalEditarTermo(termo) {
        $('#gs-modal-editar-termo').remove();

        const modal = $(
            '<div id="gs-modal-editar-termo" class="gs-modal-overlay" role="dialog" aria-modal="true">' +
                '<div class="gs-modal">' +
                    '<h3>Editar Termo</h3>' +
                    '<form id="gs-form-editar-termo">' +
                        '<input type="hidden" name="id">' +
                        '<div class="gs-form-group">' +
                            '<label>Termo: <span class="obrigatorio">*</span></label>' +
                            '<input type="text" name="termo" required>' +
                        '</div>' +
                        '<div class="gs-form-group">' +
                            '<label>Definição: <span class="obrigatorio">*</span></label>' +
                            '<textarea name="definicao" required></textarea>' +
                        '</div>' +
                        '<div class="gs-form-group">' +
                            '<label>Categorias: <span class="obrigatorio">*</span></label>' +
                            '<select name="categorias[]" multiple size="4" required></select>' +
                            '<small>Selecione ao menos uma categoria. Segure Ctrl (Windows) ou Cmd (Mac) para múltiplas.</small>' +
                        '</div>' +
                        '<div class="gs-botoes-modal">' +
                            '<button type="button" class="gs-btn-cancelar-modal" id="gs-btn-fechar-modal">Cancelar</button>' +
                            '<button type="submit" class="gs-form-submit">Salvar Alterações</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>'
        );

        modal.find('input[name="id"]').val(termo.id);
        modal.find('input[name="termo"]').val(termo.titulo || '');
        modal.find('textarea[name="definicao"]').val(termo.definicao || '');

        $('body').append(modal);

        modal.find('#gs-btn-fechar-modal').on('click', function() {
            modal.fadeOut(150, function() { $(this).remove(); });
        });

        modal.on('click', function(e) {
            if ($(e.target).is('#gs-modal-editar-termo')) {
                modal.fadeOut(150, function() { $(this).remove(); });
            }
        });

        // Carregar categorias no select do modal
        $.ajax({
            url: gs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gs_listar_categorias',
                nonce: $('#gs_nonce_categoria').val()
            },
            success: function(response) {
                if (response.success && response.data.categorias) {
                    const select = modal.find('select[name="categorias[]"]');
                    const categoriasAtuais = (termo.categorias || '').split(',').map(function(s) { return s.trim().toLowerCase(); });

                    response.data.categorias.forEach(function(cat) {
                        const catNomeLower = (cat.name || '').toLowerCase();
                        const selecionada = categoriasAtuais.includes(catNomeLower);
                        const $opt = $('<option>', {
                            value: cat.id,
                            text: catNomeLower
                        });
                        if (selecionada) {
                            $opt.prop('selected', true);
                        }
                        select.append($opt);
                    });
                }
            }
        });

        // Submissão do formulário de edição
        modal.find('form').on('submit', function(e) {
            e.preventDefault();

            const selectCats = modal.find('select[name="categorias[]"]');
            selectCats.removeClass('gs-field-error');

            if (!selectCats.val() || selectCats.val().length === 0) {
                selectCats.addClass('gs-field-error').focus();
                gsAlert('Selecione pelo menos uma categoria para o termo.');
                return;
            }

            const formData = $(this).serialize() + '&action=gs_editar_termo&nonce=' + $('#gs_nonce_categoria').val();

            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        modal.fadeOut(150, function() { $(this).remove(); });
                        carregarTermosGerenciar();
                        mostrarMensagem(response.data.message, 'sucesso');
                    } else {
                        gsAlert(typeof response.data === 'object' ? response.data.message : response.data);
                        gsLogServerError('Editar termo', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    gsAlert('Erro ao editar termo.');
                    console.error('[GS] Editar termo: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        });
    }

    /**
     * Excluir termo com diálogo in-app
     */
    function excluirTermo(id) {
        gsConfirm('Tem certeza de que deseja excluir este termo permanentemente?', function() {
            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gs_excluir_termo',
                    id: id,
                    nonce: $('#gs_nonce_categoria').val()
                },
                success: function(response) {
                    if (response.success) {
                        carregarTermosGerenciar();
                        mostrarMensagem(response.data.message, 'sucesso');
                    } else {
                        gsAlert(typeof response.data === 'object' ? response.data.message : response.data);
                        gsLogServerError('Excluir termo', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    gsAlert('Erro ao excluir termo.');
                    console.error('[GS] Excluir termo: Erro AJAX:', status, error, xhr.responseText);
                }
            });
        }, 'Excluir Termo');
    }

    /**
     * Inicialização do Dashboard de Estatísticas & Métricas
     */
    function initDashboard() {
        const container = $('.gs-dashboard-container');
        if (!container.length) return;

        // Aplicar máscara de data brasileira DD/MM/AAAA nos campos de período personalizado
        const inputInicio = $('#gs-data-inicio');
        const inputFim = $('#gs-data-fim');

        function aplicarMascaraDataBr($input) {
            $input.on('input', function() {
                let v = $(this).val().replace(/\D/g, '').slice(0, 8);
                if (v.length >= 5) {
                    $(this).val(v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4));
                } else if (v.length >= 3) {
                    $(this).val(v.slice(0, 2) + '/' + v.slice(2));
                } else {
                    $(this).val(v);
                }
            });
        }

        aplicarMascaraDataBr(inputInicio);
        aplicarMascaraDataBr(inputFim);

        // Disparo via botões de atalho de período
        $(document).on('click', '.gs-btn-periodo', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const periodo = $btn.data('periodo');

            // Limpar inputs de data ao escolher atalho pré-definido
            inputInicio.val('');
            inputFim.val('');

            executarFiltroDashboard(periodo, '', '', $btn);
        });

        // Disparo via formulário de período personalizado
        $(document).on('submit', '#gs-form-filtro-datas', function(e) {
            e.preventDefault();
            const inicio = $.trim(inputInicio.val());
            const fim = $.trim(inputFim.val());

            if (!inicio || !fim) {
                gsAlert('Por favor, preencha as datas de início e término no formato DD/MM/AAAA.', 'Período Incompleto');
                return;
            }

            // Validação simples de formato DD/MM/AAAA
            const formatoDataRegex = /^\d{2}\/\d{2}\/\d{4}$/;
            if (!formatoDataRegex.test(inicio) || !formatoDataRegex.test(fim)) {
                gsAlert('As datas devem estar estritamente no padrão brasileiro DD/MM/AAAA.', 'Formato Inválido');
                return;
            }

            $('.gs-btn-periodo').removeClass('ativo');
            executarFiltroDashboard('personalizado', inicio, fim, null);
        });

        /**
         * Executa a requisição AJAX para atualizar os KPIs e gráficos
         */
        function executarFiltroDashboard(periodo, dataInicio, dataFim, $btnClicado) {
            container.addClass('gs-dashboard-loading');
            $('.gs-dashboard-carregando').show();

            $.ajax({
                url: gs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gs_filtrar_dashboard',
                    nonce: gs_ajax.nonce,
                    periodo: periodo,
                    data_inicio: dataInicio || '',
                    data_fim: dataFim || ''
                },
                success: function(response) {
                    container.removeClass('gs-dashboard-loading');
                    $('.gs-dashboard-carregando').hide();
                    if (response.success && response.data) {
                        atualizarDashboardUI(response.data);

                        if ($btnClicado) {
                            $('.gs-btn-periodo').removeClass('ativo');
                            $btnClicado.addClass('ativo');
                        }
                    } else {
                        const erro = (response.data && response.data.message) ? response.data.message : 'Não foi possível carregar as estatísticas.';
                        gsAlert(erro, 'Erro no Dashboard');
                        gsLogServerError('Filtro Dashboard', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    container.removeClass('gs-dashboard-loading');
                    $('.gs-dashboard-carregando').hide();
                    gsAlert('Houve uma falha de comunicação com o servidor ao consultar os dados.', 'Falha de Conexão');
                    console.error('[GS Dashboard] Erro AJAX:', status, error, xhr.responseText);
                }
            });
        }

        /**
         * Atualiza os elementos da interface do Dashboard com os novos dados
         */
        function atualizarDashboardUI(data) {
            if (data.total_termos !== undefined) {
                $('#gs-kpi-total-termos').text(data.total_termos);
            }
            if (data.total_categorias !== undefined) {
                $('#gs-kpi-total-categorias').text(data.total_categorias);
            }
            
            // Cadastros no período
            const qtdPeriodo = data.cadastros_periodo !== undefined ? data.cadastros_periodo : data.termos_periodo;
            if (qtdPeriodo !== undefined) {
                $('#gs-kpi-periodo-valor').text(qtdPeriodo);
            }
            if (data.rotulo_periodo !== undefined) {
                $('#gs-kpi-periodo-rotulo').text(data.rotulo_periodo);
            }

            // Média por categoria
            if (data.media_por_cat !== undefined) {
                $('#gs-kpi-media').text(data.media_por_cat);
            }

            // Categoria com mais termos
            const topCat = data.categoria_top || data.top_categoria;
            if (topCat && topCat.nome) {
                const contagem = topCat.count !== undefined ? topCat.count : topCat.contagem;
                $('#gs-kpi-top-cat').text(topCat.nome);
                $('#gs-kpi-top-cat-sub').text(contagem + ' termo(s)');
            } else {
                $('#gs-kpi-top-cat').text('—');
                $('#gs-kpi-top-cat-sub').text('Nenhuma categoria');
            }

            // Atualizar barras de distribuição por categoria
            const cats = data.categorias || data.termos_por_categoria;
            if (Array.isArray(cats)) {
                const $listaCat = $('#gs-lista-categorias-distribuicao, .gs-cat-progresso-lista');
                if ($listaCat.length) {
                    $listaCat.empty();
                    if (cats.length === 0) {
                        $listaCat.append('<div class="gs-empty-state"><p>Nenhuma categoria encontrada.</p></div>');
                    } else {
                        cats.forEach(function(cat) {
                            const catNome = (cat.nome || '').toLowerCase();
                            const qtd = cat.count !== undefined ? cat.count : cat.contagem;
                            const percentual = parseFloat(cat.percentual) || 0;

                            const $item = $('<div>', { class: 'gs-cat-progresso-item' });
                            const $infoLinha = $('<div>', { class: 'gs-cat-info-linha' }).appendTo($item);
                            $('<span>', { class: 'gs-cat-nome', text: catNome }).appendTo($infoLinha);

                            const $qtdSpan = $('<span>', { class: 'gs-cat-qtd' }).appendTo($infoLinha);
                            $('<strong>', { text: qtd }).appendTo($qtdSpan);
                            $qtdSpan.append(' termo(s) ');
                            $('<small>', { text: '(' + percentual + '%)' }).appendTo($qtdSpan);

                            const $trilha = $('<div>', { class: 'gs-barra-trilha' }).appendTo($item);
                            $('<div>', {
                                class: 'gs-barra-preenchimento',
                                style: 'width: ' + percentual + '%;'
                            }).appendTo($trilha);

                            $listaCat.append($item);
                        });
                    }
                }
            }
        }
    }

    /**
     * Feedback visual com fade suave
     */
    function mostrarMensagem(texto, tipo) {
        const mensagem = $('#gs-mensagem');
        mensagem.removeClass('gs-sucesso gs-erro').addClass('gs-' + tipo);
        mensagem.text(texto).fadeIn(200);
        setTimeout(function() {
            mensagem.fadeOut(300);
        }, 5000);
    }

    window.mostrarMensagem = mostrarMensagem;

})(jQuery);
