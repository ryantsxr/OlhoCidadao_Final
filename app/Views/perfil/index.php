<?php
/** @var array $usuario */
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/perfil.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/telas-cartoes.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
    <div class="layout">

        <div class="topo-mobile">
            <button id="btn-menu-abrir" type="button" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <img src="<?= BASE_URL ?>/img/logo.png" class="logo-mini" alt="Logo Olho Cidadão">
            <span class="titulo-mini">Olho <span>Cidadão</span></span>
        </div>

        <div class="menu-overlay" id="menu-overlay"></div>

        <aside class="menu">
            <img src="<?= BASE_URL ?>/img/logo.png" class="logo">
            <nav>
                <a href="<?= BASE_URL ?>/?rota=dashboard"><i class="fa-solid fa-house"></i> <span id="texto-inicio" data-i18n="inicio">Início</span></a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias"><i class="fa-solid fa-file-lines"></i> <span data-i18n="minhas-denuncias">Minhas denúncias</span></a>
                <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> <span>Mapa público</span></a>
                <a href="<?= BASE_URL ?>/?rota=perfil" class="ativo"><i class="fa-solid fa-user"></i> <span data-i18n="perfil">Perfil</span></a>
                <a href="<?= BASE_URL ?>/?rota=configuracoes"><i class="fa-solid fa-gear"></i> <span id="texto-config" data-i18n="config">Configurações</span></a>
                <a href="<?= BASE_URL ?>/?rota=logout&t=<?= $this->csrfToken() ?>" class="sair"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="sair">Sair</span></a>
            </nav>
        </aside>

        <main class="conteudo">
            <header>
                <div>
                    <h1 data-i18n="meu-perfil">Meu Perfil</h1>
                    <p data-i18n="gerenciar-info">Gerencie suas informações e segurança</p>
                </div>
            </header>

            <section class="perfil-resumo">
                <div class="perfil-resumo-item" style="--tom:#3D8BFD"><span class="icone-tom"><i class="fa-solid fa-file-lines"></i></span><div><strong><?= (int) $resumoPerfil['denuncias'] ?></strong><small>denúncias feitas</small></div></div>
                <div class="perfil-resumo-item" style="--tom:#22C55E"><span class="icone-tom"><i class="fa-solid fa-circle-check"></i></span><div><strong><?= (int) $resumoPerfil['resolvidas'] ?></strong><small>resolvidas</small></div></div>
                <div class="perfil-resumo-item" style="--tom:#F5B301"><span class="icone-tom"><i class="fa-solid fa-hands-clapping"></i></span><div><strong><?= (int) $resumoPerfil['apoios'] ?></strong><small>apoios recebidos</small></div></div>
                <?php if ($resumoPerfil['desde']): ?>
                <div class="perfil-resumo-item" style="--tom:#A78BFA"><span class="icone-tom"><i class="fa-solid fa-calendar-check"></i></span><div><strong><?= htmlspecialchars($resumoPerfil['desde']) ?></strong><small>no Olho Cidadão desde</small></div></div>
                <?php endif; ?>
            </section>

            <section class="perfil-container">
                <form method="post" action="<?= BASE_URL ?>/?rota=perfil" enctype="multipart/form-data">
                    <?= $this->csrfField() ?>
                    
                    <div class="foto-sessao">
                        <div class="avatar-wrapper">
                            <img src="<?= htmlspecialchars($usuario["foto"] ?: BASE_URL . "/img/avatar-padrao.svg") ?>" id="fotoPreview" alt="Prévia da foto de perfil">
                            <label for="inputFoto" class="btn-trocar-foto">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" name="foto" id="inputFoto" accept="image/*" style="display:none">
                        </div>
                        <p data-i18n="toque-foto">Toque na câmera para alterar a foto</p>
                    </div>

                    <h3 class="titulo-secao-ic"><span class="ic-tom" style="--tom:#3D8BFD"><i class="fa-solid fa-id-card"></i></span> Dados pessoais</h3>
                    <div class="form-grid">
                        <div class="campo">
                            <label data-i18n="nome-completo">Nome Completo</label>
                            <input type="text" name="nome" id="nomeUser" value="<?= htmlspecialchars($usuario["nome"]) ?>" placeholder="Ex: Eric Valiante" minlength="2" maxlength="150" data-i18n-placeholder="placeholder-nome" required>
                        </div>

                        <div class="campo">
                            <label data-i18n="email">E-mail</label>
                            <input type="email" name="email" id="emailUser" value="<?= htmlspecialchars($usuario["email"]) ?>" placeholder="exemplo@email.com" maxlength="190" data-i18n-placeholder="placeholder-email" required>
                        </div>

                        <div class="campo">
                            <label data-i18n="cidade">Cidade</label>
                            <input type="text" name="cidade" id="cidadeUser" value="<?= htmlspecialchars($usuario["cidade"] ?? "") ?>" placeholder="Sua cidade" maxlength="150" data-i18n-placeholder="placeholder-cidade">
                        </div>

                        <div class="campo">
                            <label data-i18n="telefone">Telefone <span class="obrigatorio">*</span></label>
                            <input type="text" name="telefone" id="telUser" value="<?= htmlspecialchars($usuario["telefone"] ?? "") ?>" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" data-mask="telefone" required data-i18n-placeholder="placeholder-tel">
                        </div>

                        <div class="campo">
                            <label for="cpfUser">CPF <span class="obrigatorio">*</span></label>
                            <input type="text" name="cpf" id="cpfUser" value="<?= htmlspecialchars($usuario["cpf"] ?? "") ?>" placeholder="000.000.000-00" maxlength="14" inputmode="numeric" data-mask="cpf" required>
                        </div>

                        <div class="campo">
                            <label for="cepUser">CEP <span class="obrigatorio">*</span></label>
                            <input type="text" name="cep" id="cepUser" value="<?= htmlspecialchars($usuario["cep"] ?? "") ?>" placeholder="00000-000" maxlength="9" inputmode="numeric" data-mask="cep" required>
                        </div>
                    </div>

                    <hr class="divisor">

                    <h3 class="titulo-secao-ic"><span class="ic-tom" style="--tom:#F5B301"><i class="fa-solid fa-key"></i></span> <span data-i18n="alterar-senha">Alterar Senha</span></h3>
                    <div class="form-grid">
                        <div class="campo">
                            <label data-i18n="senha-atual">Senha Atual</label>
                            <input type="password" name="senha_atual" id="senhaAtual" placeholder="Necessária para trocar senha ou e-mail" maxlength="72" data-i18n-placeholder="placeholder-senha-atual" autocomplete="current-password">
                        </div>
                        <div class="campo">
                            <label data-i18n="nova-senha">Nova Senha</label>
                            <input type="password" name="nova_senha" id="novaSenha" placeholder="Deixe em branco para não alterar" minlength="8" maxlength="72" data-i18n-placeholder="placeholder-nova-senha" autocomplete="new-password">
                        </div>
                        <div class="campo">
                            <label data-i18n="confirmar-nova-senha">Confirmar Nova Senha</label>
                            <input type="password" name="confirma_nova_senha" id="confirmaNovaSenha" placeholder="Confirme a nova senha" minlength="8" maxlength="72" data-i18n-placeholder="placeholder-confirma-senha" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="acoes">
                        <button type="submit" class="btn-salvar" data-i18n="salvar">Salvar Alterações</button>
                    </div>
                </form>
            </section>

            <section class="zona-dados">
                <h3 class="titulo-secao-ic"><span class="ic-tom" style="--tom:#22C55E"><i class="fa-solid fa-shield-halved"></i></span> Seus dados (LGPD)</h3>
                <p>Baixe uma cópia de tudo que o sistema guarda sobre você, ou exclua sua conta de vez.</p>
                <form method="post" action="<?= BASE_URL ?>/?rota=perfil-exportar">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="btn-dados"><i class="fa-solid fa-download"></i> Baixar meus dados (JSON)</button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/?rota=perfil-excluir" class="form-excluir" data-confirmar="Excluir sua conta de forma definitiva? Isso não pode ser desfeito.">
                    <?= $this->csrfField() ?>
                    <p class="aviso-exclusao">Ao excluir, seu cadastro e notificações são apagados. Suas denúncias continuam registradas, mas sem ligação com você.</p>
                    <input type="password" name="senha_exclusao" placeholder="Digite sua senha para confirmar" maxlength="72" required autocomplete="current-password">
                    <button type="submit" class="btn-excluir-conta"><i class="fa-solid fa-trash"></i> Excluir minha conta</button>
                </form>
            </section>
        </main>
    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/app.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/confirmacao.js?v=0.52.9"></script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
<?php if (!empty($sucesso)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($sucesso) ?>, 'sucesso'));</script>
<?php endif; ?>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>