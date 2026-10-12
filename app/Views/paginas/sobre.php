<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o projeto - Olho Cidadão</title>
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
        <a href="<?= BASE_URL ?>/?rota=login"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>

    <div class="pagina-conteudo">
        <h1>Sobre o Olho Cidadão</h1>
        <p class="subtitulo">Um jeito simples de conectar o cidadão à sua cidade.</p>

        <p>
            O Olho Cidadão é um sistema de denúncias de problemas urbanos — como iluminação
            pública apagada, buracos, lixo acumulado ou falta de sinalização. A ideia é dar
            a qualquer morador um canal direto para relatar esses problemas e acompanhar,
            de forma transparente, o que está sendo feito a respeito.
        </p>
        <section class="secao-card" style="--tom:#3D8BFD">
            <h2><span class="ic-tom"><i class="fa-solid fa-route"></i></span>Como funciona</h2>
        <div class="fluxo-passos">
            <div class="passo">
                <i class="fa-solid fa-pen"></i>
                <strong>1. Denunciar</strong>
                <p>O cidadão escolhe a categoria, descreve o problema e pode anexar uma foto e a localização.</p>
            </div>
            <div class="passo">
                <i class="fa-solid fa-magnifying-glass"></i>
                <strong>2. Acompanhar</strong>
                <p>A denúncia entra na fila da prefeitura e o cidadão acompanha o status em tempo real.</p>
            </div>
            <div class="passo">
                <i class="fa-solid fa-circle-check"></i>
                <strong>3. Resolver</strong>
                <p>A equipe responsável atualiza o status até a denúncia ser marcada como resolvida.</p>
            </div>
        </div>
        </section>

        <section class="secao-card" style="--tom:#22C55E">
            <h2><span class="ic-tom"><i class="fa-solid fa-eye"></i></span>Transparência</h2>
        <p>
            Todas as denúncias com localização aparecem no
            <a href="<?= BASE_URL ?>/?rota=mapa" >mapa público</a>,
            sem precisar de login, para que qualquer pessoa veja o que está acontecendo na cidade.
        </p>
        </section>

        <section class="secao-card" style="--tom:#A78BFA">
            <h2><span class="ic-tom"><i class="fa-solid fa-graduation-cap"></i></span>Sobre este projeto</h2>
        <p>
            O Olho Cidadão foi desenvolvido como projeto escolar do curso técnico em
            Informática, com o objetivo de aplicar na prática conceitos de desenvolvimento
            web, banco de dados e experiência do usuário em um sistema completo.
        </p>
        </section>
    </div>

<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
