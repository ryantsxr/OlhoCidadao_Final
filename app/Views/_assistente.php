<?php
/**
 * Chat do Olhinho (assistente) — v0.50.
 * Inclua no fim do <body>. Defina $_assistenteFlutuante = true antes do
 * require para mostrar o botão redondo no canto da tela (painéis do cidadão).
 * Nas telas da denúncia quem abre o chat é o próprio mascote.
 */
if (!empty($GLOBALS['_assistenteIncluido'])) { return; }
$GLOBALS['_assistenteIncluido'] = true;

$_usuarioChat = !empty($_SESSION['user_id']) ? (new User)->find((int) $_SESSION['user_id']) : null;
$_cfgChat = [
    'csrf' => $this->csrfToken(),
    'avatar' => BASE_URL . '/img/mascote-rosto.png',
    'sugestoes' => Assistente::SUGESTOES,
    'ia' => Assistente::iaConfigurada(),
    'nomeUsuario' => $_usuarioChat ? explode(' ', trim((string) $_usuarioChat['nome']))[0] : '',
];
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/assistente.css?v=0.52.9">
<script nonce="<?= CSP_NONCE ?>">window.OC_ASSISTENTE = <?= json_encode($_cfgChat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/assistente.js?v=0.52.9" defer></script>
<?php if (!empty($_assistenteFlutuante)): ?>
<button type="button" class="olhinho-flutuante" data-abrir-assistente aria-label="Tirar dúvidas com o Olhinho, o assistente">
    <img src="<?= BASE_URL ?>/img/mascote-rosto.png" alt="">
    <span>Dúvidas?</span>
</button>
<?php endif; ?>
