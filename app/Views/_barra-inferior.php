<?php
/**
 * Barra de navegação fixa embaixo, só no celular (estilo aplicativo).
 * Incluída no fim do <body> das telas com menu lateral.
 * Cidadão: Início, Minhas, + (nova denúncia), Mapa, Perfil.
 * Admin:   Painel, Estatísticas, Mapa, Usuários, Histórico.
 */
$_rotaAtual = $_GET['rota'] ?? '';
$_usuarioBarra = !empty($_SESSION['user_id']) ? (new User)->find((int) $_SESSION['user_id']) : null;
if ($_usuarioBarra):
    $_ehAdminBarra = ($_usuarioBarra['role'] ?? 'cidadao') === 'admin';
    $_itens = $_ehAdminBarra
        ? [
            ['admin', 'fa-table-columns', 'Painel', ['admin']],
            ['admin-estatisticas', 'fa-chart-simple', 'Números', ['admin-estatisticas']],
            ['admin-kanban', 'fa-plus', 'Quadro', ['admin-kanban'], true],
            ['mapa', 'fa-map-location-dot', 'Mapa', ['mapa']],
            ['admin-usuarios', 'fa-users', 'Usuários', ['admin-usuarios']],
        ]
        : [
            ['dashboard', 'fa-house', 'Início', ['dashboard']],
            ['minhas-denuncias', 'fa-file-lines', 'Minhas', ['minhas-denuncias', 'denuncia-ver', 'denuncia-editar']],
            ['denuncia-passo1', 'fa-plus', 'Denunciar', ['denuncia-passo1', 'denuncia-passo2', 'denuncia-passo3'], true],
            ['mapa', 'fa-map-location-dot', 'Mapa', ['mapa']],
            ['perfil', 'fa-user', 'Perfil', ['perfil', 'configuracoes']],
        ];
?>
<div class="barra-inferior" role="navigation" aria-label="Navegação principal">
    <?php foreach ($_itens as $_i):
        [$_rota, $_icone, $_rotulo, $_ativas] = $_i;
        $_central = !empty($_i[4]);
        $_ativo = in_array($_rotaAtual, $_ativas, true);
        if ($_central && $_ehAdminBarra) { $_icone = 'fa-table-cells-large'; }
    ?>
        <a href="<?= BASE_URL ?>/?rota=<?= $_rota ?>"
           class="barra-item<?= $_central ? ' barra-central' : '' ?><?= $_ativo ? ' ativo' : '' ?>"
           <?= $_ativo ? 'aria-current="page"' : '' ?>>
            <span class="barra-icone"><i class="fa-solid <?= $_icone ?>"></i></span>
            <span class="barra-rotulo"><?= $_rotulo ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php
    // Olhinho, o assistente: botão flutuante só para o cidadão (v0.50).
    if (!$_ehAdminBarra) {
        $_assistenteFlutuante = true;
        require __DIR__ . '/_assistente.php';
    }
?>
<?php endif; ?>
