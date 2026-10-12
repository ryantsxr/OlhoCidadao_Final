<?php
/** @var string $protocolo */
/** @var array|null $denuncia */
/** @var bool $buscou */
/** @var int $bloqueado */
/** @var array|null $prazo */
$categorias = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar protocolo - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/painel.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/telas-cartoes.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<main class="pagina-solta">
    <div class="caixa-solta">
        <img src="<?= BASE_URL ?>/img/logo.png" class="logo-solta" alt="Logo Olho Cidadão">
        <h1>Consultar denúncia</h1>
        <p class="sub-solta">Digite o número de protocolo que você recebeu ao registrar a denúncia. Não precisa de login.</p>

        <form method="get" action="<?= BASE_URL ?>/" class="form-consulta">
            <input type="hidden" name="rota" value="consulta">
            <input type="text" name="protocolo" value="<?= htmlspecialchars($protocolo) ?>" placeholder="Ex.: OC-2026-000012" maxlength="20" required autocomplete="off">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
        </form>

        <?php if ($bloqueado > 0): ?>
            <div class="aviso-consulta erro">Muitas consultas seguidas. Tente de novo em <?= (int) ceil($bloqueado / 60) ?> minuto(s).</div>
        <?php elseif ($buscou && !$denuncia): ?>
            <div class="aviso-consulta erro"><i class="fa-regular fa-eye"></i> O olho não achou nenhuma denúncia com o protocolo "<?= htmlspecialchars($protocolo) ?>". Confira o número e tente de novo.</div>
        <?php elseif ($denuncia): ?>
            <?php
            $statusLower = strtolower($denuncia['status']);
            $statusClass = match ($statusLower) {
                'resolvido' => 'resolvido', 'em análise' => 'analise',
                'aguardando confirmação' => 'aguardando', 'cancelada' => 'cancelada', 'rejeitada' => 'rejeitada', default => 'pendente'
            };
            ?>
            <section class="resultado-consulta">
                <div class="resultado-topo">
                    <div>
                        <small>Protocolo</small>
                        <h2><?= htmlspecialchars(Denuncia::protocolo($denuncia)) ?></h2>
                    </div>
                    <span class="<?= $statusClass ?>"><?= htmlspecialchars($denuncia['status']) ?></span>
                </div>

                <?php $d = $denuncia; require __DIR__ . '/_etapas.php'; ?>
                <ul class="dados-consulta">
                    <li><span class="ic-tom" style="--tom:#3D8BFD"><i class="fa-solid fa-tag"></i></span><strong>Categoria:</strong> <?= htmlspecialchars($categorias[$denuncia['categoria']] ?? ucfirst($denuncia['categoria'])) ?></li>
                    <li><span class="ic-tom" style="--tom:#A78BFA"><i class="fa-regular fa-calendar"></i></span><strong>Registrada em:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($denuncia['criada_em']))) ?></li>
                    <?php if (!empty($denuncia['endereco'])): ?>
                        <li><span class="ic-tom" style="--tom:#EF4444"><i class="fa-solid fa-location-dot"></i></span><strong>Local:</strong> Localização aproximada (endereço protegido)</li>
                    <?php endif; ?>
                    <li><span class="ic-tom" style="--tom:#F5B301"><i class="fa-solid fa-hands-clapping"></i></span><strong>Apoios de moradores:</strong> <?= count($denuncia['apoios'] ?? []) ?><?= Denuncia::ehVerificada($denuncia) ? ' · verificada' : '' ?></li>
                    <?php if ($prazo && !in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true)): ?>
                        <li><span class="ic-tom" style="--tom:#14B8A6"><i class="fa-solid fa-clock"></i></span><strong>Em aberto há:</strong> <?= $prazo['dias'] ?> dia(s) (prazo esperado: <?= $prazo['prazo'] ?> dias)<?= $prazo['atrasada'] ? ' — prazo estourado' : '' ?></li>
                    <?php endif; ?>
                </ul>

                <?php require __DIR__ . '/_orgao.php'; ?>

                <h3 class="titulo-linha">Andamento</h3>
                <ol class="linha-tempo-consulta">
                    <?php foreach (array_reverse($denuncia['historico'] ?? []) as $h): ?>
                        <li>
                            <strong><?= htmlspecialchars($h['status']) ?></strong>
                            <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['data']))) ?></span>
                            <?php /* Mensagens internas não são exibidas por protocolo público. */ ?>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <?php $_selo = Integridade::verificarUma($denuncia); ?>
                <?php if ($_selo === 'integra'): ?>
                    <p class="selo-integridade ok" title="Cada etapa do histórico tem um selo digital encadeado ao anterior. Se alguém mudasse ou apagasse uma etapa fora do sistema, a corrente quebraria.">
                        <i class="fa-solid fa-shield-halved"></i> Histórico íntegro: nenhuma etapa foi alterada ou apagada (selo digital conferido agora).
                    </p>
                <?php elseif ($_selo === 'alterada'): ?>
                    <p class="selo-integridade falha">
                        <i class="fa-solid fa-triangle-exclamation"></i> Atenção: o selo digital deste histórico não confere. A administração foi orientada a verificar.
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <p class="rodape-solta"><a href="<?= BASE_URL ?>/?rota=login"><i class="fa-solid fa-arrow-left"></i> Voltar ao login</a> · <a href="<?= BASE_URL ?>/?rota=mapa">Mapa público</a> · <a href="<?= BASE_URL ?>/?rota=vitorias">Mural de vitórias</a></p>
    </div>
</main>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
