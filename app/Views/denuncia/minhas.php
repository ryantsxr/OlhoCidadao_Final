<?php
/** @var array $usuario */
/** @var array $denuncias */
/** @var array $stats */
/** @var string $filtro */

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

$rotulosVazio = [
    'todas' => ['fa-eye', 'O olho ainda não viu nada seu por aqui', 'Viu um problema na rua? Registre em poucos passos e acompanhe tudo aqui.'],
    'pendente' => ['fa-mug-hot', 'Nenhuma denúncia pendente', 'Tudo que você enviou já está com a prefeitura.'],
    'analise' => ['fa-magnifying-glass', 'Nada em análise agora', 'Quando a prefeitura começar a cuidar de uma denúncia sua, ela aparece aqui.'],
    'aguardando' => ['fa-thumbs-up', 'Nada esperando a sua confirmação', 'Quando a prefeitura disser que resolveu, você confirma por aqui.'],
    'resolvido' => ['fa-seedling', 'Nenhuma resolvida ainda', 'Assim que um problema seu for resolvido, ele vem para cá. Já já!'],
    'rejeitada' => ['fa-ban', 'Nenhuma denúncia rejeitada', 'Se alguma for rejeitada, a justificativa aparecerá nesta área.']
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Denúncias - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/painel.css?v=0.52.9">
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
                    <a href="<?= BASE_URL ?>/?rota=dashboard">
                        <i class="fa-solid fa-house"></i>
                        <span data-i18n="inicio">Início</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=minhas-denuncias" class="ativo">
                        <i class="fa-solid fa-file-lines"></i>
                        <span data-i18n="minhas-denuncias">Minhas denúncias</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Mapa público</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=perfil">
                        <i class="fa-solid fa-user"></i>
                        <span data-i18n="perfil">Perfil</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=configuracoes">
                        <i class="fa-solid fa-gear"></i>
                        <span data-i18n="config">Configurações</span>
                    </a>
                </nav>
            </div>

            <div class="menu-bottom">
                <div class="usuario-card">
                    <div class="usuario-info">
                        <img src="<?= htmlspecialchars($usuario["foto"] ?: BASE_URL . "/img/avatar-padrao.svg") ?>" alt="Foto do usuário" class="avatar" id="avatarUsuario">
                        <div class="usuario-texto">
                            <strong id="nomeUsuarioMenu"><?= htmlspecialchars(explode(" ", trim($usuario["nome"]))[0]) ?></strong>
                            <small data-i18n="cidadao">Cidadão</small>
                        </div>
                    </div>

                    <div class="usuario-acoes">
                        <a href="<?= BASE_URL ?>/?rota=perfil" title="Perfil" data-i18n-title="perfil">
                            <i class="fa-solid fa-user"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/?rota=configuracoes" title="Configurações" data-i18n-title="config">
                            <i class="fa-solid fa-gear"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/?rota=logout&t=<?= $this->csrfToken() ?>" title="Sair" data-i18n-title="sair">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <main class="conteudo">

            <header>
                <div>
                    <h1>Minhas denúncias</h1>
                    <p>Acompanhe tudo o que você já relatou</p>
                </div>

                <button type="button" class="denunciar" data-href="<?= BASE_URL ?>/?rota=denuncia-passo1">
                    <i class="fa-solid fa-plus"></i>
                    <span data-i18n="fazer-denuncia">Fazer denúncia</span>
                </button>
            </header>

            <div class="filtros-denuncias">
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=todas" class="filtro-chip <?= $filtro === 'todas' ? 'ativo' : '' ?>">
                    Todas <span><?= $stats['total'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=pendente" class="filtro-chip <?= $filtro === 'pendente' ? 'ativo' : '' ?>">
                    Pendentes <span><?= $stats['pendentes'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=analise" class="filtro-chip <?= $filtro === 'analise' ? 'ativo' : '' ?>">
                    Em análise <span><?= $stats['analise'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=aguardando" class="filtro-chip <?= $filtro === 'aguardando' ? 'ativo' : '' ?>">
                    Aguardando confirmação <span><?= $stats['aguardando'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=resolvido" class="filtro-chip <?= $filtro === 'resolvido' ? 'ativo' : '' ?>">
                    Resolvidas <span><?= $stats['resolvidas'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=rejeitada" class="filtro-chip <?= $filtro === 'rejeitada' ? 'ativo' : '' ?>">
                    Rejeitadas <span><?= $stats['rejeitadas'] ?></span>
                </a>
            </div>

            <div class="lista">
                <?php if (empty($denuncias)): ?>
                    <?php [$icVazio, $tituloVazio, $textoVazio] = $rotulosVazio[$filtro]; ?>
                    <div class="estado-vazio estado-vazio-v2">
                        <span class="vazio-ilustra"><i class="fa-solid <?= $icVazio ?>"></i></span>
                        <h3><?= htmlspecialchars($tituloVazio) ?></h3>
                        <p><?= htmlspecialchars($textoVazio) ?></p>
                        <?php if ($filtro === 'todas'): ?>
                            <a class="botao-vazio" href="<?= BASE_URL ?>/?rota=denuncia-passo1"><i class="fa-solid fa-plus"></i> Fazer minha primeira denúncia</a>
                        <?php else: ?>
                            <a class="botao-vazio secundario" href="<?= BASE_URL ?>/?rota=minhas-denuncias"><i class="fa-solid fa-list"></i> Ver todas as minhas denúncias</a>
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
                            'rejeitada' => 'rejeitada',
                            default => 'pendente'
                        };
                        $icone = $icones[$d['categoria']] ?? 'fa-file-lines';

                        // Pega a mensagem do admin (se houver) da última entrada do histórico
                        // que corresponde ao status atual da denúncia.
                        $mensagemAdmin = '';
                        foreach (array_reverse($d['historico'] ?? []) as $h) {
                            if (($h['status'] ?? '') === $d['status'] && !empty($h['mensagem']) && empty($h['orgao'])) {
                                $mensagemAdmin = $h['mensagem'];
                                break;
                            }
                        }
                    ?>
                    <?php $tomCat = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'][strtolower((string)($d['categoria'] ?? ''))] ?? '#019B38'; ?>
<div class="denuncia denuncia-card st-<?= $statusClass ?>">
                        <div class="icone-cat" style="--tom:<?= $tomCat ?>"><i class="fa-solid <?= $icone ?>"></i></div>
                        <div>
                            <h3><?= htmlspecialchars(ucfirst($d['categoria'])) ?> <span class="protocolo-tag"><?= htmlspecialchars(Denuncia::protocolo($d)) ?></span></h3>
                            <p><?= htmlspecialchars($d['descricao']) ?></p>
                            <?php require __DIR__ . '/_etapas.php'; ?>
                            <?php $enc = Denuncia::infoEncaminhamento($d); if ($enc): ?>
                                <div class="orgao-info <?= $enc['vencido'] ? 'vencido' : '' ?>">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span>
                                        Encaminhada para <strong><?= htmlspecialchars($enc['orgao']) ?></strong>
                                        <?php if (!$enc['finalizada']): ?>
                                            · prazo de <?= (int) $enc['prazo_dias'] ?> dias (até <?= htmlspecialchars(date('d/m/Y', strtotime($enc['prazo_data']))) ?>)
                                            <?php if ($enc['vencido']): ?><em>— vencido</em><?php endif; ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if ($mensagemAdmin !== ''): ?>
                                <p class="obs-admin">
                                    <i class="fa-solid fa-comment-dots"></i>
                                    <strong>Observação da prefeitura:</strong> <?= htmlspecialchars($mensagemAdmin) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($d['foto'])): ?>
                                <a href="<?= htmlspecialchars($d['foto']) ?>" target="_blank" rel="noopener" title="Ver foto da denúncia" aria-label="Ver foto da denúncia" class="link-acao"><i class="fa-solid fa-image"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($d['foto_resolucao']) && in_array($statusLower, ['aguardando confirmação', 'resolvido'], true)): ?>
                                <a href="<?= htmlspecialchars($d['foto_resolucao']) ?>" target="_blank" rel="noopener" title="Foto de comprovação enviada pela prefeitura" aria-label="Foto de comprovação enviada pela prefeitura" class="link-acao link-acao-ok"><i class="fa-solid fa-camera"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
                                <a title="Ver no mapa" aria-label="Ver no mapa" class="link-acao" href="https://www.openstreetmap.org/?mlat=<?= urlencode($d['latitude']) ?>&mlon=<?= urlencode($d['longitude']) ?>#map=17/<?= urlencode($d['latitude']) ?>/<?= urlencode($d['longitude']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($d['criada_em'])): ?>
                                <span class="denuncia-data"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['criada_em']))) ?></span>
                            <?php endif; ?>

                            <div>
                                <?php if (Denuncia::ehVerificada($d)): ?>
                                    <span class="badge-verificado"><i class="fa-solid fa-shield-halved"></i> Verificado por moradores</span>
                                <?php endif; ?>

                                <?php $totalApoios = count($d['apoios'] ?? []); if ($totalApoios > 0): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-hands-clapping"></i> <?= $totalApoios ?> apoio<?= $totalApoios === 1 ? '' : 's' ?></span>
                                <?php endif; ?>

                                <?php if (!in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true)): ?>
                                    <?php $prazoInfo = Denuncia::statusPrazo($d); ?>
                                    <span class="badge-prazo <?= $prazoInfo['atrasada'] ? 'atrasada' : '' ?>">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= $prazoInfo['dias'] ?> dia<?= $prazoInfo['dias'] === 1 ? '' : 's' ?> em aberto
                                        <?= $prazoInfo['atrasada'] ? '(prazo estourado)' : '' ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($d['denuncia_anterior_id'])): ?>
                                    <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $d['denuncia_anterior_id'] ?>" class="badge-reincidencia">
                                        <i class="fa-solid fa-rotate"></i> Reincidência
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($d['avaliacao'])): ?>
                                    <span class="apoios-contador"><i class="fa-solid fa-star" style="color:#f2b705"></i> Sua avaliação: <?= (int) $d['avaliacao'] ?>/5</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($statusLower === 'pendente'): ?>
                                <a href="<?= BASE_URL ?>/?rota=denuncia-editar&id=<?= (int)$d['id'] ?>" class="btn-editar-denuncia">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                                <form action="<?= BASE_URL ?>/?rota=denuncia-cancelar" method="POST"
                                      data-confirmar="Tem certeza que deseja cancelar esta denúncia?">
                                    <?= $this->csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="btn-cancelar-denuncia">
                                        <i class="fa-solid fa-ban"></i> Cancelar denúncia
                                    </button>
                                </form>
                            <?php elseif ($statusLower === 'aguardando confirmação'): ?>
                                <div class="acoes-confirmacao">
                                    <form action="<?= BASE_URL ?>/?rota=denuncia-confirmar" method="POST"
                                          data-confirmar="Confirmar que o problema foi realmente resolvido?">
                                        <?= $this->csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                        <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Avalie a resolução (opcional):</label>
                                        <div class="avaliacao-estrelas">
                                            <input type="radio" name="avaliacao" id="av5-<?= (int)$d['id'] ?>" value="5"><label for="av5-<?= (int)$d['id'] ?>"><i class="fa-solid fa-star"></i></label>
                                            <input type="radio" name="avaliacao" id="av4-<?= (int)$d['id'] ?>" value="4"><label for="av4-<?= (int)$d['id'] ?>"><i class="fa-solid fa-star"></i></label>
                                            <input type="radio" name="avaliacao" id="av3-<?= (int)$d['id'] ?>" value="3"><label for="av3-<?= (int)$d['id'] ?>"><i class="fa-solid fa-star"></i></label>
                                            <input type="radio" name="avaliacao" id="av2-<?= (int)$d['id'] ?>" value="2"><label for="av2-<?= (int)$d['id'] ?>"><i class="fa-solid fa-star"></i></label>
                                            <input type="radio" name="avaliacao" id="av1-<?= (int)$d['id'] ?>" value="1"><label for="av1-<?= (int)$d['id'] ?>"><i class="fa-solid fa-star"></i></label>
                                        </div>
                                        <button type="submit" class="btn-confirmar-resolucao">
                                            <i class="fa-solid fa-check"></i> Confirmar resolução
                                        </button>
                                    </form>
                                    <form action="<?= BASE_URL ?>/?rota=denuncia-reabrir" method="POST"
                                          data-confirmar="Reabrir esta denúncia? Ela volta para 'Em análise'.">
                                        <?= $this->csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                        <textarea name="motivo" maxlength="300" placeholder="O que ainda não foi resolvido? (opcional)"></textarea>
                                        <button type="submit" class="btn-reabrir-denuncia">
                                            <i class="fa-solid fa-rotate-left"></i> Não foi resolvido, reabrir
                                        </button>
                                    </form>
                                </div>
                            <?php elseif ($statusLower === 'rejeitada'): ?>
                                <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $d['id'] ?>" class="btn-reabrir-denuncia" style="display:inline-block;text-decoration:none;margin-top:10px">
                                    <i class="fa-solid fa-scale-balanced"></i> Ver motivo e solicitar revisão
                                </a>
                            <?php endif; ?>
                        </div>
                        <span class="<?= $statusClass ?>">
                            <?= htmlspecialchars($d['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($totalPaginas > 1): ?>
              <nav aria-label="Paginação das denúncias" style="display:flex;justify-content:center;gap:12px;margin:22px 0;align-items:center;">
                <?php if ($pagina > 1): ?><a class="filtro-chip" href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=<?= urlencode($filtro) ?>&pagina=<?= $pagina - 1 ?>">← Anterior</a><?php endif; ?>
                <span>Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                <?php if ($pagina < $totalPaginas): ?><a class="filtro-chip" href="<?= BASE_URL ?>/?rota=minhas-denuncias&status=<?= urlencode($filtro) ?>&pagina=<?= $pagina + 1 ?>">Próxima →</a><?php endif; ?>
              </nav>
            <?php endif; ?>
        </main>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
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
