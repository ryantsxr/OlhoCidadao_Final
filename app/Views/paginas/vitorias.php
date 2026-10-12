<?php
/** @var array $vitorias */
/** @var array $resumo */
/** @var array $rotulos */
/** @var string $categorias */
/** @var bool $logado */
$icones = ['iluminacao' => 'fa-lightbulb', 'sinalizacao' => 'fa-traffic-light', 'lixo' => 'fa-trash', 'buracos' => 'fa-road', 'esgoto' => 'fa-water', 'agua' => 'fa-faucet-drip', 'arborizacao' => 'fa-tree', 'outros' => 'fa-screwdriver-wrench'];
$fmtDias = fn(int $d) => $d === 0 ? 'no mesmo dia' : ($d === 1 ? 'em 1 dia' : "em {$d} dias");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mural de vitórias - Olho Cidadão</title>
    <meta name="description" content="Problemas da cidade que já foram resolvidos graças às denúncias dos moradores.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/paginas.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/vitorias.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body class="pagina-publica pagina-vitorias">

    <div class="pagina-topo">
        <div class="marca">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Olho Cidadão">
            <span>Olho <span>Cidadão</span></span>
        </div>
        <a href="<?= BASE_URL ?>/?rota=<?= $logado ? 'dashboard' : 'login' ?>"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>

    <main class="vitorias-conteudo" id="conteudo">
        <header class="vitorias-cabecalho">
            <img src="<?= BASE_URL ?>/img/mascote.png" alt="" class="vitorias-mascote">
            <div>
                <span class="vitorias-selo"><i class="fa-solid fa-trophy"></i> Mural de vitórias</span>
                <h1>Problemas que a cidade já resolveu</h1>
                <p>Cada cartão é uma denúncia que um morador fez, a prefeitura resolveu e o próprio morador confirmou. Arraste a barrinha para comparar o antes e o depois.</p>
            </div>
        </header>

        <section class="vitorias-numeros" aria-label="Resumo">
            <div><strong data-contar="<?= (int) $resumo['total'] ?>"><?= (int) $resumo['total'] ?></strong><span>problemas resolvidos</span></div>
            <div><strong><?= $resumo['media_dias'] === null ? '—' : str_replace('.', ',', (string) $resumo['media_dias']) ?></strong><span>dias em média para resolver</span></div>
            <div><strong><?= $resumo['nota'] === null ? '—' : str_replace('.', ',', (string) $resumo['nota']) . ' <small>/5</small>' ?></strong><span>nota dos moradores</span></div>
            <div><strong><?= (int) $resumo['apoios'] ?></strong><span>vizinhos apoiaram</span></div>
        </section>

        <nav class="vitorias-filtros" aria-label="Filtrar por categoria">
            <a href="<?= BASE_URL ?>/?rota=vitorias" class="<?= $categorias === '' ? 'ativo' : '' ?>">Todas</a>
            <?php foreach ($rotulos as $chave => $rotulo): ?>
                <a href="<?= BASE_URL ?>/?rota=vitorias&categoria=<?= $chave ?>" class="<?= $categorias === $chave ? 'ativo' : '' ?>">
                    <i class="fa-solid <?= $icones[$chave] ?>"></i> <?= $rotulo ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($vitorias)): ?>
            <div class="vitorias-vazio">
                <img src="<?= BASE_URL ?>/img/mascote.png" alt="">
                <p>Ainda não tem vitória<?= $categorias !== '' ? ' nessa categoria' : '' ?> por aqui. Que tal fazer a primeira denúncia?</p>
                <a href="<?= BASE_URL ?>/?rota=<?= $logado ? 'denuncia-passo1' : 'login' ?>" class="vitorias-botao">Fazer uma denúncia</a>
            </div>
        <?php else: ?>
            <div class="vitorias-grade">
                <?php foreach ($vitorias as $i => $v):
                    $temAntes = $v['antes'] !== '';
                    $temDepois = $v['depois'] !== '';
                ?>
                    <article class="vitoria" data-categoria="<?= htmlspecialchars($v['categoria']) ?>">
                        <?php if ($temAntes && $temDepois): ?>
                            <div class="antes-depois" style="--corte: 50%">
                                <img src="<?= htmlspecialchars($v['depois']) ?>" alt="Depois: problema resolvido" loading="lazy">
                                <img src="<?= htmlspecialchars($v['antes']) ?>" alt="Antes: foto enviada na denúncia" class="ad-antes" loading="lazy">
                                <span class="ad-rotulo ad-r-antes">Antes</span>
                                <span class="ad-rotulo ad-r-depois">Depois</span>
                                <span class="ad-linha" aria-hidden="true"><span></span></span>
                                <input type="range" min="0" max="100" value="50" class="ad-controle"
                                       aria-label="Comparar antes e depois da denúncia <?= htmlspecialchars($v['protocolo']) ?>">
                            </div>
                        <?php elseif ($temAntes || $temDepois): ?>
                            <div class="vitoria-foto">
                                <img src="<?= htmlspecialchars($temDepois ? $v['depois'] : $v['antes']) ?>" alt="<?= $temDepois ? 'Foto do problema resolvido' : 'Foto enviada na denúncia' ?>" loading="lazy">
                                <span class="ad-rotulo ad-r-depois"><?= $temDepois ? 'Depois' : 'Antes' ?></span>
                            </div>
                        <?php else: ?>
                            <div class="vitoria-sem-foto cat-<?= htmlspecialchars($v['categoria']) ?>">
                                <i class="fa-solid <?= $icones[$v['categoria']] ?? 'fa-circle-check' ?>"></i>
                            </div>
                        <?php endif; ?>

                        <div class="vitoria-corpo">
                            <div class="vitoria-topo">
                                <span class="vitoria-cat"><i class="fa-solid <?= $icones[$v['categoria']] ?? 'fa-plus' ?>"></i> <?= htmlspecialchars($rotulos[$v['categoria']] ?? 'Outros') ?></span>
                                <span class="vitoria-ok"><i class="fa-solid fa-circle-check"></i> Resolvido <?= $fmtDias($v['dias']) ?></span>
                            </div>
                            <p><?= htmlspecialchars($v['descricao']) ?></p>
                            <?php if ($v['local'] !== ''): ?>
                                <small class="vitoria-local"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($v['local']) ?></small>
                            <?php endif; ?>
                            <div class="vitoria-rodape">
                                <span>
                                    <?php if ($v['nota']): ?>
                                        <span class="vitoria-estrelas" aria-label="Nota <?= $v['nota'] ?> de 5"><?= str_repeat('★', $v['nota']) ?><span class="apagada"><?= str_repeat('★', 5 - $v['nota']) ?></span></span>
                                    <?php endif; ?>
                                    <?php if ($v['apoios'] > 0): ?>
                                        <span class="vitoria-apoios"><i class="fa-solid fa-hands-clapping"></i> <?= $v['apoios'] ?></span>
                                    <?php endif; ?>
                                </span>
                                <button type="button" class="vitoria-compartilhar"
                                        data-protocolo="<?= htmlspecialchars($v['protocolo']) ?>"
                                        data-texto="<?= htmlspecialchars('Problema resolvido em Guajará-Mirim pelo Olho Cidadão: ' . ($rotulos[$v['categoria']] ?? 'Outros') . ($v['local'] !== '' ? ' — ' . $v['local'] : '') . '.') ?>">
                                    <i class="fa-solid fa-share-nodes"></i> Compartilhar
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script nonce="<?= CSP_NONCE ?>">
        // Barra de antes/depois: o input range (acessível pelo teclado) controla o corte.
        document.querySelectorAll('.antes-depois').forEach(function (caixa) {
            const controle = caixa.querySelector('.ad-controle');
            const aplicar = () => caixa.style.setProperty('--corte', controle.value + '%');
            controle.addEventListener('input', aplicar);
            aplicar();
        });

        // Foto que não carrega (apagada, caminho antigo): troca pelo ícone da categoria.
        document.querySelectorAll('.vitoria img').forEach(function (img) {
            const trocar = function () {
                const bloco = img.closest('.antes-depois, .vitoria-foto');
                if (!bloco || bloco.dataset.trocado) return;
                bloco.dataset.trocado = '1';
                const card = bloco.closest('.vitoria');
                const sem = document.createElement('div');
                sem.className = 'vitoria-sem-foto cat-' + (card.dataset.categoria || 'outros');
                sem.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
                bloco.replaceWith(sem);
            };
            if (img.complete && img.naturalWidth === 0) trocar(); else img.addEventListener('error', trocar);
        });

        // Compartilhar: no celular abre o menu do sistema (WhatsApp, Instagram...); no PC copia o link.
        document.querySelectorAll('.vitoria-compartilhar').forEach(function (b) {
            b.addEventListener('click', async function () {
                const url = location.origin + <?= json_encode(BASE_URL) ?> + '/?rota=consulta&protocolo=' + encodeURIComponent(b.dataset.protocolo);
                const dados = { title: 'Olho Cidadão', text: b.dataset.texto, url };
                try {
                    if (navigator.share) { await navigator.share(dados); return; }
                    await navigator.clipboard.writeText(b.dataset.texto + ' ' + url);
                    const antes = b.innerHTML;
                    b.innerHTML = '<i class="fa-solid fa-check"></i> Link copiado!';
                    setTimeout(() => { b.innerHTML = antes; }, 2000);
                } catch (e) { /* a pessoa cancelou */ }
            });
        });
    </script>
    <?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
