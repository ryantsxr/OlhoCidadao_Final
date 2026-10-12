<?php
/**
 * Menu lateral do painel admin (usado pelas telas novas).
 * Espera $usuario e $ativo (nome da rota atual, ex.: 'admin-usuarios').
 */
$itens = [
    'admin' => ['fa-list-check', 'Todas as denúncias'],
    'admin-kanban' => ['fa-table-columns', 'Quadro (kanban)'],
    'admin-historico' => ['fa-clock-rotate-left', 'Histórico'],
    'admin-estatisticas' => ['fa-chart-column', 'Estatísticas'],
    'admin-usuarios' => ['fa-users-gear', 'Usuários'],
    'admin-auditoria' => ['fa-user-shield', 'Auditoria'],
    'admin-integridade' => ['fa-link', 'Histórico íntegro'],
    'admin-seguranca' => ['fa-shield-halved', 'Segurança'],
];
?>
<div class="topo-mobile">
    <button id="btn-menu-abrir" type="button" aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></button>
    <img src="<?= BASE_URL ?>/img/logo.png" class="logo-mini" alt="Logo Olho Cidadão">
    <span class="titulo-mini">Olho <span>Cidadão</span></span>
</div>
<div class="menu-overlay" id="menu-overlay"></div>
<aside class="menu">
    <div class="menu-top">
        <img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Logo Olho Cidadão">
        <nav>
            <?php foreach ($itens as $rota => [$icone, $rotulo]): ?>
                <a href="<?= BASE_URL ?>/?rota=<?= $rota ?>" class="<?= $ativo === $rota ? 'ativo' : '' ?>">
                    <i class="fa-solid <?= $icone ?>"></i><span><?= $rotulo ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i><span>Mapa público</span></a>
            <a href="<?= BASE_URL ?>/?rota=configuracoes"><i class="fa-solid fa-gear"></i><span>Configurações</span></a>
        </nav>
    </div>
    <div class="menu-bottom">
        <div class="usuario-card">
            <div class="usuario-info">
                <img src="<?= htmlspecialchars(($usuario['foto'] ?? '') ?: BASE_URL . '/img/avatar-padrao.svg') ?>" alt="Foto do usuário" class="avatar">
                <div class="usuario-texto">
                    <strong><?= htmlspecialchars(explode(' ', trim($usuario['nome']))[0]) ?></strong>
                    <small>Administrador</small>
                </div>
            </div>
            <div class="usuario-acoes">
                <a href="<?= BASE_URL ?>/?rota=logout&t=<?= $this->csrfToken() ?>" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>
</aside>
