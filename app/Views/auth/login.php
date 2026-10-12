<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olho Cidadão</title>

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

        <!-- Logo para o tema escuro -->
        <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo" class="logo logo-dark">


        <p>
            Sua voz faz a diferença.
            <br>
            Denuncie problemas da sua cidade
            de forma rápida e segura.
        </p>

        <div class="destaques">

            <div class="destaque">
                <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <strong>Seguro e privado</strong>
                    <small>Seus dados protegidos, com opção de denúncia anônima.</small>
                </div>
            </div>

            <div class="destaque">
                <div class="icon"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <strong>Acompanhe o andamento</strong>
                    <small>Cada denúncia tem um protocolo para consultar quando quiser.</small>
                </div>
            </div>

            <div class="destaque">
                <div class="icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <div>
                    <strong>Mapa da cidade</strong>
                    <small>Veja no mapa o que já foi denunciado perto de você.</small>
                </div>
            </div>

        </div>

    </div>


    <div class="right">

        <?php // Erro/sucesso aparecem dentro do formulário (mostrarAviso) — ver script no final da página. ?>
<form method="post" action="<?= BASE_URL ?>/?rota=login">
            <?= $this->csrfField() ?>
            <input type="hidden" name="tipo_login" id="tipoLogin" value="<?= htmlspecialchars($tipoAtivo ?? 'cidadao') ?>">

            <div class="tipo-login-tabs">
                <button type="button" class="tab-login" data-tipo="cidadao">
                    <i class="fa-solid fa-user"></i> Sou Cidadão
                </button>
                <button type="button" class="tab-login" data-tipo="admin">
                    <i class="fa-solid fa-user-shield"></i> Sou Admin
                </button>
            </div>

            <h2 id="tituloLogin">
                Faça login no
                <span>Olho Cidadão</span>
            </h2>

            <input 
                type="email"
                name="email" id="email"
                placeholder="Email"
                maxlength="190" autocomplete="email"
                required>

            <input 
                type="password"
                name="senha" id="senha"
                placeholder="Senha"
                required>

            <a href="<?= BASE_URL ?>/?rota=esqueci-senha" class="senha">
                Esqueceu a senha?
            </a>

            <button>
                Entrar
            </button>


            <p class="cadastro">
                Ainda não possui conta?
                <a href="<?= BASE_URL ?>/?rota=cadastro">Criar conta</a>
            </p>

            <nav class="atalhos" aria-label="Atalhos">
                <a href="<?= BASE_URL ?>/?rota=mapa">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Mapa público</span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=consulta">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Consultar protocolo</span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=vitorias">
                    <i class="fa-solid fa-trophy"></i>
                    <span>Mural de vitórias</span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=sobre">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Sobre o projeto</span>
                </a>
            </nav>

        </form>

    </div>

</div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/aviso-form.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>">
        const tipoLoginInput = document.getElementById('tipoLogin');
        const tituloLogin = document.getElementById('tituloLogin');
        const abas = document.querySelectorAll('.tab-login');

        function ativarAba(tipo) {
            tipoLoginInput.value = tipo;
            abas.forEach(a => a.classList.toggle('ativa', a.dataset.tipo === tipo));
            tituloLogin.innerHTML = tipo === 'admin'
                ? 'Login do <span>Administrador</span>'
                : 'Faça login no <span>Olho Cidadão</span>';
        }

        abas.forEach(aba => aba.addEventListener('click', () => ativarAba(aba.dataset.tipo)));
        ativarAba(tipoLoginInput.value === 'admin' ? 'admin' : 'cidadao');

        document.querySelector('form').addEventListener('submit', function (e) {
            const senha = document.getElementById('senha').value;

            if (senha.length < 6) {
                e.preventDefault();
                mostrarAviso('Digite a senha completa (mínimo 6 caracteres).', 'erro');
            }
        });
    </script>
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