<?php
/** @var array $usuario */
/** @var array $painel */
$ativo = 'admin-seguranca';
$resumo = $painel['resumo'];
$nivelGeral = $resumo['critico'] > 0 ? 'critico' : ($resumo['atencao'] > 0 ? 'atencao' : 'ok');
$tituloGeral = [
    'ok' => 'Tudo certo ✓',
    'atencao' => $resumo['atencao'] . ' ponto(s) de atenção',
    'critico' => $resumo['critico'] . ' problema(s) crítico(s)',
][$nivelGeral];
$rotulos = ['ok' => 'OK', 'atencao' => 'Atenção', 'critico' => 'Crítico'];
$alvos = $painel['tentativas']['alvos'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/integridade.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/seguranca.css?v=1.0.3">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/_menu.php'; ?>
    <main class="conteudo">
        <header>
            <div>
                <h1>Segurança</h1>
                <p>Confere a configuração do sistema, os backups, a verificação em duas etapas e as tentativas de invasão</p>
            </div>
            <a href="<?= BASE_URL ?>/?rota=admin-seguranca" class="denunciar seg-link"><i class="fa-solid fa-rotate"></i> <span>Verificar de novo</span></a>
        </header>

        <section class="integ-status <?= ['ok' => 'ok', 'atencao' => 'seg-geral-atencao', 'critico' => 'falha'][$nivelGeral] ?>">
            <div class="integ-icone"><i class="fa-solid <?= $nivelGeral === 'ok' ? 'fa-shield-halved' : 'fa-triangle-exclamation' ?>"></i></div>
            <div>
                <h2><?= htmlspecialchars($tituloGeral) ?></h2>
                <p><?= (int) $resumo['ok'] ?> OK · <?= (int) $resumo['atencao'] ?> atenção · <?= (int) $resumo['critico'] ?> crítico. Verificado em <?= date('d/m/Y H:i') ?>.</p>
            </div>
        </section>

        <ul class="seg-lista">
            <?php foreach ($painel['itens'] as $item): ?>
                <li class="seg-item seg-<?= $item['nivel'] ?>">
                    <span class="seg-icone"><i class="fa-solid <?= htmlspecialchars($item['icone']) ?>"></i></span>
                    <div class="seg-texto">
                        <strong><?= htmlspecialchars($item['titulo']) ?></strong>
                        <p><?= htmlspecialchars($item['detalhe']) ?></p>
                    </div>
                    <span class="seg-selo"><?= $rotulos[$item['nivel']] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($alvos): ?>
            <section class="integ-explica">
                <h2><i class="fa-solid fa-user-lock"></i> Contas com mais senhas erradas (24 h)</h2>
                <ul class="seg-alvos">
                    <?php foreach ($alvos as $email => $qtd): ?>
                        <li><span><?= htmlspecialchars(PainelSeguranca::mascararEmail((string) $email)) ?></span><strong><?= (int) $qtd ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <p class="integ-dica">Os e-mails aparecem mascarados. Cada combinação de e-mail + IP é bloqueada por 5 minutos após 5 erros, e a conta inteira após 30 erros em 30 minutos.</p>
            </section>
        <?php endif; ?>

        <section class="integ-explica">
            <h2><i class="fa-solid fa-circle-info"></i> Proteções ativas no sistema</h2>
            <ol>
                <li><strong>Senhas</strong> guardadas com Argon2id (ou bcrypt), mínimo de 8 caracteres e bloqueio de senhas comuns.</li>
                <li><strong>Login</strong> com limite de tentativas por IP e por conta, mesma resposta exista ou não o e-mail, e sessão que expira por inatividade.</li>
                <li><strong>Formulários</strong> protegidos contra CSRF; textos escapados contra XSS; e a Política de Segurança de Conteúdo (CSP) só deixa rodar scripts do próprio site.</li>
                <li><strong>Fotos</strong> redesenhadas (sem GPS/EXIF), com limite de tamanho e resolução, guardadas fora da pasta pública e liberadas só ao dono e à administração.</li>
                <li><strong>CPF</strong> criptografado; <strong>histórico</strong> com selos encadeados; <strong>backup</strong> diário conferido por SHA-256.</li>
                <li><strong>Anti-spam</strong>: no máximo <?= (int) LIMITE_DENUNCIAS_DIA ?> denúncias, <?= (int) LIMITE_APOIOS_DIA ?> apoios e <?= (int) LIMITE_COMPLEMENTOS_DIA ?> complementos por conta a cada 24 horas.</li>
            </ol>
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
