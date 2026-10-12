<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Olho Cidadão</title>

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
            Sua voz, nossa cidade.
            <br>
            Crie sua conta e ajude a construir um lugar melhor para todos.
        </p>

        <div class="destaques">

            <div class="destaque">
                <div class="num">1</div>
                <div>
                    <strong>Crie sua conta</strong>
                    <small>Leva menos de um minuto, só com nome e e-mail.</small>
                </div>
            </div>

            <div class="destaque">
                <div class="num">2</div>
                <div>
                    <strong>Registre o problema</strong>
                    <small>Escolha a categoria, descreva e marque o local no mapa.</small>
                </div>
            </div>

            <div class="destaque">
                <div class="num">3</div>
                <div>
                    <strong>Acompanhe</strong>
                    <small>Receba aviso quando o status da denúncia mudar.</small>
                </div>
            </div>

        </div>

    </div>

    <div class="right">

        <?php // Erro/sucesso aparecem dentro do formulário (mostrarAviso) — ver script no final da página. ?>
<form method="post" action="<?= BASE_URL ?>/?rota=cadastro">
    <?= $this->csrfField() ?>

            <h2>
                Cadastre-se no
                <span>Olho Cidadão</span>
            </h2>

            <div class="campo">
                <i class="fa-solid fa-user icone-campo"></i>
                <input
                    type="text"
                    name="nome" id="nome"
                    placeholder="Nome de usuário"
                    minlength="2" maxlength="150" autocomplete="name"
                    required>
            </div>

            <div class="campo">
                <i class="fa-solid fa-envelope icone-campo"></i>
                <input
                    type="email"
                    name="email" id="email"
                    placeholder="E-mail"
                    maxlength="190" autocomplete="email"
                    required>
            </div>

            <div class="campo">
                <i class="fa-solid fa-lock icone-campo"></i>
                <input
                    type="password"
                    name="senha" id="senha"
                    placeholder="Senha (mínimo 8 caracteres)"
                    minlength="8" autocomplete="new-password"
                    required>
                <button type="button" class="ver-senha" data-alvo="senha" aria-label="Mostrar ou ocultar a senha">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <div class="forca-senha" id="forcaSenha" data-nivel="0" aria-live="polite">
                <div class="forca-barras"><i></i><i></i><i></i></div>
                <span id="forcaTexto">Digite uma senha</span>
            </div>

            <div class="campo">
                <i class="fa-solid fa-shield-halved icone-campo"></i>
                <input
                    type="password"
                    name="confirmar_senha" id="confirmar_senha"
                    placeholder="Confirmar senha"
                    minlength="8" autocomplete="new-password"
                    required>
                <button type="button" class="ver-senha" data-alvo="confirmar_senha" aria-label="Mostrar ou ocultar a confirmação da senha">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <div class="dica-confirmar" id="dicaConfirmar"></div>

            <div class="termos-linha">
                <input type="checkbox" name="termos" id="termos" required>
                <label for="termos">
                    Li e concordo com os
                    <a href="<?= BASE_URL ?>/?rota=termos" target="_blank" rel="noopener">Termos de uso</a>
                </label>
            </div>

            <button type="submit">
                <i class="fa-solid fa-user-plus"></i> Criar minha conta
            </button>


            <p class="cadastro">
                Já possui conta?
                <a href="<?= BASE_URL ?>/?rota=login">Fazer login</a>
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
                <a href="<?= BASE_URL ?>/?rota=sobre">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Sobre o projeto</span>
                </a>
            </nav>

        </form>

    </div>

</div>

<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/aviso-form.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
(function () {
    const senha = document.getElementById('senha');
    const confirmar = document.getElementById('confirmar_senha');
    const forca = document.getElementById('forcaSenha');
    const forcaTexto = document.getElementById('forcaTexto');
    const dica = document.getElementById('dicaConfirmar');

    // Olhinho: mostra/oculta a senha.
    document.querySelectorAll('.ver-senha').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const campo = document.getElementById(btn.dataset.alvo);
            const mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            btn.querySelector('i').className = mostrar ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    });

    // Força da senha: só orienta a pessoa, quem valida de verdade é o servidor.
    function nivelSenha(v) {
        if (!v) return 0;
        let pontos = 0;
        if (v.length >= 8) pontos++;
        if (v.length >= 10) pontos++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) pontos++;
        if (/\d/.test(v)) pontos++;
        if (/[^A-Za-z0-9]/.test(v)) pontos++;
        if (v.length < 8) return 1;
        return pontos >= 4 ? 3 : (pontos >= 3 ? 2 : 1);
    }

    function atualizar() {
        const n = nivelSenha(senha.value);
        forca.dataset.nivel = n;
        forcaTexto.textContent = ['Digite uma senha', 'Senha fraca', 'Senha média', 'Senha forte'][n];

        if (!confirmar.value) {
            dica.textContent = '';
            dica.className = 'dica-confirmar';
        } else if (confirmar.value === senha.value) {
            dica.textContent = 'As senhas conferem';
            dica.className = 'dica-confirmar ok';
        } else {
            dica.textContent = 'As senhas ainda não são iguais';
            dica.className = 'dica-confirmar erro';
        }
    }

    senha.addEventListener('input', atualizar);
    confirmar.addEventListener('input', atualizar);
})();

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