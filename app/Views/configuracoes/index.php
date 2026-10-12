<?php
/** @var array $usuario */
/** @var array $cfg */
/** @var bool $ehAdmin */
$ehAdmin = $ehAdmin ?? false;
$cfg = $cfg ?? [
    'temaEscuro' => true,
    'idioma' => 'pt-br',
    'fonte' => 'padrao',
    'altoContraste' => false,
    'modoDaltonico' => false,
    'fonteLeitura' => false
];

$fontSize = match ($cfg['fonte'] ?? 'padrao') {
    'grande' => '120%',
    'extra-grande' => '140%',
    default => '100%'
};

$classesBody = [];
if (empty($cfg['temaEscuro'])) {
    $classesBody[] = 'tema-claro';
}
if (!empty($cfg['altoContraste'])) {
    $classesBody[] = 'alto-contraste';
}
if (!empty($cfg['modoDaltonico'])) {
    $classesBody[] = 'modo-daltonico';
}
if (!empty($cfg['fonteLeitura'])) {
    $classesBody[] = 'fonte-leitura';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($cfg['idioma'] ?? 'pt-br') ?>" style="font-size: <?= $fontSize ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Olho Cidadão</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/configuracoes.css?v=0.52.9">

    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>

<body class="<?= htmlspecialchars(implode(' ', $classesBody)) ?>">
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
        <div class="menu-top">
            <img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Logo Olho Cidadão">
            <nav>
                <?php if ($ehAdmin): ?>
                    <a href="<?= BASE_URL ?>/?rota=admin"><i class="fa-solid fa-list-check"></i><span>Todas as denúncias</span></a>
                    <a href="<?= BASE_URL ?>/?rota=admin-historico"><i class="fa-solid fa-clock-rotate-left"></i><span>Histórico</span></a>
                    <a href="<?= BASE_URL ?>/?rota=admin-estatisticas"><i class="fa-solid fa-chart-column"></i><span>Estatísticas</span></a>
                    <a href="<?= BASE_URL ?>/?rota=admin-usuarios"><i class="fa-solid fa-users-gear"></i><span>Usuários</span></a>
                    <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i><span>Mapa público</span></a>
                    <a href="<?= BASE_URL ?>/?rota=configuracoes" class="ativo"><i class="fa-solid fa-gear"></i><span>Configurações</span></a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/?rota=dashboard"><i class="fa-solid fa-house"></i><span id="texto-inicio" data-i18n="inicio">Início</span></a>
                    <a href="<?= BASE_URL ?>/?rota=minhas-denuncias"><i class="fa-solid fa-file-lines"></i><span data-i18n="minhas-denuncias">Minhas denúncias</span></a>
                    <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i><span>Mapa público</span></a>
                    <a href="<?= BASE_URL ?>/?rota=perfil"><i class="fa-solid fa-user"></i><span data-i18n="perfil">Perfil</span></a>
                    <a href="<?= BASE_URL ?>/?rota=configuracoes" class="ativo"><i class="fa-solid fa-gear"></i><span id="texto-config" data-i18n="config">Configurações</span></a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="menu-bottom">
            <div class="usuario-card">
                <div class="usuario-info">
                    <img src="<?= htmlspecialchars($usuario['foto'] ?: BASE_URL . '/img/avatar-padrao.svg') ?>" alt="Foto do usuário" class="avatar" id="avatarUsuario">
                    <div class="usuario-texto">
                        <strong id="nomeUsuarioMenu"><?= htmlspecialchars(explode(' ', trim($usuario['nome'] ?? 'Usuário'))[0]) ?></strong>
                        <?php if ($ehAdmin): ?>
                            <small>Administrador</small>
                        <?php else: ?>
                            <small data-i18n="cidadao">Cidadão</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="usuario-acoes">
                    <?php if (!$ehAdmin): ?>
                        <a href="<?= BASE_URL ?>/?rota=perfil" title="Perfil" data-i18n-title="perfil"><i class="fa-solid fa-user"></i></a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/?rota=logout&t=<?= $this->csrfToken() ?>" title="Sair" data-i18n-title="sair"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </aside>

    <main class="conteudo">
        <header class="header-config">
            <div>
                <h1 id="titulo-principal-config" data-i18n="titulo-config">Configurações</h1>
                <p data-i18n="sub-config">Personalize a sua experiência no aplicativo.</p>
            </div>
        </header>

        <form method="post" action="<?= BASE_URL ?>/?rota=configuracoes">
            <?= $this->csrfField() ?>
            <div class="config-layout">
            <section class="painel-config">

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-moon"></i>
                        <div>
                            <h3 data-i18n="tema-escuro">Tema Escuro</h3>
                            <p data-i18n="desc-tema">Altera a aparência geral do sistema</p>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="temaEscuro" id="toggle-tema" <?= !empty($cfg['temaEscuro']) ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-globe"></i>
                        <div>
                            <h3 data-i18n="idioma">Idioma</h3>
                            <p data-i18n="desc-idioma">Selecione o idioma de sua preferência</p>
                        </div>
                    </div>
                    <select name="idioma" id="select-idioma">
                        <option <?= ($cfg['idioma'] ?? '') === 'pt-br' ? 'selected' : '' ?> value="pt-br" data-i18n="opcao-pt">Português (BR)</option>
                        <option <?= ($cfg['idioma'] ?? '') === 'en' ? 'selected' : '' ?> value="en" data-i18n="opcao-en">English (US)</option>
                        <option <?= ($cfg['idioma'] ?? '') === 'es' ? 'selected' : '' ?> value="es" data-i18n="opcao-es">Español</option>
                    </select>
                </div>

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-font"></i>
                        <div>
                            <h3 data-i18n="tamanho-fonte">Tamanho da Fonte</h3>
                            <p data-i18n="desc-fonte">Aumente ou diminua o tamanho dos textos</p>
                        </div>
                    </div>
                    <select name="fonte" id="select-fonte">
                        <option <?= ($cfg['fonte'] ?? '') === 'padrao' ? 'selected' : '' ?> value="padrao" data-i18n="opcao-padrao">Padrão</option>
                        <option <?= ($cfg['fonte'] ?? '') === 'grande' ? 'selected' : '' ?> value="grande" data-i18n="opcao-grande">Grande (+20%)</option>
                        <option <?= ($cfg['fonte'] ?? '') === 'extra-grande' ? 'selected' : '' ?> value="extra-grande" data-i18n="opcao-extra-grande">Extra Grande (+40%)</option>
                    </select>
                </div>

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-eye"></i>
                        <div>
                            <h3 data-i18n="contraste">Alto Contraste</h3>
                            <p data-i18n="desc-contraste">Melhora a leitura para pessoas com baixa visão</p>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="altoContraste" id="toggle-contraste" <?= !empty($cfg['altoContraste']) ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-palette"></i>
                        <div>
                            <h3>Cores para daltonismo</h3>
                            <p>Troca as cores dos status por uma paleta fácil de distinguir (os status também têm ícone)</p>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="modoDaltonico" id="toggle-daltonico" <?= !empty($cfg['modoDaltonico']) ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <div class="config-item">
                    <div class="config-info">
                        <i class="fa-solid fa-book-open-reader"></i>
                        <div>
                            <h3>Fonte de leitura fácil</h3>
                            <p>Letras mais distintas e espaçadas, indicada para dislexia</p>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="fonteLeitura" id="toggle-leitura" <?= !empty($cfg['fonteLeitura']) ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <button class="btn-salvar" type="submit" data-i18n="salvar">Salvar Alterações</button>
            </section>

            <aside class="config-preview" aria-hidden="true">
                <h3>Pré-visualização</h3>
                <p class="prev-sub">Veja na hora como ficam o tema, a fonte e o contraste. Clique em salvar para manter.</p>

                <div class="prev-item">
                    <div class="prev-icone"><i class="fa-solid fa-lightbulb"></i></div>
                    <div class="prev-corpo">
                        <strong>Iluminação</strong>
                        <p>Poste apagado na Rua 15, esquina com a Av. Principal.</p>
                        <small>OC-2026-000123</small>
                    </div>
                </div>
                <b class="prev-pill">Em análise</b>

                <button type="button" class="prev-botao" tabindex="-1"><i class="fa-solid fa-plus"></i> Fazer denúncia</button>
            </aside>
            </div>
        </form>

    <?php if ($ehAdmin): ?>
    <section id="seguranca-conta" class="painel-config" style="margin:20px 0;max-width:780px;padding:24px;scroll-margin-top:20px;" aria-label="Segurança do administrador">
        <h2><i class="fa-solid fa-shield-halved"></i> Segurança da conta</h2>
        <?php if (!empty($erro)): ?><p role="alert" style="color:#ef4444"><?= htmlspecialchars($erro, ENT_QUOTES) ?></p><?php endif; ?>
        <?php if (!empty($sucesso)): ?><p role="status" style="color:#16a34a"><?= htmlspecialchars($sucesso, ENT_QUOTES) ?></p><?php endif; ?>
        <?php if (!empty($codigosRecuperacao)): ?>
            <p><strong>Guarde estes códigos fora do sistema. Eles serão exibidos só agora.</strong></p>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:15px 0;">
                <?php foreach ($codigosRecuperacao as $cod): ?><code style="padding:9px;border:1px solid #a7b0af;border-radius:6px"><?= htmlspecialchars($cod) ?></code><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (SegurancaConta::doisFatoresAtivo($usuario)): ?>
            <p>Verificação em duas etapas <strong>ativada</strong>. A cada novo login administrativo, será necessário o código do aplicativo.</p>
            <?php $qtdBackups = SegurancaConta::contarCodigosRecuperacao((int)$usuario['id']); ?>
            <div style="margin:16px 0;padding:14px;border:1px solid #596575;border-radius:9px;">
                <p style="margin:0 0 8px;"><strong>Códigos de recuperação disponíveis: <?= $qtdBackups ?>/8</strong></p>
                <?php if ($qtdBackups <= 2): ?>
                    <p role="status" style="color:#f59e0b;margin:5px 0 10px;"><i class="fa-solid fa-triangle-exclamation"></i>
                    <?= $qtdBackups === 0 ? 'Você não tem códigos disponíveis. Gere novos códigos agora.' : 'Restam poucos códigos de recuperação. Gere um novo conjunto.' ?></p>
                <?php else: ?>
                    <p style="margin:5px 0 10px;">Cada código só pode ser usado uma vez. Você pode gerar um novo conjunto quando quiser.</p>
                <?php endif; ?>
                <form method="post" action="<?= BASE_URL ?>/?rota=seguranca-2fa-regenerar">
                    <?= $this->csrfField() ?>
                    <label for="senha-regenerar">Senha atual</label>
                    <input id="senha-regenerar" name="senha_atual" type="password" autocomplete="current-password" required>
                    <label for="codigo-regenerar">Código do Google Authenticator ou de recuperação</label>
                    <input id="codigo-regenerar" name="codigo" type="text" maxlength="15" autocomplete="one-time-code" required>
                    <label style="display:flex;align-items:flex-start;gap:8px;margin:12px 0;">
                        <input type="checkbox" name="confirmar_substituicao" value="1" required style="width:auto;margin-top:4px;flex-shrink:0;">
                        <span>Entendo que os códigos anteriores serão invalidados imediatamente.</span>
                    </label>
                    <button type="submit" class="btn-salvar"><i class="fa-solid fa-rotate"></i> Gerar novos 8 códigos</button>
                </form>
            </div>
            <form method="post" action="<?= BASE_URL ?>/?rota=seguranca-2fa-desativar">
                <?= $this->csrfField() ?>
                <label for="senha-desativar">Senha atual</label>
                <input id="senha-desativar" name="senha_atual" type="password" autocomplete="current-password" required>
                <label for="codigo-desativar">Código do autenticador ou código de recuperação</label>
                <input id="codigo-desativar" name="codigo" type="text" maxlength="15" autocomplete="one-time-code" required>
                <button type="submit" class="btn-salvar">Desativar 2FA</button>
            </form>
        <?php elseif (!empty($segredoPendente) && ($segredoPendente['expira'] ?? 0) > time()): ?>
            <p>Abra um aplicativo como Google Authenticator ou Microsoft Authenticator e <strong>leia o QR code</strong> abaixo (ou digite a chave):</p>
            <div id="totp-qr" class="totp-qr" role="img" aria-label="QR code para o aplicativo autenticador"
                 data-otpauth="<?= htmlspecialchars(Totp::uri((string)$segredoPendente['segredo'], (string)($usuario['email'] ?? '')), ENT_QUOTES) ?>"></div>
            <p><code style="font-size:1rem;word-break:break-all;user-select:all;"><?= htmlspecialchars(chunk_split((string)$segredoPendente['segredo'],4,' ')) ?></code></p>
            <p><small>Não compartilhe a chave nem capture esta tela. O cadastro expira em 10 minutos.</small></p>
            <form method="post" action="<?= BASE_URL ?>/?rota=seguranca-2fa-ativar">
                <?= $this->csrfField() ?>
                <label for="codigo-ativar">Código de 6 dígitos do aplicativo</label>
                <input id="codigo-ativar" name="codigo" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                <button class="btn-salvar" type="submit">Confirmar e ativar 2FA</button>
            </form>
        <?php else: ?>
            <?php if (Ambiente::admin2faObrigatorio()): ?>
            <p><strong>Obrigatório:</strong> neste servidor, o painel administrativo só abre depois de ativar a verificação em duas etapas. Você vai precisar do celular com um aplicativo autenticador.</p>
            <?php else: ?>
            <p>Adicione uma segunda proteção ao login administrativo. Recomendado — e obrigatório quando o sistema estiver num servidor de verdade.</p>
            <?php endif; ?>
            <form method="post" action="<?= BASE_URL ?>/?rota=seguranca-2fa-iniciar">
                <?= $this->csrfField() ?>
                <label for="senha-iniciar">Confirme sua senha atual</label>
                <input id="senha-iniciar" name="senha_atual" type="password" autocomplete="current-password" required>
                <button type="submit" class="btn-salvar">Configurar autenticação em duas etapas</button>
            </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    </main>
</div>

<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.4-i18n2"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
    // Aplica na hora o que for marcado (a gravação de verdade continua sendo no botão Salvar).
    ['toggle-tema', 'select-fonte', 'toggle-contraste', 'toggle-daltonico', 'toggle-leitura'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', function () { aplicarConfiguracaoVisual(obterConfiguracaoFormulario()); });
    });
</script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php if ($ehAdmin && !empty($segredoPendente)): ?>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/vendor/qrcode.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
// QR code da chave 2FA, gerado no próprio navegador (a chave não sai do site).
(function () {
    const el = document.getElementById('totp-qr');
    if (!el || typeof qrcode !== 'function') return;
    const qr = qrcode(0, 'M'); qr.addData(el.dataset.otpauth); qr.make();
    const n = qr.getModuleCount(); let d = '';
    for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) if (qr.isDark(y, x)) d += 'M' + x + ' ' + y + 'h1v1h-1z';
    el.innerHTML = '<svg viewBox="-2 -2 ' + (n + 4) + ' ' + (n + 4) + '" shape-rendering="crispEdges"><rect x="-2" y="-2" width="' + (n + 4) + '" height="' + (n + 4) + '" fill="#fff"/><path d="' + d + '" fill="#111"/></svg>';
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
