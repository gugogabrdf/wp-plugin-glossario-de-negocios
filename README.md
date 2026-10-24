# 📘 Glossário de Negócios — WordPress Plugin

<div align="center">

[![WordPress](https://img.shields.io/badge/WordPress-6.0%20%7C%206.7+-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/Licen%C3%A7a-GPL--2.0--or--later-blue.svg?style=for-the-badge)](LICENSE)
[![Acessibilidade](https://img.shields.io/badge/WCAG-2.1%20AA%20Compliant-success?style=for-the-badge)](https://www.w3.org/WAI/standards-guidelines/wcag/)
[![i18n](https://img.shields.io/badge/i18n-pt__BR%20%7C%20en__US-orange?style=for-the-badge)](languages/)
[![Qualidade Técnica](https://img.shields.io/badge/Diagn%C3%B3stico%20T%C3%A9cnico-9.8%20%2F%2010-0ea5e9?style=for-the-badge)](dev/diag-v2.md)

**Solução moderna, acessível e de alta performance para criação, gestão e exibição de glossários corporativos, dicionários técnicos e catálogos de termos no WordPress.**

[Funcionalidades](#-recursos-principais) • [Instalação](#-instalação-rápida) • [Shortcodes](#-shortcodes-disponíveis) • [Dashboard](#-dashboard-analítico--métricas) • [Importação CSV](#-motor-de-importação-e-exportação-csv) • [Desenvolvedores](#-guia-do-desenvolvedor)

</div>

---

## 🌟 Por que o Glossário de Negócios?

O **Glossário de Negócios** foi projetado do zero para superar as limitações dos plugins tradicionais de dicionário. Ele combina **paginação server-side assíncrona** com cache de alto rendimento, uma interface pública com design system moderno e neutro, gerenciamento integrado em abas no front-end e no back-end, auditoria de importação em lote e conformidade estrita com acessibilidade **WCAG 2.1 AA**.

> [!TIP]
> **Pronto para Produção e Grandes Volumes:** Testado e aprovado com nota **9.8/10** em auditoria de qualidade arquitetural, performance, segurança e usabilidade.

---

## ⚡ Recursos Principais

### 🔍 Experiência Pública do Usuário (Frontend)
- **Busca Acento-Insensível em Tempo Real:** Pesquise por `comunicacao` e encontre `comunicação` instantaneamente via normalização fonética NFD e debounce otimizado de 250ms.
- **Índice Alfabético A–Z Inteligente:** Filtragem direta por inicial com suporte a caracteres acentuados (Á, É, Í agrupados nas letras base).
- **Paginação Server-Side Otimizada:** Carregamento sob demanda via AJAX que consome frações de milissegundos mesmo com bases de milhares de termos (opções de 10, 20, 100 ou 500 por página).
- **Skeleton Shimmer Loading:** Efeito visual de carregamento que melhora a percepção de velocidade e elimina Content Layout Shifts (CLS).
- **Badges de Múltiplas Categorias:** Exibição elegante das categorias vinculadas a cada termo com normalização automática em minúsculas.

### 🎛️ Painel de Gestão Completo em Abas (`[glossario_cadastro]`)
Uma central administrativa unificada para administradores e editores, sem necessidade de navegar por telas dispersas do painel WP:
1. **Cadastro Manual:** Formulário com validações em tempo real e prevenção de duplicatas na mesma categoria.
2. **Importação CSV:** Upload em lote inteligente com relatório auditado de linhas ignoradas.
3. **Exportação CSV:** Backup instantâneo de todos os termos com data no nome do arquivo.
4. **Gerenciamento de Categorias (CRUD):** Criação, edição inline e exclusão segura (com bloqueio para categorias com termos ativos).
5. **Gerenciamento de Termos (CRUD):** Busca rápida interna, modal acessível de edição e exclusão com confirmação in-app.
6. **Instruções & Boas Práticas:** Manual de uso incorporado diretamente na interface.
7. **Dashboard Integrado:** Visão analítica em tempo real embutida no formulário.

### 📊 Dashboard Analítico & Inteligência de Dados
- **KPIs Essenciais em Tempo Real:** Total de termos, total de categorias, cadastros no período selecionado, média de termos por categoria e categoria de maior volume.
- **Filtros Temporais Inteligentes:** Atalhos rápidos (*Hoje*, *Últimos 7 dias*, *Últimos 30 dias*, *Este Ano*, *Histórico Completo*) e seletor de intervalo de datas personalizado no padrão brasileiro (`DD/MM/AAAA`).
- **Distribuição Percentual por Categoria:** Barras de progresso com cálculo percentual dinâmico da volumetria.
- **Tabela de Atividade Recente:** Últimos termos registrados com formatação amigável de data.
- **Cache de Alta Performance:** Integração com a **WordPress Transients API** (validade de 10 min com invalidação imediata em hooks de criação, edição ou exclusão).

### ♿ Acessibilidade e Design Inclusivo (WCAG 2.1 AA)
- **Navegação Completa por Teclado nas Abas:** Suporte aos padrões WAI-ARIA com setas direcionais (<kbd>←</kbd> <kbd>→</kbd> <kbd>↑</kbd> <kbd>↓</kbd>), <kbd>Home</kbd> e <kbd>End</kbd>, com técnica *roving tabindex*.
- **Modais In-App Nativos:** Substituição definitiva de caixas invasivas do navegador (`alert()` / `confirm()`) por diálogos acessíveis com foco automático e fechamento via <kbd>Esc</kbd>.
- **Suporte a Movimento Reduzido:** Respeito integral à preferência `@media (prefers-reduced-motion: reduce)` desabilitando shimmers e transições bruscas.
- **Contraste e Foco Visível:** Cores em conformidade com contraste mínimo de 4.5:1 e anéis de foco `:focus-visible`.

---

## 🚀 Instalação Rápida

### Via Painel do WordPress (.zip)
1. Baixe o pacote de distribuição `.zip` do plugin.
2. Acesse seu painel WordPress em **Plugins > Adicionar Novo > Enviar Plugin**.
3. Selecione o arquivo `.zip` e clique em **Instalar Agora**.
4. Clique em **Ativar Plugin**.

---

## 🧩 Shortcodes Disponíveis

O plugin fornece 3 shortcodes principais, projetados para serem inseridos no editor de blocos (Gutenberg), Elementor ou templates PHP:

### 1. `[glossario_lista]`
Exibe o catálogo público interativo de termos, com campo de busca, filtro por categorias, índice A–Z e tabela paginada.

```html
[glossario_lista]
```

### 2. `[glossario_cadastro]`
Exibe a central de gestão e cadastro com abas modulares. Ideal para páginas internas ou restritas a colaboradores/administradores.

```html
<!-- Exibição padrão completa (todas as abas ativas) -->
[glossario_cadastro]

<!-- Ocultando abas de importação e exportação para usuários básicos -->
[glossario_cadastro mostrar_importacao="nao" mostrar_exportacao="nao"]
```

| Atributo | Tipo | Padrão | Descrição |
| :--- | :---: | :---: | :--- |
| `mostrar_importacao` | `string` | `"sim"` | Define se a aba de Importação CSV deve ser exibida (`"sim"` ou `"nao"`). |
| `mostrar_exportacao` | `string` | `"sim"` | Define se a aba de Exportação CSV deve ser exibida (`"sim"` ou `"nao"`). |

### 3. `[glossario_dashboard]`
Incorpora o painel de métricas analíticas e estatísticas em tempo real em qualquer página ou post do site.

```html
[glossario_dashboard]
```

---

## 📂 Motor de Importação e Exportação CSV

O módulo [`includes/csv-handler.php`](file:///c:/Users/PC/Desktop/Pessoal/wp-plugin/glossario/glossario-de-negocios/includes/csv-handler.php) implementa um pipeline tolerante a falhas para processamento de arquivos em lote:

### Recursos do Parser:
- **Detecção Automática de Delimitador:** Identifica automaticamente se o arquivo utiliza vírgula (`,`) ou ponto e vírgula (`;`).
- **Higienização de BOM:** Remove automaticamente o marcador UTF-8 BOM (`\xEF\xBB\xBF`) gerado por editores como Microsoft Excel.
- **Unicidade de Termos por Categoria:** Permite que termos homônimos existam desde que alocados em categorias distintas. Duplicatas na mesma categoria são ignoradas com segurança.
- **Criação Dinâmica de Taxonomias:** Categorias ausentes são criadas em tempo real durante a importação.
- **Relatório Detalhado de Auditoria:** O usuário recebe um feedback completo com a quantidade de termos inseridos com sucesso e a lista numerada de linhas ignoradas acompanhadas do respectivo motivo.

### Formato Recomendado do CSV:
```csv
termo,definicao,categoria,referencia
ROI,"Retorno sobre o Investimento, métrica financeira usada para avaliar o lucro de um aporte.",finanças,https://exemplo.com/roi
KPI,"Indicador-Chave de Desempenho, ferramenta de gestão para medição de sucesso.",gestão estratégica,
SaaS,"Software como Serviço, modelo de distribuição em nuvem.",tecnologia,https://exemplo.com/saas
```

> [!IMPORTANT]
> - As colunas `termo`, `definicao` e `categoria` são **estritamente obrigatórias**.
> - A coluna `referencia` é opcional.
> - Utilize codificação **UTF-8**. O botão **"Baixar Modelo CSV"** dentro da interface do plugin fornece um arquivo pré-formatado pronto para preenchimento.

---

## 🛡️ Segurança, Resiliência & Isolamento

O código segue as melhores práticas de desenvolvimento defensivo e padrões de codificação do WordPress (WordPress VIP Coding Standards):

| Camada | Implementação | Benefício |
| :--- | :--- | :--- |
| **Proteção CSRF** | `wp_verify_nonce` e `check_ajax_referer` em 100% dos 10 endpoints AJAX. | Previne execução não autorizada de requisições. |
| **Controle de Acesso** | Validação de capacidades via `current_user_can('edit_posts')`. | Impede que usuários não autenticados alterem dados. |
| **Proteção contra XSS** | Sanitização em camadas com `esc_html()`, `esc_attr()`, `wp_kses_post()`, jQuery `.text()` e codificador nativo `gsEscapeHtml()`. | Neutraliza injeção maliciosa de scripts. |
| **Isolamento de Falhas** | Tratamento abrangente com blocos `try-catch` em todas as rotinas PHP. | Erros externos ou de banco não derrubam o WordPress. |
| **Chave de Emergência** | Suporte à constante `GS_GLOSSARIO_DISABLED` no `wp-config.php`. | Desabilita o plugin instantaneamente sem acessar o painel. |
| **Compatibilidade WP 6.2+** | Consultas via `get_posts()` parametrizadas. | Zero uso da função depreciada `get_page_by_title()`. |

---

## 🌐 Internacionalização (i18n)

O plugin é **100% traduzível** utilizando o sistema Gettext nativo do WordPress:

- **Text Domain:** `glossario-negocios`
- **Idiomas Nativos Inclusos:**
  - 🇧🇷 **Português do Brasil (`pt_BR`)** — Idioma base.
  - 🇺🇸 **Inglês (`en_US`)** — Tradução semântica completa compilada.
- **Arquivos Disponíveis na pasta [`languages/`](file:///c:/Users/PC/Desktop/Pessoal/wp-plugin/glossario/glossario-de-negocios/languages):**
  - `glossario-negocios.pot`: Catálogo mestre atualizado.
  - `glossario-negocios-en_US.po`: Fonte textual de tradução.
  - `glossario-negocios-en_US.mo`: Catálogo binário otimizado.

---

## 📜 Requisitos do Sistema

- **WordPress:** 6.0 ou superior (totalmente compatível com 6.7+)
- **PHP:** 7.4, 8.0, 8.1, 8.2 ou 8.3
- **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.3+
- **Navegadores Homologados:** Chrome, Firefox, Edge, Safari (últimas 2 versões) e navegadores mobile iOS/Android.

---

## 🤝 Créditos & Autoria

- **Autor e Desenvolvedor:** Gustavo Silva
- **Colaboração em IA:** Antigravity (Google DeepMind Team) & MiMoCode (Xiaomi MiMo Team)
- **Licença:** Distribuído sob a licença [GPL-2.0-or-later](LICENSE). Sinta-se livre para utilizar, modificar e contribuir!

---

<div align="center">
Feito com dedicação para a comunidade WordPress. Se este plugin foi útil para você, considere deixar uma estrela ⭐ no repositório!
</div>
