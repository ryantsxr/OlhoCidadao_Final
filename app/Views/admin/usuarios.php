<?php
/** @var array $usuario */
/** @var array $lista */
/** @var array $contagem */
/** @var string $busca */
$ativo = 'admin-usuarios';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=1.0.6">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/_menu.php'; ?>
    <main class="conteudo">
        <header>
            <div>
                <h1>Usuários</h1>
                <p>Gerencie quem é administrador e quem pode acessar o sistema</p>
            </div>
        </header>

        <form method="get" action="<?= BASE_URL ?>/" class="busca-denuncias busca-usuarios-admin">
            <input type="hidden" name="rota" value="admin-usuarios">
            <input type="search" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome ou e-mail...">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <div class="lista lista-usuarios-admin">
            <?php if (!$lista): ?>
                <div class="estado-vazio"><p>Nenhum usuário encontrado.</p></div>
            <?php endif; ?>
            <?php foreach ($lista as $u):
                $uid = (int) $u['id'];
                $ehAdmin = ($u['role'] ?? 'cidadao') === 'admin';
                $suspenso = !User::estaAtivo($u);
                $souEu = $uid === (int) $usuario['id'];
            ?>
            <div class="denuncia denuncia-admin linha-usuario">
                <div><img src="<?= htmlspecialchars(($u['foto'] ?? '') ?: BASE_URL . '/img/avatar-padrao.svg') ?>" alt="" class="avatar"></div>
                <div>
                    <h3>
                        <?= htmlspecialchars($u['nome']) ?>
                        <?php if ($ehAdmin): ?><span class="badge-prioridade prio-alta"><i class="fa-solid fa-user-shield"></i> Admin</span><?php endif; ?>
                        <?php if ($suspenso): ?><span class="badge-prioridade prio-urgente"><i class="fa-solid fa-ban"></i> Suspenso</span><?php endif; ?>
                        <?php if ($souEu): ?><span class="protocolo-tag">você</span><?php endif; ?>
                    </h3>
                    <span class="denuncia-usuario"><?= htmlspecialchars($u['email']) ?> · <?= (int) ($contagem[$uid] ?? 0) ?> denúncia(s)</span>
                </div>
                <?php if (!$souEu): ?>
                <div class="acoes-usuario">
                    <?php
                    $botoes = $ehAdmin
                        ? [['rebaixar', 'Remover admin', 'fa-user-minus', 'Remover o acesso de administrador de ' . $u['nome'] . '?']]
                        : [['promover', 'Tornar admin', 'fa-user-shield', 'Tornar ' . $u['nome'] . ' administrador? Essa pessoa verá todas as denúncias e dados dos cidadãos.']];
                    $botoes[] = $suspenso
                        ? ['reativar', 'Reativar', 'fa-rotate-left', 'Reativar a conta de ' . $u['nome'] . '?']
                        : ['suspender', 'Suspender', 'fa-ban', 'Suspender a conta de ' . $u['nome'] . '? Ela perde o acesso na hora.'];
                    foreach ($botoes as [$acao, $rotulo, $icone, $confirma]): ?>
                        <form method="post" action="<?= BASE_URL ?>/?rota=admin-usuario-alterar" data-confirmar="<?= htmlspecialchars($confirma) ?>">
                            <?= $this->csrfField() ?>
                            <input type="hidden" name="id" value="<?= $uid ?>">
                            <input type="hidden" name="acao" value="<?= $acao ?>">
                            <button type="submit" class="btn-acao-usuario <?= $acao ?>"><i class="fa-solid <?= $icone ?>"></i> <?= $rotulo ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/menu-mobile.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/confirmacao.js?v=0.52.9"></script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
<?php if (!empty($sucesso)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($sucesso) ?>, 'sucesso'));</script>
<?php endif; ?>
<?php require __DIR__ . '/../_vlibras.php'; ?>
<?php require __DIR__ . '/../_barra-inferior.php'; ?>
</body>
</html>
