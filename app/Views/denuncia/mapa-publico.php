<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de denúncias - Olho Cidadão</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/leaflet.markercluster/MarkerCluster.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/mapa-publico.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>

<body>

    <div class="mapa-publico-layout">

        <header class="mapa-publico-topo">
            <a href="<?= BASE_URL ?>/?rota=<?= $logado ? ($ehAdmin ? 'admin' : 'dashboard') : 'login' ?>" class="mapa-publico-logo">
                <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Olho Cidadão">
                <span>Olho <strong>Cidadão</strong></span>
            </a>

            <div class="mapa-publico-legenda">
                <span><i class="fa-solid fa-clock legenda-pendente" aria-hidden="true" style="color:#f2b705"></i> Pendente</span>
                <span><i class="fa-solid fa-magnifying-glass legenda-analise" aria-hidden="true" style="color:#3d8bfd"></i> Em análise</span>
                <span><i class="fa-solid fa-hourglass-half legenda-aguardando" aria-hidden="true" style="color:#a78bfa"></i> Aguardando confirmação</span>
                <span><i class="fa-solid fa-check legenda-resolvido" aria-hidden="true" style="color:#00d600"></i> Resolvido</span>
            </div>

            <a href="<?= BASE_URL ?>/?rota=transparencia" class="mapa-publico-voltar" aria-label="Ver indicadores públicos">Transparência</a>
            <a href="<?= BASE_URL ?>/?rota=<?= $logado ? ($ehAdmin ? 'admin' : 'dashboard') : 'login' ?>" class="mapa-publico-voltar">
                <?= $logado ? 'Voltar ao painel' : 'Entrar' ?>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </header>

        <div class="mapa-publico-corpo">
          <div class="mapa-area">
            <div class="mapa-modo" role="group" aria-label="Modo de visualização">
                <button type="button" id="modo-pontos" class="ativo"><i class="fa-solid fa-location-dot"></i> Pontos</button>
                <button type="button" id="modo-calor"><i class="fa-solid fa-fire"></i> Calor</button>
                <button type="button" id="modo-lista" class="so-celular" aria-controls="mapa-lista" aria-expanded="false"><i class="fa-solid fa-list"></i> Lista</button>
            </div>
            <aside class="mapa-filtros" id="mapa-filtros" aria-label="Filtros do mapa">
                <button type="button" class="mapa-filtros-titulo" id="mapa-filtros-toggle" aria-expanded="true" aria-controls="mapa-filtros-corpo">
                    <span><i class="fa-solid fa-filter"></i> Filtros</span>
                    <span class="mapa-filtros-contador" id="mapa-contador"></span>
                    <i class="fa-solid fa-chevron-up mapa-filtros-seta"></i>
                </button>
                <div class="mapa-filtros-corpo" id="mapa-filtros-corpo">
                    <p class="mapa-filtros-rotulo">Categoria</p>
                    <div class="mapa-chips" id="filtro-categoria">
                        <label class="chip"><input type="checkbox" value="iluminacao" checked><span><i class="fa-solid fa-lightbulb"></i> Iluminação <em data-qtd-cat="iluminacao"></em></span></label>
                        <label class="chip"><input type="checkbox" value="sinalizacao" checked><span><i class="fa-solid fa-traffic-light"></i> Sinalização <em data-qtd-cat="sinalizacao"></em></span></label>
                        <label class="chip"><input type="checkbox" value="lixo" checked><span><i class="fa-solid fa-trash"></i> Lixo <em data-qtd-cat="lixo"></em></span></label>
                        <label class="chip"><input type="checkbox" value="buracos" checked><span><i class="fa-solid fa-road"></i> Buracos e vias <em data-qtd-cat="buracos"></em></span></label>
                        <label class="chip"><input type="checkbox" value="esgoto" checked><span><i class="fa-solid fa-water"></i> Esgoto e drenagem <em data-qtd-cat="esgoto"></em></span></label>
                        <label class="chip"><input type="checkbox" value="agua" checked><span><i class="fa-solid fa-faucet-drip"></i> Abastecimento de água <em data-qtd-cat="agua"></em></span></label>
                        <label class="chip"><input type="checkbox" value="arborizacao" checked><span><i class="fa-solid fa-tree"></i> Árvores e poda <em data-qtd-cat="arborizacao"></em></span></label>
                        <label class="chip"><input type="checkbox" value="outros" checked><span><i class="fa-solid fa-plus"></i> Outros <em data-qtd-cat="outros"></em></span></label>
                    </div>
                    <p class="mapa-filtros-rotulo">Status</p>
                    <div class="mapa-chips" id="filtro-status">
                        <label class="chip"><input type="checkbox" value="pendente" checked><span><i class="fa-solid fa-circle" style="color:#f2b705"></i> Pendente <em data-qtd-st="pendente"></em></span></label>
                        <label class="chip"><input type="checkbox" value="em análise" checked><span><i class="fa-solid fa-circle" style="color:#3d8bfd"></i> Em análise <em data-qtd-st="em análise"></em></span></label>
                        <label class="chip"><input type="checkbox" value="aguardando confirmação" checked><span><i class="fa-solid fa-circle" style="color:#a78bfa"></i> Aguardando <em data-qtd-st="aguardando confirmação"></em></span></label>
                        <label class="chip"><input type="checkbox" value="resolvido" checked><span><i class="fa-solid fa-circle" style="color:#00d600"></i> Resolvido <em data-qtd-st="resolvido"></em></span></label>
                    </div>
                    <p class="mapa-filtros-rotulo">Período</p>
                    <div class="mapa-periodo" id="filtro-periodo" role="radiogroup" aria-label="Período">
                        <button type="button" role="radio" aria-checked="false" data-dias="7">7 dias</button>
                        <button type="button" role="radio" aria-checked="false" data-dias="30">30 dias</button>
                        <button type="button" role="radio" aria-checked="false" data-dias="90">90 dias</button>
                        <button type="button" role="radio" aria-checked="true" data-dias="0" class="ativo">Tudo</button>
                    </div>
                    <button type="button" class="mapa-play" id="btn-linha-tempo"><i class="fa-solid fa-play"></i> Ver a evolução no tempo</button>
                    <label class="mapa-opcao"><input type="checkbox" id="filtro-agrupar" checked> Agrupar pontos próximos</label>
                    <button type="button" class="mapa-limpar" id="filtro-limpar"><i class="fa-solid fa-rotate-left"></i> Mostrar tudo</button>
                </div>
            </aside>
            <div class="mapa-estilos" id="mapa-estilos" role="group" aria-label="Estilo do mapa">
                <button type="button" data-estilo="escuro" title="Mapa escuro"><i class="fa-solid fa-moon"></i></button>
                <button type="button" data-estilo="ruas" title="Ruas"><i class="fa-solid fa-road"></i></button>
                <button type="button" data-estilo="satelite" title="Satélite"><i class="fa-solid fa-satellite"></i></button>
            </div>
            <div id="mapa-publico" class="mapa-publico-mapa"></div>
            <div class="mapa-ficha" id="mapa-ficha" aria-hidden="true" role="dialog" aria-label="Detalhes da denúncia">
                <span class="mapa-ficha-alca" aria-hidden="true"></span>
                <button type="button" class="mapa-ficha-fechar" id="mapa-ficha-fechar" aria-label="Fechar detalhes"><i class="fa-solid fa-xmark"></i></button>
                <div class="mapa-ficha-conteudo"></div>
            </div>
            <div class="mapa-linha-tempo" id="mapa-linha-tempo" hidden>
                <span class="mapa-lt-data" id="mapa-lt-data"></span>
                <div class="mapa-lt-barra"><span id="mapa-lt-progresso"></span></div>
                <span class="mapa-lt-qtd" id="mapa-lt-qtd"></span>
            </div>
            <div class="mapa-vazio" id="mapa-vazio" hidden>
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Nenhuma denúncia com esses filtros.</span>
                <button type="button" data-mostrar-tudo>Mostrar tudo</button>
            </div>
          </div>

          <aside class="mapa-lista" id="mapa-lista" aria-label="Lista de denúncias no mapa">
              <div class="mapa-lista-topo">
                  <div>
                      <h2>Denúncias no mapa</h2>
                      <p id="mapa-lista-resumo">—</p>
                  </div>
                  <select id="mapa-lista-ordem" aria-label="Ordenar lista">
                      <option value="recentes">Mais recentes</option>
                      <option value="apoios">Mais apoiadas</option>
                  </select>
                  <button type="button" class="mapa-lista-fechar so-celular" id="mapa-lista-fechar" aria-label="Fechar lista"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <ul class="mapa-lista-itens" id="mapa-lista-itens"></ul>
              <div class="estado-vazio-mapa" id="mapa-lista-vazia" hidden>
                  <span class="estado-vazio-icone"><i class="fa-solid fa-magnifying-glass-location"></i></span>
                  <strong>Nada por aqui</strong>
                  <p>Nenhuma denúncia combina com os filtros escolhidos.</p>
                  <button type="button" class="btn-primario-mapa" data-mostrar-tudo><i class="fa-solid fa-rotate-left"></i> Mostrar tudo</button>
              </div>
          </aside>
        </div>

    </div>

    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet/leaflet.js"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet.heat/leaflet-heat.js"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet.markercluster/leaflet.markercluster.js"></script>
    <script nonce="<?= CSP_NONCE ?>">
        const pontos = <?= json_encode($pontos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // Escapa texto digitado por usuário antes de colocar no HTML do popup (evita XSS).
        function esc(texto) {
            const d = document.createElement('div');
            d.textContent = (texto === null || texto === undefined) ? '' : String(texto);
            return d.innerHTML;
        }

        // As cores do mapa são geradas em JS e não respondem a classes CSS.
        // O sinalizador inicial é definido no <head>, antes do Leaflet criar os pinos.
        const paletaDaltonismo = document.documentElement.classList.contains('pre-modo-daltonico')
            || document.body.classList.contains('modo-daltonico');
        const cores = paletaDaltonismo ? {
            'pendente': '#B76A00', 'em análise': '#0072B2',
            'aguardando confirmação': '#974A84', 'resolvido': '#007A62',
            'cancelada': '#626B7A', 'rejeitada': '#A34E27'
        } : {
            'pendente': '#f2b705', 'em análise': '#3d8bfd',
            'aguardando confirmação': '#a78bfa', 'resolvido': '#00d600',
            'cancelada': '#6c757d', 'rejeitada': '#ef4444'
        };
        const icones = { iluminacao: 'fa-lightbulb', sinalizacao: 'fa-traffic-light', lixo: 'fa-trash', buracos: 'fa-road', esgoto: 'fa-water', agua: 'fa-faucet-drip', arborizacao: 'fa-tree', outros: 'fa-plus' };
        const nomesCat = { iluminacao: 'Iluminação', sinalizacao: 'Sinalização', lixo: 'Lixo', buracos: 'Buracos e vias', esgoto: 'Esgoto e drenagem', agua: 'Abastecimento de água', arborizacao: 'Árvores e poda', outros: 'Outros' };

        // Guajará-Mirim/RO como centro padrão — igual ao mapa usado ao registrar a denúncia.
        const map = L.map('mapa-publico', { zoomControl: false }).setView([-10.7838, -65.3355], 13);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // ---------------- Estilos do mapa: escuro, ruas e satélite ----------------
        const estilos = {
            // Mapa escuro: o mesmo mapa do OpenStreetMap com as cores invertidas por CSS
            // (.tiles-escuras). Não depende de chave de API de nenhum serviço.
            escuro: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors', maxZoom: 19, className: 'tiles-escuras'
            }),
            ruas: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors', maxZoom: 19
            }),
            satelite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Imagens &copy; Esri', maxZoom: 19
            })
        };
        // Se o serviço de ruas ficar indisponível, evita exibir mosaico de erros 403.
        // A alternativa de satélite já fazia parte deste mapa e mantém os pontos visíveis.
        let errosMapaRuas = 0;
        let avisoAlternativoExibido = false;
        function lidarComFalhaMapa() {
            if (estiloAtual === 'satelite' || ++errosMapaRuas < 3) return;
            trocarEstilo('satelite');
            if (!avisoAlternativoExibido) {
                avisoAlternativoExibido = true;
                const aviso = document.createElement('div');
                aviso.className = 'mapa-aviso-provedor';
                aviso.setAttribute('role', 'status');
                aviso.textContent = 'O mapa de ruas está indisponível. Exibindo imagens de satélite.';
                document.querySelector('.mapa-area').appendChild(aviso);
                window.setTimeout(function () { aviso.remove(); }, 8500);
            }
        }
        estilos.escuro.on('tileerror', lidarComFalhaMapa);
        estilos.ruas.on('tileerror', lidarComFalhaMapa);
        let estiloAtual = null;
        function trocarEstilo(nome) {
            if (estiloAtual) map.removeLayer(estilos[estiloAtual]);
            estiloAtual = nome;
            estilos[nome].addTo(map).bringToBack();
            document.querySelectorAll('#mapa-estilos button').forEach(function (b) {
                b.classList.toggle('ativo', b.dataset.estilo === nome);
                b.setAttribute('aria-pressed', b.dataset.estilo === nome ? 'true' : 'false');
            });
            document.getElementById('mapa-publico').dataset.estilo = nome;
            try { localStorage.setItem('oc-estilo-mapa', nome); } catch (e) {}
        }
        let estiloSalvo = null;
        try { estiloSalvo = localStorage.getItem('oc-estilo-mapa'); } catch (e) {}
        trocarEstilo(estilos[estiloSalvo] ? estiloSalvo : (document.body.classList.contains('tema-claro') ? 'ruas' : 'escuro'));
        document.querySelectorAll('#mapa-estilos button').forEach(function (b) {
            b.addEventListener('click', function () { trocarEstilo(b.dataset.estilo); });
        });

        // ---------------- Botão "onde estou" com efeito de radar ----------------
        const ControleLocal = L.Control.extend({
            options: { position: 'bottomright' },
            onAdd: function () {
                const b = L.DomUtil.create('button', 'mapa-localizar');
                b.type = 'button';
                b.title = 'Mostrar onde estou';
                b.setAttribute('aria-label', 'Mostrar onde estou');
                b.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
                L.DomEvent.disableClickPropagation(b);
                L.DomEvent.on(b, 'click', localizar);
                return b;
            }
        });
        map.addControl(new ControleLocal());
        let camadaEu = null;
        function localizar() {
            const b = document.querySelector('.mapa-localizar');
            if (!navigator.geolocation) { window.mostrarToast && mostrarToast('Seu navegador não informa a localização.', 'aviso'); return; }
            b.classList.add('buscando');
            navigator.geolocation.getCurrentPosition(function (pos) {
                b.classList.remove('buscando');
                const eu = [pos.coords.latitude, pos.coords.longitude];
                if (camadaEu) map.removeLayer(camadaEu);
                camadaEu = L.layerGroup([
                    L.circle(eu, { radius: Math.max(30, pos.coords.accuracy), color: '#3d8bfd', weight: 1, fillOpacity: .08, interactive: false }),
                    L.marker(eu, { icon: L.divIcon({ className: 'radar-eu', html: '<span class="radar-onda"></span><span class="radar-onda r2"></span><span class="radar-ponto"></span>', iconSize: [20, 20] }), interactive: false })
                ]).addTo(map);
                map.flyTo(eu, 16, { duration: .8 });
                const perto = marcadores.filter(function (m) { return map.distance(eu, m.getLatLng()) <= 500 && !/resolvido/i.test(m.dados.status); }).length;
                window.mostrarToast && mostrarToast(perto ? perto + ' denúncia' + (perto === 1 ? '' : 's') + ' aberta' + (perto === 1 ? '' : 's') + ' a até 500 m de você.' : 'Nenhuma denúncia aberta a 500 m de você.', 'info');
            }, function () {
                b.classList.remove('buscando');
                window.mostrarToast && mostrarToast('Não foi possível pegar sua localização.', 'aviso');
            }, { enableHighAccuracy: true, timeout: 10000 });
        }

        // Ícone do ponto: alfinete na cor do status, com o ícone da categoria dentro.
        function iconePonto(p) {
            const cor = cores[(p.status || '').toLowerCase()] || '#AAAAAA';
            const icone = icones[p.categoria] || 'fa-location-dot';
            const novo = p.criada_em && (Date.now() - new Date(p.criada_em.replace(' ', 'T')).getTime()) < 3 * 86400000;
            const pulsa = !/resolvido/i.test(p.status) && (novo || p.prioridade === 'Urgente');
            return L.divIcon({
                className: 'pino-denuncia' + (pulsa ? ' pulsa' : '') + (p.prioridade === 'Urgente' ? ' urgente' : ''),
                html: (pulsa ? '<span class="pino-pulso" style="--cor:' + cor + '"></span>' : '') + '<span class="pino" style="--cor:' + cor + '"><i class="fa-solid ' + icone + '"></i></span>',
                iconSize: [30, 30],
                iconAnchor: [15, 30],
                popupAnchor: [0, -28]
            });
        }

        // Ícone do grupo: mostra quantas denúncias tem ali; a borda fica com a
        // cor do status mais "urgente" do grupo (pendente > análise > aguardando > resolvido).
        const ordemStatus = ['pendente', 'em análise', 'aguardando confirmação', 'resolvido'];
        function iconeGrupo(cluster) {
            const filhos = cluster.getAllChildMarkers();
            let pior = 3;
            filhos.forEach(function (m) {
                const i = ordemStatus.indexOf((m.dados.status || '').toLowerCase());
                if (i !== -1 && i < pior) pior = i;
            });
            const n = cluster.getChildCount();
            const tam = n < 10 ? 38 : (n < 50 ? 46 : 54);
            return L.divIcon({
                className: 'grupo-denuncias',
                html: '<span style="--cor:' + cores[ordemStatus[pior]] + ';width:' + tam + 'px;height:' + tam + 'px">' + n + '</span>',
                iconSize: [tam, tam]
            });
        }

        const classesStatus = {
            'pendente': 'pendente', 'em análise': 'analise',
            'aguardando confirmação': 'aguardando', 'resolvido': 'resolvido',
            'cancelada': 'cancelada', 'rejeitada': 'rejeitada'
        };
        function pilula(status, curto) {
            const txt = (curto && (status || '').toLowerCase() === 'aguardando confirmação') ? 'Aguardando' : status;
            return '<span class="pilula-status st-' + (classesStatus[(status || '').toLowerCase()] || 'pendente') + '" title="' + esc(status) + '">' + esc(txt) + '</span>';
        }
        function dataBR(txt) {
            return txt ? new Date(txt.replace(' ', 'T')).toLocaleDateString('pt-BR') : '';
        }
        // Foto da denúncia ou, se não tiver, um bloco com o ícone grande da categoria.
        function miniatura(p, classe) {
            if (p.foto) {
                return '<div class="' + classe + ' cat-' + esc(p.categoria) + '" data-icone="' + (icones[p.categoria] || 'fa-location-dot') + '"><img src="' + esc(p.foto) + '" alt="Foto da denúncia" loading="lazy" data-sem-foto></div>';
            }
            return '<div class="' + classe + ' sem-foto cat-' + esc(p.categoria) + '"><i class="fa-solid ' + (icones[p.categoria] || 'fa-location-dot') + '"></i></div>';
        }

        // Se a foto não carregar, troca pelo ícone da categoria (evento capturado: sem onerror no HTML).
        document.addEventListener('error', function (e) {
            if (e.target && e.target.tagName === 'IMG' && e.target.hasAttribute('data-sem-foto')) semFoto(e.target);
        }, true);
        function semFoto(img) {
            const caixa = img.parentNode;
            caixa.classList.add('sem-foto');
            const i = document.createElement('i');
            i.className = 'fa-solid ' + caixa.dataset.icone;
            img.replaceWith(i);
        }

        function popup(p) {
            const linhas = [];
            if (p.endereco) linhas.push('<li><i class="fa-solid fa-location-dot"></i>' + esc(p.endereco) + '</li>');
            if (p.orgao) linhas.push('<li><i class="fa-solid fa-building-columns"></i>' + esc(p.orgao) + '</li>');
            const extra = [];
            if (p.criada_em) extra.push('<i class="fa-regular fa-calendar"></i> ' + dataBR(p.criada_em));
            if (p.apoios > 0) extra.push('<i class="fa-solid fa-hands-clapping"></i> ' + p.apoios);
            if (p.verificada) extra.push('<span class="popup-verificado"><i class="fa-solid fa-shield-halved"></i> Verificado</span>');
            return '<div class="popup-denuncia">' +
                miniatura(p, 'popup-foto') +
                '<div class="popup-corpo">' +
                    '<div class="popup-titulo"><strong>' + esc(nomesCat[p.categoria] || 'Denúncia') + '</strong>' + pilula(p.status) + '</div>' +
                    (p.descricao ? '<p class="popup-desc">' + esc(p.descricao) + '</p>' : '') +
                    (linhas.length ? '<ul class="popup-linhas">' + linhas.join('') + '</ul>' : '') +
                    (extra.length ? '<div class="popup-extra">' + extra.join('<span>·</span>') + '</div>' : '') +
                    '<a class="popup-botao" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=' + p.id + '">Ver detalhes e apoiar <i class="fa-solid fa-arrow-right"></i></a>' +
                '</div></div>';
        }

        function ehCelular() { return window.matchMedia('(max-width: 720px)').matches; }
        const ficha = document.getElementById('mapa-ficha');
        function abrirFicha(p) {
            ficha.querySelector('.mapa-ficha-conteudo').innerHTML = popup(p);
            ficha.classList.add('aberta');
            ficha.setAttribute('aria-hidden', 'false');
            const lista = document.getElementById('mapa-lista'); if (lista) lista.classList.remove('aberta');
        }
        function fecharFicha() { ficha.classList.remove('aberta'); ficha.setAttribute('aria-hidden', 'true'); }
        document.getElementById('mapa-ficha-fechar').addEventListener('click', fecharFicha);
        // arrastar a ficha para baixo fecha
        (function () {
            let y0 = null;
            ficha.addEventListener('touchstart', function (e) { y0 = e.touches[0].clientY; }, { passive: true });
            ficha.addEventListener('touchmove', function (e) {
                if (y0 === null) return;
                const dy = Math.max(0, e.touches[0].clientY - y0);
                ficha.style.transform = 'translateY(' + dy + 'px)';
            }, { passive: true });
            ficha.addEventListener('touchend', function (e) {
                const dy = y0 === null ? 0 : (e.changedTouches[0].clientY - y0);
                ficha.style.transform = '';
                if (dy > 80) fecharFicha();
                y0 = null;
            });
        })();

        // Cria um marcador por denúncia (uma vez só); os filtros só escolhem quais entram no mapa.
        const marcadores = pontos.map(function (p) {
            const m = L.marker([p.latitude, p.longitude], { icon: iconePonto(p), title: nomesCat[p.categoria] || 'Denúncia' });
            m.dados = p;
            m.bindPopup(popup(p), { maxWidth: 300, minWidth: 260, className: 'popup-oc' });
            // No celular, em vez do balão, os detalhes sobem de baixo.
            m.on('click', function () {
                if (ehCelular()) { m.closePopup(); abrirFicha(p); }
            });
            return m;
        });

        const grupoCluster = L.markerClusterGroup({
            iconCreateFunction: iconeGrupo,
            showCoverageOnHover: false,
            maxClusterRadius: 50,
            disableClusteringAtZoom: 18,
            spiderfyOnMaxZoom: true
        });
        const grupoSimples = L.layerGroup();

        // Mapa de calor: cada denúncia pesa 1, e cada apoio aumenta um pouco o peso (máx. 3).
        const calor = L.heatLayer([], { radius: 32, blur: 22, maxZoom: 16, minOpacity: 0.35 });

        // ---------------- Período e linha do tempo ----------------
        marcadores.forEach(function (m) {
            m.dados._ts = m.dados.criada_em ? new Date(m.dados.criada_em.replace(' ', 'T')).getTime() : 0;
        });
        let diasPeriodo = 0;       // 0 = tudo
        let limiteTempo = null;    // durante a reprodução: só mostra o que foi criado até essa data
        let reproduzindo = false;

        const caixasCat = document.querySelectorAll('#filtro-categoria input');
        const caixasSt = document.querySelectorAll('#filtro-status input');
        const caixaAgrupar = document.getElementById('filtro-agrupar');
        const contador = document.getElementById('mapa-contador');
        const avisoVazio = document.getElementById('mapa-vazio');
        let modo = 'pontos';
        let primeiraVez = true;

        function marcados(caixas) {
            return Array.from(caixas).filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        }

        // Quantidade de cada categoria/status ao lado do nome do filtro.
        document.querySelectorAll('[data-qtd-cat]').forEach(function (el) {
            el.textContent = pontos.filter(function (p) { return p.categoria === el.dataset.qtdCat; }).length;
        });
        document.querySelectorAll('[data-qtd-st]').forEach(function (el) {
            el.textContent = pontos.filter(function (p) { return (p.status || '').toLowerCase() === el.dataset.qtdSt; }).length;
        });

        function aplicarFiltros() {
            const cats = marcados(caixasCat);
            const sts = marcados(caixasSt);
            const desde = diasPeriodo > 0 ? Date.now() - diasPeriodo * 86400000 : -Infinity;
            const ate = limiteTempo !== null ? limiteTempo : Infinity;
            const visiveis = marcadores.filter(function (m) {
                const t = m.dados._ts;
                return cats.indexOf(m.dados.categoria) !== -1 && sts.indexOf((m.dados.status || '').toLowerCase()) !== -1
                    && t >= desde && t <= ate;
            });

            grupoCluster.clearLayers();
            grupoSimples.clearLayers();
            [grupoCluster, grupoSimples].forEach(function (c) { if (map.hasLayer(c)) map.removeLayer(c); });
            if (modo !== 'calor' && map.hasLayer(calor)) map.removeLayer(calor);

            if (modo === 'calor') {
                // Primeiro entra no mapa, depois recebe os pontos (o leaflet-heat
                // precisa estar no mapa para redesenhar).
                calor.addTo(map);
                calor.setLatLngs(visiveis.map(function (m) {
                    return [m.dados.latitude, m.dados.longitude, Math.min(3, 1 + (m.dados.apoios || 0) * 0.5)];
                }));
            } else if (caixaAgrupar.checked) {
                grupoCluster.addLayers(visiveis);
                grupoCluster.addTo(map);
            } else {
                visiveis.forEach(function (m) { grupoSimples.addLayer(m); });
                grupoSimples.addTo(map);
            }

            contador.textContent = visiveis.length + ' de ' + marcadores.length;
            avisoVazio.hidden = visiveis.length > 0;
            desenharLista(visiveis);

            if (primeiraVez && visiveis.length) {
                map.fitBounds(L.latLngBounds(visiveis.map(function (m) { return m.getLatLng(); })).pad(0.2));
                primeiraVez = false;
            }
        }

        // ---------------- Lista lateral ----------------
        const listaEl = document.getElementById('mapa-lista-itens');
        const listaVazia = document.getElementById('mapa-lista-vazia');
        const listaResumo = document.getElementById('mapa-lista-resumo');
        const listaOrdem = document.getElementById('mapa-lista-ordem');
        const painelLista = document.getElementById('mapa-lista');
        let ultimosVisiveis = [];

        function desenharLista(visiveis) {
            ultimosVisiveis = visiveis;
            const ordenados = visiveis.slice().sort(function (a, b) {
                if (listaOrdem.value === 'apoios' && b.dados.apoios !== a.dados.apoios) return b.dados.apoios - a.dados.apoios;
                return (b.dados.criada_em || '').localeCompare(a.dados.criada_em || '');
            });
            listaResumo.textContent = visiveis.length === 1 ? '1 denúncia' : visiveis.length + ' denúncias';
            listaVazia.hidden = visiveis.length > 0;
            listaEl.innerHTML = ordenados.map(function (m) {
                const p = m.dados;
                return '<li><button type="button" class="mapa-item" data-id="' + p.id + '">' +
                    miniatura(p, 'mapa-item-foto') +
                    '<span class="mapa-item-texto">' +
                        '<span class="mapa-item-titulo"><strong>' + esc(nomesCat[p.categoria] || 'Denúncia') + '</strong>' + pilula(p.status, true) + '</span>' +
                        '<span class="mapa-item-end">' + esc(p.endereco || 'Sem endereço') + '</span>' +
                        '<span class="mapa-item-meta">' + dataBR(p.criada_em) + (p.apoios > 0 ? ' · ' + p.apoios + ' apoio' + (p.apoios === 1 ? '' : 's') : '') + '</span>' +
                    '</span></button></li>';
            }).join('');
        }

        function focarMarcador(id) {
            const m = marcadores.find(function (x) { return x.dados.id === id; });
            if (!m) return;
            if (modo === 'calor') trocarModo('pontos');
            if (window.matchMedia('(max-width: 720px)').matches) abrirLista(false);
            const abrir = function () { if (ehCelular()) abrirFicha(m.dados); else m.openPopup(); };
            if (caixaAgrupar.checked) {
                grupoCluster.zoomToShowLayer(m, abrir);
            } else {
                map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 17), { duration: 0.6 });
                setTimeout(abrir, 650);
            }
            listaEl.querySelectorAll('.mapa-item').forEach(function (b) { b.classList.toggle('ativo', Number(b.dataset.id) === id); });
        }

        // Passar o mouse num item da lista faz o ponto "pular" no mapa.
        listaEl.addEventListener('mouseover', function (e) {
            const b = e.target.closest('.mapa-item'); if (!b) return;
            marcadores.forEach(function (m) {
                const el = m.getElement && m.getElement();
                if (el) el.classList.toggle('destacado', m.dados.id === Number(b.dataset.id));
            });
        });
        listaEl.addEventListener('mouseleave', function () {
            marcadores.forEach(function (m) { const el = m.getElement && m.getElement(); if (el) el.classList.remove('destacado'); });
        });
        // Celular: deslizar o item para a direita abre a denúncia já no botão de apoiar.
        (function () {
            let x0 = null, y0 = null, item = null, dx = 0;
            listaEl.addEventListener('touchstart', function (e) {
                item = e.target.closest('.mapa-item'); if (!item) return;
                x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; dx = 0;
            }, { passive: true });
            listaEl.addEventListener('touchmove', function (e) {
                if (!item) return;
                dx = e.touches[0].clientX - x0;
                if (Math.abs(e.touches[0].clientY - y0) > 30) { item.style.transform = ''; item = null; return; }
                if (dx > 0) { item.style.transform = 'translateX(' + Math.min(dx, 120) + 'px)'; item.parentNode.classList.toggle('deslizando', dx > 20); item.parentNode.classList.toggle('pronto', dx > 90); }
            }, { passive: true });
            listaEl.addEventListener('touchend', function () {
                if (!item) return;
                const li = item.parentNode, id = item.dataset.id;
                item.style.transform = '';
                li.classList.remove('deslizando', 'pronto');
                if (dx > 90) location.href = '<?= BASE_URL ?>/?rota=denuncia-ver&id=' + id + '#apoiar';
                item = null;
            });
        })();

        listaEl.addEventListener('click', function (e) {
            const b = e.target.closest('.mapa-item');
            if (b) focarMarcador(Number(b.dataset.id));
        });
        listaOrdem.addEventListener('change', function () { desenharLista(ultimosVisiveis); });

        // Destaca o item da lista quando um ponto é aberto no mapa.
        map.on('popupopen', function (e) {
            const m = e.popup._source;
            if (!m || !m.dados) return;
            listaEl.querySelectorAll('.mapa-item').forEach(function (b) {
                const ativo = Number(b.dataset.id) === m.dados.id;
                b.classList.toggle('ativo', ativo);
                if (ativo && !window.matchMedia('(max-width: 720px)').matches) b.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        });

        // Celular: a lista vira uma "gaveta" que sobe da parte de baixo.
        const btnLista = document.getElementById('modo-lista');
        function abrirLista(abrir) {
            painelLista.classList.toggle('aberta', abrir);
            btnLista.classList.toggle('ativo', abrir);
            btnLista.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        }
        btnLista.addEventListener('click', function () { abrirLista(!painelLista.classList.contains('aberta')); });
        document.getElementById('mapa-lista-fechar').addEventListener('click', function () { abrirLista(false); });

        caixasCat.forEach(function (c) { c.addEventListener('change', aplicarFiltros); });
        caixasSt.forEach(function (c) { c.addEventListener('change', aplicarFiltros); });
        caixaAgrupar.addEventListener('change', aplicarFiltros);
        function mostrarTudo() {
            caixasCat.forEach(function (c) { c.checked = true; });
            caixasSt.forEach(function (c) { c.checked = true; });
            definirPeriodo(0);
        }

        const botoesPeriodo = document.querySelectorAll('#filtro-periodo button');
        function definirPeriodo(dias) {
            diasPeriodo = dias;
            botoesPeriodo.forEach(function (b) {
                const ativo = Number(b.dataset.dias) === dias;
                b.classList.toggle('ativo', ativo);
                b.setAttribute('aria-checked', ativo ? 'true' : 'false');
            });
            aplicarFiltros();
        }
        botoesPeriodo.forEach(function (b) {
            b.addEventListener('click', function () { if (!reproduzindo) definirPeriodo(Number(b.dataset.dias)); });
        });

        // "Ver a evolução no tempo": o mapa de calor vai se espalhando dia a dia,
        // do início do período escolhido até hoje.
        const btnLinhaTempo = document.getElementById('btn-linha-tempo');
        const painelLT = document.getElementById('mapa-linha-tempo');
        const ltData = document.getElementById('mapa-lt-data');
        const ltProg = document.getElementById('mapa-lt-progresso');
        const ltQtd = document.getElementById('mapa-lt-qtd');
        let quadroLT = null;

        function pararLinhaTempo() {
            reproduzindo = false;
            cancelAnimationFrame(quadroLT);
            limiteTempo = null;
            painelLT.hidden = true;
            btnLinhaTempo.innerHTML = '<i class="fa-solid fa-play"></i> Ver a evolução no tempo';
            btnLinhaTempo.classList.remove('ativo');
            aplicarFiltros();
        }

        btnLinhaTempo.addEventListener('click', function () {
            if (reproduzindo) { pararLinhaTempo(); return; }
            const tempos = marcadores.map(function (m) { return m.dados._ts; }).filter(Boolean);
            if (!tempos.length) return;
            const fim = Date.now();
            const inicio = diasPeriodo > 0 ? fim - diasPeriodo * 86400000 : Math.min.apply(null, tempos) - 86400000;
            const duracao = 6000;
            reproduzindo = true;
            if (modo !== 'calor') trocarModo('calor');
            map.closePopup();
            painelLT.hidden = false;
            btnLinhaTempo.innerHTML = '<i class="fa-solid fa-stop"></i> Parar';
            btnLinhaTempo.classList.add('ativo');
            if (window.matchMedia('(max-width: 720px)').matches) definirAberto(false);
            const t0 = performance.now();
            (function passo(agora) {
                const p = Math.min(1, (agora - t0) / duracao);
                limiteTempo = inicio + (fim - inicio) * p;
                aplicarFiltros();
                ltData.textContent = new Date(limiteTempo).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
                ltProg.style.width = (p * 100) + '%';
                ltQtd.textContent = contador.textContent.split(' de ')[0] + ' denúncias';
                if (p < 1 && reproduzindo) {
                    quadroLT = requestAnimationFrame(passo);
                } else if (reproduzindo) {
                    // terminou: deixa a última imagem um instante e volta ao normal
                    setTimeout(function () { if (reproduzindo) pararLinhaTempo(); }, 1600);
                }
            })(t0);
        });
        document.getElementById('filtro-limpar').addEventListener('click', mostrarTudo);
        document.querySelectorAll('[data-mostrar-tudo]').forEach(function (b) { b.addEventListener('click', mostrarTudo); });

        // Painel de filtros recolhível (no celular começa fechado pra não cobrir o mapa).
        const painel = document.getElementById('mapa-filtros');
        const btnToggle = document.getElementById('mapa-filtros-toggle');
        function definirAberto(aberto) {
            painel.classList.toggle('fechado', !aberto);
            btnToggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        }
        btnToggle.addEventListener('click', function () { definirAberto(painel.classList.contains('fechado')); });
        if (window.matchMedia('(max-width: 720px)').matches) definirAberto(false);

        const btnPontos = document.getElementById('modo-pontos');
        const btnCalor = document.getElementById('modo-calor');
        function trocarModo(novo) {
            if (novo === 'pontos' && reproduzindo) { modo = novo; pararLinhaTempo(); }
            modo = novo;
            btnPontos.classList.toggle('ativo', novo === 'pontos');
            btnCalor.classList.toggle('ativo', novo === 'calor');
            if (novo === 'calor') map.closePopup();
            caixaAgrupar.disabled = novo === 'calor';
            aplicarFiltros();
        }
        btnPontos.addEventListener('click', function () { trocarModo('pontos'); });
        btnCalor.addEventListener('click', function () { trocarModo('calor'); });

        aplicarFiltros();
    </script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
