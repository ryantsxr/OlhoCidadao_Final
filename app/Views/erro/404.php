<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/paginas.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/telas-cartoes.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body class="pagina-publica">

    <div class="pagina-topo">
        <div class="marca">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Olho Cidadão">
            <span>Olho <span>Cidadão</span></span>
        </div>
    </div>

    <div class="pagina-conteudo pagina-erro">
        <img src="<?= BASE_URL ?>/img/logo.png" alt="" class="erro-olho" data-olho-vivo>
        <span class="erro-codigo">404</span>
        <h1>O olho não achou essa página</h1>
        <p>Procuramos em todo canto da cidade, mas esse endereço não existe ou mudou de lugar.</p>
        <div class="erro-acoes">
            <a href="<?= BASE_URL ?>/?rota=<?= !empty($_SESSION['user_id']) ? 'dashboard' : 'login' ?>"><i class="fa-solid fa-house"></i> Voltar ao início</a>
            <a href="<?= BASE_URL ?>/?rota=mapa" class="secundario"><i class="fa-solid fa-map-location-dot"></i> Ver o mapa</a>
        </div>
    </div>

<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
