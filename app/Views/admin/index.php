<?php
/** @var array $usuario */
/** @var array $denuncias */
/** @var array $stats */
/** @var string $filtro */
/** @var string $categoriaFiltro */
/** @var string $busca */
/** @var int $pagina */
/** @var int $totalPaginas */
/** @var int $totalFiltrado */
/** @var int $totalNaoVistas */

$icones = [
    'iluminacao' => 'fa-lightbulb',
    'sinalizacao' => 'fa-traffic-light',
    'lixo' => 'fa-trash',
    'buracos' => 'fa-road',
    'esgoto' => 'fa-water',
    'agua' => 'fa-faucet-drip',
    'arborizacao' => 'fa-tree',
    'outros' => 'fa-plus'
];

$categoriasLabel = [
    'iluminacao' => 'Iluminação',
    'sinalizacao' => 'Sinalização',
    'lixo' => 'Lixo',
    'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'
];

$statusOpcoes = ['Pendente', 'Em análise', 'Aguardando confirmação'];

/** Monta a URL do filtro trocando só a parte (status ou categoria) que o link controla.
 *  Mantém a busca de texto ativa, mas sempre volta pra página 1 (a lista mudou). */
function urlFiltro(string $status, string $categoria, string $busca = ''): string {
    $query = 'status=' . urlencode($status) . '&categoria=' . urlencode($categoria);
    if (($_GET['ordem'] ?? '') === 'prioridade') $query .= '&ordem=prioridade';
    if (!empty($_GET['orgao']) && isset(Denuncia::ORGAOS[$_GET['orgao']])) $query .= '&orgao=' . urlencode($_GET['orgao']);
    if ($busca !== '') {
        $query .= '&busca=' . urlencode($busca);
    }
    return BASE_URL . '/?rota=admin&' . $query;
}

/** Monta a URL de uma página específica, mantendo os filtros e a busca atuais. */
function urlPagina(int $numeroPagina, string $status, string $categoria, string $busca): string {
    $query = 'status=' . urlencode($status) . '&categoria=' . urlencode($categoria) . '&pagina=' . $numeroPagina;
    if (($_GET['ordem'] ?? '') === 'prioridade') $query .= '&ordem=prioridade';
    if (!empty($_GET['orgao']) && isset(Denuncia::ORGAOS[$_GET['orgao']])) $query .= '&orgao=' . urlencode($_GET['orgao']);
    if ($busca !== '') {
        $query .= '&busca=' . urlencode($busca);
    }
    return BASE_URL . '/?rota=admin&' . $query;
}

$rotulosVazio = [
    'todas' => ['fa-eye', 'O olho ainda não viu nenhuma denúncia', 'Quando os cidadãos registrarem problemas, eles aparecem aqui.'],
    'pendente' => ['fa-mug-hot', 'Nenhuma denúncia pendente', 'Caixa de entrada zerada. Bom trabalho!'],
    'analise' => ['fa-magnifying-glass', 'Nada em análise agora', 'Mova denúncias pendentes para cá quando a equipe começar a cuidar.'],
    'aguardando' => ['fa-hourglass-half', 'Ninguém esperando confirmação', 'Quando você marcar uma denúncia como resolvida, ela fica aqui até o cidadão confirmar.'],
    'resolvido' => ['fa-seedling', 'Nenhuma resolvida ainda', 'As denúncias confirmadas pelos cidadãos aparecem aqui.'],
    'rejeitada' => ['fa-ban', 'Nenhuma denúncia rejeitada', 'Denúncias rejeitadas aparecem aqui.'],
    'cancelada' => ['fa-circle-xmark', 'Nenhuma denúncia cancelada', 'Denúncias canceladas aparecem aqui.'],
    'atrasadas' => ['fa-champagne-glasses', 'Nenhuma denúncia com prazo estourado', 'Todos os prazos em dia. 🎉']
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/telas-cartoes.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
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
                    <a href="<?= BASE_URL ?>/?rota=admin" class="ativo">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Todas as denúncias</span>
                        <?php if ($totalNaoVistas > 0): ?>
                            <span class="badge-novo-nav"><?= $totalNaoVistas ?></span>
                        <?php endif; ?>
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
                    <h1>Painel do Administrador</h1>
                    <p>Acompanhe e trate as denúncias de todos os cidadãos</p>
                </div>

                <div class="header-acoes">
                    <a href="<?= BASE_URL ?>/?rota=admin-relatorio" class="denunciar" style="text-decoration:none;">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Relatório geral</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=admin-exportar-csv" class="denunciar" style="text-decoration:none;">
                        <i class="fa-solid fa-file-csv"></i>
                        <span>Exportar CSV</span>
                    </a>
                </div>
            </header>

            <section class="cards">
                <div class="card">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <div>
                        <h3><?= $stats["total"] ?></h3>
                        <p>Denúncias no total</p>
                    </div>
                </div>

                <div class="card">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <h3><?= $stats["analise"] ?></h3>
                        <p>Em análise</p>
                    </div>
                </div>

                <div class="card">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <div>
                        <h3><?= $stats["aguardando"] ?></h3>
                        <p>Aguardando confirmação</p>
                    </div>
                </div>

                <div class="card">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>
                        <h3><?= $stats["resolvidas"] ?></h3>
                        <p>Resolvidas</p>
                    </div>
                </div>

                <div class="card">
                    <i class="fa-solid fa-stopwatch"></i>
                    <div>
                        <h3><?= htmlspecialchars($stats["tempo_medio_texto"]) ?></h3>
                        <p>Tempo médio até resolver</p>
                    </div>
                </div>
            </section>

            <div class="filtros-denuncias">
                <a href="<?= urlFiltro('todas', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'todas' ? 'ativo' : '' ?>">
                    Todas <span><?= $stats['total'] ?></span>
                </a>
                <a href="<?= urlFiltro('pendente', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'pendente' ? 'ativo' : '' ?>">
                    Pendentes <span><?= $stats['pendentes'] ?></span>
                </a>
                <a href="<?= urlFiltro('analise', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'analise' ? 'ativo' : '' ?>">
                    Em análise <span><?= $stats['analise'] ?></span>
                </a>
                <a href="<?= urlFiltro('aguardando', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'aguardando' ? 'ativo' : '' ?>">
                    Aguardando confirmação <span><?= $stats['aguardando'] ?></span>
                </a>
                <a href="<?= urlFiltro('resolvido', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'resolvido' ? 'ativo' : '' ?>">
                    Resolvidas <span><?= $stats['resolvidas'] ?></span>
                </a>
                <a href="<?= urlFiltro('rejeitada', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'rejeitada' ? 'ativo' : '' ?>">Rejeitadas <span><?= (int)($stats['rejeitadas'] ?? 0) ?></span></a>
                <a href="<?= urlFiltro('cancelada', $categoriaFiltro, $busca) ?>" class="filtro-chip <?= $filtro === 'cancelada' ? 'ativo' : '' ?>">Canceladas <span><?= (int)($stats['canceladas'] ?? 0) ?></span></a>
                <a href="<?= urlFiltro('atrasadas', $categoriaFiltro, $busca) ?>" class="filtro-chip chip-atrasadas <?= $filtro === 'atrasadas' ? 'ativo' : '' ?>">
                    <i class="fa-solid fa-triangle-exclamation"></i> Atrasadas <span><?= $stats['atrasadas'] ?></span>
                </a>
            </div>

            <form method="get" action="<?= BASE_URL ?>/" class="busca-denuncias">
                <input type="hidden" name="rota" value="admin">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filtro) ?>">
                <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaFiltro) ?>">
                <input type="hidden" name="orgao" value="<?= htmlspecialchars($orgaoFiltro) ?>">
                <input type="hidden" name="ordem" value="<?= htmlspecialchars($ordem) ?>">
                <input type="search" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por descrição, categoria, cidadão ou protocolo...">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if ($busca !== ''): ?>
                    <a href="<?= urlFiltro($filtro, $categoriaFiltro, '') ?>" class="busca-limpar" title="Limpar busca">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>

            <div class="filtros-denuncias">
                <a href="<?= urlFiltro($filtro, 'todas', $busca) ?>" class="filtro-chip <?= $categoriaFiltro === 'todas' ? 'ativo' : '' ?>">
                    Todas as categorias
                </a>
                <?php foreach ($categoriasLabel as $valor => $rotulo): ?>
                    <a href="<?= urlFiltro($filtro, $valor, $busca) ?>" class="filtro-chip <?= $categoriaFiltro === $valor ? 'ativo' : '' ?>">
                        <i class="fa-solid <?= $icones[$valor] ?>"></i> <?= $rotulo ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?= BASE_URL ?>/" class="barra-ordem" aria-label="Filtrar pelo setor responsável">
                <input type="hidden" name="rota" value="admin">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filtro) ?>">
                <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaFiltro) ?>">
                <input type="hidden" name="ordem" value="<?= htmlspecialchars($ordem) ?>">
                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                <label for="filtro-orgao-admin">Setor responsável:</label>
                <select id="filtro-orgao-admin" name="orgao" class="select-status">
                    <option value="todos">Todos os setores</option>
                    <?php foreach (Denuncia::ORGAOS as $chave => $setor): ?>
                        <option value="<?= htmlspecialchars($chave) ?>" <?= $orgaoFiltro === $chave ? 'selected' : '' ?>><?= htmlspecialchars($setor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-encaminhar">Filtrar setor</button>
            </form>
            <div class="barra-ordem">
                <i class="fa-solid fa-arrow-down-wide-short"></i> Ordenar:
                <a href="<?= BASE_URL ?>/?rota=admin&status=<?= urlencode($filtro) ?>&categoria=<?= urlencode($categoriaFiltro) ?><?= $busca !== '' ? '&busca=' . urlencode($busca) : '' ?><?= $orgaoFiltro !== 'todos' ? '&orgao=' . urlencode($orgaoFiltro) : '' ?>" class="<?= $ordem === 'recentes' ? 'ativo' : '' ?>">Mais recentes</a>
                <a href="<?= BASE_URL ?>/?rota=admin&status=<?= urlencode($filtro) ?>&categoria=<?= urlencode($categoriaFiltro) ?>&ordem=prioridade<?= $busca !== '' ? '&busca=' . urlencode($busca) : '' ?><?= $orgaoFiltro !== 'todos' ? '&orgao=' . urlencode($orgaoFiltro) : '' ?>" class="<?= $ordem === 'prioridade' ? 'ativo' : '' ?>">Mais urgentes primeiro</a>
            </div>

            <div class="lista">
                <?php if (empty($denuncias)): ?>
                    <?php [$icVazio, $tituloVazio, $textoVazio] = $busca !== ''
                        ? ['fa-magnifying-glass', 'O olho não achou nada para "' . $busca . '"', 'Tente o número do protocolo, o nome da rua ou o nome do cidadão.']
                        : $rotulosVazio[$filtro]; ?>
                    <div class="estado-vazio estado-vazio-v2">
                        <span class="vazio-ilustra"><i class="fa-solid <?= $icVazio ?>"></i></span>
                        <h3><?= htmlspecialchars($tituloVazio) ?></h3>
                        <p><?= htmlspecialchars($textoVazio) ?></p>
                        <?php if ($busca !== '' || $filtro !== 'todas' || $categoriaFiltro !== 'todas' || $orgaoFiltro !== 'todos'): ?>
                            <a class="botao-vazio secundario" href="<?= BASE_URL ?>/?rota=admin"><i class="fa-solid fa-rotate-left"></i> Limpar filtros</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($denuncias as $d):
                        $statusLower = strtolower($d['status'] ?? '');
                        $statusClass = match ($statusLower) {
                            'resolvido' => 'resolvido',
                            'em análise' => 'analise',
                            'aguardando confirmação' => 'aguardando',
                            'cancelada' => 'cancelada',
                            default => 'pendente'
                        };
                        $ehStatusFinal = in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true);
                        $icone = $icones[$d['categoria']] ?? 'fa-file-lines';
                    ?>
                    <?php $tomCat = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'][strtolower((string)($d['categoria'] ?? ''))] ?? '#019B38'; ?>
<div class="denuncia denuncia-admin denuncia-card st-<?= $statusClass ?>">
                        <div class="icone-cat" style="--tom:<?= $tomCat ?>">
                            <?php if (!$ehStatusFinal): ?>
                                <label class="selecao-massa" title="Selecionar para ação em massa">
                                    <input type="checkbox" name="ids[]" value="<?= (int) $d['id'] ?>" form="form-massa" aria-label="Selecionar <?= htmlspecialchars(Denuncia::protocolo($d)) ?>">
                                </label>
                            <?php endif; ?>
                            <i class="fa-solid <?= $icone ?>"></i>
                        </div>
                        <div>
                            <h3>
                                <?= htmlspecialchars(ucfirst($d['categoria'])) ?>
                                <span class="protocolo-tag"><?= htmlspecialchars(Denuncia::protocolo($d)) ?></span>
                                <?php if (!empty($d['eh_novo'])): ?>
                                    <span class="badge-novo">Novo</span>
                                <?php endif; ?>
                            </h3>
                            <p><?= htmlspecialchars($d['descricao']) ?></p>
                            <span class="denuncia-usuario">
                                Por <?= htmlspecialchars($d['usuario_nome']) ?>
                                <?php if (!empty($d['criada_em'])): ?>
                                    · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['criada_em']))) ?>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($d['foto'])): ?>
                                <a href="<?= htmlspecialchars($d['foto']) ?>" target="_blank" rel="noopener" title="Ver foto da denúncia" aria-label="Ver foto da denúncia" class="link-acao"><i class="fa-solid fa-image"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
                                <a title="Ver no mapa" aria-label="Ver no mapa" class="link-acao" href="https://www.openstreetmap.org/?mlat=<?= urlencode($d['latitude']) ?>&mlon=<?= urlencode($d['longitude']) ?>#map=17/<?= urlencode($d['latitude']) ?>/<?= urlencode($d['longitude']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i></a>
                            <?php endif; ?>
                            <a title="Gerar relatório PDF" aria-label="Gerar relatório PDF" class="link-acao" href="<?= BASE_URL ?>/?rota=denuncia-relatorio&amp;origem=admin&amp;id=<?= (int)$d['id'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i></a>

                            <div>
                                <span class="badge-prioridade prio-<?= strtolower($d['prioridade']) ?>"><i class="fa-solid fa-flag"></i> <?= htmlspecialchars($d['prioridade']) ?></span>

                                <?php if (!empty($d['anonima'])): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-user-secret"></i> Anônima pros outros moradores</span>
                                <?php endif; ?>

                                <?php if (Denuncia::ehVerificada($d)): ?>
                                    <span class="badge-verificado"><i class="fa-solid fa-shield-halved"></i> Verificado por moradores</span>
                                <?php endif; ?>

                                <?php $totalApoios = count($d['apoios'] ?? []); if ($totalApoios > 0): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-hands-clapping"></i> <?= $totalApoios ?> apoio<?= $totalApoios === 1 ? '' : 's' ?></span>
                                <?php endif; ?>

                                <?php if (!$ehStatusFinal): $prazoInfo = Denuncia::statusPrazo($d); ?>
                                    <span class="badge-prazo <?= $prazoInfo['atrasada'] ? 'atrasada' : '' ?>">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= $prazoInfo['dias'] ?> dia<?= $prazoInfo['dias'] === 1 ? '' : 's' ?> em aberto
                                        <?= $prazoInfo['atrasada'] ? '(prazo estourado)' : '' ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($d['denuncia_anterior_id'])): ?>
                                    <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $d['denuncia_anterior_id'] ?>" class="badge-reincidencia" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-rotate"></i> Reincidência da #<?= (int) $d['denuncia_anterior_id'] ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php $enc = Denuncia::infoEncaminhamento($d); ?>
                            <?php if ($enc): ?>
                                <div class="orgao-info <?= $enc['vencido'] ? 'vencido' : '' ?>">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span>
                                        <strong><?= htmlspecialchars($enc['orgao']) ?></strong>
                                        <?php if (!$enc['finalizada']): ?>
                                            · prazo até <?= htmlspecialchars(date('d/m/Y', strtotime($enc['prazo_data']))) ?>
                                            <?php if ($enc['vencido']): ?>
                                                <em>(vencido há <?= abs($enc['dias_restantes']) ?> dia<?= abs($enc['dias_restantes']) === 1 ? '' : 's' ?>)</em>
                                            <?php elseif ($enc['dias_restantes'] === 0): ?>
                                                <em>(vence hoje)</em>
                                            <?php else: ?>
                                                <em>(faltam <?= $enc['dias_restantes'] ?> dia<?= $enc['dias_restantes'] === 1 ? '' : 's' ?>)</em>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!$ehStatusFinal): $sugerido = $enc['chave'] ?? Denuncia::orgaoSugerido($d['categoria'] ?? ''); ?>
                                <form method="post" action="<?= BASE_URL ?>/?rota=admin-encaminhar" class="form-encaminhar">
                                    <?= $this->csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                                    <input type="hidden" name="categoria_filtro" value="<?= htmlspecialchars($categoriaFiltro) ?>">
                                    <input type="hidden" name="orgao_filtro" value="<?= htmlspecialchars($orgaoFiltro) ?>">
                                    <label for="orgao-<?= (int)$d['id'] ?>"><?= $enc ? 'Reencaminhar para' : 'Encaminhar para' ?></label>
                                    <select name="orgao" id="orgao-<?= (int)$d['id'] ?>" class="select-status">
                                        <?php foreach (Denuncia::ORGAOS as $chave => $o): ?>
                                            <option value="<?= $chave ?>" <?= $chave === $sugerido ? 'selected' : '' ?>><?= htmlspecialchars($o['nome']) ?> (<?= $o['prazo'] ?> dias)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="prazo-<?= (int)$d['id'] ?>" style="font-size:12px">Prazo previsto (dias)</label>
                                    <input type="number" name="prazo_dias" id="prazo-<?= (int)$d['id'] ?>" min="1" max="180" step="1" placeholder="Padrão do setor" title="Opcional: entre 1 e 180 dias; vazio usa o prazo padrão do setor" style="width:110px;max-width:100%;padding:8px;border-radius:7px;border:1px solid #94a3b8;background:transparent;color:inherit">
                                    <button type="submit" class="btn-encaminhar"><i class="fa-solid fa-share"></i> <?= $enc ? 'Reencaminhar' : 'Encaminhar' ?></button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if ($ehStatusFinal): ?>
                            <span class="<?= $statusClass ?>"><?= htmlspecialchars($d['status']) ?></span>
                        <?php else: ?>
                            <form method="post" action="<?= BASE_URL ?>/?rota=admin-status" class="form-status-admin" enctype="multipart/form-data">
                                <?= $this->csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                                <input type="hidden" name="categoria_filtro" value="<?= htmlspecialchars($categoriaFiltro) ?>">
                                    <input type="hidden" name="orgao_filtro" value="<?= htmlspecialchars($orgaoFiltro) ?>">
                                <select name="status" class="select-status">
                                    <?php foreach ($statusOpcoes as $opcao): ?>
                                        <option value="<?= $opcao ?>" <?= $d['status'] === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                                    <?php endforeach; ?>
                                    <option value="Rejeitada">Rejeitada (motivo obrigatório)</option>
                                </select>
                                <select name="prioridade" class="select-status" title="Prioridade">
                                    <?php foreach (Denuncia::PRIORIDADES as $p): ?>
                                        <option value="<?= $p ?>" <?= ($d['prioridade'] ?? 'Normal') === $p ? 'selected' : '' ?>>Prioridade: <?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea name="mensagem" maxlength="300" placeholder="Mensagem ao cidadão (obrigatória para rejeitar, mín. 10 caracteres)"></textarea>
                                <label class="label-foto-comprovacao">
                                    <i class="fa-solid fa-camera"></i>
                                    <strong>Foto de comprovação</strong>
                                    <small>Opcional. Aparece ao marcar "Aguardando confirmação".</small>
                                    <em class="nome-arquivo-comprovacao">Escolher imagem</em>
                                    <input type="file" name="foto_resolucao" class="input-foto-comprovacao" accept="image/png,image/jpeg,image/webp">
                                </label>
                                <?php if (!empty($d['foto_resolucao'])): ?>
                                    <a href="<?= htmlspecialchars($d['foto_resolucao']) ?>" target="_blank" rel="noopener" class="link-foto-comprovacao-atual">
                                        <i class="fa-solid fa-image"></i> Ver foto de comprovação já enviada
                                    </a>
                                <?php endif; ?>
                                <button type="submit" class="btn-atualizar-status">Atualizar</button>
                            </form>
                        <?php endif; ?>
                        <details class="excluir-denuncia" style="margin-top:10px;font-size:13px">
                            <summary style="cursor:pointer;color:#dc2626"><i class="fa-solid fa-trash-can"></i> Excluir denúncia</summary>
                            <form method="post" action="<?= BASE_URL ?>/?rota=admin-excluir-denuncia" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                                <?= $this->csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                                <input type="hidden" name="categoria_filtro" value="<?= htmlspecialchars($categoriaFiltro) ?>">
                                <input type="hidden" name="orgao_filtro" value="<?= htmlspecialchars($orgaoFiltro) ?>">
                                <label style="display:flex;gap:6px;align-items:center">
                                    <input type="checkbox" name="confirmar" value="1" required>
                                    Confirmo: apagar de vez (use só para teste, spam ou duplicada)
                                </label>
                                <button type="submit" style="background:#dc2626;color:#fff;border:0;border-radius:7px;padding:7px 12px;cursor:pointer"><i class="fa-solid fa-trash-can"></i> Excluir</button>
                            </form>
                        </details>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 42. barra flutuante de ações em massa -->
            <form method="post" action="<?= BASE_URL ?>/?rota=admin-massa" id="form-massa" class="barra-massa" hidden>
                <?= $this->csrfField() ?>
                <input type="hidden" name="volta" value="<?= htmlspecialchars(http_build_query(array_filter(['status' => $filtro !== 'todas' ? $filtro : null, 'categoria' => $categoriaFiltro !== 'todas' ? $categoriaFiltro : null, 'orgao' => $orgaoFiltro !== 'todos' ? $orgaoFiltro : null, 'ordem' => $ordem !== 'recentes' ? $ordem : null, 'busca' => $busca !== '' ? $busca : null, 'pagina' => $pagina > 1 ? $pagina : null]))) ?>">
                <input type="hidden" name="acao" id="massa-acao">
                <input type="hidden" name="valor" id="massa-valor">
                <span class="barra-massa-qtd"><strong id="massa-qtd">0</strong> selecionada(s)</span>
                <label class="barra-massa-todas"><input type="checkbox" id="massa-todas"> todas da página</label>
                <select id="massa-escolha" aria-label="O que fazer com as selecionadas">
                    <option value="">Escolha uma ação…</option>
                    <optgroup label="Mudar status">
                        <option value="status|Em análise">Mover para "Em análise"</option>
                        <option value="status|Aguardando confirmação">Marcar como resolvidas (aguardando o cidadão)</option>
                        <option value="status|Pendente">Voltar para "Pendente"</option>
                    </optgroup>
                    <optgroup label="Encaminhar para">
                        <?php foreach (Denuncia::ORGAOS as $chaveOrg => $org): ?>
                            <option value="orgao|<?= $chaveOrg ?>"><?= htmlspecialchars($org['nome']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Prioridade">
                        <?php foreach (Denuncia::PRIORIDADES as $pr): ?>
                            <option value="prioridade|<?= $pr ?>">Prioridade: <?= $pr ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <button type="submit" class="barra-massa-aplicar"><i class="fa-solid fa-bolt"></i> Aplicar</button>
                <button type="button" class="barra-massa-limpar" id="massa-limpar" aria-label="Limpar seleção"><i class="fa-solid fa-xmark"></i></button>
            </form>

            <?php if ($totalPaginas > 1): ?>
                <div class="paginacao-admin">
                    <?php if ($pagina > 1): ?>
                        <a href="<?= urlPagina($pagina - 1, $filtro, $categoriaFiltro, $busca) ?>" class="pagina-link">
                            <i class="fa-solid fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <span class="pagina-atual">Página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $totalFiltrado ?> denúncias)</span>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="<?= urlPagina($pagina + 1, $filtro, $categoriaFiltro, $busca) ?>" class="pagina-link">
                            Próxima <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>">
        // Mostra o nome da imagem escolhida no cartão de foto de comprovação.
        document.querySelectorAll('.input-foto-comprovacao').forEach(function (input) {
            input.addEventListener('change', function () {
                const nome = input.parentElement.querySelector('.nome-arquivo-comprovacao');
                const tem = input.files && input.files.length;
                if (nome) nome.textContent = tem ? input.files[0].name : 'Escolher imagem';
                input.parentElement.classList.toggle('com-arquivo', !!tem);
            });
        });
    </script>
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
