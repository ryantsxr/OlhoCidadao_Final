document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('mapa-denuncia');
    const btnToggle = document.getElementById('btn-toggle-localizacao');
    const painel = document.getElementById('localizacao-painel');
    if (!mapEl || !btnToggle || !painel || typeof L === 'undefined') return;

    const inputLat = document.getElementById('input-latitude');
    const inputLng = document.getElementById('input-longitude');
    const inputEndereco = document.getElementById('input-endereco');
    const btnLocalizacao = document.getElementById('btn-minha-localizacao');
    const textoEndereco = document.getElementById('localizacao-texto');

    // Centro padrão do mapa: Guajará-Mirim/RO. Se o sistema for usado em outra
    // cidade, é só trocar essas duas coordenadas.
    const centroPadrao = [-10.7838, -65.3355];

    let map = null;

    // Aviso no canto da tela (toast); se por algum motivo não existir, usa o alert do navegador.
    function avisar(msg) {
        if (typeof window.mostrarToast === 'function') window.mostrarToast(msg, 'aviso');
        else alert(msg);
    }
    let marcador = null;

    // O mapa só é criado na primeira vez que o painel abre — assim ele nasce
    // com o container já visível (senão o Leaflet mede tamanho 0 e os
    // ladrilhos do mapa aparecem cortados/cinzas) e a tela não carrega peso
    // de mapa para quem nem for usar essa parte opcional.
    function iniciarMapaSeNecessario() {
        if (map) {
            map.invalidateSize();
            return;
        }

        map = L.map(mapEl).setView(centroPadrao, 13);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Estilo "aplicativo de transporte": o pino fica parado no centro e
        // o cidadão arrasta o mapa até o lugar certo. Tocar no mapa também
        // leva o pino até o ponto tocado.
        const pino = document.createElement('div');
        pino.className = 'pino-centro';
        pino.innerHTML = '<span class="pino-centro-icone"><i class="fa-solid fa-location-dot"></i></span><span class="pino-centro-sombra"></span>';
        mapEl.appendChild(pino);
        const dica = document.createElement('div');
        dica.className = 'pino-centro-dica';
        dica.textContent = 'Arraste o mapa até o local do problema';
        mapEl.appendChild(dica);

        map.on('movestart', function () { if (interagiu) pino.classList.add('levantado'); });
        map.on('dragstart', function () { interagiu = true; pino.classList.add('levantado'); dica.remove(); });
        map.on('moveend', function () {
            pino.classList.remove('levantado');
            if (!interagiu) return;
            const c = map.getCenter();
            atualizarLocalizacao(c.lat, c.lng);
        });
        map.on('click', function (e) {
            interagiu = true;
            dica.remove();
            map.panTo(e.latlng);
        });
    }

    let interagiu = false;
    // Usado pelo GPS: centraliza o mapa (o pino do centro marca o local).
    function definirMarcador(lat, lng) {
        interagiu = true;
        const d = mapEl.querySelector('.pino-centro-dica'); if (d) d.remove();
        map.setView([lat, lng], Math.max(map.getZoom(), 16));
    }

    function atualizarLocalizacao(lat, lng) {
        inputLat.value = lat.toFixed(6);
        inputLng.value = lng.toFixed(6);
        if (textoEndereco) {
            textoEndereco.textContent = 'Local marcado (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';
        }
        buscarEndereco(lat, lng);
        verificarParecidas(lat, lng);
    }

    // ------------------------------------------------------------------
    // Aviso de denúncia parecida: depois de marcar o local, pergunta ao
    // servidor se já existe uma denúncia ativa da mesma categoria a até
    // 100 m. Se existir, mostra um aviso oferecendo apoiar a existente.
    // ------------------------------------------------------------------
    const aviso = document.getElementById('aviso-parecida');
    let camadaParecidas = null;
    let ultimaBusca = 0;

    function esc(texto) {
        const d = document.createElement('div');
        d.textContent = (texto === null || texto === undefined) ? '' : String(texto);
        return d.innerHTML;
    }

    function limparParecidas() {
        if (camadaParecidas && map) map.removeLayer(camadaParecidas);
        camadaParecidas = null;
        if (aviso) { aviso.hidden = true; aviso.innerHTML = ''; }
    }

    function verificarParecidas(lat, lng) {
        if (!aviso || !aviso.dataset.url) return;
        const idBusca = ++ultimaBusca;
        fetch(aviso.dataset.url + '&lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (dados) {
                if (idBusca !== ultimaBusca) return; // o cidadão já marcou outro ponto
                limparParecidas();
                if (!dados || !dados.denuncias || !dados.denuncias.length) return;
                mostrarParecidas(lat, lng, dados.raio, dados.denuncias);
            })
            .catch(function () { /* sem aviso, a denúncia segue normal */ });
    }

    function mostrarParecidas(lat, lng, raio, lista) {
        // No mapa: círculo do raio verificado + as denúncias encontradas em laranja.
        camadaParecidas = L.layerGroup().addTo(map);
        L.circle([lat, lng], { radius: raio, color: '#f59e0b', weight: 1, fillOpacity: 0.08, interactive: false }).addTo(camadaParecidas);
        lista.forEach(function (d) {
            L.circleMarker([d.latitude, d.longitude], { radius: 8, color: '#fff', weight: 2, fillColor: '#f59e0b', fillOpacity: 1 })
                .bindTooltip(d.categoria + ' — ' + d.distancia_m + ' m')
                .addTo(camadaParecidas);
        });

        const maisPerto = lista[0];
        const titulo = lista.length === 1
            ? 'Já existe uma denúncia de ' + esc(maisPerto.categoria) + ' a ' + maisPerto.distancia_m + ' m daqui'
            : 'Já existem ' + lista.length + ' denúncias de ' + esc(maisPerto.categoria) + ' a menos de ' + raio + ' m daqui';

        const cards = lista.map(function (d) {
            let acao;
            if (d.propria) {
                acao = '<span class="aviso-parecida-tag">Essa denúncia é sua</span>';
            } else if (d.ja_apoiou) {
                acao = '<span class="aviso-parecida-tag">Você já apoia</span>';
            } else {
                acao = '<button type="button" class="aviso-parecida-apoiar" data-id="' + d.id + '">' +
                    '<i class="fa-solid fa-hand-holding-heart"></i> Apoiar esta denúncia</button>';
            }
            return '<li class="aviso-parecida-item">' +
                '<div class="aviso-parecida-topo"><strong>' + esc(d.protocolo) + '</strong>' +
                '<span>' + d.distancia_m + ' m · ' + esc(d.status) + '</span></div>' +
                '<p>' + esc(d.descricao) + '</p>' +
                '<small>' + esc(d.endereco || 'Sem endereço') + ' · aberta em ' + esc(d.criada_em) +
                (d.apoios > 0 ? ' · ' + d.apoios + ' apoio' + (d.apoios === 1 ? '' : 's') : '') + '</small>' +
                '<div class="aviso-parecida-acoes">' + acao +
                '<a href="' + aviso.dataset.urlVer + d.id + '" target="_blank" rel="noopener">Ver detalhes</a></div>' +
                '</li>';
        }).join('');

        aviso.innerHTML =
            '<div class="aviso-parecida-cabecalho"><i class="fa-solid fa-triangle-exclamation"></i><div>' +
            '<strong>' + titulo + '</strong>' +
            '<p>Se for o mesmo problema, apoie a denúncia existente: ela ganha prioridade e a prefeitura não recebe duplicadas.</p>' +
            '</div></div>' +
            '<ul class="aviso-parecida-lista">' + cards + '</ul>' +
            '<button type="button" class="aviso-parecida-fechar">Não é o mesmo problema, continuar minha denúncia</button>';
        aviso.hidden = false;
    }

    if (aviso) {
        aviso.addEventListener('click', function (e) {
            const apoiar = e.target.closest('.aviso-parecida-apoiar');
            if (apoiar) {
                // Envia um formulário à parte (fora do form da denúncia) para a rota de apoio.
                const f = document.createElement('form');
                f.method = 'post';
                f.action = aviso.dataset.urlApoiar;
                [['csrf_token', aviso.dataset.csrf], ['id', apoiar.dataset.id]].forEach(function (par) {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = par[0]; i.value = par[1];
                    f.appendChild(i);
                });
                document.body.appendChild(f);
                f.submit();
                return;
            }
            if (e.target.closest('.aviso-parecida-fechar')) {
                aviso.hidden = true;
                if (camadaParecidas && map) map.removeLayer(camadaParecidas);
            }
        });
    }

    // Busca o endereço legível do ponto marcado. É só um extra visual — se a
    // internet cair ou a API demorar, a denúncia continua funcionando normal
    // com a latitude/longitude que já foram salvas.
    function buscarEndereco(lat, lng) {
        if (!inputEndereco) return;
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=16')
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.display_name) {
                    inputEndereco.value = data.display_name;
                    if (textoEndereco) textoEndereco.textContent = data.display_name;
                }
            })
            .catch(function () { /* segue sem endereço legível, sem problema */ });
    }

    btnToggle.addEventListener('click', function () {
        const abrindo = painel.classList.contains('oculto');
        painel.classList.toggle('oculto');
        btnToggle.classList.toggle('aberto', abrindo);
        btnToggle.querySelector('span').textContent = abrindo
            ? 'Ocultar localização'
            : 'Adicionar localização (opcional)';

        if (abrindo) {
            // Só inicializa/redimensiona o mapa depois do painel ficar visível.
            setTimeout(iniciarMapaSeNecessario, 0);
        }
    });

    if (btnLocalizacao) {
        const textoOriginalBtn = btnLocalizacao.innerHTML;

        btnLocalizacao.addEventListener('click', function () {
            if (!navigator.geolocation) {
                avisar('Seu navegador não permite obter a localização automática. Toque no mapa para marcar o local.');
                return;
            }

            btnLocalizacao.disabled = true;
            btnLocalizacao.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Localizando...';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    definirMarcador(pos.coords.latitude, pos.coords.longitude);
                    btnLocalizacao.disabled = false;
                    btnLocalizacao.innerHTML = textoOriginalBtn;
                },
                function () {
                    avisar('Não foi possível obter sua localização. Toque no mapa para marcar o local manualmente.');
                    btnLocalizacao.disabled = false;
                    btnLocalizacao.innerHTML = textoOriginalBtn;
                }
            );
        });
    }
});

