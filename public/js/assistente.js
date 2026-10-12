/*
 * Chat do Olhinho (assistente) — v0.50.
 * Abre ao clicar em qualquer elemento com [data-abrir-assistente] (o mascote,
 * o botão "Tirar dúvida" ou o botão flutuante). Conversa com a rota
 * ?rota=assistente (PHP). A conversa fica guardada só nesta aba do navegador.
 */
(function () {
    'use strict';

    const cfg = window.OC_ASSISTENTE || {};
    const BASE = window.OC_BASE || cfg.base || '';
    const CHAVE = 'olhinhoConversa';
    let painel = null, lista = null, campo = null, sugestoesEl = null, enviando = false;
    let conversa = [];

    try { conversa = JSON.parse(sessionStorage.getItem(CHAVE) || '[]') || []; } catch (e) { conversa = []; }
    const guardar = () => { try { sessionStorage.setItem(CHAVE, JSON.stringify(conversa.slice(-30))); } catch (e) {} };

    function el(tag, classe, texto) {
        const e = document.createElement(tag);
        if (classe) e.className = classe;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }

    function criarPainel() {
        painel = el('section', 'olhinho-chat');
        painel.setAttribute('role', 'dialog');
        painel.setAttribute('aria-label', 'Converse com o Olhinho, o assistente');
        painel.hidden = true;

        const topo = el('header', 'olhinho-topo');
        const avatar = el('img', 'olhinho-avatar');
        avatar.src = cfg.avatar || (BASE + '/img/mascote.png');
        avatar.alt = '';
        const titulo = el('div', 'olhinho-titulo');
        titulo.appendChild(el('strong', '', 'Olhinho'));
        titulo.appendChild(el('small', '', cfg.ia ? 'Assistente · respostas prontas + IA' : 'Assistente do Olho Cidadão'));
        const fechar = el('button', 'olhinho-fechar');
        fechar.type = 'button';
        fechar.setAttribute('aria-label', 'Fechar conversa');
        fechar.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
        fechar.addEventListener('click', fecharPainel);
        topo.append(avatar, titulo, fechar);

        lista = el('div', 'olhinho-lista');
        lista.setAttribute('role', 'log');
        lista.setAttribute('aria-live', 'polite');

        sugestoesEl = el('div', 'olhinho-sugestoes');
        (cfg.sugestoes || []).forEach((s) => {
            const b = el('button', 'olhinho-chip', s);
            b.type = 'button';
            b.addEventListener('click', () => perguntar(s));
            sugestoesEl.appendChild(b);
        });

        const form = el('form', 'olhinho-form');
        campo = el('input', 'olhinho-campo');
        campo.type = 'text';
        campo.maxLength = 500;
        campo.placeholder = 'Escreva sua dúvida ou um protocolo…';
        campo.setAttribute('aria-label', 'Sua pergunta para o Olhinho');
        campo.autocomplete = 'off';
        const enviar = el('button', 'olhinho-enviar');
        enviar.type = 'submit';
        enviar.setAttribute('aria-label', 'Enviar pergunta');
        enviar.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M3 11.5L21 3l-8.5 18-2.2-7.3L3 11.5z" fill="currentColor"/></svg>';
        form.append(campo, enviar);
        form.addEventListener('submit', (e) => { e.preventDefault(); perguntar(campo.value); });

        const aviso = el('p', 'olhinho-aviso', 'Sou um assistente automático e posso errar. Em emergência, ligue 193, 192 ou 190.');

        painel.append(topo, lista, sugestoesEl, form, aviso);
        document.body.appendChild(painel);

        if (conversa.length === 0) {
            const nome = cfg.nomeUsuario ? ', ' + cfg.nomeUsuario : '';
            adicionar({ de: 'olhinho', texto: 'Oi' + nome + '! Eu sou o Olhinho. 👋 Pode me perguntar qualquer coisa sobre o Olho Cidadão, ou me mandar o número de um protocolo que eu vejo como está.' }, true);
        } else {
            conversa.forEach((m) => desenharMensagem(m));
        }
        lista.scrollTop = lista.scrollHeight;
    }

    function desenharMensagem(m) {
        const linha = el('div', 'olhinho-msg ' + (m.de === 'eu' ? 'eu' : 'ele'));
        const balao = el('div', 'olhinho-balao', m.texto);
        linha.appendChild(balao);
        if (m.acoes && m.acoes.length) {
            const acoes = el('div', 'olhinho-acoes');
            m.acoes.forEach((a) => {
                const link = el('a', 'olhinho-acao', a.rotulo);
                link.href = a.url;
                acoes.appendChild(link);
            });
            linha.appendChild(acoes);
        }
        if (m.fonte === 'ia') linha.appendChild(el('small', 'olhinho-fonte', 'Resposta gerada por IA'));
        lista.appendChild(linha);
    }

    function adicionar(m, salvar) {
        if (salvar !== false) { conversa.push(m); guardar(); }
        desenharMensagem(m);
        lista.scrollTop = lista.scrollHeight;
    }

    function digitando(ligar) {
        let d = lista.querySelector('.olhinho-digitando');
        if (ligar && !d) {
            d = el('div', 'olhinho-msg ele olhinho-digitando');
            d.innerHTML = '<div class="olhinho-balao"><span></span><span></span><span></span></div>';
            d.setAttribute('aria-label', 'Olhinho está digitando');
            lista.appendChild(d);
            lista.scrollTop = lista.scrollHeight;
        } else if (!ligar && d) {
            d.remove();
        }
    }

    async function perguntar(texto) {
        texto = String(texto || '').trim();
        if (!texto || enviando) return;
        enviando = true;
        campo.value = '';
        const historico = conversa.slice(-6).map((m) => ({ de: m.de, texto: m.texto }));
        adicionar({ de: 'eu', texto });
        digitando(true);
        if (window.Mascote) window.Mascote.reagir('pensando', 2500);

        let resp;
        try {
            const r = await fetch(BASE + '/?rota=assistente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf || '' },
                credentials: 'same-origin',
                body: JSON.stringify({ pergunta: texto, historico }),
            });
            resp = await r.json();
        } catch (e) {
            resp = { texto: 'Não consegui falar com o servidor agora. Confira a internet e tente de novo.', humor: 'preocupado' };
        }

        // Pequena pausa para a resposta não aparecer "seca".
        await new Promise((ok) => setTimeout(ok, 350));
        digitando(false);
        adicionar({ de: 'olhinho', texto: resp.texto || '…', acoes: resp.acoes || [], fonte: resp.fonte });
        if (window.Mascote && resp.humor) window.Mascote.reagir(resp.humor, 3500);
        enviando = false;
        campo.focus();
    }

    let quemAbriu = null;
    function abrirPainel(origem) {
        if (!painel) criarPainel();
        quemAbriu = origem || document.activeElement;
        painel.hidden = false;
        requestAnimationFrame(() => painel.classList.add('aberto'));
        document.documentElement.classList.add('olhinho-aberto');
        setTimeout(() => campo && campo.focus(), 120);
    }

    function fecharPainel() {
        if (!painel) return;
        painel.classList.remove('aberto');
        document.documentElement.classList.remove('olhinho-aberto');
        setTimeout(() => { painel.hidden = true; }, 200);
        if (quemAbriu && quemAbriu.focus) quemAbriu.focus();
    }

    document.addEventListener('click', (e) => {
        const alvo = e.target.closest('[data-abrir-assistente]');
        if (!alvo) return;
        e.preventDefault();
        if (painel && !painel.hidden) fecharPainel(); else abrirPainel(alvo);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && painel && !painel.hidden) fecharPainel();
        const alvo = e.target.closest && e.target.closest('[data-abrir-assistente][role="button"]');
        if (alvo && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); abrirPainel(alvo); }
    });

    window.Olhinho = { abrir: abrirPainel, fechar: fecharPainel, perguntar: (t) => { abrirPainel(); perguntar(t); } };
})();
