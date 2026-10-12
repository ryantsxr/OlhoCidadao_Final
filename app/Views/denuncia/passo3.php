<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passo 3 - Olho Cidadão</title>
    
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
            <div class="caixa-denuncia">
                
                <div class="progresso">
                    <div class="circulo ativo">1</div>
                    <div class="linha ativa"></div>
                    <div class="circulo ativo">2</div>
                    <div class="linha ativa"></div>
                    <div class="circulo ativo">3</div>
                </div>

                <div class="conteudo-passo3">
                    <?php if (!empty($protocolo)):
                        $rotulos = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
                        $icones = ['iluminacao' => 'fa-lightbulb', 'sinalizacao' => 'fa-traffic-light', 'lixo' => 'fa-trash', 'buracos' => 'fa-road', 'esgoto' => 'fa-water', 'agua' => 'fa-faucet-drip', 'arborizacao' => 'fa-tree', 'outros' => 'fa-plus'];
                        $cat = $denuncia['categoria'] ?? 'outros';
                    ?>
                    <div class="sucesso-topo">
                        <span class="sucesso-check" aria-hidden="true">
                            <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24"/><path d="M15 27l7 7 15-16"/></svg>
                        </span>
                        <h2>Denúncia registrada!</h2>
                        <p>A prefeitura já recebeu. Guarde o seu ingresso para acompanhar.</p>
                    </div>

                    <div class="ingresso" id="ingresso">
                        <div class="ingresso-corpo">
                            <div class="ingresso-cabecalho">
                                <span class="ingresso-marca"><i class="fa-solid fa-eye"></i> Olho Cidadão</span>
                                <span class="ingresso-cat"><i class="fa-solid <?= $icones[$cat] ?? 'fa-plus' ?>"></i> <?= htmlspecialchars($rotulos[$cat] ?? 'Outros') ?></span>
                            </div>
                            <small class="ingresso-rotulo">Protocolo</small>
                            <div class="ingresso-protocolo" id="protocolo-texto"><?= htmlspecialchars($protocolo) ?></div>
                            <dl class="ingresso-dados">
                                <div><dt>Data</dt><dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($denuncia['criada_em'] ?? 'now'))) ?></dd></div>
                                <div><dt>Status</dt><dd><span class="ingresso-status">Pendente</span></dd></div>
                                <?php if (!empty($denuncia['endereco'])): ?>
                                    <div class="largo"><dt>Local</dt><dd><?= htmlspecialchars($denuncia['endereco']) ?></dd></div>
                                <?php endif; ?>
                            </dl>
                            <button type="button" class="ingresso-copiar" id="btn-copiar" data-protocolo="<?= htmlspecialchars($protocolo) ?>">
                                <i class="fa-regular fa-copy"></i> <span>Copiar protocolo</span>
                            </button>
                        </div>
                        <div class="ingresso-canhoto">
                            <div class="ingresso-qr" id="ingresso-qr"
                                 data-url="<?= htmlspecialchars(BASE_URL . '/?rota=consulta&protocolo=' . urlencode($protocolo)) ?>"
                                 role="img" aria-label="QR code para consultar o protocolo"></div>
                            <small>Aponte a câmera para acompanhar</small>
                        </div>
                    </div>

                    <div class="sucesso-acoes">
                        <?php if (!empty($denuncia['id'])): ?>
                            <a class="btn-sucesso-secundario" href="<?= BASE_URL ?>/?rota=denuncia-ver&id=<?= (int) $denuncia['id'] ?>"><i class="fa-solid fa-magnifying-glass"></i> Acompanhar</a>
                        <?php endif; ?>
                        <a id="btn-voltar" class="btn-sucesso-principal" href="<?= BASE_URL ?>/?rota=dashboard"><i class="fa-solid fa-house"></i> Voltar ao início</a>
                    </div>
                    <?php else: ?>
                    <div class="box-sucesso">
                        <h2>Sua denúncia foi<br>registrada pelo<br>sistema</h2>
                        <p class="protocolo-final"><small>Você encontra o protocolo em "Minhas denúncias".</small></p>
                    </div>
                    <button type="button" id="btn-voltar" data-href="<?= BASE_URL ?>/?rota=dashboard">Voltar ao inicio</button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/vendor/qrcode.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/ingresso.js?v=0.52.9"<?= !empty($primeira) ? ' data-confete' : '' ?>></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/denuncia-form.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>