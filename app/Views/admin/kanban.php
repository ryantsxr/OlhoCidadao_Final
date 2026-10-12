<?php
/** @var array $usuario */
/** @var array $colunas  status => lista de denúncias */
/** @var int $totalResolvidas */
$ativo = 'admin-kanban';
$rotulosCat = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
$iconesCat = ['iluminacao' => 'fa-lightbulb', 'sinalizacao' => 'fa-traffic-light', 'lixo' => 'fa-trash', 'buracos' => 'fa-road', 'esgoto' => 'fa-water', 'agua' => 'fa-faucet-drip', 'arborizacao' => 'fa-tree', 'outros' => 'fa-plus'];
$tonsCat = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'];
$infoColunas = [
    'Pendente' => ['pendente', 'fa-inbox', 'Chegaram e ninguém olhou ainda'],
    'Em análise' => ['analise', 'fa-magnifying-glass', 'A prefeitura está cuidando'],
    'Aguardando confirmação' => ['aguardando', 'fa-hourglass-half', 'Esperando o cidadão confirmar'],
    'Resolvido' => ['resolvido', 'fa-circle-check', 'Confirmadas pelo cidadão'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quadro de denúncias - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/kanban.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/_menu.php'; ?>
    <main class="conteudo conteudo-kanban">
        <header>
            <div>
                <h1>Quadro de denúncias</h1>
                <p>Arraste um cartão para outra coluna para mudar o status. O cidadão é avisado na hora.</p>
            </div>
            <a href="<?= BASE_URL ?>/?rota=admin" class="btn-kanban-lista"><i class="fa-solid fa-list"></i> Ver em lista</a>
        </header>

        <form action="<?= BASE_URL ?>/" method="get" class="kanban-filtros" aria-label="Filtrar quadro de denúncias">
            <input type="hidden" name="rota" value="admin-kanban">
            <label>Categoria
                <select name="categoria">
                    <option value="todas">Todas</option>
                    <?php foreach ($rotulosCat as $chave => $nome): ?>
                        <option value="<?= htmlspecialchars($chave) ?>" <?= $filtroCategoria === $chave ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Setor
                <select name="orgao">
                    <option value="todos">Todos</option>
                    <?php foreach (Denuncia::ORGAOS as $chave => $info): ?>
                        <option value="<?= htmlspecialchars($chave) ?>" <?= $filtroOrgao === $chave ? 'selected' : '' ?>><?= htmlspecialchars($info['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridade
                <select name="prioridade">
                    <option value="todas">Todas</option>
                    <?php foreach (Denuncia::PRIORIDADES as $nivel): ?>
                        <option value="<?= htmlspecialchars($nivel) ?>" <?= $filtroPrioridade === $nivel ? 'selected' : '' ?>><?= htmlspecialchars($nivel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prazo
                <select name="prazo">
                    <option value="todos">Todos</option>
                    <option value="atrasadas" <?= $apenasAtrasadas ? 'selected' : '' ?>>Atrasadas</option>
                </select>
            </label>
            <label>Busca
                <input type="search" name="busca" value="<?= htmlspecialchars($buscaKanban, ENT_QUOTES, 'UTF-8') ?>" maxlength="80" placeholder="Protocolo ou descrição">
            </label>
            <button type="submit">Filtrar</button>
            <a href="<?= BASE_URL ?>/?rota=admin-kanban">Limpar</a>
        </form>

        <div class="kanban" id="kanban"
             data-url="<?= htmlspecialchars(BASE_URL . '/?rota=admin-kanban-mover') ?>"
             data-csrf="<?= htmlspecialchars($this->csrfToken()) ?>">
            <?php foreach ($colunas as $status => $cartoes):
                [$classe, $icone, $dica] = $infoColunas[$status];
                $travada = $status === 'Resolvido';
            ?>
            <section class="kanban-coluna col-<?= $classe ?><?= $travada ? ' travada' : '' ?>" data-status="<?= htmlspecialchars($status) ?>" aria-label="<?= htmlspecialchars($status) ?>">
                <header class="kanban-coluna-topo">
                    <span class="kanban-coluna-icone"><i class="fa-solid <?= $icone ?>"></i></span>
                    <div>
                        <h2><?= htmlspecialchars($status) ?></h2>
                        <small><?= $travada ? 'Só o cidadão move para cá' : $dica ?></small>
                    </div>
                    <span class="kanban-contador" data-contador><?= $travada ? $totalResolvidas : count($cartoes) ?></span>
                </header>
                <div class="kanban-cartoes" data-zona>
                    <?php foreach ($cartoes as $d):
                        $cat = $d['categoria'] ?? 'outros';
                        $prazo = Denuncia::statusPrazo($d);
                        $enc = Denuncia::infoEncaminhamento($d);
                        $apoios = count($d['apoios'] ?? []);
                    ?>
                    <article class="kanban-cartao prio-<?= strtolower($d['prioridade'] ?? 'normal') ?>"
                             <?= $travada ? '' : 'draggable="true"' ?>
                             data-id="<?= (int) $d['id'] ?>" data-status="<?= htmlspecialchars($status) ?>"
                             style="--tom:<?= $tonsCat[$cat] ?? '#019B38' ?>">
                        <div class="kanban-cartao-topo">
                            <span class="kanban-cat"><i class="fa-solid <?= $iconesCat[$cat] ?? 'fa-plus' ?>"></i> <?= $rotulosCat[$cat] ?? 'Outros' ?></span>
                            <?php if (!$travada): ?>
                            <div class="kanban-mover">
                                <button type="button" class="kanban-mover-btn" aria-label="Mover denúncia <?= htmlspecialchars(Denuncia::protocolo($d)) ?>" aria-haspopup="menu"><i class="fa-solid fa-ellipsis"></i></button>
                                <div class="kanban-mover-menu" role="menu" hidden>
                                    <?php foreach (['Pendente', 'Em análise', 'Aguardando confirmação'] as $destino): if ($destino === $status) continue; ?>
                                        <button type="button" role="menuitem" data-mover-para="<?= htmlspecialchars($destino) ?>"><i class="fa-solid fa-arrow-right"></i> <?= htmlspecialchars($destino) ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="kanban-titulo"><?= htmlspecialchars(mb_strimwidth($d['descricao'] ?? '', 0, 90, '…')) ?></p>
                        <div class="kanban-meta">
                            <a class="protocolo-tag kanban-ver" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $d['id'] ?>" draggable="false" title="Abrir a denúncia"><?= htmlspecialchars(Denuncia::protocolo($d)) ?> <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            <span><?= htmlspecialchars(explode(' ', trim($d['usuario_nome']))[0]) ?></span>
                        </div>
                        <div class="kanban-selos">
                            <?php if (($d['prioridade'] ?? 'Normal') !== 'Normal'): ?>
                                <span class="kanban-selo selo-prio"><i class="fa-solid fa-flag"></i> <?= htmlspecialchars($d['prioridade']) ?></span>
                            <?php endif; ?>
                            <?php if ($apoios): ?>
                                <span class="kanban-selo"><i class="fa-solid fa-hands-clapping"></i> <?= $apoios ?></span>
                            <?php endif; ?>
                            <?php if (!$travada): ?>
                                <span class="kanban-selo<?= $prazo['atrasada'] ? ' selo-atrasada' : '' ?>"><i class="fa-regular fa-clock"></i> <?= $prazo['dias'] ?>d<?= $prazo['atrasada'] ? ' · atrasada' : '' ?></span>
                            <?php endif; ?>
                            <?php if ($enc): ?>
                                <span class="kanban-selo" title="<?= htmlspecialchars($enc['orgao']) ?>"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars(preg_replace('/^(Secretaria( Municipal)?|Departamento Municipal|Setor) (de |do |da )?/u', '', $enc['orgao'])) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($d['avaliacao'])): ?>
                                <span class="kanban-selo"><i class="fa-solid fa-star"></i> <?= (int) $d['avaliacao'] ?>/5</span>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <div class="kanban-vazio"<?= $cartoes ? ' hidden' : '' ?>>
                        <i class="fa-regular fa-face-smile"></i>
                        <span><?= $travada ? 'Nenhuma resolvida ainda' : 'Nada por aqui' ?></span>
                    </div>
                    <?php if ($travada && $totalResolvidas > count($cartoes)): ?>
                        <a class="kanban-mais" href="<?= BASE_URL ?>/?rota=admin&status=resolvido">Ver todas as <?= $totalResolvidas ?> resolvidas</a>
                    <?php endif; ?>
                </div>
            </section>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/kanban.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
