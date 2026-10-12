<?php
/** @var array $denuncia */
/** @var array|null $autor */
/** @var bool $mostrarAutor */
/** @var bool $ehDono */
/** @var bool $ehAdmin */
/** @var bool $jaApoiou */
/** @var bool $podeApoiar */
/** @var bool $verificada */
/** @var array $prazo */
/** @var array|null $anteriorVinculada */

$rotulosCategoria = [
    'iluminacao' => 'Iluminação pública',
    'sinalizacao' => 'Sinalização de trânsito',
    'lixo' => 'Lixo / limpeza urbana',
    'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'
];

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

$statusLower = strtolower($denuncia['status'] ?? '');
$statusClass = match ($statusLower) {
    'resolvido' => 'resolvido',
    'em análise' => 'analise',
    'aguardando confirmação' => 'aguardando',
    'cancelada' => 'cancelada',
    'rejeitada' => 'rejeitada',
    default => 'pendente'
};

$localProtegido = !$ehDono && !$ehAdmin;
$latVisivel = $localProtegido ? round((float)($denuncia['latitude'] ?? 0), 2) : ($denuncia['latitude'] ?? 0);
$lngVisivel = $localProtegido ? round((float)($denuncia['longitude'] ?? 0), 2) : ($denuncia['longitude'] ?? 0);
$totalApoios = count($denuncia['apoios'] ?? []);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denúncia #<?= (int) $denuncia['id'] ?> - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/mapa-publico.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/detalhe.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>

    <header class="mapa-publico-topo">
        <a href="<?= BASE_URL ?>/?rota=<?= $ehAdmin ? 'admin' : ($ehDono ? 'minhas-denuncias' : 'mapa') ?>" class="mapa-publico-logo">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Olho Cidadão">
            <span>Olho <strong>Cidadão</strong></span>
        </a>
        <a href="<?= BASE_URL ?>/?rota=<?= $ehAdmin ? 'admin' : ($ehDono ? 'minhas-denuncias' : 'mapa') ?>" class="mapa-publico-voltar">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </header>

    <main class="detalhe-denuncia-wrap detalhe-v2">
        <?php
        $protocolo = Denuncia::protocolo($denuncia);
        $urlConsulta = BASE_URL . '/?rota=consulta&protocolo=' . urlencode($protocolo);
        $tomCat = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'][$denuncia['categoria']] ?? '#019B38';
        $temLocal = !empty($denuncia['latitude']) && !empty($denuncia['longitude']);
        ?>
        <section class="detalhe-topo">
            <span class="detalhe-cat" style="--tom:<?= $tomCat ?>"><i class="fa-solid <?= $icones[$denuncia['categoria']] ?? 'fa-file-lines' ?>"></i></span>
            <div class="detalhe-titulo">
                <h1><?= htmlspecialchars($rotulosCategoria[$denuncia['categoria']] ?? ucfirst($denuncia['categoria'])) ?></h1>
                <p>
                    <span class="protocolo-tag"><?= htmlspecialchars($protocolo) ?></span>
                    <?= $mostrarAutor && $autor ? 'por ' . htmlspecialchars($autor['nome']) : 'Denúncia anônima' ?>
                    <?php if (!empty($denuncia['criada_em'])): ?> · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($denuncia['criada_em']))) ?><?php endif; ?>
                </p>
            </div>
            <span class="<?= $statusClass ?> detalhe-status"><?= htmlspecialchars($denuncia['status']) ?></span>
        </section>

        <!-- 38. ações em botões redondos só com ícone -->
        <div class="acoes-redondas" role="toolbar" aria-label="Ações da denúncia">
            <?php if ($podeApoiar): ?>
                <form action="<?= BASE_URL ?>/?rota=denuncia-apoiar" method="POST" id="apoiar">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $denuncia['id'] ?>">
                    <button type="submit" class="acao-redonda principal btn-apoiar-denuncia" title="Eu também sofro com isso (apoiar)"><i class="fa-solid fa-hands-clapping"></i></button>
                    <span>Apoiar</span>
                </form>
            <?php elseif ($jaApoiou): ?>
                <div id="apoiar"><button type="button" class="acao-redonda feito" disabled title="Você já apoiou"><i class="fa-solid fa-check"></i></button><span>Apoiada</span></div>
            <?php endif; ?>
            <?php if ($temLocal): ?>
                <div><a class="acao-redonda" title="Ver no mapa" href="https://www.openstreetmap.org/?mlat=<?= urlencode($latVisivel) ?>&mlon=<?= urlencode($lngVisivel) ?>#map=18/<?= urlencode($latVisivel) ?>/<?= urlencode($lngVisivel) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i></a><span>Mapa</span></div>
            <?php endif; ?>
            <div><button type="button" class="acao-redonda" id="btn-compartilhar" data-url="<?= htmlspecialchars($urlConsulta) ?>" data-titulo="Denúncia <?= htmlspecialchars($protocolo) ?> - Olho Cidadão" title="Compartilhar"><i class="fa-solid fa-share-nodes"></i></button><span>Compartilhar</span></div>
            <div><button type="button" class="acao-redonda" id="btn-copiar-protocolo" data-protocolo="<?= htmlspecialchars($protocolo) ?>" title="Copiar protocolo"><i class="fa-regular fa-copy"></i></button><span>Protocolo</span></div>
            <?php if ($ehDono && $statusLower === 'pendente'): ?>
                <div><a class="acao-redonda" title="Editar" href="<?= BASE_URL ?>/?rota=denuncia-editar&id=<?= (int) $denuncia['id'] ?>"><i class="fa-solid fa-pen"></i></a><span>Editar</span></div>
            <?php endif; ?>
            <?php if ($ehAdmin): ?>
                <div><a class="acao-redonda" title="Relatório em PDF" href="<?= BASE_URL ?>/?rota=denuncia-relatorio&amp;origem=denuncia-ver&amp;id=<?= (int) $denuncia['id'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i></a><span>Relatório</span></div>
            <?php endif; ?>
        </div>

        <div class="detalhe-grade">
            <div class="detalhe-principal">
                <section class="detalhe-bloco">
                    <h2>O que aconteceu</h2>
                    <p class="detalhe-desc"><?= nl2br(htmlspecialchars($localProtegido ? 'Descrição completa visível somente para o autor e a administração.' : $denuncia['descricao'])) ?></p>
                    <?php if (!empty($denuncia['endereco'])): ?>
                        <p class="detalhe-local"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($localProtegido ? 'Região aproximada (endereço protegido)' : $denuncia['endereco']) ?></p>
                    <?php endif; ?>
                    <div class="detalhe-selos">
                        <?php if ($verificada): ?><span class="badge-verificado"><i class="fa-solid fa-shield-halved"></i> Verificado por moradores</span><?php endif; ?>
                        <span class="apoios-contador"><i class="fa-solid fa-hands-clapping"></i> <?= $totalApoios ?> apoio<?= $totalApoios === 1 ? '' : 's' ?></span>
                        <?php if (!in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true)): ?>
                            <span class="badge-prazo <?= $prazo['atrasada'] ? 'atrasada' : '' ?>"><i class="fa-solid fa-clock"></i> <?= $prazo['dias'] ?> dia<?= $prazo['dias'] === 1 ? '' : 's' ?> em aberto <?= $prazo['atrasada'] ? '(prazo estourado)' : '(prazo: ' . $prazo['prazo'] . ' dias)' ?></span>
                        <?php endif; ?>
                        <?php if ($anteriorVinculada): ?>
                            <a href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $anteriorVinculada['id'] ?>" class="badge-reincidencia"><i class="fa-solid fa-rotate"></i> Reincidência da #<?= (int) $anteriorVinculada['id'] ?></a>
                        <?php endif; ?>
                        <?php if (!empty($denuncia['avaliacao'])): ?>
                            <span class="apoios-contador"><i class="fa-solid fa-star" style="color:#f2b705"></i> Avaliação: <?= (int) $denuncia['avaliacao'] ?>/5</span>
                        <?php endif; ?>
                    </div>
                    <?php require __DIR__ . '/_orgao.php'; ?>
                    <?php if ($ehDono): ?>
                        <p class="detalhe-nota"><i class="fa-solid fa-circle-info"></i> Essa é a sua denúncia — acompanhe também em "Minhas denúncias".</p>
                    <?php elseif (!$podeApoiar && !$jaApoiou): ?>
                        <p class="detalhe-nota"><i class="fa-solid fa-circle-info"></i> Essa denúncia está encerrada — não é mais possível apoiar.</p>
                    <?php endif; ?>
                </section>

                <!-- 37. foto de antes e de depois lado a lado -->
                <?php if (($ehDono || $ehAdmin) && (!empty($denuncia['foto']) || !empty($denuncia['foto_resolucao']))): ?>
                <section class="detalhe-bloco">
                    <h2>Antes e depois</h2>
                    <div class="antes-depois">
                        <figure>
                            <?php if (!empty($denuncia['foto'])): ?>
                                <a href="<?= htmlspecialchars($denuncia['foto']) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($denuncia['foto']) ?>" alt="Foto enviada pelo cidadão" loading="lazy"></a>
                            <?php else: ?>
                                <div class="foto-vazia" style="--tom:<?= $tomCat ?>"><i class="fa-solid <?= $icones[$denuncia['categoria']] ?? 'fa-image' ?>"></i><small>Sem foto do problema</small></div>
                            <?php endif; ?>
                            <figcaption><span class="rotulo-antes">Antes</span> foto do cidadão</figcaption>
                        </figure>
                        <figure>
                            <?php if (!empty($denuncia['foto_resolucao']) && in_array($statusLower, ['aguardando confirmação', 'resolvido'], true)): ?>
                                <a href="<?= htmlspecialchars($denuncia['foto_resolucao']) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($denuncia['foto_resolucao']) ?>" alt="Foto da solução enviada pela prefeitura" loading="lazy"></a>
                            <?php else: ?>
                                <div class="foto-vazia aguardando-foto"><i class="fa-solid fa-hourglass-half"></i><small>A foto da solução aparece aqui quando a prefeitura resolver</small></div>
                            <?php endif; ?>
                            <figcaption><span class="rotulo-depois">Depois</span> comprovação da prefeitura</figcaption>
                        </figure>
                    </div>
                </section>
                <?php endif; ?>
                <?php if (($ehDono || $ehAdmin) && count($denuncia['fotos'] ?? []) > 1): ?>
                <section class="detalhe-bloco"><h2>Outras fotos anexadas</h2><div class="antes-depois">
                  <?php foreach(array_slice($denuncia['fotos'], 1) as $f): ?>
                    <figure><a href="<?= htmlspecialchars($f) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= htmlspecialchars($f) ?>" alt="Foto complementar da denúncia"></a></figure>
                  <?php endforeach; ?>
                </div></section>
                <?php endif; ?>
            </div>

            <!-- 36. linha do tempo das etapas, com datas -->
            <aside class="detalhe-bloco detalhe-linha">
                <h2>Linha do tempo</h2>
                <?php
                $visual = [
                    'Pendente' => ['fa-paper-plane', '#F5B301', 'Denúncia recebida'],
                    'Em análise' => ['fa-magnifying-glass', '#3D8BFD', 'Em análise pela prefeitura'],
                    'Aguardando confirmação' => ['fa-hourglass-half', '#A78BFA', 'Prefeitura marcou como resolvida'],
                    'Resolvido' => ['fa-circle-check', '#22C55E', 'Solução confirmada pelo cidadão'],
                    'Cancelada' => ['fa-ban', '#6C757D', 'Denúncia cancelada'],
                    'Rejeitada' => ['fa-circle-xmark', '#EF4444', 'Denúncia rejeitada com justificativa'],
                ];
                $hist = $denuncia['historico'] ?? [];
                ?>
                <ol class="linha-etapas">
                    <?php foreach ($hist as $i => $h):
                        $stH = $h['status'] ?? 'Pendente';
                        [$icH, $corH, $tituloH] = $visual[$stH] ?? ['fa-circle', '#019B38', $stH];
                        if (!empty($h['orgao'])) { $icH = 'fa-building-columns'; $tituloH = 'Encaminhada ao órgão responsável'; }
                        elseif (($h['tipo'] ?? '') === 'recurso_rejeicao') { $icH = 'fa-scale-balanced'; $tituloH = 'Revisão solicitada pelo cidadão'; }
                        elseif (($h['tipo'] ?? '') === 'prioridade') { $icH = 'fa-flag'; $tituloH = 'Prioridade atualizada'; }
                        elseif (!empty($h['mensagem']) && str_contains($h['mensagem'], 'editada')) { $icH = 'fa-pen'; $tituloH = 'Descrição corrigida'; }
                        elseif (!empty($h['mensagem']) && str_starts_with($h['mensagem'], 'Reaberta')) { $icH = 'fa-rotate-left'; $tituloH = 'Reaberta pelo cidadão'; }
                        $ultimo = $i === count($hist) - 1;
                    ?>
                    <li class="<?= $ultimo ? 'atual' : '' ?>" style="--tom:<?= $corH ?>">
                        <span class="linha-icone"><i class="fa-solid <?= $icH ?>"></i></span>
                        <div>
                            <strong><?= htmlspecialchars($tituloH) ?></strong>
                            <time datetime="<?= htmlspecialchars($h['data'] ?? '') ?>"><?= htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($h['data'] ?? 'now'))) ?></time>
                            <?php if (!empty($h['mensagem']) && empty($h['orgao']) && !str_contains($h['mensagem'], 'editada')): ?>
                                <?php if (!$localProtegido): ?>
                                    <p>“<?= htmlspecialchars($h['mensagem']) ?>”</p>
                                <?php endif; ?>
                            <?php elseif (!empty($h['orgao'])): ?>
                                <p><?= htmlspecialchars($localProtegido ? 'Encaminhamento registrado (detalhes reservados).' : ($h['mensagem'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php if (!in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true)): ?>
                    <li class="futuro" style="--tom:#22C55E">
                        <span class="linha-icone"><i class="fa-solid fa-flag-checkered"></i></span>
                        <div><strong>Resolvida</strong><time>quando o problema for solucionado</time></div>
                    </li>
                    <?php endif; ?>
                </ol>
            </aside>
        </div>
      <?php if ($ehDono && !in_array($statusLower, ['resolvido', 'cancelada', 'rejeitada'], true)): ?>
      <section class="detalhe-bloco" style="max-width:1100px;margin:24px auto;padding:20px">
        <h2>Complementar esta denúncia</h2>
        <p>Adicione informações novas sem apagar o relato anterior (até 10 complementos).</p>
        <form action="<?= BASE_URL ?>/?rota=denuncia-complementar" method="post">
          <?= $this->csrfField() ?>
          <input type="hidden" name="id" value="<?= (int)$denuncia['id'] ?>">
          <textarea name="complemento" minlength="10" maxlength="1000" rows="4" required placeholder="Descreva as novas informações..." style="display:block;width:100%;margin:12px 0;padding:12px;border-radius:8px"></textarea>
          <button type="submit" style="padding:10px 18px;border-radius:8px;cursor:pointer">Adicionar ao histórico</button>
        </form>
      </section>
      <?php endif; ?>
      <?php if ($ehDono && $statusLower === 'rejeitada'): ?>
      <section class="detalhe-bloco" style="max-width:1100px;margin:24px auto;padding:20px">
        <h2><i class="fa-solid fa-scale-balanced"></i> Solicitar revisão da rejeição</h2>
        <?php if (!empty($denuncia['motivo_rejeicao'])): ?>
          <p><strong>Motivo informado na rejeição:</strong> <?= nl2br(htmlspecialchars($denuncia['motivo_rejeicao'])) ?></p>
        <?php endif; ?>
        <p>Explique por que a denúncia deve ser reavaliada. Seu pedido será registrado no histórico e enviado à administração.</p>
        <form action="<?= BASE_URL ?>/?rota=denuncia-recorrer" method="post">
          <?= $this->csrfField() ?>
          <input type="hidden" name="id" value="<?= (int) $denuncia['id'] ?>">
          <label for="justificativa-recurso">Justificativa do recurso</label>
          <textarea id="justificativa-recurso" name="justificativa" minlength="20" maxlength="1000" rows="4" required placeholder="Explique quais informações justificam uma nova análise..." style="display:block;width:100%;margin:12px 0;padding:12px;border-radius:8px"></textarea>
          <button type="submit" style="padding:10px 18px;border-radius:8px;cursor:pointer"><i class="fa-solid fa-rotate-left"></i> Enviar pedido de revisão</button>
        </form>
      </section>
      <?php endif; ?>
    </main>

    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/detalhe.js?v=0.52.9"></script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
<?php if (!empty($sucesso)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($sucesso) ?>, 'sucesso'));</script>
<?php endif; ?>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
