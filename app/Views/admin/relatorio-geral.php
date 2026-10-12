<?php
/** @var array $usuario */
/** @var array $filtradas */
/** @var string $dataInicio */
/** @var string $dataFim */
/** @var string $categoria */
/** @var string $status */
/** @var array $porCategoria */
/** @var array $porStatus */

$categoriasLabel = [
    'iluminacao' => 'Iluminação pública',
    'sinalizacao' => 'Sinalização de trânsito',
    'lixo' => 'Lixo / limpeza urbana',
    'buracos' => 'Buracos e vias',
    'esgoto' => 'Esgoto e drenagem',
    'agua' => 'Abastecimento de água',
    'arborizacao' => 'Árvores e poda',
    'outros' => 'Outros'
];

$statusOpcoes = ['Pendente', 'Em análise', 'Aguardando confirmação', 'Resolvido', 'Cancelada', 'Rejeitada'];

/** Mantém os filtros atuais na URL de exportação/impressão. */
function queryFiltros(string $dataInicio, string $dataFim, string $categoria, string $status): string {
    $partes = [];
    if ($dataInicio !== '') $partes[] = 'data_inicio=' . urlencode($dataInicio);
    if ($dataFim !== '') $partes[] = 'data_fim=' . urlencode($dataFim);
    if ($categoria !== 'todas') $partes[] = 'categoria=' . urlencode($categoria);
    if ($status !== 'todas') $partes[] = 'status=' . urlencode($status);
    return implode('&', $partes);
}

$query = queryFiltros($dataInicio, $dataFim, $categoria, $status);
$periodoTexto = ($dataInicio !== '' || $dataFim !== '')
    ? 'De ' . ($dataInicio !== '' ? date('d/m/Y', strtotime($dataInicio)) : '—') . ' até ' . ($dataFim !== '' ? date('d/m/Y', strtotime($dataFim)) : '—')
    : 'Todo o período';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Geral - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/relatorio.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/relatorio-geral.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>

<body>

    <div class="barra-acoes no-print">
        <a href="<?= BASE_URL ?>/?rota=admin"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        <div class="barra-acoes-botoes">
            <a href="<?= BASE_URL ?>/?rota=admin-exportar-csv<?= $query !== '' ? '&' . $query : '' ?>" class="btn-secundario">
                <i class="fa-solid fa-file-csv"></i> Exportar Excel (CSV)
            </a>
            <button type="button" data-acao="imprimir"><i class="fa-solid fa-file-pdf"></i> Imprimir / Salvar como PDF</button>
        </div>
    </div>

    <form method="get" action="<?= BASE_URL ?>/" class="filtro-relatorio no-print">
        <input type="hidden" name="rota" value="admin-relatorio">

        <label>
            De
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>">
        </label>
        <label>
            Até
            <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>">
        </label>
        <label>
            Categoria
            <select name="categoria">
                <option value="todas" <?= $categoria === 'todas' ? 'selected' : '' ?>>Todas</option>
                <?php foreach ($categoriasLabel as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $categoria === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Status
            <select name="status">
                <option value="todas" <?= $status === 'todas' ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($statusOpcoes as $opcao): ?>
                    <option value="<?= $opcao ?>" <?= $status === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>

    <div class="folha">

        <div class="cabecalho">
            <img src="<?= BASE_URL ?>/img/logo_white.png" alt="Logo Olho Cidadão" class="logo-relatorio">
            <div>
                <h1>Relatório Geral de Denúncias</h1>
                <p>Sistema Olho Cidadão</p>
            </div>
            <div class="cabecalho-id">
                <span>Total no período</span>
                <strong><?= count($filtradas) ?></strong>
            </div>
        </div>

        <table class="info-basica">
            <tr>
                <td><strong>Período</strong><br><?= htmlspecialchars($periodoTexto) ?></td>
                <td><strong>Categoria</strong><br><?= $categoria === 'todas' ? 'Todas' : htmlspecialchars($categoriasLabel[$categoria] ?? $categoria) ?></td>
                <td><strong>Status</strong><br><?= htmlspecialchars($status === 'todas' ? 'Todos' : $status) ?></td>
            </tr>
            <tr>
                <td colspan="3"><strong>Relatório gerado em</strong><br><?= date('d/m/Y H:i') ?> por <?= htmlspecialchars($usuario['nome'] ?? '') ?></td>
            </tr>
        </table>

        <div class="bloco">
            <h2>Resumo por categoria</h2>
            <table class="tabela-historico">
                <thead><tr><th>Categoria</th><th>Quantidade</th></tr></thead>
                <tbody>
                    <?php foreach ($porCategoria as $valor => $qtd): ?>
                        <tr><td><?= htmlspecialchars($categoriasLabel[$valor] ?? $valor) ?></td><td><?= $qtd ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="bloco">
            <h2>Resumo por status</h2>
            <table class="tabela-historico">
                <thead><tr><th>Status</th><th>Quantidade</th></tr></thead>
                <tbody>
                    <?php foreach ($porStatus as $valor => $qtd): ?>
                        <tr><td><?= htmlspecialchars($valor) ?></td><td><?= $qtd ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="bloco">
            <h2>Encaminhamentos e prazos</h2>
            <p>Denúncias com prazo de atendimento ultrapassado: <strong><?= (int) $atrasadasFiltradas ?></strong></p>
            <table class="tabela-historico">
                <thead><tr><th>Setor responsável</th><th>Quantidade</th></tr></thead>
                <tbody>
                    <?php foreach ($porOrgao as $chave => $quantidade): ?>
                        <tr><td><?= htmlspecialchars(Denuncia::ORGAOS[$chave]['nome']) ?></td><td><?= (int) $quantidade ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td>Sem encaminhamento</td><td><?= (int) $semEncaminhamento ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="bloco">
            <h2>Denúncias no período (<?= count($filtradas) ?>)</h2>
            <?php if (empty($filtradas)): ?>
                <p>Nenhuma denúncia encontrada com esses filtros.</p>
            <?php else: ?>
                <table class="tabela-historico">
                    <thead>
                        <tr><th>ID</th><th>Categoria</th><th>Descrição</th><th>Status</th><th>Setor</th><th>Prioridade</th><th>Criada em</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtradas as $d): ?>
                            <tr>
                                <td>#<?= (int)$d['id'] ?></td>
                                <td><?= htmlspecialchars($categoriasLabel[$d['categoria']] ?? ucfirst($d['categoria'])) ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($d['descricao'] ?? '', 0, 60, '...')) ?></td>
                                <td><?= htmlspecialchars($d['status'] ?? '') ?></td>
                                <td><?= htmlspecialchars(Denuncia::ORGAOS[$d['orgao'] ?? '']['nome'] ?? 'Não encaminhada') ?></td>
                                <td><?= htmlspecialchars($d['prioridade'] ?? 'Normal') ?></td>
                                <td><?= !empty($d['criada_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($d['criada_em']))) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="rodape-relatorio">
            Relatório emitido automaticamente pelo sistema Olho Cidadão em <?= date('d/m/Y \à\s H:i') ?>, por <?= htmlspecialchars($usuario['nome'] ?? '') ?>.
        </div>

    </div>

    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
