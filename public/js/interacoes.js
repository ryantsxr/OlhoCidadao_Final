/* =========================================================================
 * Interações v0.49 (todas as páginas): cartões em 3D, dicas (tooltips),
 * reações ao toque, menu recolhível, rótulos que sobem, tela de abertura do
 * PWA, contador no ícone da aba, puxar para atualizar e rodapé.
 * ========================================================================= */
(function () {
    'use strict';
    const reduzir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const temMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const BASE = window.OC_BASE || '';

    // ------------------------------------------------ 6. cartões em 3D
    function cartoes3d() {
        if (reduzir || !temMouse) return;
        document.querySelectorAll('.categoria, .caixa-denuncia .card, .destaque').forEach(function (el) {
            el.classList.add('oc-3d');
            el.addEventListener('pointermove', function (e) {
                const r = el.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
                el.style.transform = 'perspective(700px) rotateX(' + (-y * 8).toFixed(2) + 'deg) rotateY(' + (x * 10).toFixed(2) + 'deg) translateY(-4px)';
            });
            el.addEventListener('pointerleave', function () { el.style.transform = ''; });
        });
    }

    // ------------------------------------------------ 8. dicas (tooltips)
    // Botões/links só com ícone ganham uma dica estilizada no lugar do "title" do navegador.
    function dicas() {
        let dica = null, alvo = null;
        function mostrar(el) {
            const txt = el.getAttribute('data-dica');
            if (!txt) return;
            alvo = el;
            if (!dica) { dica = document.createElement('div'); dica.className = 'oc-dica'; dica.setAttribute('role', 'tooltip'); document.body.appendChild(dica); }
            dica.textContent = txt;
            const r = el.getBoundingClientRect();
            dica.style.left = '0px'; dica.style.top = '0px';
            dica.classList.add('visivel');
            const w = dica.offsetWidth, h = dica.offsetHeight;
            let top = r.top - h - 8; if (top < 6) top = r.bottom + 8;
            dica.style.left = Math.max(6, Math.min(innerWidth - w - 6, r.left + r.width / 2 - w / 2)) + 'px';
            dica.style.top = top + 'px';
        }
        function esconder() { if (dica) dica.classList.remove('visivel'); alvo = null; }
        function preparar(raiz) {
            raiz.querySelectorAll('a[title], button[title]').forEach(function (el) {
                if (el.textContent.trim().length > 2 || el.closest('.leaflet-container')) return;
                el.setAttribute('data-dica', el.getAttribute('title'));
                if (!el.getAttribute('aria-label')) el.setAttribute('aria-label', el.getAttribute('title'));
                el.removeAttribute('title');
            });
        }
        preparar(document);
        document.addEventListener('mouseover', function (e) { const el = e.target.closest('[data-dica]'); if (el && el !== alvo) mostrar(el); else if (!el && alvo) esconder(); });
        document.addEventListener('focusin', function (e) { const el = e.target.closest('[data-dica]'); if (el) mostrar(el); });
        document.addEventListener('focusout', esconder);
        addEventListener('scroll', esconder, { passive: true });
    }

    // ------------------------------------------------ 47. reações ao toque
    function reacoes() {
        document.addEventListener('pointerdown', function (e) {
            const b = e.target.closest('button[type="submit"], [class*="btn"], .denunciar, .barra-item, .popup-botao');
            if (!b || reduzir || b.disabled) return;
            const r = b.getBoundingClientRect();
            const cs = getComputedStyle(b);
            if (cs.position === 'static') b.style.position = 'relative';
            if (cs.overflow !== 'hidden') b.style.overflow = 'hidden';
            const onda = document.createElement('span');
            const t = Math.max(r.width, r.height);
            onda.className = 'oc-onda';
            Object.assign(onda.style, { width: t + 'px', height: t + 'px', left: (e.clientX - r.left - t / 2) + 'px', top: (e.clientY - r.top - t / 2) + 'px' });
            b.appendChild(onda);
            setTimeout(function () { onda.remove(); }, 600);
        });
        // Pulso ao apoiar / copiar
        document.addEventListener('click', function (e) {
            const b = e.target.closest('.btn-apoiar-denuncia, .aviso-parecida-apoiar, .ingresso-copiar');
            if (!b || reduzir) return;
            b.classList.remove('oc-pulso'); void b.offsetWidth; b.classList.add('oc-pulso');
            if (navigator.vibrate) navigator.vibrate(18);
        });
    }

    // ------------------------------------------------ 20. menu lateral recolhível
    function menuRecolhivel() {
        const menu = document.querySelector('aside.menu .menu-top');
        if (!menu) return;
        const logo = menu.querySelector('.logo');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-recolher-menu';
        btn.innerHTML = '<i class="fa-solid fa-angles-left"></i><span>Recolher menu</span>';
        btn.setAttribute('aria-label', 'Recolher ou expandir o menu');
        (logo && logo.nextSibling) ? menu.insertBefore(btn, logo.nextSibling) : menu.prepend(btn);
        // nos itens do menu, o texto vira dica quando recolhido
        menu.querySelectorAll('nav a').forEach(function (a) {
            const s = a.querySelector('span'); if (s && !a.getAttribute('aria-label')) a.setAttribute('aria-label', s.textContent.trim());
        });
        function aplicar(rec) {
            document.body.classList.toggle('menu-recolhido', rec);
            btn.setAttribute('aria-expanded', rec ? 'false' : 'true');
            menu.querySelectorAll('nav a').forEach(function (a) {
                if (rec) a.setAttribute('data-dica', a.getAttribute('aria-label') || ''); else a.removeAttribute('data-dica');
            });
        }
        let rec = false;
        try { rec = localStorage.getItem('oc-menu-recolhido') === '1'; } catch (e) {}
        aplicar(rec);
        btn.addEventListener('click', function () {
            rec = !document.body.classList.contains('menu-recolhido');
            aplicar(rec);
            try { localStorage.setItem('oc-menu-recolhido', rec ? '1' : '0'); } catch (e) {}
        });
    }

    // ------------------------------------------------ 24. rótulos que sobem + validação
    function rotulosFlutuantes() {
        document.querySelectorAll('.pagina-auth form input[placeholder]:not([type="hidden"]):not([type="checkbox"])').forEach(function (inp) {
            if (inp.closest('.campo-flutua')) return;
            let caixa = inp.closest('.campo');
            if (!caixa || caixa.querySelectorAll('input').length > 1) {
                caixa = document.createElement('span');
                inp.parentNode.insertBefore(caixa, inp);
                caixa.appendChild(inp);
            }
            caixa.classList.add('campo-flutua');
            if (caixa.querySelector('.icone-campo')) caixa.classList.add('com-icone');
            if (!inp.id) inp.id = 'campo-' + Math.random().toString(36).slice(2, 8);
            const rot = document.createElement('label');
            rot.className = 'rotulo-flutua';
            rot.htmlFor = inp.id;
            rot.textContent = inp.getAttribute('placeholder');
            inp.insertAdjacentElement('afterend', rot);
            function estado(validar) {
                caixa.classList.toggle('ativo', inp.value !== '' || document.activeElement === inp);
                if (validar && inp.value !== '') {
                    const ok = inp.checkValidity();
                    caixa.classList.toggle('valido', ok);
                    caixa.classList.toggle('invalido', !ok);
                } else if (inp.value === '') {
                    caixa.classList.remove('valido', 'invalido');
                }
            }
            inp.addEventListener('focus', function () { estado(false); });
            inp.addEventListener('blur', function () { estado(true); });
            inp.addEventListener('input', function () { estado(caixa.classList.contains('invalido') || caixa.classList.contains('valido')); });
            setTimeout(function () { estado(false); }, 300); // preenchimento automático do navegador
            estado(false);
        });
    }

    // ------------------------------------------------ 49. tela de abertura do app instalado
    function splash() {
        const instalado = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
        let ja = false;
        try { ja = sessionStorage.getItem('oc-splash') === '1'; sessionStorage.setItem('oc-splash', '1'); } catch (e) { ja = true; }
        if (!instalado || ja) return;
        const s = document.createElement('div');
        s.className = 'oc-splash';
        s.innerHTML = '<img src="' + BASE + '/img/logo.png" alt=""><span>Sua voz faz a diferença</span>';
        document.body.appendChild(s);
        setTimeout(function () { s.classList.add('saindo'); setTimeout(function () { s.remove(); }, 500); }, reduzir ? 300 : 1300);
    }

    // ------------------------------------------------ 50. contador no ícone da aba
    function faviconComContador() {
        const badge = document.querySelector('.badge-notificacao');
        const n = badge ? badge.textContent.trim() : '';
        if (!n) return;
        document.title = '(' + n + ') ' + document.title.replace(/^\(\S+\)\s/, '');
        const img = new Image();
        img.onload = function () {
            const c = document.createElement('canvas'); c.width = c.height = 64;
            const ctx = c.getContext('2d');
            ctx.drawImage(img, 0, 0, 64, 64);
            ctx.fillStyle = '#ef4444';
            ctx.beginPath(); ctx.arc(46, 18, 17, 0, Math.PI * 2); ctx.fill();
            ctx.lineWidth = 3; ctx.strokeStyle = '#181f2c'; ctx.stroke();
            ctx.fillStyle = '#fff'; ctx.font = 'bold ' + (n.length > 1 ? 20 : 24) + 'px sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(n, 46, 19);
            let link = document.querySelector('link[rel="icon"]');
            if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
            link.href = c.toDataURL('image/png');
        };
        img.src = BASE + '/img/icon-192.png';
    }

    // ------------------------------------------------ 48. puxar para atualizar (celular)
    function puxarParaAtualizar() {
        if (!('ontouchstart' in window) || document.querySelector('.leaflet-container:not(.mapa-perto)') && document.querySelector('.mapa-publico-layout')) return;
        const ind = document.createElement('div');
        ind.className = 'oc-puxar';
        ind.innerHTML = '<i class="fa-solid fa-arrow-down"></i>';
        document.body.appendChild(ind);
        let y0 = null, dy = 0;
        addEventListener('touchstart', function (e) {
            if (scrollY > 0 || e.touches.length > 1 || e.target.closest('.leaflet-container, .gaveta, .mapa-lista, .kanban, textarea, .menu-aberto')) { y0 = null; return; }
            y0 = e.touches[0].clientY; dy = 0;
        }, { passive: true });
        addEventListener('touchmove', function (e) {
            if (y0 === null) return;
            dy = Math.max(0, e.touches[0].clientY - y0);
            const p = Math.min(dy, 120);
            ind.style.transform = 'translateY(' + (p - 50) + 'px)';
            ind.style.opacity = Math.min(1, p / 70);
            ind.classList.toggle('pronto', p >= 90);
        }, { passive: true });
        addEventListener('touchend', function () {
            if (y0 === null) return;
            if (dy >= 90) {
                ind.innerHTML = '<i class="fa-solid fa-rotate fa-spin"></i>';
                ind.style.transform = 'translateY(20px)';
                location.reload();
            } else {
                ind.style.transform = 'translateY(-60px)'; ind.style.opacity = 0;
            }
            y0 = null;
        });
    }

    // ------------------------------------------------ 56. rodapé com identidade
    function rodape() {
        if (document.querySelector('.oc-rodape') || document.querySelector('.mapa-publico-layout, .relatorio, .pagina-relatorio')) return;
        const alvo = document.querySelector('main.conteudo') || document.querySelector('.pagina-auth .container') || document.querySelector('.pagina-conteudo, .pagina-erro, .consulta-wrap, .detalhe-denuncia-wrap');
        if (!alvo || alvo.closest('.oc-splash')) return;
        const f = document.createElement('footer');
        f.className = 'oc-rodape';
        const equipe = window.OC_EQUIPE ? ' · Feito por ' + window.OC_EQUIPE : ' · Projeto do curso técnico em Informática';
        f.innerHTML = '<span><strong>Olho Cidadão</strong> <span class="oc-rodape-versao">' + (window.OC_VERSAO || '') + '</span>' + equipe.replace(/</g, '&lt;') + '</span>' +
            '<nav aria-label="Links úteis"><a href="' + BASE + '/?rota=mapa">Mapa público</a><a href="' + BASE + '/?rota=consulta">Consultar protocolo</a><a href="' + BASE + '/?rota=sobre">Sobre</a><a href="' + BASE + '/?rota=termos">Termos e privacidade</a></nav>';
        if (alvo.matches('main.conteudo')) { alvo.appendChild(f); return; }
        alvo.insertAdjacentElement('afterend', f);
        // Fora do painel (detalhe, consulta, páginas avulsas): o rodapé acompanha a
        // largura do conteúdo, centralizado, em vez de encostar nas bordas da tela.
        if (alvo.closest('.pagina-auth')) return;
        f.classList.add('oc-rodape-solto');
        const ajustar = function () {
            const cs = getComputedStyle(alvo);
            const largura = alvo.getBoundingClientRect().width - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
            f.style.maxWidth = Math.max(280, Math.round(largura)) + 'px';
        };
        ajustar();
        addEventListener('resize', ajustar, { passive: true });
    }

    function iniciar() {
        splash();
        menuRecolhivel();
        dicas();
        cartoes3d();
        reacoes();
        rotulosFlutuantes();
        faviconComContador();
        puxarParaAtualizar();
        rodape();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
