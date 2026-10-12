<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passo 2 - Olho Cidadão</title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/denuncia-passos.css?v=1.0.3">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body>

    <div class="container-principal">
        
        <?php require __DIR__ . '/_lado-esquerdo.php'; ?>

        <div class="lado-direito">
            <div class="caixa-denuncia passo2" data-categoria="<?= htmlspecialchars($categoria) ?>">
                <a class="voltar-passo" href="<?= BASE_URL ?>/?rota=denuncia-passo1"><i class="fa-solid fa-arrow-left"></i> Voltar</a>

                
                <div class="progresso">
                    <div class="circulo ativo">1</div>
                    <div class="linha ativa"></div>
                    <div class="circulo ativo">2</div>
                    <div class="linha"></div>
                    <div class="circulo">3</div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="aviso-erro-passo" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="conteudo-passo2"><form method="post" action="<?= BASE_URL ?>/?rota=denuncia-passo2" enctype="multipart/form-data"><?= $this->csrfField() ?>

                    <div class="p2-regras">
                        <strong><i class="fa-solid fa-circle-info"></i> Regras</strong>
                        <span><i class="fa-solid fa-check"></i> Seja claro e objetivo</span>
                        <span><i class="fa-solid fa-check"></i> Poste provas do ocorrido</span>
                    </div>

                    <div class="p2-descricao">
                        <label for="descricao">Descreva o Problema</label>
                        <textarea name="descricao" id="descricao" minlength="5" maxlength="2000" required placeholder="Ex: Poste na rua 15 queimado!!"></textarea>
                    </div>

                    <div class="p2-foto">
                        <span class="p2-rotulo">Foto</span>
                        <div class="zona-foto" id="zona-foto">
                            <input type="file" name="foto" id="input-foto" accept="image/png,image/jpeg,image/webp" class="zona-foto-input">
                            <label for="input-foto" class="zona-foto-vazia" id="zona-foto-vazia">
                                <span class="zona-foto-icone"><i class="fa-solid fa-camera"></i></span>
                                <strong><span class="so-desktop">Arraste uma foto aqui ou </span>clique para escolher</strong>
                                <small>Opcional · JPG, PNG ou WEBP de até 5 MB</small>
                            </label>
                            <div class="zona-foto-previa" id="zona-foto-previa" hidden>
                                <img id="zona-foto-img" alt="Prévia da foto escolhida">
                                <div class="zona-foto-info">
                                    <span class="zona-foto-nome" id="zona-foto-nome"></span>
                                    <small id="zona-foto-tamanho"></small>
                                    <div class="zona-foto-acoes">
                                        <label for="input-foto" class="zona-foto-btn"><i class="fa-solid fa-arrows-rotate"></i> Trocar</label>
                                        <button type="button" class="zona-foto-btn perigo" id="zona-foto-remover"><i class="fa-solid fa-trash-can"></i> Remover</button>
                                    </div>
                                </div>
                            </div>
                            <div class="zona-foto-soltar" aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i> Solte a foto aqui</div>
                        </div>
                    </div>

                    <div class="p2-local">
                        <div class="localizacao-bloco">
                            <button type="button" id="btn-toggle-localizacao" class="btn-toggle-localizacao">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Adicionar localização (opcional)</span>
                            </button>

                            <div id="localizacao-painel" class="localizacao-painel oculto">
                                <div class="mapa-acoes">
                                    <button type="button" id="btn-minha-localizacao">
                                        <i class="fa-solid fa-location-crosshairs"></i> Usar minha localização
                                    </button>
                                    <span class="localizacao-endereco" id="localizacao-texto">Toque no mapa para marcar o local exato</span>
                                </div>
                                <div id="mapa-denuncia"></div>
                                <input type="hidden" name="latitude" id="input-latitude">
                                <input type="hidden" name="longitude" id="input-longitude">
                                <input type="hidden" name="endereco" id="input-endereco">
                                <div id="aviso-parecida" class="aviso-parecida" hidden
                                     data-url="<?= htmlspecialchars(BASE_URL . '/?rota=denuncias-proximas') ?>"
                                     data-url-ver="<?= htmlspecialchars(BASE_URL . '/?rota=denuncia-ver&id=') ?>"
                                     data-url-apoiar="<?= htmlspecialchars(BASE_URL . '/?rota=denuncia-apoiar') ?>"
                                     data-csrf="<?= htmlspecialchars($this->csrfToken()) ?>"
                                     aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($resolvidasAnteriores)): ?>
                        <div class="campo-reincidencia">
                            <label for="denuncia_anterior_id">Esse problema já tinha sido resolvido antes e voltou? (opcional)</label>
                            <select name="denuncia_anterior_id" id="denuncia_anterior_id">
                                <option value="">Não, é um problema novo</option>
                                <?php foreach ($resolvidasAnteriores as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>">
                                        #<?= (int) $r['id'] ?> — <?= htmlspecialchars($r['endereco'] ?: 'sem endereço') ?> (resolvida em <?= htmlspecialchars(date('d/m/Y', strtotime($r['criada_em']))) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                    <div class="p2-rodape">
                        <label class="campo-anonima">
                            <input type="checkbox" name="anonima" value="1">
                            Denunciar anonimamente (seu nome não aparece pra outros moradores)
                        </label>
                        <button id="btn-enviar" type="submit">Enviar</button>
                    </div>

                </form>
</div>

            </div>
        </div>

    </div>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/app.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/fundo-animado.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/configuracoes.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/vendor/leaflet/leaflet.js"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/mapa-denuncia.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/foto-denuncia.js?v=0.52.9"></script>
    <script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/validacao-formularios.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/denuncia-form.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/voz.js?v=0.52.9"></script>
<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>