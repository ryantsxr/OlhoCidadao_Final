/* =========================================================================
 * Acabamento visual do Olho Cidadão (carregado em todas as páginas via _pwa.php)
 *
 *  1. Aplica o tema claro / alto contraste salvo, mesmo nas páginas que não
 *     carregam o configuracoes.js (mapa, consulta, sobre, termos, erros).
 *  2. Avisos (toasts): mostrarToast(mensagem, tipo) com ícone, cor, botão de
 *     fechar e barra de tempo. Tipos: 'sucesso', 'erro', 'aviso', 'info'.
 *     Também transforma em aviso os blocos de erro soltos no topo da página.
 *  3. Animações leves: cartões entrando com fade e números contando até o valor.
 *     Tudo é desligado se o sistema pedir "reduzir movimento".
 * ========================================================================= */
(function () {
    'use strict';

    const reduzirMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Link de acesso rápido para quem navega por teclado/leitor de tela.
    // Só aparece ao receber foco, sem alterar o visual do site.
    const principal = document.querySelector('main, [role="main"]');
    if (principal && !document.querySelector('.oc-pular-conteudo')) {
        if (!principal.id) principal.id = 'conteudo-principal';
        if (!principal.hasAttribute('tabindex')) principal.setAttribute('tabindex', '-1');
        const pular = document.createElement('a');
        pular.href = '#' + principal.id;
        pular.className = 'oc-pular-conteudo';
        pular.textContent = 'Pular para o conteúdo';
        pular.addEventListener('click', function () {
            // Permite foco programático também em navegadores móveis.
            principal.focus({ preventScroll: true });
        });
        document.body.insertBefore(pular, document.body.firstChild);
    }



    // ------------------------------------------------------------------ 1. tema
    function lerConfig() {
        try {
            return window.CONFIG_SERVIDOR || JSON.parse(localStorage.getItem('configOlhoCidadao') || 'null');
        } catch (e) {
            return null;
        }
    }
    const cfg = lerConfig();
    if (cfg && document.body) {
        document.body.classList.toggle('tema-claro', cfg.temaEscuro === false);
        document.body.classList.toggle('alto-contraste', cfg.altoContraste === true);
        document.body.classList.toggle('modo-daltonico', cfg.modoDaltonico === true);
        document.body.classList.toggle('fonte-leitura', cfg.fonteLeitura === true);
    }
    document.documentElement.classList.remove('pre-tema-claro', 'pre-modo-daltonico');

    // O logo padrão tem o texto "olho" em branco; no tema claro usamos a versão
    // com texto escuro (logo_white.png — o nome indica "para fundo branco").
    function trocarLogo() {
        const claro = document.body.classList.contains('tema-claro');
        // .logo-relatorio fica de fora: o relatório é sempre papel branco.
        document.querySelectorAll('img[src*="/img/logo"]:not(.logo-relatorio)').forEach(function (img) {
            const src = img.getAttribute('src');
            const novo = claro
                ? src.replace(/\/logo(_olho)?\.png/, '/logo_white$1.png')
                : src.replace(/\/logo_white(_olho)?\.png/, '/logo$1.png');
            if (novo !== src) img.setAttribute('src', novo);
        });
    }
    trocarLogo();
    // Na tela de Configurações o tema muda na hora; acompanha a troca.
    new MutationObserver(trocarLogo).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    // ---------------------------------------------------------------- 2. avisos
    const ICONES = {
        sucesso: 'fa-circle-check',
        erro: 'fa-circle-exclamation',
        aviso: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    function caixaDeAvisos() {
        let c = document.querySelector('.oc-toasts');
        if (!c) {
            c = document.createElement('div');
            c.className = 'oc-toasts';
            c.setAttribute('role', 'region');
            c.setAttribute('aria-label', 'Avisos');
            document.body.appendChild(c);
        }
        return c;
    }

    function mostrarToast(mensagem, tipo, duracao) {
        if (!mensagem) return;
        tipo = ICONES[tipo] ? tipo : 'erro';
        duracao = duracao || (tipo === 'erro' ? 6500 : 4500);

        const t = document.createElement('div');
        t.className = 'oc-toast oc-toast-' + tipo;
        t.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');
        t.style.setProperty('--duracao', duracao + 'ms');

        const icone = document.createElement('i');
        icone.className = 'fa-solid ' + ICONES[tipo] + ' oc-toast-icone';
        icone.setAttribute('aria-hidden', 'true');

        const texto = document.createElement('span');
        texto.className = 'oc-toast-texto';
        texto.textContent = mensagem;

        const fechar = document.createElement('button');
        fechar.type = 'button';
        fechar.className = 'oc-toast-fechar';
        fechar.setAttribute('aria-label', 'Fechar aviso');
        fechar.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';

        const barra = document.createElement('span');
        barra.className = 'oc-toast-barra';

        t.append(icone, texto, fechar, barra);
        caixaDeAvisos().appendChild(t);

        // Some sozinho; pausa enquanto o mouse está em cima.
        let restante = duracao;
        let inicio = Date.now();
        let timer = setTimeout(sair, restante);
        t.addEventListener('mouseenter', function () {
            clearTimeout(timer);
            restante -= Date.now() - inicio;
            t.classList.add('pausado');
        });
        t.addEventListener('mouseleave', function () {
            inicio = Date.now();
            t.classList.remove('pausado');
            timer = setTimeout(sair, Math.max(800, restante));
        });
        fechar.addEventListener('click', sair);

        function sair() {
            clearTimeout(timer);
            if (t.classList.contains('saindo')) return;
            t.classList.add('saindo');
            setTimeout(function () { t.remove(); }, reduzirMovimento ? 0 : 260);
        }
    }

    // Substitui a versão antiga (do configuracoes.js) em todas as páginas.
    window.mostrarToast = mostrarToast;

    // Blocos de mensagem que o servidor coloca no topo da página viram aviso no canto.
    function converterBlocos() {
        document.querySelectorAll('.aviso-erro-passo, [data-toast]').forEach(function (el) {
            const txt = el.textContent.trim();
            if (!txt) return;
            const tipo = el.dataset.toast || 'erro';
            el.hidden = true;
            el.style.display = 'none';
            mostrarToast(txt, tipo);
        });
    }

    // ------------------------------------------------------------- 3. animações
    const SELETOR_CARTOES = [
        '.card', '.categoria', '.denuncia', '.caixa-denuncia', '.painel-config',
        '.resultado-consulta', '.box-sucesso', '.perfil-card', '.secao-sobre',
        '.estado-vazio', '.grafico-card', '.usuario-linha', '.linha-auditoria'
    ].join(',');

    function animarCartoes() {
        if (reduzirMovimento || !('IntersectionObserver' in window)) return;
        const cartoes = Array.from(document.querySelectorAll(SELETOR_CARTOES))
            .filter(function (el) { return !el.closest('.leaflet-container, .oc-toasts, .mapa-lista'); });
        if (!cartoes.length) return;

        const io = new IntersectionObserver(function (entradas) {
            let ordem = 0;
            entradas.forEach(function (e) {
                if (!e.isIntersecting) return;
                const el = e.target;
                el.style.setProperty('--oc-atraso', Math.min(ordem++ * 55, 440) + 'ms');
                el.classList.add('oc-visivel');
                io.unobserve(el);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -20px 0px' });

        cartoes.forEach(function (el) {
            el.classList.add('oc-entrar');
            io.observe(el);
        });
        // Ao imprimir (ou salvar PDF pelo navegador), mostra tudo de uma vez.
        window.addEventListener('beforeprint', function () {
            cartoes.forEach(function (el) { el.classList.add('oc-visivel'); });
        });
    }

    // Números das estatísticas "contando" até o valor final (ex.: 0 → 18).
    function contarNumeros() {
        const alvos = document.querySelectorAll('.card h3, .card-stat h3, [data-contar]');
        alvos.forEach(function (el) {
            if (el.children.length) return;
            const original = el.textContent.trim();
            const m = original.match(/^(\d{1,6})(%?)$/);
            if (!m) return;
            const final = parseInt(m[1], 10);
            if (final < 2 || reduzirMovimento) return;
            const sufixo = m[2];
            el.textContent = '0' + sufixo;
            el.setAttribute('aria-label', original);

            const comecar = function () {
                const dur = Math.min(1200, 500 + final * 20);
                const t0 = performance.now();
                (function passo(agora) {
                    const p = Math.min(1, (agora - t0) / dur);
                    const suave = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(final * suave) + sufixo;
                    if (p < 1) requestAnimationFrame(passo);
                })(t0);
            };

            if ('IntersectionObserver' in window) {
                const io = new IntersectionObserver(function (es) {
                    if (es[0].isIntersecting) { io.disconnect(); comecar(); }
                });
                io.observe(el);
            } else {
                comecar();
            }
        });
    }

    // ------------------------------------------------ 4. olho que segue o mouse
    // Nas telas de login/cadastro, a pupila do logo acompanha o cursor (ou o dedo).
    // O logo "_olho.png" é o mesmo desenho sem a pupila; a pupila é um elemento
    // por cima, que se move dentro da íris.
    function olhoVivo() {
        const imgs = document.querySelectorAll('.pagina-auth .left .logo, [data-olho-vivo]');
        if (!imgs.length) return;
        const olhos = [];
        imgs.forEach(function (img) {
            if (img.closest('.olho-vivo')) return;
            const caixa = document.createElement('span');
            caixa.className = 'olho-vivo';
            img.parentNode.insertBefore(caixa, img);
            caixa.appendChild(img);
            img.setAttribute('src', img.getAttribute('src').replace(/\/logo(_white)?\.png/, '/logo$1_olho.png'));
            const pupila = document.createElement('span');
            pupila.className = 'olho-pupila';
            pupila.setAttribute('aria-hidden', 'true');
            caixa.appendChild(pupila);
            olhos.push({ img: img, pupila: pupila });
        });
        if (!olhos.length) return;

        // Centro da pupila no desenho original (imagem de 1254 px): x 631, y 528.
        const CX = 631 / 1254, CY = 528.5 / 1254, MAX = 55 / 1254;
        let alvoX = null, alvoY = null, pendente = false;

        function mover() {
            pendente = false;
            olhos.forEach(function (o) {
                const r = o.img.getBoundingClientRect();
                if (!r.width) return;
                const cx = r.left + r.width * CX, cy = r.top + r.height * CY;
                const dx = alvoX - cx, dy = alvoY - cy;
                const dist = Math.hypot(dx, dy) || 1;
                const lim = r.width * MAX;
                const k = Math.min(1, dist / (r.width * 2.2)); // perto do olho, move menos
                o.pupila.style.transform = 'translate(-50%, -50%) translate(' + (dx / dist * lim * k).toFixed(1) + 'px,' + (dy / dist * lim * k).toFixed(1) + 'px)';
            });
        }
        function olhar(x, y) {
            alvoX = x; alvoY = y;
            if (!pendente) { pendente = true; requestAnimationFrame(mover); }
        }
        document.addEventListener('pointermove', function (e) { olhar(e.clientX, e.clientY); }, { passive: true });
        document.addEventListener('touchstart', function (e) { const t = e.touches[0]; if (t) olhar(t.clientX, t.clientY); }, { passive: true });
        // Quando o cursor sai da janela, o olho volta ao centro.
        document.addEventListener('mouseleave', function () {
            olhos.forEach(function (o) { o.pupila.style.transform = 'translate(-50%, -50%)'; });
        });
        // Ao focar um campo, o olho "olha" para ele.
        document.addEventListener('focusin', function (e) {
            if (!e.target.matches('input, textarea, select')) return;
            const r = e.target.getBoundingClientRect();
            olhar(r.left + Math.min(r.width, 60), r.top + r.height / 2);
        });
        // Piscada de vez em quando.
        if (!reduzirMovimento) {
            setInterval(function () {
                olhos.forEach(function (o) {
                    o.img.parentNode.classList.add('piscando');
                    setTimeout(function () { o.img.parentNode.classList.remove('piscando'); }, 220);
                });
            }, 5200);
        }
    }

    // ------------------------------------------ 5. carregando (olho piscando)
    // Ao enviar um formulário ou abrir outra página, se demorar mais que um
    // instante, aparece o olho piscando — evita a sensação de tela travada.
    // O botão de envio também mostra "Enviando...".
    const SVG_OLHO = '<svg viewBox="0 0 120 70" class="oc-olho-svg" aria-hidden="true">' +
        '<g class="oc-olho-palpebra">' +
        '<path d="M6 35 Q60 -12 114 35 Q60 82 6 35 Z" fill="#fff" stroke="#0b1020" stroke-width="5" stroke-linejoin="round"/>' +
        '<circle cx="60" cy="35" r="20" fill="#22c55e"/><circle cx="60" cy="35" r="20" fill="url(#ocIris)"/>' +
        '<g class="oc-olho-pupila"><circle cx="60" cy="35" r="10" fill="#0b1020"/><circle cx="56" cy="31" r="3" fill="#fff"/></g>' +
        '</g>' +
        '<defs><radialGradient id="ocIris" cx="50%" cy="35%" r="65%"><stop offset="0" stop-color="#d9f99d"/><stop offset="1" stop-color="#16a34a" stop-opacity=".2"/></radialGradient></defs>' +
        '</svg>';

    let timerCarregando = null;
    function mostrarCarregando(texto) {
        clearTimeout(timerCarregando);
        timerCarregando = setTimeout(function () {
            let el = document.querySelector('.oc-carregando');
            if (!el) {
                el = document.createElement('div');
                el.className = 'oc-carregando';
                el.setAttribute('role', 'status');
                el.innerHTML = '<div class="oc-carregando-caixa">' + SVG_OLHO + '<span class="oc-carregando-texto"></span></div>';
                document.body.appendChild(el);
            }
            el.querySelector('.oc-carregando-texto').textContent = texto || 'Carregando...';
            el.classList.add('ativo');
            // Segurança: se nada acontecer (ex.: um download), some sozinho.
            setTimeout(esconderCarregando, 9000);
        }, 350);
    }
    function esconderCarregando() {
        clearTimeout(timerCarregando);
        const el = document.querySelector('.oc-carregando');
        if (el) el.classList.remove('ativo');
        document.querySelectorAll('[data-oc-enviando]').forEach(function (b) {
            b.innerHTML = b.dataset.ocTextoOriginal;
            b.disabled = false;
            b.removeAttribute('data-oc-enviando');
        });
    }
    window.mostrarCarregando = mostrarCarregando;
    window.esconderCarregando = esconderCarregando;

    function ligarCarregamento() {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            // Espera os outros scripts decidirem (validação, confirmação personalizada).
            setTimeout(function () {
                if (e.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-sem-carregando')) return;
                if (/exportar/.test(form.action)) return; // download: a página não muda
                const botao = e.submitter || form.querySelector('button[type="submit"], button:not([type])');
                if (botao && !botao.hasAttribute('data-oc-enviando')) {
                    botao.dataset.ocTextoOriginal = botao.innerHTML;
                    botao.setAttribute('data-oc-enviando', '');
                    botao.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Enviando...';
                    // desabilita depois do envio já ter saído (senão o valor do botão se perde)
                    setTimeout(function () { botao.disabled = true; }, 0);
                }
                mostrarCarregando(form.method.toLowerCase() === 'post' ? 'Enviando...' : 'Carregando...');
            }, 0);
        });

        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;
            const href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
            if (a.target === '_blank' || a.hasAttribute('download') || /exportar|csv/.test(href)) return;
            if (a.origin !== location.origin) return;
            mostrarCarregando();
        });

        // Voltar pelo navegador (página vinda do cache) não pode ficar com o olho na tela.
        window.addEventListener('pageshow', esconderCarregando);
    }

    // ------------------------------------------- 6. luz que segue o mouse
    // Nos cartões, um brilho verde acompanha o cursor e a borda acende em degradê.
    const SELETOR_LUZ = '.card, .categoria, .denuncia-card, .item-denuncia, .destaque, .atalhos a, .painel-config, .grafico-card';
    function luzNosCartoes() {
        if (reduzirMovimento || !window.matchMedia('(hover: hover)').matches) return;
        document.querySelectorAll(SELETOR_LUZ).forEach(function (el) {
            if (el.closest('.leaflet-container')) return;
            const pos = getComputedStyle(el).position;
            if (pos !== 'static' && pos !== 'relative') return;
            el.classList.add('oc-luz');
        });
        document.addEventListener('pointermove', function (e) {
            const el = e.target.closest && e.target.closest('.oc-luz');
            if (!el) return;
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            el.style.setProperty('--my', (e.clientY - r.top) + 'px');
        }, { passive: true });
    }

    function iniciar() {
        olhoVivo();
        luzNosCartoes();
        ligarCarregamento();
        converterBlocos();
        animarCartoes();
        contarNumeros();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
