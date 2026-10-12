/* Formulário de denúncia (v0.49): barra de progresso com %, contador de
 * caracteres, revisão final antes de enviar e categoria já escolhida quando
 * o cidadão vem de um atalho do painel. */
document.addEventListener('DOMContentLoaded', function () {
    const caixa = document.querySelector('.caixa-denuncia');
    const progresso = document.querySelector('.progresso');
    if (!caixa || !progresso) return;

    const passo = document.querySelectorAll('.progresso .circulo.ativo').length; // 1, 2 ou 3
    const veioDeTras = /denuncia-passo(\d)/.exec(document.referrer || '');
    if (veioDeTras && Number(veioDeTras[1]) > passo) caixa.classList.add('entrar-esquerda');

    // ------------------------------------------------ 22. barra de progresso com %
    const barra = document.createElement('div');
    barra.className = 'progresso-barra';
    barra.innerHTML = '<div class="progresso-barra-trilho"><span></span></div><small><strong>0%</strong> <em></em></small>';
    progresso.insertAdjacentElement('afterend', barra);
    const preenchimento = barra.querySelector('span');
    const pct = barra.querySelector('strong');
    const legenda = barra.querySelector('em');
    function definir(valor, texto) {
        valor = Math.round(valor);
        preenchimento.style.width = valor + '%';
        pct.textContent = valor + '%';
        legenda.textContent = texto;
        barra.classList.toggle('completo', valor >= 100);
    }

    // ------------------------------------------------ passo 1
    if (passo === 1) {
        const categoria = document.getElementById('categoria');
        const atualizar1 = function () { definir(categoria && categoria.value ? 30 : 8, categoria && categoria.value ? 'categoria escolhida — continue' : 'escolha a categoria do problema'); };
        document.querySelectorAll('.grid-cards .card').forEach(function (c) { c.addEventListener('click', function () { setTimeout(atualizar1, 0); }); });
        atualizar1();
        return;
    }

    // ------------------------------------------------ passo 3
    if (passo === 3) { requestAnimationFrame(function () { definir(100, 'denúncia enviada'); }); return; }

    // ------------------------------------------------ passo 2
    const form = caixa.querySelector('form');
    const desc = document.getElementById('descricao');
    const foto = document.getElementById('input-foto');
    const lat = document.getElementById('input-latitude');
    const MIN = 5, MAX = Number(desc && desc.getAttribute('maxlength')) || 2000;

    // 27. contador que muda de cor
    let contador = null;
    if (desc) {
        contador = document.createElement('div');
        contador.className = 'contador-caracteres';
        contador.setAttribute('aria-live', 'polite');
        desc.insertAdjacentElement('afterend', contador);
    }
    function atualizarContador() {
        if (!desc) return;
        const n = desc.value.trim().length;
        contador.classList.remove('curto', 'ok', 'quase');
        if (n < MIN) { contador.classList.add('curto'); contador.innerHTML = '<i class="fa-solid fa-pen"></i> Escreva pelo menos ' + MIN + ' caracteres <span>' + n + ' / ' + MAX + '</span>'; }
        else if (n > MAX * .9) { contador.classList.add('quase'); contador.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Quase no limite <span>' + n + ' / ' + MAX + '</span>'; }
        else { contador.classList.add('ok'); contador.innerHTML = '<i class="fa-solid fa-check"></i> Boa descrição <span>' + n + ' / ' + MAX + '</span>'; }
        desc.classList.toggle('campo-ok', n >= MIN);
    }

    function atualizar2() {
        let v = 34;
        const falta = [];
        if (desc && desc.value.trim().length >= MIN) v += 36; else falta.push('descrição');
        if (foto && foto.files && foto.files.length) v += 15; else falta.push('foto');
        if (lat && lat.value) v += 15; else falta.push('local');
        definir(v, falta.length ? 'falta: ' + falta.join(', ') + (falta.length === 1 && falta[0] !== 'descrição' ? ' (opcional)' : '') : 'tudo pronto para enviar');
        atualizarContador();
    }
    if (desc) desc.addEventListener('input', atualizar2);
    if (foto) foto.addEventListener('change', function () { setTimeout(atualizar2, 50); });
    // a latitude é preenchida por script: observa a mudança do valor
    if (lat) new MutationObserver(atualizar2).observe(lat, { attributes: true, attributeFilter: ['value'] });
    setInterval(function () { if (lat && lat.dataset.ultimo !== lat.value) { lat.dataset.ultimo = lat.value; atualizar2(); } }, 700);
    document.addEventListener('click', function (e) { if (e.target.closest('#zona-foto-remover')) setTimeout(atualizar2, 50); });
    atualizar2();

    // ------------------------------------------------ 26. revisão final antes de enviar
    if (!form) return;
    let confirmado = false;
    form.addEventListener('submit', function (e) {
        if (confirmado || e.defaultPrevented) return;
        if (desc && desc.value.trim().length < MIN) return; // a validação normal avisa
        e.preventDefault();
        e.stopImmediatePropagation();
        revisar();
    });

    function esc(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

    function revisar() {
        const cat = caixa.dataset.categoria || '';
        const nomes = { iluminacao: ['Iluminação', 'fa-lightbulb'], sinalizacao: ['Sinalização', 'fa-traffic-light'], lixo: ['Lixo', 'fa-trash'], buracos: ['Buracos e vias', 'fa-road'], esgoto: ['Esgoto e drenagem', 'fa-water'], agua: ['Abastecimento de água', 'fa-faucet-drip'], arborizacao: ['Árvores e poda', 'fa-tree'], outros: ['Outros', 'fa-plus'] };
        const c = nomes[cat] || ['Denúncia', 'fa-file-lines'];
        const img = document.getElementById('zona-foto-img');
        const temFoto = foto && foto.files && foto.files.length && img && img.src;
        const end = document.getElementById('input-endereco');
        const local = end && end.value ? end.value : (lat && lat.value ? 'Ponto marcado no mapa (' + Number(lat.value).toFixed(5) + ', ' + Number(document.getElementById('input-longitude').value).toFixed(5) + ')' : '');
        const anonima = form.querySelector('[name="anonima"]');
        const reinc = form.querySelector('[name="denuncia_anterior_id"]');

        const fundo = document.createElement('div');
        fundo.className = 'revisao-fundo';
        fundo.innerHTML =
            '<div class="revisao" role="dialog" aria-modal="true" aria-labelledby="rev-titulo">' +
            '<h3 id="rev-titulo"><i class="fa-solid fa-clipboard-check"></i> Confira antes de enviar</h3>' +
            '<div class="revisao-corpo">' +
                (temFoto ? '<img src="' + img.src + '" alt="Foto da denúncia">' : '<div class="revisao-sem-foto"><i class="fa-solid ' + c[1] + '"></i><small>sem foto</small></div>') +
                '<dl>' +
                    '<div><dt>Categoria</dt><dd><i class="fa-solid ' + c[1] + '"></i> ' + c[0] + '</dd></div>' +
                    '<div><dt>Descrição</dt><dd>' + esc(desc.value.trim()) + '</dd></div>' +
                    '<div><dt>Local</dt><dd>' + (local ? esc(local) : '<span class="revisao-falta">Não informado — a equipe pode ter dificuldade para achar</span>') + '</dd></div>' +
                    '<div><dt>Identificação</dt><dd>' + (anonima && anonima.checked ? '<i class="fa-solid fa-user-secret"></i> Anônima para outros moradores' : '<i class="fa-solid fa-user"></i> Com seu nome') + '</dd></div>' +
                    (reinc && reinc.value ? '<div><dt>Reincidência</dt><dd>Vinculada à denúncia #' + esc(reinc.value) + '</dd></div>' : '') +
                '</dl>' +
            '</div>' +
            '<div class="revisao-acoes"><button type="button" class="rev-editar"><i class="fa-solid fa-pen"></i> Editar</button>' +
            '<button type="button" class="rev-enviar"><i class="fa-solid fa-paper-plane"></i> Enviar denúncia</button></div>' +
            '</div>';
        document.body.appendChild(fundo);
        fundo.querySelector('.rev-enviar').focus();
        function fechar() { fundo.remove(); document.removeEventListener('keydown', tecla); }
        function tecla(e) { if (e.key === 'Escape') fechar(); }
        document.addEventListener('keydown', tecla);
        fundo.addEventListener('click', function (e) { if (e.target === fundo) fechar(); });
        fundo.querySelector('.rev-editar').addEventListener('click', function () { fechar(); desc.focus(); });
        fundo.querySelector('.rev-enviar').addEventListener('click', function () {
            confirmado = true;
            fechar();
            const btn = document.getElementById('btn-enviar');
            if (form.requestSubmit) form.requestSubmit(btn || undefined); else form.submit();
        });
    }
});
