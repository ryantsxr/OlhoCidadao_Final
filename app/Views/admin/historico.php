<?php
/** @var array $usuario */
/** @var array $todas */

$icones = [
    'iluminacao' => 'fa-lightbulb',
    'sinalizacao' => 'fa-traffic-light',
    'lixo' => 'fa-trash',
    'outros' => 'fa-plus'
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de denúncias - Olho Cidadão</title>
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
                    <a href="<?= BASE_URL ?>/?rota=admin-historico" class="ativo">
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
                    <a href="<?= BASE_URL ?>/?rota=admin-auditoria">
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
                    <h1>Histórico de denúncias</h1>
                    <p>Todas as denúncias já registradas, com a linha do tempo de cada uma</p>
                </div>
            </header>

            <div class="lista">
                <?php if (empty($todas)): ?>
                    <div class="estado-vazio">
                        <p>Nenhuma denúncia registrada no sistema ainda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($todas as $d):
                        $statusLower = strtolower($d['status'] ?? '');
                        $statusClass = match ($statusLower) {
                            'resolvido' => 'resolvido',
                            'em análise' => 'analise',
                            'cancelada' => 'cancelada',
                            default => 'pendente'
                        };
                        $icone = $icones[$d['categoria']] ?? 'fa-file-lines';
                        $historico = $d['historico'] ?? [];
                    ?>
                    <div class="denuncia denuncia-historico">
                        <div><i class="fa-solid <?= $icone ?>"></i></div>
                        <div>
                            <h3><?= htmlspecialchars(Denuncia::protocolo($d)) ?> · <?= htmlspecialchars(ucfirst($d['categoria'])) ?></h3>
                            <span class="denuncia-usuario">
                                Por <?= htmlspecialchars($d['usuario_nome']) ?>
                                <?php if (!empty($d['criada_em'])): ?>
                                    · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['criada_em']))) ?>
                                <?php endif; ?>
                            </span>
                            <p><?= htmlspecialchars($d['descricao']) ?></p>

                            <?php if (!empty($historico)): ?>
                                <details class="timeline-historico">
                                    <summary>Ver linha do tempo (<?= count($historico) ?>)</summary>
                                    <ul>
                                        <?php foreach ($historico as $h): ?>
                                            <li>
                                                <strong><?= htmlspecialchars($h['status'] ?? '') ?></strong>
                                                <?php if (!empty($h['data'])): ?>
                                                    — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['data']))) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($h['foto'])): ?>
                                                    · <a href="<?= htmlspecialchars(Uploader::urlAtual((string) $h['foto'])) ?>" target="_blank" rel="noopener">Ver foto de comprovação</a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/?rota=denuncia-relatorio&amp;origem=admin-historico&amp;id=<?= (int)$d['id'] ?>" target="_blank" rel="noopener">
                                <i class="fa-solid fa-file-pdf"></i> Gerar relatório PDF
                            </a>

                            <div>
                                <?php if (!empty($d['anonima'])): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-user-secret"></i> Anônima</span>
                                <?php endif; ?>
                                <?php if (Denuncia::ehVerificada($d)): ?>
                                    <span class="badge-verificado"><i class="fa-solid fa-shield-halved"></i> Verificado por moradores</span>
                                <?php endif; ?>
                                <?php $totalApoios = count($d['apoios'] ?? []); if ($totalApoios > 0): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-hands-clapping"></i> <?= $totalApoios ?> apoio<?= $totalApoios === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                                <?php if (!empty($d['denuncia_anterior_id'])): ?>
                                    <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $d['denuncia_anterior_id'] ?>" class="badge-reincidencia" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-rotate"></i> Reincidência da #<?= (int) $d['denuncia_anterior_id'] ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($d['avaliacao'])): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-star" style="color:#f2b705"></i> Avaliação: <?= (int) $d['avaliacao'] ?>/5</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="<?= $statusClass ?>">
                            <?= htmlspecialchars($d['status']) ?>
                        </span>
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
