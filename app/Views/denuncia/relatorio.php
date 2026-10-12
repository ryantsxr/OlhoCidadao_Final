<?php
/** @var array $denuncia */
/** @var array|null $autor */
/** @var array $geradoPor */

$rotulosCategoria = [
    'iluminacao' => 'Iluminação pública',
    'sinalizacao' => 'Sinalização de trânsito',
    'lixo' => 'Lixo / limpeza urbana',
    'buracos' => 'Buracos e vias',
    'esgoto' => 'Esgoto e drenagem',
    'agua' => 'Abastecimento de água',
    'arborizacao' => 'Árvores e poda',
    'outros' => 'Outros'
];

// A página do relatório abre em outra aba. history.back() não tem uma
// página anterior nessa situação, então o retorno usa uma rota real.
// Aceitar apenas destinos internos conhecidos impede redirecionamentos externos.
$origemRelatorio = $_GET['origem'] ?? 'admin';
$rotasRetornoPermitidas = ['admin', 'admin-historico', 'denuncia-ver'];
$rotaVoltar = is_string($origemRelatorio) && in_array($origemRelatorio, $rotasRetornoPermitidas, true)
    ? $origemRelatorio
    : 'admin';
$urlVoltar = BASE_URL . '/?rota=' . $rotaVoltar;
if ($rotaVoltar === 'denuncia-ver') {
    $urlVoltar .= '&id=' . (int)$denuncia['id'];
}

$historico = $denuncia['historico'] ?? [];
$temLocalizacao = !empty($denuncia['latitude']) && !empty($denuncia['longitude']);

if ($temLocalizacao) {
    $lat = (float)$denuncia['latitude'];
    $lng = (float)$denuncia['longitude'];
    $delta = 0.006;
    $bbox = ($lng - $delta) . ',' . ($lat - $delta) . ',' . ($lng + $delta) . ',' . ($lat + $delta);
    $mapaEmbedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox=' . $bbox . '&marker=' . $lat . ',' . $lng;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório da denúncia #<?= (int)$denuncia['id'] ?> - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/relatorio.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>

<body>

    <div class="barra-acoes no-print">
        <a href="<?= htmlspecialchars($urlVoltar, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        <button type="button" data-acao="imprimir"><i class="fa-solid fa-file-pdf"></i> Baixar / Imprimir PDF</button>
    </div>

    <div class="folha">

        <div class="faixa-oficial"><span>Relatório de acompanhamento — Olho Cidadão</span><span>Nº <?= htmlspecialchars(Denuncia::protocolo($denuncia)) ?></span></div>
        <div class="cabecalho">
            <img src="<?= BASE_URL ?>/img/logo_white.png" alt="Logo Olho Cidadão" class="logo-relatorio">
            <div>
                <h1>Relatório de Denúncia</h1>
                <p>Sistema Olho Cidadão</p>
            </div>
            <div class="cabecalho-id">
                <span>Protocolo</span>
                <strong><?= htmlspecialchars(Denuncia::protocolo($denuncia)) ?></strong>
            </div>
            <div class="relatorio-qr" id="relatorio-qr" data-url="<?= htmlspecialchars(BASE_URL . '/?rota=consulta&protocolo=' . urlencode(Denuncia::protocolo($denuncia))) ?>" role="img" aria-label="QR code do protocolo"></div>
        </div>

        <table class="info-basica">
            <tr>
                <td><strong>Categoria</strong><br><?= htmlspecialchars($rotulosCategoria[$denuncia['categoria']] ?? ucfirst($denuncia['categoria'])) ?></td>
                <td><strong>Status atual</strong><br><?= htmlspecialchars($denuncia['status']) ?></td>
                <td><strong>Registrada em</strong><br><?= !empty($denuncia['criada_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($denuncia['criada_em']))) : '—' ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Denunciante</strong><br><?= htmlspecialchars($autor['nome'] ?? 'Usuário removido') ?><?= !empty($autor['email']) ? ' · ' . htmlspecialchars($autor['email']) : '' ?></td>
                <td><strong>Relatório gerado em</strong><br><?= date('d/m/Y H:i') ?></td>
            </tr>
            <?php $enc = Denuncia::infoEncaminhamento($denuncia); if ($enc): ?>
            <tr>
                <td colspan="2"><strong>Órgão responsável</strong><br><?= htmlspecialchars($enc['orgao']) ?> · encaminhada em <?= htmlspecialchars(date('d/m/Y', strtotime($enc['encaminhada_em']))) ?></td>
                <td><strong>Prazo de resposta</strong><br><?= (int) $enc['prazo_dias'] ?> dias (até <?= htmlspecialchars(date('d/m/Y', strtotime($enc['prazo_data']))) ?>)<?= $enc['vencido'] ? ' — vencido' : '' ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="bloco">
            <h2>Descrição</h2>
            <p><?= nl2br(htmlspecialchars($denuncia['descricao'])) ?></p>
        </div>

        <?php if (!empty($denuncia['foto'])): ?>
            <div class="bloco">
                <h2>Foto anexada</h2>
                <img src="<?= htmlspecialchars($denuncia['foto']) ?>" alt="Foto da denúncia" class="foto-relatorio">
            </div>
        <?php endif; ?>

        <?php if ($temLocalizacao): ?>
            <div class="bloco">
                <h2>Localização</h2>
                <p>
                    <?php if (!empty($denuncia['endereco'])): ?>
                        <?= htmlspecialchars($denuncia['endereco']) ?><br>
                    <?php endif; ?>
                    Coordenadas: <?= htmlspecialchars($denuncia['latitude']) ?>, <?= htmlspecialchars($denuncia['longitude']) ?>
                </p>
                <iframe class="mapa-relatorio no-print" src="<?= htmlspecialchars($mapaEmbedUrl) ?>"></iframe>
            </div>
        <?php endif; ?>

        <?php if (!empty($historico)): ?>
            <div class="bloco">
                <h2>Linha do tempo</h2>
                <table class="tabela-historico">
                    <thead>
                        <tr><th>Etapa</th><th>Data</th><th>Observação</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['status'] ?? '') ?></td>
                                <td><?= !empty($h['data']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($h['data']))) : '—' ?></td>
                                <td><?= htmlspecialchars($h['mensagem'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="assinaturas">
            <div><span></span><small>Responsável pelo atendimento</small></div>
            <div><span></span><small>Órgão responsável<?php $encR = Denuncia::infoEncaminhamento($denuncia); echo $encR ? ' — ' . htmlspecialchars($encR['orgao']) : ''; ?></small></div>
        </div>
        <div class="autenticidade">
            <i class="fa-solid fa-shield-halved"></i>
            <p>Para conferir a autenticidade e o andamento atualizado, escaneie o QR code no topo deste documento ou acesse a consulta pública com o protocolo <strong><?= htmlspecialchars(Denuncia::protocolo($denuncia)) ?></strong>.</p>
        </div>
        <div class="rodape-relatorio">
            Relatório emitido automaticamente pelo sistema Olho Cidadão em <?= date('d/m/Y \à\s H:i') ?>, por <?= htmlspecialchars($geradoPor['nome'] ?? '') ?>.
        </div>

    </div>

<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/vendor/qrcode.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>">
// QR do protocolo no cabeçalho (gerado no navegador, funciona sem internet)
(function () {
    const el = document.getElementById('relatorio-qr');
    if (!el || typeof qrcode !== 'function') return;
    const qr = qrcode(0, 'M'); qr.addData(new URL(el.dataset.url, location.origin).href); qr.make();
    const n = qr.getModuleCount(); let d = '';
    for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) if (qr.isDark(y, x)) d += 'M' + x + ' ' + y + 'h1v1h-1z';
    el.innerHTML = '<svg viewBox="-1 -1 ' + (n + 2) + ' ' + (n + 2) + '" shape-rendering="crispEdges"><rect x="-1" y="-1" width="' + (n + 2) + '" height="' + (n + 2) + '" fill="#fff"/><path d="' + d + '" fill="#111"/></svg>';
})();
</script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
