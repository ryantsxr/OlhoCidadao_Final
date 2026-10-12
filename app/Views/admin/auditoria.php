<?php
/** @var array $usuario */
/** @var array $registros */
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
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
            <div class="menu-top">
                <img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Logo Olho Cidadão">

                <nav>
                    <a href="<?= BASE_URL ?>/?rota=admin">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Todas as denúncias</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-kanban">
                        <i class="fa-solid fa-table-columns"></i><span>Quadro (kanban)</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-historico">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Histórico</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-estatisticas">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>Estatísticas</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-usuarios">
                        <i class="fa-solid fa-users-gear"></i>
                        <span>Usuários</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-auditoria" class="ativo">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Auditoria</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-integridade">
                        <i class="fa-solid fa-link"></i>
                        <span>Histórico íntegro</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-seguranca">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Segurança</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Mapa público</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=configuracoes">
                        <i class="fa-solid fa-gear"></i>
                        <span>Configurações</span>
                    </a>
                </nav>
            </div>

            <div class="menu-bottom">
                <div class="usuario-card">
                    <div class="usuario-info">
                        <img src="<?= htmlspecialchars($usuario["foto"] ?: BASE_URL . "/img/avatar-padrao.svg") ?>" alt="Foto do usuário" class="avatar">
                        <div class="usuario-texto">
                            <strong><?= htmlspecialchars(explode(" ", trim($usuario["nome"]))[0]) ?></strong>
                            <small>Administrador</small>
                        </div>
                    </div>

                    <div class="usuario-acoes">
                        <a href="<?= BASE_URL ?>/?rota=logout&t=<?= $this->csrfToken() ?>" title="Sair">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <main class="conteudo">

            <header>
                <div>
                    <h1>Auditoria</h1>
                    <p>Últimas ações administrativas registradas no sistema</p>
                </div>
            </header>

            <div class="lista">
                <?php if (empty($registros)): ?>
                    <div class="estado-vazio">
                        <p>Nenhuma ação administrativa registrada ainda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                    <div class="denuncia denuncia-historico">
                        <div><i class="fa-solid fa-user-shield"></i></div>
                        <div>
                            <h3><?= htmlspecialchars($r['admin_nome'] ?? 'Admin') ?></h3>
                            <span class="denuncia-usuario">
                                <?php if (!empty($r['data'])): ?>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['data']))) ?>
                                <?php endif; ?>
                                <?php if (!empty($r['denuncia_id'])): ?>
                                    · Denúncia #<?= (int) $r['denuncia_id'] ?>
                                <?php endif; ?>
                            </span>
                            <p><?= htmlspecialchars($r['acao'] ?? '') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
