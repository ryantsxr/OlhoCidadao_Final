<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passo 1 - Olho Cidadão</title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/denuncia-passos.css?v=1.0.3">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>

    <div class="container-principal">
        
        <?php require __DIR__ . '/_lado-esquerdo.php'; ?>

        <div class="lado-direito">
            
            <div class="caixa-denuncia passo1">
                <a class="voltar-passo" href="<?= BASE_URL ?>/?rota=dashboard"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>

                
                <div class="progresso">
                    <div class="circulo ativo">1</div>
                    <div class="linha"></div>
                    <div class="circulo">2</div>
                    <div class="linha"></div>
                    <div class="circulo">3</div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="aviso-erro-passo" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="conteudo-caixa"><form method="post" action="<?= BASE_URL ?>/?rota=denuncia-passo1"><?= $this->csrfField() ?><input type="hidden" name="categoria" id="categoria">
                    
                    <div class="grid-cards">
                        <div class="card" data-categoria="iluminacao" role="button" tabindex="0">
                            <i class="fa-solid fa-lightbulb"></i>
                            <span>Iluminação</span>
                        </div>
                        
                        <div class="card" data-categoria="sinalizacao" role="button" tabindex="0">
                            <i class="fa-solid fa-traffic-light"></i>
                            <span>Sinalização de Trânsito</span>
                        </div>
                        
                        <div class="card" data-categoria="lixo" role="button" tabindex="0">
                            <i class="fa-solid fa-trash"></i>
                            <span>Lixo nas ruas</span>
                        </div>
                        
                        <div class="card" data-categoria="buracos" role="button" tabindex="0">
                            <i class="fa-solid fa-road"></i>
                            <span>Buracos e vias</span>
                        </div>

                        <div class="card" data-categoria="esgoto" role="button" tabindex="0">
                            <i class="fa-solid fa-water"></i>
                            <span>Esgoto e drenagem</span>
                        </div>

                        <div class="card" data-categoria="agua" role="button" tabindex="0">
                            <i class="fa-solid fa-faucet-drip"></i>
                            <span>Abastecimento de água</span>
                        </div>

                        <div class="card" data-categoria="arborizacao" role="button" tabindex="0">
                            <i class="fa-solid fa-tree"></i>
                            <span>Árvores e poda</span>
                        </div>

                        <div class="card" data-categoria="outros" role="button" tabindex="0">
                            <i class="fa-solid fa-plus"></i>
                            <span>Outros</span>
                        </div>
                    </div>

                    <div class="painel-info">
                        <i id="info-icone" class="fa-solid fa-lightbulb"></i>
                        <h3 id="info-titulo">Selecione</h3>
                        <p id="info-texto">Clique em um dos cartões ao lado para escolher o tipo de problema que você deseja relatar.</p>
                        
                        <button id="btn-continuar" class="oculto" type="submit">Continuar</button>
                    </div>

                </div>

                </form>
            </div>
        </div>

    </div>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=1.0.6"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/denuncia.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/denuncia-form.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>