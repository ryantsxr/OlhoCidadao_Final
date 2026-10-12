<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - Olho Cidadão</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/login.css?v=1.0.1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>

<body class="pagina-auth">

<div class="container">

    <div class="left">

        <img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Logo Olho Cidadão">

        <p>
            Aconteceu com todo mundo.
            <br><br>
            Informe seu e-mail e mandamos um<br>
            link para você criar uma senha nova.
        </p>

        <div class="icons">
            <div class="icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="icon">
                <i class="fa-solid fa-key"></i>
            </div>
            <div class="icon">
                <i class="fa-solid fa-envelope"></i>
            </div>
        </div>

    </div>

    <div class="right">

        <?php // Erro/sucesso aparecem dentro do formulário (mostrarAviso) — ver script no final da página. ?>
        <form method="post" action="<?= BASE_URL ?>/?rota=esqueci-senha">
            <?= $this->csrfField() ?>

            <h2>
                Recuperar
                <span>senha</span>
            </h2>

            <input
                type="email"
                name="email" id="email"
                placeholder="Seu e-mail cadastrado"
                maxlength="190" autocomplete="email"
                required>

            <button type="submit">
                Enviar link de recuperação
            </button>

            <p class="cadastro">
                Lembrou a senha?
                <a href="<?= BASE_URL ?>/?rota=login">Voltar para o login</a>
            </p>

            <?php if (!empty($linkTeste)): ?>
                <div style="margin-top:18px; padding:14px; border-radius:10px; background:rgba(0,214,0,0.08); border:1px dashed #00d600;">
                    <p style="color:#00d600; font-size:0.78rem; font-weight:600; margin:0 0 8px;">
                        MODO DE TESTE — este site ainda não tem envio de e-mail configurado.
                    </p>
                    <p style="font-size:0.8rem; word-break:break-all; margin:0;">
                        <a href="<?= htmlspecialchars($linkTeste) ?>" style="color:#00d600;">
                            <?= htmlspecialchars($linkTeste) ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

        </form>

    </div>

</div>

<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/aviso-form.js?v=0.52.9"></script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarAviso(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
<?php if (!empty($sucesso)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarAviso(<?= json_encode($sucesso) ?>, 'sucesso'));</script>
<?php endif; ?>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
