<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha - Olho Cidadão</title>

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
            Quase lá.
            <br><br>
            Escolha uma senha nova para<br>
            voltar a acessar sua conta.
        </p>

        <div class="icons">
            <div class="icon">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="icon">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

    </div>

    <div class="right">

        <?php // Erro/sucesso aparecem dentro do formulário (mostrarAviso) — ver script no final da página. ?>
        <form method="post" action="<?= BASE_URL ?>/?rota=redefinir-senha">
            <?= $this->csrfField() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <h2>
                Defina sua
                <span>nova senha</span>
            </h2>

            <input
                type="password"
                name="senha" id="senha"
                placeholder="NOVA SENHA"
                minlength="8" maxlength="72" autocomplete="new-password"
                required>

            <input
                type="password"
                name="confirmar_senha" id="confirmar_senha"
                placeholder="CONFIRMAR NOVA SENHA"
                minlength="8" maxlength="72" autocomplete="new-password"
                required>

            <button type="submit">
                Salvar nova senha
            </button>

            <p class="cadastro">
                <a href="<?= BASE_URL ?>/?rota=login">Voltar para o login</a>
            </p>

        </form>

    </div>

</div>

<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/aviso-form.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
document.querySelector('form').addEventListener('submit', (ev) => {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    if (senha !== confirmar) {
        ev.preventDefault();
        mostrarAviso('As senhas não são iguais. Confira os dois campos.', 'erro');
    }
});
</script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarAviso(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
