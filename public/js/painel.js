/* Painel do cidadão (v0.49): saudação pelo horário, mini gráficos, dica do dia,
 * gaveta de notificações, mini mapa "perto de você" e tour do primeiro acesso. */
document.addEventListener('DOMContentLoaded', function () {
    const reduzir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ------------------------------------------------ saudação pelo horário
    const h = new Date().getHours();
    const [saud, icone, sub] =
        h < 5 ? ['Boa noite', 'fa-moon', 'Trabalhando até tarde? A cidade agradece.'] :
        h < 12 ? ['Bom dia', 'fa-sun', 'Que tal dar uma olhada na sua rua hoje?'] :
        h < 18 ? ['Boa tarde', 'fa-cloud-sun', 'Acompanhe o andamento das suas denúncias.'] :
                 ['Boa noite', 'fa-moon', 'Poste apagado na volta pra casa? Conte pra gente.'];
    const elS = document.getElementById('saudacao');
    if (elS) elS.textContent = saud;
    const elI = document.getElementById('saudacao-icone');
    if (elI) { elI.innerHTML = '<i class="fa-solid ' + icone + '"></i>'; elI.dataset.periodo = icone; }
    const elSub = document.getElementById('saudacao-sub');
    if (elSub) elSub.textContent = sub;

    // ------------------------------------------------ mini gráficos (sparklines)
    document.querySelectorAll('svg.sparkline').forEach(function (svg) {
        let serie;
        try { serie = JSON.parse(svg.dataset.serie || '[]'); } catch (e) { return; }
        if (!serie.length) return;
        const max = Math.max(1, Math.max.apply(null, serie));
        const passo = 100 / (serie.length - 1 || 1);
        const pts = serie.map(function (v, i) { return [i * passo, 29 - (v / max) * 25]; });
        const linha = pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
        const area = linha + ' L100 32 L0 32 Z';
        const ult = pts[pts.length - 1];
        svg.innerHTML = '<path class="spark-area" d="' + area + '"/><path class="spark-linha" d="' + linha + '" pathLength="100"/>' +
            '<circle class="spark-ponto" cx="' + ult[0] + '" cy="' + ult[1] + '" r="2.4"/>';
        svg.setAttribute('aria-label', 'Últimos 6 meses: ' + serie.join(', '));
    });

    // ------------------------------------------------ dica do dia
    const DICAS = [
        'Fotos tiradas de dia, mostrando o problema de perto, ajudam a prefeitura a agir mais rápido.',
        'Marque o local exato no mapa: a equipe chega direto no ponto, sem procurar.',
        'Viu que alguém já denunciou o mesmo problema? Apoie a denúncia em vez de criar outra — ela ganha prioridade.',
        'Com 3 apoios de vizinhos, a denúncia ganha o selo "verificado por moradores".',
        'Guarde o protocolo: com ele qualquer pessoa acompanha a denúncia, mesmo sem login.',
        'Quando a prefeitura marcar como resolvida, confirme só se o problema sumiu mesmo.',
        'Descreva o que, onde e desde quando. Ex.: "Poste apagado em frente ao nº 120 há 5 dias".',
        'Prefere não aparecer? Marque a opção de denúncia anônima no envio.',
        'No celular, instale o Olho Cidadão na tela inicial: ele abre como um aplicativo.'
    ];
    const elDica = document.getElementById('dica-texto');
    if (elDica) {
        const inicioAno = new Date(new Date().getFullYear(), 0, 0);
        let i = Math.floor((new Date() - inicioAno) / 86400000) % DICAS.length;
        elDica.textContent = DICAS[i];
        const btn = document.getElementById('dica-proxima');
        if (btn) btn.addEventListener('click', function () {
            i = (i + 1) % DICAS.length;
            elDica.classList.remove('trocando'); void elDica.offsetWidth; elDica.classList.add('trocando');
            elDica.textContent = DICAS[i];
        });
    }

    // ------------------------------------------------ gaveta de notificações
    const btnN = document.getElementById('btn-notificacoes');
    const painelN = document.getElementById('painel-notificacoes');
    if (btnN && painelN) {
        const fundo = document.createElement('div');
        fundo.className = 'gaveta-fundo';
        document.body.appendChild(fundo);
        document.body.appendChild(painelN);   // sai de dentro do cabeçalho para cobrir a tela toda
        painelN.classList.add('gaveta');
        painelN.setAttribute('role', 'dialog');
        painelN.setAttribute('aria-label', 'Notificações');
        function abrir(sim) {
            painelN.classList.toggle('oculto', !sim);
            painelN.classList.toggle('aberta', sim);
            fundo.classList.toggle('ativo', sim);
            btnN.setAttribute('aria-expanded', sim ? 'true' : 'false');
            if (sim) { const f = painelN.querySelector('button, a'); if (f) f.focus(); } else btnN.focus();
        }
        btnN.addEventListener('click', function (e) { e.stopPropagation(); abrir(!painelN.classList.contains('aberta')); });
        fundo.addEventListener('click', function () { abrir(false); });
        const fechar = document.getElementById('fechar-notificacoes');
        if (fechar) fechar.addEventListener('click', function () { abrir(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && painelN.classList.contains('aberta')) abrir(false); });
    }

    // ------------------------------------------------ mini mapa "perto de você"
    const elMapa = document.getElementById('mapa-perto');
    if (elMapa && typeof L !== 'undefined') {
        let pontos = [];
        try { pontos = JSON.parse(elMapa.dataset.pontos || '[]'); } catch (e) {}
        const cores = { 'pendente': '#f2b705', 'em análise': '#3d8bfd', 'aguardando confirmação': '#a78bfa' };
        const escuro = !document.body.classList.contains('tema-claro');
        const mapa = L.map(elMapa, { zoomControl: false, attributionControl: true, scrollWheelZoom: false }).setView([-10.7838, -65.3355], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors', maxZoom: 19, className: escuro ? 'tiles-escuras' : ''
        }).addTo(mapa);
        const grupo = L.featureGroup().addTo(mapa);
        pontos.forEach(function (p) {
            L.circleMarker([p.lat, p.lng], { radius: 6, color: '#fff', weight: 1.5, fillColor: cores[(p.status || '').toLowerCase()] || '#aaa', fillOpacity: 1 })
                .bindTooltip(p.categoria.charAt(0).toUpperCase() + p.categoria.slice(1) + ' · ' + p.status)
                .on('click', function () { location.href = elMapa.dataset.urlVer + p.id; })
                .addTo(grupo);
        });
        if (pontos.length) mapa.fitBounds(grupo.getBounds().pad(0.25));
        const resumo = document.getElementById('perto-resumo');
        if (resumo) resumo.textContent = pontos.length + ' denúncia' + (pontos.length === 1 ? '' : 's') + ' aberta' + (pontos.length === 1 ? '' : 's') + ' na cidade';

        const btnPerto = document.getElementById('btn-perto');
        if (btnPerto) btnPerto.addEventListener('click', function () {
            if (!navigator.geolocation) { window.mostrarToast && mostrarToast('Seu navegador não informa a localização.', 'aviso'); return; }
            btnPerto.disabled = true;
            btnPerto.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Localizando...';
            navigator.geolocation.getCurrentPosition(function (pos) {
                const eu = L.latLng(pos.coords.latitude, pos.coords.longitude);
                L.circle(eu, { radius: 1000, color: '#22c55e', weight: 1, fillOpacity: .06 }).addTo(mapa);
                L.marker(eu, { icon: L.divIcon({ className: 'voce-aqui', html: '<span></span>', iconSize: [18, 18] }) }).addTo(mapa).bindTooltip('Você está aqui');
                mapa.setView(eu, 15);
                const perto = pontos.filter(function (p) { return eu.distanceTo([p.lat, p.lng]) <= 1000; }).length;
                resumo.textContent = perto === 0 ? 'Nenhuma denúncia aberta a 1 km de você' : perto + ' denúncia' + (perto === 1 ? '' : 's') + ' aberta' + (perto === 1 ? '' : 's') + ' a até 1 km de você';
                btnPerto.innerHTML = '<i class="fa-solid fa-check"></i> Localizado';
            }, function () {
                btnPerto.disabled = false;
                btnPerto.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Usar minha localização';
                window.mostrarToast && mostrarToast('Não foi possível pegar sua localização.', 'aviso');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    }

    // ------------------------------------------------ tour do primeiro acesso
    const PASSOS = [
        ['.denunciar', 'Fazer uma denúncia', 'Comece por aqui: escolha a categoria, descreva, tire uma foto e marque no mapa.'],
        ['.cards', 'Seus números', 'Quantas denúncias você fez, quantas estão em análise e quantas já foram resolvidas.'],
        ['#card-impacto', 'Seu impacto', 'Veja o que suas denúncias já causaram na cidade.'],
        ['#btn-notificacoes', 'Avisos', 'Quando a prefeitura responder ou alguém apoiar sua denúncia, o aviso chega aqui.'],
        ['.categorias', 'Atalhos', 'Toque numa categoria para começar a denúncia já com ela escolhida.'],
        ['.card-perto', 'Perto de você', 'O que já foi denunciado no seu bairro. Se for o mesmo problema, apoie em vez de repetir.']
    ];
    function tour() {
        const passos = PASSOS.filter(function (p) { const el = document.querySelector(p[0]); return el && el.offsetParent !== null; });
        if (!passos.length) return;
        let i = 0;
        const realce = document.createElement('div'); realce.className = 'tour-realce';
        const balao = document.createElement('div'); balao.className = 'tour-balao'; balao.setAttribute('role', 'dialog'); balao.setAttribute('aria-live', 'polite');
        document.body.append(realce, balao);
        function fim() { realce.remove(); balao.remove(); try { localStorage.setItem('oc-tour-painel', '1'); } catch (e) {} document.removeEventListener('keydown', tecla); }
        function tecla(e) { if (e.key === 'Escape') fim(); if (e.key === 'ArrowRight') ir(i + 1); if (e.key === 'ArrowLeft') ir(i - 1); }
        document.addEventListener('keydown', tecla);
        function ir(n) {
            if (n < 0) return;
            if (n >= passos.length) { fim(); return; }
            i = n;
            const [sel, titulo, texto] = passos[i];
            const el = document.querySelector(sel);
            el.scrollIntoView({ block: 'center', behavior: reduzir ? 'auto' : 'smooth' });
            setTimeout(function () {
                const r = el.getBoundingClientRect();
                Object.assign(realce.style, { top: (r.top - 8) + 'px', left: (r.left - 8) + 'px', width: (r.width + 16) + 'px', height: (r.height + 16) + 'px' });
                balao.innerHTML = '<small>' + (i + 1) + ' de ' + passos.length + '</small><strong>' + titulo + '</strong><p>' + texto + '</p>' +
                    '<div class="tour-acoes"><button type="button" data-tour="pular">Pular</button><span>' +
                    (i > 0 ? '<button type="button" data-tour="voltar">Voltar</button>' : '') +
                    '<button type="button" data-tour="proximo" class="principal">' + (i === passos.length - 1 ? 'Concluir' : 'Próximo') + '</button></span></div>';
                const bw = Math.min(320, innerWidth - 24);
                let top = r.bottom + 16, left = Math.max(12, Math.min(r.left, innerWidth - bw - 12));
                if (top + 190 > innerHeight) top = Math.max(12, r.top - 200);
                Object.assign(balao.style, { top: top + 'px', left: left + 'px', width: bw + 'px' });
                balao.querySelector('[data-tour="proximo"]').focus();
            }, reduzir ? 0 : 350);
        }
        balao.addEventListener('click', function (e) {
            const b = e.target.closest('[data-tour]'); if (!b) return;
            if (b.dataset.tour === 'pular') fim(); else ir(i + (b.dataset.tour === 'voltar' ? -1 : 1));
        });
        addEventListener('resize', function () { if (document.body.contains(balao)) ir(i); });
        ir(0);
    }
    const btnTour = document.getElementById('btn-tour');
    if (btnTour) btnTour.addEventListener('click', tour);
    let jaViu = false;
    try { jaViu = localStorage.getItem('oc-tour-painel') === '1'; } catch (e) { jaViu = true; }
    if (!jaViu) setTimeout(tour, 900);
});
