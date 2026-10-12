<?php
/** @var array $usuario */
/** @var array $denuncias */
/** @var array $stats */
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/painel.css?v=0.52.9">
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
                    <a href="<?= BASE_URL ?>/?rota=dashboard" class="ativo">
                        <i class="fa-solid fa-house"></i>
                        <span id="texto-inicio" data-i18n="inicio">Início</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=minhas-denuncias">
                        <i class="fa-solid fa-file-lines"></i>
                        <span id="minhas-denuncias" data-i18n="minhas-denuncias">Minhas denúncias</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=mapa" target="_blank" rel="noopener">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Mapa público</span>
                    </a>
                    <a href="<?= BASE_URL ?>/?rota=vitorias">
                        <i class="fa-solid fa-trophy"></i>
                        <span>Mural de vitórias</span>
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

        <main class="conteudo dash">

            <header>
                <div>
                    <h1 class="saudacao"><span class="saudacao-icone" id="saudacao-icone" aria-hidden="true"><i class="fa-solid fa-sun"></i></span><span class="saudacao-texto"><span id="saudacao">Olá</span>, <span id="nomeUsuario"><?= htmlspecialchars(explode(" ", trim($usuario["nome"]))[0]) ?></span></span></h1>
                    <p id="saudacao-sub">Bem-vindo ao Olho Cidadão</p>
                </div>

                <div class="header-acoes">
                <button type="button" class="denunciar" data-href="<?= BASE_URL ?>/?rota=denuncia-passo1">
                    <i class="fa-solid fa-plus"></i>
                    <span data-i18n="fazer-denuncia">Fazer denúncia</span>
                </button>

                <button type="button" class="btn-notificacoes" id="btn-tour" aria-label="Fazer o tour do painel" title="Tour do painel"><i class="fa-solid fa-circle-question"></i></button>
                <div class="notificacoes-wrapper">
                    <button type="button" id="btn-notificacoes" class="btn-notificacoes" aria-label="Notificações">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($naoLidas > 0): ?>
                            <span class="badge-notificacao"><?= $naoLidas > 9 ? '9+' : $naoLidas ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="painel-notificacoes oculto" id="painel-notificacoes">
                        <div class="painel-notificacoes-topo">
                            <strong>Notificações</strong>
                            <button type="button" class="painel-notificacoes-fechar" id="fechar-notificacoes" aria-label="Fechar notificações"><i class="fa-solid fa-xmark"></i></button>
                            <?php if ($naoLidas > 0): ?>
                                <form method="post" action="<?= BASE_URL ?>/?rota=notificacoes-marcar-lidas">
                                    <?= $this->csrfField() ?>
                                    <button type="submit">Marcar como lidas</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="painel-notificacoes-lista">
                            <?php if (empty($notificacoes)): ?>
                                <div class="notificacao-vazia"><span><i class="fa-regular fa-bell-slash"></i></span><strong>Tudo tranquilo por aqui</strong><p>Quando a prefeitura responder ou alguém apoiar sua denúncia, o aviso aparece aqui.</p></div>
                            <?php else: ?>
                                <?php
                                $grupos = [];
                                $hoje = date('Y-m-d'); $ontem = date('Y-m-d', strtotime('-1 day')); $semana = date('Y-m-d', strtotime('-7 days'));
                                foreach (array_slice($notificacoes, 0, 20) as $n) {
                                    $dia = substr($n['criada_em'] ?? '', 0, 10);
                                    $g = $dia === $hoje ? 'Hoje' : ($dia === $ontem ? 'Ontem' : ($dia >= $semana ? 'Esta semana' : 'Antes'));
                                    $grupos[$g][] = $n;
                                }
                                foreach ($grupos as $nomeGrupo => $itensGrupo): ?>
                                    <h4 class="notificacao-grupo"><?= $nomeGrupo ?></h4>
                                    <?php foreach ($itensGrupo as $n):
                                        $msg = $n['mensagem'] ?? '';
                                        $ic = str_contains($msg, 'apoi') ? ['fa-hands-clapping', '#F5B301'] : (str_contains($msg, 'resolvid') ? ['fa-circle-check', '#22C55E'] : (str_contains($msg, 'ncaminhad') ? ['fa-building-columns', '#3D8BFD'] : ['fa-bell', '#A78BFA']));
                                    ?>
                                    <a class="notificacao-item <?= empty($n['lida']) ? 'nao-lida' : '' ?>" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) ($n['denuncia_id'] ?? 0) ?>">
                                        <span class="notificacao-icone" style="--tom:<?= $ic[1] ?>"><i class="fa-solid <?= $ic[0] ?>"></i></span>
                                        <span class="notificacao-texto">
                                            <p><?= htmlspecialchars($msg) ?></p>
                                            <span><?= htmlspecialchars(date(in_array($nomeGrupo, ['Hoje', 'Ontem'], true) ? 'H:i' : 'd/m H:i', strtotime($n['criada_em']))) ?></span>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
            </header>

            <section class="cards">
                <div class="card card-stat" style="--tom:#019B38">
                    <span class="icone-tom"><i class="fa-solid fa-file-circle-check"></i></span>
                    <div>
                        <h3><?= $stats["total"] ?></h3>
                        <p data-i18n="denuncias-feitas">Denúncias feitas</p>
                    </div>
                    <svg class="sparkline" viewBox="0 0 100 32" preserveAspectRatio="none" data-serie="<?= htmlspecialchars(json_encode($series['total'])) ?>" aria-hidden="true"></svg>
                </div>

                <div class="card card-stat" style="--tom:#3D8BFD">
                    <span class="icone-tom"><i class="fa-solid fa-clock"></i></span>
                    <div>
                        <h3><?= $stats["analise"] ?></h3>
                        <p data-i18n="em-analise">Em análise</p>
                    </div>
                    <svg class="sparkline" viewBox="0 0 100 32" preserveAspectRatio="none" data-serie="<?= htmlspecialchars(json_encode($series['analise'])) ?>" aria-hidden="true"></svg>
                </div>

                <div class="card card-stat" style="--tom:#22C55E">
                    <span class="icone-tom"><i class="fa-solid fa-circle-check"></i></span>
                    <div>
                        <h3><?= $stats["resolvidas"] ?></h3>
                        <p data-i18n="resolvidas">Resolvidas</p>
                    </div>
                    <svg class="sparkline" viewBox="0 0 100 32" preserveAspectRatio="none" data-serie="<?= htmlspecialchars(json_encode($series['resolvidas'])) ?>" aria-hidden="true"></svg>
                </div>
            </section>

            <section class="painel-duplo">
                <div class="card-impacto" id="card-impacto">
                    <div class="card-impacto-topo">
                        <span class="icone-tom" style="--tom:#F5B301"><i class="fa-solid fa-seedling"></i></span>
                        <div>
                            <h3>Seu impacto na cidade</h3>
                            <p><?= $impacto['resolvidos'] > 0 ? 'Graças a você, ' . $impacto['resolvidos'] . ' problema' . ($impacto['resolvidos'] === 1 ? '' : 's') . ' já ' . ($impacto['resolvidos'] === 1 ? 'foi resolvido' : 'foram resolvidos') . '.' : 'Cada denúncia ajuda a prefeitura a enxergar a cidade.' ?></p>
                        </div>
                    </div>
                    <div class="impacto-numeros">
                        <div><strong data-contar><?= (int) $impacto['resolvidos'] ?></strong><span>problemas resolvidos</span></div>
                        <div><strong data-contar><?= (int) $impacto['apoios_recebidos'] ?></strong><span>apoios recebidos</span></div>
                        <div><strong data-contar><?= (int) $impacto['verificadas'] ?></strong><span>verificadas por vizinhos</span></div>
                        <div><strong data-contar><?= (int) $impacto['apoios_dados'] ?></strong><span>denúncias que você apoiou</span></div>
                    </div>
                </div>
                <div class="card-dica" id="card-dica">
                    <span class="card-dica-rotulo"><i class="fa-solid fa-lightbulb"></i> Dica do dia</span>
                    <p id="dica-texto">Fotos tiradas de dia, mostrando o problema de perto, ajudam a prefeitura a agir mais rápido.</p>
                    <button type="button" id="dica-proxima" class="card-dica-btn">Outra dica <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </section>

            <h2 data-i18n="categorias">Categorias</h2>
            
            <section class="categorias">
                <a class="categoria" style="--tom:#F5B301" href="<?= BASE_URL ?>/?rota=denuncia-passo1&categoria=iluminacao">
                    <span class="icone-tom"><i class="fa-solid fa-lightbulb"></i></span>
                    <p data-i18n="iluminacao">Iluminação</p>
                    <small>Poste apagado, lâmpada queimada</small>
                </a>

                <a class="categoria" style="--tom:#3D8BFD" href="<?= BASE_URL ?>/?rota=denuncia-passo1&categoria=sinalizacao">
                    <span class="icone-tom"><i class="fa-solid fa-traffic-light"></i></span>
                    <p data-i18n="sinalizacao">Sinalização</p>
                    <small>Semáforo, placas, faixas</small>
                </a>

                <a class="categoria" style="--tom:#14B8A6" href="<?= BASE_URL ?>/?rota=denuncia-passo1&categoria=lixo">
                    <span class="icone-tom"><i class="fa-solid fa-trash"></i></span>
                    <p data-i18n="lixo">Lixo</p>
                    <small>Entulho e lixo acumulado</small>
                </a>

                <a class="categoria" style="--tom:#A78BFA" href="<?= BASE_URL ?>/?rota=denuncia-passo1&categoria=outros">
                    <span class="icone-tom"><i class="fa-solid fa-ellipsis"></i></span>
                    <p data-i18n="outros">Outros</p>
                    <small>Algo que não se encaixa</small>
                </a>
            </section>

            <?php
            $recentes = array_reverse($denuncias);
            $totalDenuncias = count($recentes);
            $recentes = array_slice($recentes, 0, 5);
            $visCat = [
                'iluminacao'  => ['fa-lightbulb', '#F5B301'],
                'sinalizacao' => ['fa-traffic-light', '#3D8BFD'],
                'lixo'        => ['fa-trash', '#14B8A6'],
                'outros'      => ['fa-ellipsis', '#A78BFA'],
            ];
            ?>
            <div class="titulo-secao">
                <h2 id="denuncias" data-i18n="denuncias-recentes">Denúncias recentes</h2>
                <?php if ($totalDenuncias > 5): ?>
                    <a class="ver-todas" href="<?= BASE_URL ?>/?rota=minhas-denuncias">Ver todas (<?= $totalDenuncias ?>)</a>
                <?php endif; ?>
            </div>

            <div class="lista">
                <?php if (empty($recentes)): ?>
                    <div class="vazio-dash">
                        <span class="icone-tom" style="--tom:#019B38"><i class="fa-solid fa-eye"></i></span>
                        <h3>Nenhuma denúncia ainda</h3>
                        <p>Viu algo errado na sua rua? Registre em poucos passos e acompanhe aqui.</p>
                        <a class="botao-vazio" href="<?= BASE_URL ?>/?rota=denuncia-passo1"><i class="fa-solid fa-plus"></i> Fazer minha primeira denúncia</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentes as $d): ?>
                    <?php
                        $chave = strtolower((string)($d['categoria'] ?? ''));
                        [$iconeCat, $tomCat] = $visCat[$chave] ?? ['fa-file-lines', '#019B38'];
                        $statusClassRecente = match (strtolower($d['status'])) {
                            'resolvido' => 'resolvido',
                            'em análise' => 'analise',
                            'aguardando confirmação' => 'aguardando',
                            'cancelada' => 'cancelada',
                            default => 'pendente'
                        };
                        $dataTxt = !empty($d['criada_em']) ? date('d/m/Y', strtotime($d['criada_em'])) : '';
                    ?>
                    <a class="item-denuncia" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int)$d['id'] ?>">
                        <span class="icone-tom" style="--tom:<?= $tomCat ?>"><i class="fa-solid <?= $iconeCat ?>"></i></span>
                        <div class="item-corpo">
                            <h3><?= htmlspecialchars(ucfirst($d['categoria'])) ?></h3>
                            <p class="item-desc"><?= htmlspecialchars($d['descricao']) ?></p>
                            <small class="item-meta"><?= htmlspecialchars(Denuncia::protocolo($d)) ?><?= $dataTxt ? ' &middot; ' . $dataTxt : '' ?></small>
                            <?php require __DIR__ . '/../denuncia/_etapas.php'; ?>
                        </div>
                        <?php if (!empty($d['foto'])): ?>
                            <img class="item-foto" src="<?= htmlspecialchars($d['foto']) ?>" alt="Foto da denúncia" loading="lazy" data-remover-se-falhar>
                        <?php endif; ?>
                        <span class="<?= $statusClassRecente ?>"><?= htmlspecialchars($d['status']) ?></span>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <section class="painel-duplo painel-duplo-baixo">
                <div class="card-perto">
                    <div class="card-perto-topo">
                        <div>
                            <h2>Perto de você</h2>
                            <p id="perto-resumo">Denúncias abertas na cidade</p>
                        </div>
                        <button type="button" id="btn-perto" class="btn-perto"><i class="fa-solid fa-location-crosshairs"></i> Usar minha localização</button>
                    </div>
                    <div id="mapa-perto" class="mapa-perto" data-url-ver="<?= htmlspecialchars(BASE_URL . '/?rota=denuncia-ver&id=') ?>" data-pontos="<?= htmlspecialchars(json_encode($pontosPerto)) ?>"></div>
                    <a class="card-perto-link" href="<?= BASE_URL ?>/?rota=mapa">Abrir o mapa completo <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="card-atividade">
                    <h2>Sua atividade</h2>
                    <?php if (!$atividade): ?>
                        <div class="atividade-vazia"><i class="fa-regular fa-calendar"></i><p>Quando você registrar uma denúncia, cada passo dela aparece aqui.</p></div>
                    <?php else: ?>
                    <ol class="atividade">
                        <?php foreach ($atividade as $a):
                            [$icA, $corA] = match ($a['status']) {
                                'Pendente' => ['fa-paper-plane', '#F5B301'],
                                'Em análise' => [!empty($a['mensagem']) && str_contains($a['mensagem'], 'ncaminhad') ? 'fa-building-columns' : 'fa-magnifying-glass', '#3D8BFD'],
                                'Aguardando confirmação' => ['fa-hourglass-half', '#A78BFA'],
                                'Resolvido' => ['fa-circle-check', '#22C55E'],
                                'Cancelada' => ['fa-ban', '#6C757D'],
                                default => ['fa-circle', '#019B38'],
                            };
                            $textoA = match ($a['status']) {
                                'Pendente' => 'Você registrou uma denúncia de ' . ucfirst($a['categoria']),
                                'Em análise' => !empty($a['mensagem']) ? $a['mensagem'] : 'A prefeitura começou a analisar',
                                'Aguardando confirmação' => 'A prefeitura marcou como resolvida',
                                'Resolvido' => 'Você confirmou a solução',
                                'Cancelada' => 'Você cancelou a denúncia',
                                default => $a['status'],
                            };
                        ?>
                        <li style="--tom:<?= $corA ?>">
                            <span class="atividade-icone"><i class="fa-solid <?= $icA ?>"></i></span>
                            <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= $a['id'] ?>">
                                <strong><?= htmlspecialchars($textoA) ?></strong>
                                <small><?= htmlspecialchars($a['protocolo']) ?> · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['data']))) ?></small>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>
                </div>
            </section>

        </main>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet/leaflet.js"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/painel.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>">
        // O painel de notificações agora é uma gaveta lateral (ver js/painel.js).
    </script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>