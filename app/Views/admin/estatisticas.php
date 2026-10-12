<?php
/** @var array $usuario */
/** @var array $est */
$ativo = 'admin-estatisticas';
$rotCat = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
$maxCat = max(1, ...array_values($est['por_categoria'] ?: [0]));
$maxMes = max(1, ...array_values($est['por_mes']));
$maxStatus = max(1, ...array_values($est['por_status'] ?: [0]));
$mesesPt = ['01'=>'jan','02'=>'fev','03'=>'mar','04'=>'abr','05'=>'mai','06'=>'jun','07'=>'jul','08'=>'ago','09'=>'set','10'=>'out','11'=>'nov','12'=>'dez'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin-extra.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/_menu.php'; ?>
    <main class="conteudo dash">
        <header>
            <div>
                <h1>Estatísticas</h1>
                <p>Visão geral do que está acontecendo na cidade</p>
            </div>
            <a class="btn-telao" href="<?= BASE_URL ?>/?rota=admin-telao" target="_blank" rel="noopener"><i class="fa-solid fa-tv"></i> Modo telão</a>
        </header>

        <?php
        // ▲ / ▼ em relação ao mês anterior
        $seta = function (array $c, bool $menosEhMelhor = false): string {
            if ($c['variacao'] === null) {
                return $c['atual'] > 0 ? '<span class="comparacao neutro">' . $c['atual'] . ' este mês</span>' : '<span class="comparacao neutro">sem dados do mês passado</span>';
            }
            $sobe = $c['variacao'] >= 0;
            $bom = $menosEhMelhor ? !$sobe : $sobe;
            return '<span class="comparacao ' . ($c['variacao'] === 0 ? 'neutro' : ($bom ? 'bom' : 'ruim')) . '" title="' . $c['atual'] . ' este mês, ' . $c['anterior'] . ' no mês passado">'
                . ($c['variacao'] === 0 ? '■' : ($sobe ? '▲' : '▼')) . ' ' . abs($c['variacao']) . '% <small>vs mês passado</small></span>';
        };
        ?>
        <section class="cards cards-comparacao">
            <div class="card card-stat" style="--tom:#3D8BFD"><span class="icone-tom"><i class="fa-solid fa-inbox"></i></span><div><h3><?= $comparacao['novas']['atual'] ?></h3><p>Novas denúncias este mês</p><?= $seta($comparacao['novas']) ?></div></div>
            <div class="card card-stat" style="--tom:#22C55E"><span class="icone-tom"><i class="fa-solid fa-circle-check"></i></span><div><h3><?= $comparacao['resolvidas']['atual'] ?></h3><p>Resolvidas este mês</p><?= $seta($comparacao['resolvidas']) ?></div></div>
        </section>

        <section class="cards cards-stats">
            <div class="card card-stat" style="--tom:#019B38"><span class="icone-tom"><i class="fa-solid fa-file-circle-check"></i></span><div><h3><?= $est['total'] ?></h3><p>Denúncias no total</p></div></div>
            <div class="card card-stat" style="--tom:#22C55E"><span class="icone-tom"><i class="fa-solid fa-circle-check"></i></span><div><h3><?= $est['taxa_resolucao'] ?>%</h3><p>Taxa de resolução</p></div></div>
            <div class="card card-stat" style="--tom:#3D8BFD"><span class="icone-tom"><i class="fa-solid fa-stopwatch"></i></span><div><h3><?= htmlspecialchars($est['tempo_medio_texto']) ?></h3><p>Tempo médio até resolver</p></div></div>
            <div class="card card-stat" style="--tom:#F5B301"><span class="icone-tom"><i class="fa-solid fa-star"></i></span><div><h3><?= $est['avaliacao_media'] !== null ? number_format($est['avaliacao_media'], 1, ',', '') . '/5' : '—' ?></h3><p>Avaliação média (<?= $est['avaliacoes'] ?>)</p></div></div>
            <div class="card card-stat" style="--tom:#EF4444"><span class="icone-tom"><i class="fa-solid fa-triangle-exclamation"></i></span><div><h3><?= $est['atrasadas'] ?></h3><p>Com prazo estourado</p></div></div>
            <div class="card card-stat" style="--tom:#A78BFA"><span class="icone-tom"><i class="fa-solid fa-rotate"></i></span><div><h3><?= $est['reincidencias'] ?></h3><p>Reincidências</p></div></div>
            <div class="card card-stat" style="--tom:#14B8A6"><span class="icone-tom"><i class="fa-solid fa-users"></i></span><div><h3><?= $est['total_usuarios'] ?></h3><p>Cidadãos cadastrados</p></div></div>
        </section>

        <?php if ($est['total'] > 0): ?>
        <div class="grade-graficos grade-charts">
            <section class="painel-grafico">
                <h3>Situação das denúncias</h3>
                <div class="chart-box"><canvas id="chart-status"></canvas></div>
            </section>
            <section class="painel-grafico">
                <h3>Por categoria</h3>
                <div class="chart-box"><canvas id="chart-categoria"></canvas></div>
            </section>
            <section class="painel-grafico painel-largo-2">
                <h3>Novas denúncias nos últimos 6 meses</h3>
                <div class="chart-box"><canvas id="chart-meses"></canvas></div>
            </section>
            <section class="painel-grafico">
                <h3>Prioridades (sem canceladas)</h3>
                <div class="chart-box"><canvas id="chart-prioridade"></canvas></div>
            </section>
        </div>
        <?php else: ?>
        <section class="painel-grafico largo">
            <h3>Gráficos</h3>
            <p class="vazio-grafico">Os gráficos aparecem assim que existirem denúncias.</p>
        </section>
        <?php endif; ?>

        <section class="painel-grafico largo">
            <h3>Calendário de atividade <small class="sub-calendario">novas denúncias por dia, últimos 6 meses</small></h3>
            <?php
            $maxDia = max(1, max($atividadeDias ?: [0]));
            $diasLista = array_keys($atividadeDias);
            $mesesCal = ['01' => 'jan', '02' => 'fev', '03' => 'mar', '04' => 'abr', '05' => 'mai', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'set', '10' => 'out', '11' => 'nov', '12' => 'dez'];
            $totalPeriodo = array_sum($atividadeDias);
            ?>
            <div class="calendario-wrap">
                <div class="calendario-dias-semana"><span>seg</span><span></span><span>qua</span><span></span><span>sex</span><span></span><span></span></div>
                <div class="calendario" role="img" aria-label="<?= $totalPeriodo ?> denúncias nos últimos 6 meses">
                    <?php $mesAnterior = ''; foreach ($atividadeDias as $dia => $qtd):
                        $nivel = $qtd === 0 ? 0 : (int) ceil($qtd / $maxDia * 4);
                        $mesDia = substr($dia, 5, 2);
                        $marcaMes = (date('N', strtotime($dia)) == 1 && $mesDia !== $mesAnterior) ? $mesesCal[$mesDia] : '';
                        if ($marcaMes) $mesAnterior = $mesDia;
                    ?><span class="dia n<?= $nivel ?>" data-dica="<?= $qtd ?> denúncia<?= $qtd === 1 ? '' : 's' ?> em <?= date('d/m/Y', strtotime($dia)) ?>"<?= $marcaMes ? ' data-mes="' . $marcaMes . '"' : '' ?>></span><?php endforeach; ?>
                </div>
            </div>
            <div class="calendario-legenda"><span>menos</span><i class="dia n0"></i><i class="dia n1"></i><i class="dia n2"></i><i class="dia n3"></i><i class="dia n4"></i><span>mais</span></div>
        </section>

        <section class="painel-grafico largo">
            <h3>Mapa de calor das denúncias</h3>
            <?php if (!$pontosCalor): ?>
                <p class="vazio-grafico">Nenhuma denúncia com localização ainda.</p>
            <?php else: ?>
                <div id="mapa-calor" style="height:380px;border-radius:10px;overflow:hidden"></div>
                <p class="vazio-grafico" style="margin-top:8px">Áreas mais quentes concentram mais denúncias; apoios aumentam o peso.</p>
            <?php endif; ?>
        </section>

        <section class="painel-grafico largo">
            <h3>Mais apoiadas (ainda abertas)</h3>
            <?php if (!$est['mais_apoiadas']): ?>
                <p class="vazio-grafico">Nenhuma denúncia aberta com apoios.</p>
            <?php else: foreach ($est['mais_apoiadas'] as $d): ?>
                <a class="linha-apoiada" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int)$d['id'] ?>" target="_blank" rel="noopener">
                    <strong><?= htmlspecialchars(Denuncia::protocolo($d)) ?></strong>
                    <span><?= htmlspecialchars(mb_strimwidth($d['descricao'], 0, 90, '…')) ?></span>
                    <em><i class="fa-solid fa-hands-clapping"></i> <?= count($d['apoios']) ?></em>
                </a>
            <?php endforeach; endif; ?>
        </section>
    </main>
</div>
<?php if ($est['total'] > 0):
    $statusCores = ['Pendente' => '#F2B705', 'Em análise' => '#3D8BFD', 'Aguardando confirmação' => '#9333EA', 'Resolvido' => '#22C55E', 'Cancelada' => '#6C757D', 'Rejeitada' => '#EF4444'];
    $prioCores = ['Baixa' => '#60A5FA', 'Normal' => '#94A3B8', 'Alta' => '#F59E0B', 'Urgente' => '#EF4444'];
    $catCores = ['iluminacao' => '#F5B301', 'sinalizacao' => '#3D8BFD', 'lixo' => '#14B8A6', 'buracos' => '#EF4444', 'esgoto' => '#06B6D4', 'agua' => '#38BDF8', 'arborizacao' => '#22C55E', 'outros' => '#A78BFA'];
    $dadosCharts = [
        'status' => [
            'labels' => array_keys($est['por_status']),
            'values' => array_values($est['por_status']),
            'cores' => array_map(fn($k) => $statusCores[$k] ?? '#94A3B8', array_keys($est['por_status'])),
        ],
        'categoria' => [
            'labels' => array_values($rotCat),
            'values' => array_map(fn($k) => (int)($est['por_categoria'][$k] ?? 0), array_keys($rotCat)),
            'cores' => array_values($catCores),
        ],
        'meses' => [
            'labels' => array_map(fn($ym) => $mesesPt[substr($ym, 5, 2)] . '/' . substr($ym, 2, 2), array_keys($est['por_mes'])),
            'values' => array_values($est['por_mes']),
        ],
        'prioridade' => [
            'labels' => array_keys($est['por_prioridade']),
            'values' => array_values($est['por_prioridade']),
            'cores' => array_map(fn($k) => $prioCores[$k] ?? '#94A3B8', array_keys($est['por_prioridade'])),
        ],
    ];
?>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/chart/chart.umd.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    window.addEventListener('load', function () {
        if (typeof Chart === 'undefined') return;
        const D = <?= json_encode($dadosCharts, JSON_UNESCAPED_UNICODE) ?>;
        // Canvas não recebe as regras CSS: aplicar paleta acessível também aos gráficos.
        if (document.body.classList.contains('modo-daltonico')) {
            const status = {'Pendente':'#B76A00','Em análise':'#0072B2',
                'Aguardando confirmação':'#974A84','Resolvido':'#007A62',
                'Cancelada':'#626B7A','Rejeitada':'#A34E27'};
            const prioridade = {'Baixa':'#0072B2','Normal':'#626B7A',
                'Alta':'#B76A00','Urgente':'#A34E27'};
            const categorias = ['#0072B2','#B76A00','#007A62','#974A84',
                '#626B7A','#D19900','#56B4E9','#A34E27'];
            D.status.cores = D.status.labels.map(nome => status[nome] || '#626B7A');
            D.prioridade.cores = D.prioridade.labels.map(nome => prioridade[nome] || '#626B7A');
            D.categoria.cores = D.categoria.labels.map((_, i) => categorias[i % categorias.length]);
        }
        // Lê as cores do tema atual (escuro/claro) depois que o configuracoes.js aplicou as classes.
        const css = getComputedStyle(document.body);
        const texto = (css.getPropertyValue('--text-sub') || '#AEB6C5').trim();
        const grade = document.body.classList.contains('tema-claro') ? 'rgba(0,0,0,.08)' : 'rgba(255,255,255,.08)';
        Chart.defaults.color = texto;
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;

        const rosca = function (id, d) {
            new Chart(document.getElementById(id), {
                type: 'doughnut',
                data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: d.cores, borderWidth: 0, hoverOffset: 6 }] },
                options: { maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom' } } }
            });
        };
        rosca('chart-status', D.status);
        rosca('chart-prioridade', D.prioridade);

        new Chart(document.getElementById('chart-categoria'), {
            type: 'bar',
            data: { labels: D.categoria.labels, datasets: [{ data: D.categoria.values, backgroundColor: D.categoria.cores, borderRadius: 8, maxBarThickness: 46 }] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grade } } }
            }
        });

        new Chart(document.getElementById('chart-meses'), {
            type: 'line',
            data: { labels: D.meses.labels, datasets: [{ data: D.meses.values, borderColor: '#019B38', backgroundColor: 'rgba(1,155,56,.18)', fill: true, tension: .35, pointRadius: 4, pointBackgroundColor: '#019B38' }] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grade } } }
            }
        });
    });
</script>
<?php endif; ?>
<?php if ($pontosCalor): ?>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet/leaflet.js"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet.heat/leaflet-heat.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    (function () {
        const pontos = <?= json_encode($pontosCalor) ?>;
        // Guajará-Mirim/RO como centro padrão, igual ao restante do sistema.
        const map = L.map('mapa-calor').setView([-10.7838, -65.3355], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors', maxZoom: 19
        }).addTo(map);
        L.heatLayer(pontos, { radius: 32, blur: 22, maxZoom: 16, minOpacity: 0.35 }).addTo(map);
        map.fitBounds(L.latLngBounds(pontos.map(function (p) { return [p[0], p[1]]; })).pad(0.2));
    })();
</script>
<?php endif; ?>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
