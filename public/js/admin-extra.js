/* Admin (v0.49): busca rápida com Ctrl+K e barra de ações em massa. */
(function () {
    const script = document.currentScript;
    const URL_BUSCA = script ? script.dataset.busca : '';
    const BASE = window.OC_BASE || '';

    document.addEventListener('DOMContentLoaded', function () {
        buscaRapida();
        acoesEmMassa();
    });

    // ---------------------------------------------------------- 41. Ctrl+K
    function buscaRapida() {
        const PAGINAS = [
            ['Todas as denúncias', 'fa-list-check', 'admin'],
            ['Quadro (kanban)', 'fa-table-columns', 'admin-kanban'],
            ['Estatísticas', 'fa-chart-column', 'admin-estatisticas'],
            ['Modo telão', 'fa-tv', 'admin-telao'],
            ['Histórico', 'fa-clock-rotate-left', 'admin-historico'],
            ['Usuários', 'fa-users-gear', 'admin-usuarios'],
            ['Auditoria', 'fa-user-shield', 'admin-auditoria'],
            ['Relatório geral', 'fa-file-lines', 'admin-relatorio'],
            ['Denúncias atrasadas', 'fa-triangle-exclamation', 'admin&status=atrasadas'],
            ['Mapa público', 'fa-map-location-dot', 'mapa'],
            ['Configurações', 'fa-gear', 'configuracoes']
        ];
        const ICONES = { iluminacao: 'fa-lightbulb', sinalizacao: 'fa-traffic-light', lixo: 'fa-trash', outros: 'fa-plus' };
        let caixa = null, campo = null, lista = null, itens = [], sel = 0, timer = null;

        // atalho visível no menu
        const nav = document.querySelector('aside.menu nav');
        if (nav) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn-busca-rapida';
            b.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i><span>Buscar</span><kbd>Ctrl K</kbd>';
            b.addEventListener('click', abrir);
            nav.parentNode.insertBefore(b, nav);
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); caixa && !caixa.hidden ? fechar() : abrir(); }
            else if (e.key === '/' && !e.target.closest('input, textarea, select') && !(caixa && !caixa.hidden)) { e.preventDefault(); abrir(); }
        });

        function esc(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

        function montar() {
            caixa = document.createElement('div');
            caixa.className = 'paleta-fundo';
            caixa.hidden = true;
            caixa.innerHTML = '<div class="paleta" role="dialog" aria-modal="true" aria-label="Busca rápida">' +
                '<div class="paleta-campo"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Buscar denúncia (protocolo, rua, cidadão) ou página..." aria-label="Buscar" autocomplete="off"><kbd>Esc</kbd></div>' +
                '<ul class="paleta-lista" role="listbox"></ul>' +
                '<div class="paleta-rodape"><span><kbd>↑</kbd><kbd>↓</kbd> navegar</span><span><kbd>Enter</kbd> abrir</span><span><kbd>Ctrl</kbd><kbd>K</kbd> abre de qualquer tela</span></div></div>';
            document.body.appendChild(caixa);
            campo = caixa.querySelector('input');
            lista = caixa.querySelector('.paleta-lista');
            caixa.addEventListener('click', function (e) { if (e.target === caixa) fechar(); });
            campo.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(pesquisar, 160); });
            campo.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown') { e.preventDefault(); marcar(sel + 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(sel - 1); }
                else if (e.key === 'Enter') { e.preventDefault(); if (itens[sel]) location.href = itens[sel].url; }
                else if (e.key === 'Escape') { fechar(); }
            });
            lista.addEventListener('mousemove', function (e) { const li = e.target.closest('li[data-i]'); if (li) marcar(Number(li.dataset.i), true); });
            lista.addEventListener('click', function (e) { const li = e.target.closest('li[data-i]'); if (li) location.href = itens[Number(li.dataset.i)].url; });
        }

        function abrir() {
            if (!caixa) montar();
            caixa.hidden = false;
            campo.value = '';
            pesquisar();
            setTimeout(function () { campo.focus(); }, 10);
        }
        function fechar() { if (caixa) caixa.hidden = true; }

        function marcar(i, semRolar) {
            if (!itens.length) return;
            sel = (i + itens.length) % itens.length;
            lista.querySelectorAll('li[data-i]').forEach(function (li) {
                const ativo = Number(li.dataset.i) === sel;
                li.classList.toggle('ativo', ativo);
                li.setAttribute('aria-selected', ativo ? 'true' : 'false');
                if (ativo && !semRolar) li.scrollIntoView({ block: 'nearest' });
            });
        }

        function desenhar(paginas, denuncias, carregando) {
            itens = [];
            let html = '';
            if (denuncias.length) {
                html += '<li class="paleta-grupo">Denúncias</li>';
                denuncias.forEach(function (d) {
                    const i = itens.push({ url: BASE + '/?rota=denuncia-ver&id=' + d.id }) - 1;
                    html += '<li data-i="' + i + '" role="option"><span class="paleta-ic"><i class="fa-solid ' + (ICONES[d.categoria] || 'fa-file') + '"></i></span>' +
                        '<span class="paleta-txt"><strong>' + esc(d.protocolo) + ' · ' + esc(d.descricao) + '</strong><small>' + esc(d.status) + (d.cidadao ? ' · ' + esc(d.cidadao) : '') + '</small></span><i class="fa-solid fa-arrow-turn-down fa-rotate-90 paleta-enter"></i></li>';
                });
            }
            if (paginas.length) {
                html += '<li class="paleta-grupo">Ir para</li>';
                paginas.forEach(function (p) {
                    const i = itens.push({ url: BASE + '/?rota=' + p[2] }) - 1;
                    html += '<li data-i="' + i + '" role="option"><span class="paleta-ic"><i class="fa-solid ' + p[1] + '"></i></span><span class="paleta-txt"><strong>' + esc(p[0]) + '</strong></span><i class="fa-solid fa-arrow-turn-down fa-rotate-90 paleta-enter"></i></li>';
                });
            }
            if (!itens.length) html = carregando ? '<li class="paleta-vazio"><i class="fa-solid fa-spinner fa-spin"></i> Procurando...</li>' : '<li class="paleta-vazio"><i class="fa-regular fa-eye"></i> O olho não achou nada com esse termo.</li>';
            lista.innerHTML = html;
            marcar(0);
        }

        async function pesquisar() {
            const termo = campo.value.trim().toLowerCase();
            const paginas = PAGINAS.filter(function (p) { return !termo || p[0].toLowerCase().indexOf(termo) !== -1; });
            if (termo.length < 2 || !URL_BUSCA) { desenhar(paginas, []); return; }
            desenhar(paginas, [], true);
            try {
                const r = await fetch(URL_BUSCA + '&q=' + encodeURIComponent(termo), { credentials: 'same-origin' });
                const d = r.ok ? await r.json() : [];
                if (campo.value.trim().toLowerCase() === termo) desenhar(paginas, d);
            } catch (e) { desenhar(paginas, []); }
        }
    }

    // ---------------------------------------------------------- 42. ações em massa
    function acoesEmMassa() {
        const form = document.getElementById('form-massa');
        if (!form) return;
        const caixas = function () { return Array.from(document.querySelectorAll('input[name="ids[]"][form="form-massa"]')); };
        const qtd = document.getElementById('massa-qtd');
        const todas = document.getElementById('massa-todas');
        const escolha = document.getElementById('massa-escolha');

        function atualizar() {
            const marcadas = caixas().filter(function (c) { return c.checked; });
            qtd.textContent = marcadas.length;
            form.hidden = marcadas.length === 0;
            todas.checked = marcadas.length > 0 && marcadas.length === caixas().length;
            caixas().forEach(function (c) { c.closest('.denuncia-card').classList.toggle('selecionada', c.checked); });
        }
        document.addEventListener('change', function (e) {
            if (e.target.matches('input[name="ids[]"][form="form-massa"]')) atualizar();
        });
        todas.addEventListener('change', function () { caixas().forEach(function (c) { c.checked = todas.checked; }); atualizar(); });
        document.getElementById('massa-limpar').addEventListener('click', function () { caixas().forEach(function (c) { c.checked = false; }); atualizar(); });
        form.addEventListener('submit', function (e) {
            if (!escolha.value) { e.preventDefault(); window.mostrarToast && mostrarToast('Escolha o que fazer com as denúncias selecionadas.', 'aviso'); escolha.focus(); return; }
            const [acao, valor] = escolha.value.split('|');
            document.getElementById('massa-acao').value = acao;
            document.getElementById('massa-valor').value = valor;
        });
        atualizar();
    }
})();
