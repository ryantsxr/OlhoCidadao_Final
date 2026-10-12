<?php
/** @var array $denuncia */
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar denúncia - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/novidades.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>
<main class="pagina-solta">
    <div class="caixa-solta">
        <h1>Editar denúncia</h1>
        <p class="sub-solta"><?= htmlspecialchars(Denuncia::protocolo($denuncia)) ?> · só dá para editar enquanto estiver <strong>Pendente</strong>.</p>

        <form method="post" action="<?= BASE_URL ?>/?rota=denuncia-editar" class="form-editar">
            <?= $this->csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $denuncia['id'] ?>">

            <label for="descricao">Descrição do problema</label>
            <textarea id="descricao" name="descricao" minlength="5" maxlength="2000" required><?= htmlspecialchars($denuncia['descricao']) ?></textarea>

            <label for="endereco">Endereço / referência</label>
            <input type="text" id="endereco" name="endereco" maxlength="255" value="<?= htmlspecialchars($denuncia['endereco'] ?? '') ?>">

            <div class="acoes-editar">
                <a href="<?= BASE_URL ?>/?rota=minhas-denuncias" class="btn-voltar">Cancelar</a>
                <button type="submit" class="btn-salvar-edicao"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            </div>
        </form>
    </div>
</main>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/voz.js?v=0.52.9"></script>
<?php if (!empty($error)): ?>
<script nonce="<?= CSP_NONCE ?>">document.addEventListener('DOMContentLoaded', () => mostrarToast(<?= json_encode($error) ?>, 'erro'));</script>
<?php endif; ?>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
