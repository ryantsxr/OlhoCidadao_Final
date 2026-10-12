<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Segundo fator — Olho Cidadão</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/login.css?v=1.0.1"><link rel="stylesheet" href="<?= BASE_URL ?>/css/fundo-animado.css?v=0.52.9">
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n-dicionario.js?v=1.0.6" defer></script><script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n.js?v=1.0.6" defer></script>
</head><body class="pagina-auth"><div class="container">
<div class="left"><img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Olho Cidadão"><p>Proteção adicional para o administrador.</p></div>
<div class="right"><form method="post" action="<?= BASE_URL ?>/?rota=segundo-fator">
<?= $this->csrfField() ?>
<h2>Confirme sua <span>identidade</span></h2>
<p>Digite o código de 6 dígitos do aplicativo autenticador ou um código de recuperação.</p>
<?php if (!empty($erro)): ?><p role="alert" style="color:#f87171"><?= htmlspecialchars($erro, ENT_QUOTES) ?></p><?php endif; ?>
<input type="text" name="codigo" autocomplete="one-time-code" inputmode="text" maxlength="15" placeholder="Código do autenticador" required autofocus>
<button type="submit">Confirmar código</button>
<p class="cadastro"><a href="<?= BASE_URL ?>/?rota=login">Voltar para o login</a></p>
</form></div></div></body></html>
