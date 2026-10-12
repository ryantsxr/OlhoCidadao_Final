/* Quadro kanban do admin: arrastar cartões entre colunas muda o status.
 * No celular (onde arrastar não funciona bem) cada cartão tem o botão "⋯" com as opções.
 * Ao mover para "Aguardando confirmação", pede uma mensagem opcional para o cidadão. */
document.addEventListener('DOMContentLoaded', function () {
    const quadro = document.getElementById('kanban');
    if (!quadro) return;
    const URL_MOVER = quadro.dataset.url;
    const CSRF = quadro.dataset.csrf;
    let arrastado = null;

    function toast(msg, tipo) { if (window.mostrarToast) window.mostrarToast(msg, tipo); }

    function atualizarContadores() {
        quadro.querySelectorAll('.kanban-coluna:not(.travada)').forEach(function (col) {
            const n = col.querySelectorAll('.kanban-cartao').length;
            col.querySelector('[data-contador]').textContent = n;
            col.querySelector('.kanban-vazio').hidden = n > 0;
        });
    }

    // Pergunta a mensagem opcional ao marcar como "Aguardando confirmação".
    function pedirMensagem() {
        return new Promise(function (resolver) {
            const fundo = document.createElement('div');
            fundo.className = 'kanban-dialogo-fundo';
            fundo.innerHTML =
                '<div class="kanban-dialogo" role="dialog" aria-modal="true" aria-labelledby="kd-titulo">' +
                '<h3 id="kd-titulo"><i class="fa-solid fa-hourglass-half"></i> Marcar como resolvida?</h3>' +
                '<p>O cidadão vai receber um aviso para confirmar se o problema foi mesmo resolvido.</p>' +
                '<label for="kd-msg">Mensagem para o cidadão (opcional)</label>' +
                '<textarea id="kd-msg" maxlength="300" placeholder="Ex.: a lâmpada foi trocada hoje de manhã"></textarea>' +
                '<div class="kanban-dialogo-acoes">' +
                '<button type="button" class="kd-cancelar">Cancelar</button>' +
                '<button type="button" class="kd-confirmar"><i class="fa-solid fa-check"></i> Confirmar</button>' +
                '</div></div>';
            document.body.appendChild(fundo);
            const campo = fundo.querySelector('textarea');
            setTimeout(function () { campo.focus(); }, 30);
            function fechar(valor) { fundo.remove(); document.removeEventListener('keydown', tecla); resolver(valor); }
            function tecla(e) { if (e.key === 'Escape') fechar(null); }
            document.addEventListener('keydown', tecla);
            fundo.addEventListener('click', function (e) { if (e.target === fundo) fechar(null); });
            fundo.querySelector('.kd-cancelar').addEventListener('click', function () { fechar(null); });
            fundo.querySelector('.kd-confirmar').addEventListener('click', function () { fechar(campo.value.trim()); });
        });
    }

    async function mover(cartao, coluna) {
        const destino = coluna.dataset.status;
        const origem = cartao.dataset.status;
        if (destino === origem || coluna.classList.contains('travada')) return;

        let mensagem = '';
        if (destino === 'Aguardando confirmação') {
            mensagem = await pedirMensagem();
            if (mensagem === null) return; // cancelou
        }

        // Move na tela na hora (sensação de rapidez); se o servidor recusar, volta.
        const colunaOrigem = cartao.closest('.kanban-coluna');
        const proximo = cartao.nextElementSibling;
        const zona = coluna.querySelector('[data-zona]');
        zona.insertBefore(cartao, zona.firstChild);
        cartao.dataset.status = destino;
        cartao.classList.add('salvando');
        atualizarContadores();

        const dados = new FormData();
        dados.append('csrf_token', CSRF);
        dados.append('id', cartao.dataset.id);
        dados.append('status', destino);
        dados.append('mensagem', mensagem);

        try {
            const resp = await fetch(URL_MOVER, { method: 'POST', body: dados, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const json = await resp.json().catch(function () { return {}; });
            if (!resp.ok || !json.ok) throw new Error(json.erro || 'Não foi possível mover a denúncia.');
            cartao.classList.remove('salvando');
            cartao.classList.add('movido');
            setTimeout(function () { cartao.classList.remove('movido'); }, 700);
            reconstruirMenu(cartao);
            toast((json.protocolo || 'Denúncia') + ' agora está em "' + destino + '". O cidadão foi avisado.', 'sucesso');
        } catch (erro) {
            colunaOrigem.querySelector('[data-zona]').insertBefore(cartao, proximo);
            cartao.dataset.status = origem;
            cartao.classList.remove('salvando');
            atualizarContadores();
            toast(erro.message, 'erro');
        }
    }

    // Recria as opções do menu "⋯" conforme a nova coluna do cartão.
    function reconstruirMenu(cartao) {
        const menu = cartao.querySelector('.kanban-mover-menu');
        if (!menu) return;
        menu.innerHTML = ['Pendente', 'Em análise', 'Aguardando confirmação']
            .filter(function (s) { return s !== cartao.dataset.status; })
            .map(function (s) { return '<button type="button" role="menuitem" data-mover-para="' + s + '"><i class="fa-solid fa-arrow-right"></i> ' + s + '</button>'; })
            .join('');
    }

    // ---------------- arrastar e soltar (computador)
    quadro.addEventListener('dragstart', function (e) {
        const c = e.target.closest('.kanban-cartao[draggable="true"]');
        if (!c) return;
        arrastado = c;
        c.classList.add('arrastando');
        quadro.classList.add('em-arraste');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', c.dataset.id);
    });
    quadro.addEventListener('dragend', function () {
        if (arrastado) arrastado.classList.remove('arrastando');
        arrastado = null;
        quadro.classList.remove('em-arraste');
        quadro.querySelectorAll('.alvo').forEach(function (c) { c.classList.remove('alvo'); });
    });
    quadro.querySelectorAll('.kanban-coluna').forEach(function (col) {
        col.addEventListener('dragover', function (e) {
            if (!arrastado || col.classList.contains('travada')) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('alvo');
        });
        col.addEventListener('dragleave', function (e) {
            if (!col.contains(e.relatedTarget)) col.classList.remove('alvo');
        });
        col.addEventListener('drop', function (e) {
            e.preventDefault();
            col.classList.remove('alvo');
            if (arrastado) mover(arrastado, col);
        });
    });

    // ---------------- menu "⋯" (celular e teclado)
    quadro.addEventListener('click', function (e) {
        const botao = e.target.closest('.kanban-mover-btn');
        const opcao = e.target.closest('[data-mover-para]');
        quadro.querySelectorAll('.kanban-mover-menu').forEach(function (m) {
            if (!botao || m !== botao.nextElementSibling) m.hidden = true;
        });
        if (botao) {
            const menu = botao.nextElementSibling;
            menu.hidden = !menu.hidden;
            if (!menu.hidden) { const b = menu.querySelector('button'); if (b) b.focus(); }
        }
        if (opcao) {
            const cartao = opcao.closest('.kanban-cartao');
            const col = quadro.querySelector('.kanban-coluna[data-status="' + opcao.dataset.moverPara + '"]');
            opcao.closest('.kanban-mover-menu').hidden = true;
            if (cartao && col) mover(cartao, col);
        }
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.kanban-mover')) quadro.querySelectorAll('.kanban-mover-menu').forEach(function (m) { m.hidden = true; });
    });
});
