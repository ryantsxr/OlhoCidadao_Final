<?php
/** @var array $dados */
$cores = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'];
$icones = ['iluminacao' => 'fa-lightbulb', 'sinalizacao' => 'fa-traffic-light', 'lixo' => 'fa-trash', 'buracos' => 'fa-road', 'esgoto' => 'fa-water', 'agua' => 'fa-faucet-drip', 'arborizacao' => 'fa-tree', 'outros' => 'fa-plus'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telão - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin-extra.css?v=0.52.9">
    <meta name="theme-color" content="#0b1020">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body class="pagina-telao">
<main class="telao" id="telao" data-url="<?= htmlspecialchars(BASE_URL . '/?rota=admin-telao&formato=json') ?>">
    <header class="telao-topo">
        <div class="telao-marca">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="">
            <div>
                <h1>Olho Cidadão <span>ao vivo</span></h1>
                <p>Denúncias da cidade em tempo real</p>
            </div>
        </div>
        <div class="telao-relogio">
            <strong id="telao-hora">--:--</strong>
            <small id="telao-data"></small>
        </div>
        <div class="telao-botoes">
            <button type="button" id="telao-cheia" title="Tela cheia"><i class="fa-solid fa-expand"></i></button>
            <a href="<?= BASE_URL ?>/?rota=admin-estatisticas" title="Sair do telão"><i class="fa-solid fa-xmark"></i></a>
        </div>
    </header>

    <section class="telao-numeros">
        <div class="telao-card" style="--tom:#3D8BFD"><i class="fa-solid fa-file-lines"></i><span class="telao-num" data-campo="total"><?= (int) $dados['total'] ?></span><small>denúncias registradas</small></div>
        <div class="telao-card" style="--tom:#F5B301"><i class="fa-solid fa-hourglass-half"></i><span class="telao-num" data-campo="abertas"><?= (int) $dados['abertas'] ?></span><small>abertas agora</small></div>
        <div class="telao-card" style="--tom:#22C55E"><i class="fa-solid fa-circle-check"></i><span class="telao-num" data-campo="resolvidas"><?= (int) $dados['resolvidas'] ?></span><small>resolvidas</small></div>
        <div class="telao-card" style="--tom:#A78BFA"><i class="fa-solid fa-percent"></i><span class="telao-num"><span data-campo="taxa"><?= (int) $dados['taxa'] ?></span>%</span><small>taxa de resolução</small></div>
        <div class="telao-card" style="--tom:#14B8A6"><i class="fa-solid fa-stopwatch"></i><span class="telao-num telao-num-texto" data-campo="tempo"><?= htmlspecialchars($dados['tempo']) ?></span><small>tempo médio até resolver</small></div>
        <div class="telao-card" style="--tom:#EF4444"><i class="fa-solid fa-triangle-exclamation"></i><span class="telao-num" data-campo="atrasadas"><?= (int) $dados['atrasadas'] ?></span><small>com prazo estourado</small></div>
    </section>

    <section class="telao-baixo">
        <div class="telao-painel">
            <h2>Por categoria</h2>
            <div class="telao-barras" id="telao-barras">
                <?php $maxC = max(1, max(array_column($dados['categorias'], 'qtd'))); foreach ($dados['categorias'] as $c): ?>
                <div class="telao-barra" style="--tom:<?= $cores[$c['chave']] ?>">
                    <span><i class="fa-solid <?= $icones[$c['chave']] ?>"></i> <?= htmlspecialchars($c['nome']) ?></span>
                    <div><b style="width:<?= round($c['qtd'] / $maxC * 100) ?>%"></b></div>
                    <strong><?= (int) $c['qtd'] ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="telao-painel">
            <h2>Últimas denúncias</h2>
            <ul class="telao-ultimas" id="telao-ultimas">
                <?php foreach ($dados['ultimas'] as $u): ?>
                <li style="--tom:<?= $cores[$u['chave']] ?? '#019B38' ?>">
                    <span class="telao-ic"><i class="fa-solid <?= $icones[$u['chave']] ?? 'fa-file' ?>"></i></span>
                    <div><strong><?= htmlspecialchars($u['categoria']) ?> · <?= htmlspecialchars($u['protocolo']) ?></strong><small><?= htmlspecialchars($u['endereco'] ?: 'Sem endereço') ?></small></div>
                    <em><?= htmlspecialchars($u['status']) ?><br><small><?= htmlspecialchars($u['quando']) ?></small></em>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <footer class="telao-rodape"><span class="telao-ao-vivo"></span> Atualiza sozinho a cada 30 segundos · última atualização <span id="telao-atualizado"><?= htmlspecialchars($dados['atualizado']) ?></span></footer>
</main>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
(function () {
    const tela = document.getElementById('telao');
    const cores = <?= json_encode($cores) ?>, icones = <?= json_encode($icones) ?>;
    function esc(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
    function relogio() {
        const a = new Date();
        document.getElementById('telao-hora').textContent = a.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('telao-data').textContent = a.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
    }
    relogio(); setInterval(relogio, 10000);
    function animar(el, para) {
        const de = parseInt(el.textContent, 10) || 0;
        if (de === para) return;
        el.classList.add('mudou');
        const t0 = performance.now();
        (function q(t) { const p = Math.min(1, (t - t0) / 900); el.textContent = Math.round(de + (para - de) * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(q); else setTimeout(function () { el.classList.remove('mudou'); }, 600); })(t0);
    }
    async function atualizar() {
        try {
            const r = await fetch(tela.dataset.url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!r.ok) return;
            const d = await r.json();
            tela.querySelectorAll('[data-campo]').forEach(function (el) {
                const v = d[el.dataset.campo];
                if (typeof v === 'number') animar(el, v); else el.textContent = v;
            });
            const max = Math.max(1, Math.max.apply(null, d.categorias.map(function (c) { return c.qtd; })));
            document.getElementById('telao-barras').innerHTML = d.categorias.map(function (c) {
                return '<div class="telao-barra" style="--tom:' + cores[c.chave] + '"><span><i class="fa-solid ' + icones[c.chave] + '"></i> ' + esc(c.nome) + '</span><div><b style="width:' + Math.round(c.qtd / max * 100) + '%"></b></div><strong>' + c.qtd + '</strong></div>';
            }).join('');
            document.getElementById('telao-ultimas').innerHTML = d.ultimas.map(function (u) {
                return '<li style="--tom:' + (cores[u.chave] || '#019B38') + '"><span class="telao-ic"><i class="fa-solid ' + (icones[u.chave] || 'fa-file') + '"></i></span><div><strong>' + esc(u.categoria) + ' · ' + esc(u.protocolo) + '</strong><small>' + esc(u.endereco || 'Sem endereço') + '</small></div><em>' + esc(u.status) + '<br><small>' + esc(u.quando) + '</small></em></li>';
            }).join('');
            document.getElementById('telao-atualizado').textContent = d.atualizado;
        } catch (e) { /* sem rede: tenta de novo daqui a pouco */ }
    }
    setInterval(atualizar, 30000);
    document.getElementById('telao-cheia').addEventListener('click', function () {
        if (document.fullscreenElement) document.exitFullscreen(); else document.documentElement.requestFullscreen().catch(function () {});
    });
})();
</script>
</body>
</html>
