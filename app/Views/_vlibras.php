<?php
/**
 * Widget oficial do VLibras (Governo Federal) — tradução do conteúdo do site para Libras.
 * Incluído no final de cada página, logo antes do </body>.
 * O widget usa os servidores públicos do VLibras (vlibras.gov.br), então não precisa
 * hospedar a translate-api nem RabbitMQ/MongoDB.
 */
?>
<div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
        <div class="vw-plugin-top-wrapper"></div>
    </div>
</div>
<script nonce="<?= CSP_NONCE ?>" src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    // Sem internet (ou com o VLibras fora do ar) o site continua normal, sem erro no console.
    if (window.VLibras && typeof window.VLibras.Widget === 'function') new window.VLibras.Widget('https://vlibras.gov.br/app');
</script>
