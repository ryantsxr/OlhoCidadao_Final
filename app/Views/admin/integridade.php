<?php
/** @var array $usuario */
/** @var array $resultado */
/** @var int $total */
$ativo = 'admin-integridade';
$problemas = $resultado['problemas'];
$tudoOk = empty($problemas);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico íntegro - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/integridade.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/_menu.php'; ?>
    <main class="conteudo">
        <header>
            <div>
                <h1>Histórico íntegro</h1>
                <p>Confere se alguém mudou ou apagou alguma coisa do histórico das denúncias fora do sistema</p>
            </div>
            <a href="<?= BASE_URL ?>/?rota=admin-integridade" class="denunciar" style="text-decoration:none;"><i class="fa-solid fa-rotate"></i> <span>Verificar de novo</span></a>
        </header>

        <section class="integ-status <?= $tudoOk ? 'ok' : 'falha' ?>">
            <div class="integ-icone"><i class="fa-solid <?= $tudoOk ? 'fa-shield-halved' : 'fa-triangle-exclamation' ?>"></i></div>
            <div>
                <h2><?= $tudoOk ? 'Tudo íntegro ✓' : count($problemas) . ' denúncia(s) com sinal de alteração' ?></h2>
                <p>
                    <?= (int) $resultado['ok'] ?> de <?= (int) $total ?> denúncias com a corrente de selos conferida.
                    <?php if ($resultado['sem_selo'] > 0): ?>
                        <?= (int) $resultado['sem_selo'] ?> ainda sem selo (criadas antes da v0.50 e não alteradas desde então; recebem o selo na próxima gravação).
                    <?php endif; ?>
                    Verificado em <?= date('d/m/Y H:i', strtotime($resultado['verificado_em'])) ?>.
                </p>
            </div>
        </section>

        <?php if (!$tudoOk): ?>
            <div class="lista">
                <?php foreach ($problemas as $p): ?>
                    <div class="denuncia integ-problema">
                        <div><i class="fa-solid fa-link-slash"></i></div>
                        <div>
                            <h3><?= htmlspecialchars($p['protocolo']) ?></h3>
                            <p><?= htmlspecialchars(ucfirst($p['motivo'])) ?>.</p>
                        </div>
                        <?php if ($p['id'] > 0 && strpos($p['protocolo'], '????') === false): ?>
                            <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $p['id'] ?>" class="pagina-link">Abrir</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="integ-dica">O backup diário em <code>storage/backups/</code> tem a cópia anterior do arquivo, se precisar comparar.</p>
        <?php endif; ?>

        <section class="integ-explica">
            <h2><i class="fa-solid fa-circle-info"></i> Como funciona</h2>
            <ol>
                <li>Cada vez que o histórico de uma denúncia ganha uma linha nova (mudança de status, encaminhamento, edição), o sistema calcula um <strong>selo</strong>: um código HMAC-SHA256 feito com o conteúdo da linha, o selo da linha anterior e uma chave secreta.</li>
                <li>Como um selo depende do anterior, as linhas ficam <strong>encadeadas</strong> (a mesma ideia de um blockchain). Mudar uma data, um status antigo ou apagar uma linha do meio quebra a corrente dali para frente.</li>
                <li>Uma <strong>âncora</strong> por denúncia (em <code>storage/integridade.json</code>) guarda o último selo e os dados principais. Assim também aparece se alguém apagar a última linha ou trocar a descrição direto no arquivo.</li>
                <li>Esta tela recalcula tudo e compara. Na consulta pública do protocolo aparece o selo "Histórico íntegro".</li>
            </ol>
            <p class="integ-dica">Limite: quem tiver a chave secreta (<code>storage/integridade.key</code>) e acesso aos arquivos consegue refazer os selos. Num servidor de verdade, a chave deve ficar fora da pasta do site.</p>
        </section>
    </main>
</div>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
