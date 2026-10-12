<?php
/**
 * Painel da esquerda dos 3 passos da denúncia (v0.50).
 * Agora com o Olhinho, o mascote 3D: ele acena, segue o mouse, reage ao
 * formulário, dá dicas no balão e abre o assistente quando alguém clica nele.
 * Embaixo continua a lista dos passos (o atual destacado).
 * O passo vem da rota (denuncia-passo1/2/3); se não der para saber, assume o 1.
 */
$_passo = (int) substr((string) ($_GET['rota'] ?? ''), -1);
if ($_passo < 1 || $_passo > 3) { $_passo = 1; }

$_leEtapas = [
    1 => ['fa-list-check',     'Escolha a categoria',  'O tipo de problema que você encontrou'],
    2 => ['fa-pen-to-square',  'Descreva e localize',  'Texto, foto e local no mapa'],
    3 => ['fa-flag-checkered', 'Receba o protocolo',   'Um número para acompanhar tudo'],
];
// Dicas que o Olhinho fala quando a pessoa fica um tempo parada (edite à vontade).
$_leDicas = [
    1 => ['São só 3 passos rápidos!', 'Na dúvida, escolha "Outros" e explique no próximo passo.', 'Clique em mim se tiver alguma dúvida. 😉'],
    2 => ['Cite a rua e um ponto de referência.', 'Uma foto de perto, de dia, ajuda muito.', 'Marque o local exato no mapa.', 'Sem tempo de digitar? Use o microfone! 🎙️', 'Se preferir, denuncie anonimamente.'],
    3 => ['Guarde o número do protocolo!', 'Você é avisado quando o status mudar.', 'Acompanhe tudo em Minhas denúncias.'],
];
$_usuarioLe = !empty($_SESSION['user_id']) ? (new User)->find((int) $_SESSION['user_id']) : null;
$_cfgMascote = [
    'passo' => $_passo,
    'dicas' => $_leDicas[$_passo],
    'nomeUsuario' => $_usuarioLe ? explode(' ', trim((string) $_usuarioLe['nome']))[0] : '',
];
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/mascote.css?v=0.52.9">
<div class="lado-esquerdo">
    <div class="le-conteudo">
        <a class="le-marca" href="<?= BASE_URL ?>/?rota=dashboard" aria-label="Olho Cidadão — voltar ao painel">olho <span>cidadão</span></a>

        <div class="mascote">
            <div class="mascote-balao" id="mascote-balao" role="status" aria-live="polite"></div>
            <div class="mascote-3d" id="mascote-3d" data-abrir-assistente role="button" tabindex="0"
                 aria-label="Olhinho, o mascote do Olho Cidadão. Toque para tirar dúvidas.">
                <img src="<?= BASE_URL ?>/img/mascote.png" alt="" class="mascote-reserva">
            </div>
            <button type="button" class="mascote-ajuda" data-abrir-assistente>
                <i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Tirar dúvida com o Olhinho
            </button>
        </div>

        <ol class="le-passos" aria-label="Passos da denúncia">
            <?php foreach ($_leEtapas as $_n => [$_icone, $_titulo, $_texto]):
                $_classe = ($_n < $_passo || $_passo === 3) ? 'feito' : ($_n === $_passo ? 'atual' : '');
            ?>
                <li class="<?= $_classe ?>"<?= $_n === $_passo ? ' aria-current="step"' : '' ?>>
                    <span class="le-ico"><i class="fa-solid <?= $_classe === 'feito' ? 'fa-check' : $_icone ?>" aria-hidden="true"></i></span>
                    <div>
                        <strong><?= $_titulo ?></strong>
                        <small><?= $_texto ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="le-seguro">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span>Seus dados ficam protegidos e você pode denunciar de forma anônima.</span>
        </div>
    </div>
</div>
<script nonce="<?= CSP_NONCE ?>">window.OC_MASCOTE = <?= json_encode($_cfgMascote, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script nonce="<?= CSP_NONCE ?>" type="module" src="<?= BASE_URL ?>/js/mascote.js?v=0.52.9"></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/mascote-reacoes.js?v=0.52.9" defer></script>
<?php require __DIR__ . '/../_assistente.php'; ?>
