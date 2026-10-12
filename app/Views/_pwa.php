<?php
/**
 * PWA: manifest, ícone do iPhone e registro do service worker.
 * Incluído no <head> de todas as páginas. Os caminhos usam BASE_URL, então
 * funcionam em subpastas (ex.: /OlhoCidadao/public).
 */
// Carrega as preferências SALVAS NA CONTA também nas demais páginas.
// Só exportamos flags de apresentação, nunca dados pessoais do usuário.
$_ocPreferencias = null;
$_ocUsuarioAtual = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $_ocUsuarioAtual = (new User)->find((int) $_SESSION['user_id']);
        if (is_array($_ocUsuarioAtual)) {
            $_ocPadrao = ['temaEscuro' => true, 'idioma' => 'pt-br', 'fonte' => 'padrao',
                'altoContraste' => false, 'modoDaltonico' => false, 'fonteLeitura' => false];
            $_ocPreferencias = array_merge($_ocPadrao, array_intersect_key(
                is_array($_ocUsuarioAtual['config'] ?? null) ? $_ocUsuarioAtual['config'] : [], $_ocPadrao));
        }
    } catch (Throwable $e) { /* Não interrompe o carregamento por uma preferência. */ }
}
?>
<?php if ($_ocPreferencias !== null): ?>
<script nonce="<?= CSP_NONCE ?>">window.CONFIG_SERVIDOR = <?= json_encode($_ocPreferencias, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.webmanifest">
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/icon-192.png">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>/img/apple-touch-icon.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Olho Cidadão">
<script nonce="<?= CSP_NONCE ?>">
    // Marca o tema claro antes da página aparecer (evita o "piscar" escuro → claro).
    try {
        var _ocCfg = window.CONFIG_SERVIDOR || JSON.parse(localStorage.getItem('configOlhoCidadao') || 'null');
        if (_ocCfg && _ocCfg.temaEscuro === false) document.documentElement.classList.add('pre-tema-claro');
        if (_ocCfg && _ocCfg.modoDaltonico === true) document.documentElement.classList.add('pre-modo-daltonico');
    } catch (e) {}
    // Ações sem "onclick" no HTML (exigência da Política de Segurança de Conteúdo):
    //   data-href="..."  → abre o endereço;  data-acao="imprimir" → imprime;
    //   <img data-remover-se-falhar> → some se a foto não carregar.
    document.addEventListener('click', function (e) {
        var alvo = e.target.closest ? e.target.closest('[data-href], [data-acao="imprimir"]') : null;
        if (!alvo) return;
        if (alvo.hasAttribute('data-href')) { window.location.href = alvo.getAttribute('data-href'); }
        else { window.print(); }
    });
    document.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.hasAttribute('data-remover-se-falhar')) t.remove();
    }, true);
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/acabamento.css?v=0.52.9">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/interacoes.css?v=1.0.3">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/daltonismo.css?v=0.52.9">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/acessibilidade.css?v=0.52.9">
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n-dicionario.js?v=1.0.6" defer></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n.js?v=1.0.6" defer></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/acabamento.js?v=0.52.9" defer></script>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/interacoes.js?v=1.0.3" defer></script>
<?php if (($_ocUsuarioAtual['role'] ?? '') === 'admin'): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/admin-extra.css?v=0.52.9">
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/admin-extra.js?v=0.52.9" defer data-busca="<?= htmlspecialchars(BASE_URL . '/?rota=admin-busca') ?>"></script>
<?php endif; ?>
<script nonce="<?= CSP_NONCE ?>">window.OC_BASE = <?= json_encode(BASE_URL) ?>; window.OC_VERSAO = <?= json_encode(trim((string) @file_get_contents(__DIR__ . "/../../VERSION"))) ?>; window.OC_EQUIPE = <?= json_encode(defined('APP_EQUIPE') ? APP_EQUIPE : '') ?>;</script>
<script nonce="<?= CSP_NONCE ?>">
    // O service worker só funciona em HTTPS ou localhost.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').catch(function () {});
        });
    }
</script>
