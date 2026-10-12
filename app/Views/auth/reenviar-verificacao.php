<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Confirmar e-mail — Olho Cidadão</title><link rel="stylesheet" href="<?= BASE_URL ?>/css/login.css?v=1.0.1"><script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n-dicionario.js?v=1.0.6" defer></script><script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n.js?v=1.0.6" defer></script>
</head>
<body class="pagina-auth"><div class="container">
<div class="left"><img src="<?= BASE_URL ?>/img/logo.png" class="logo" alt="Olho Cidadão"><p>Confirme seu e-mail para proteger seu cadastro.</p></div>
<div class="right"><form method="post" action="<?= BASE_URL ?>/?rota=reenviar-verificacao">
<?= $this->csrfField() ?><h2>Confirmar <span>e-mail</span></h2>
<?php if (!empty($erro)): ?><p role="alert" style="color:#f87171"><?= htmlspecialchars($erro, ENT_QUOTES) ?></p><?php endif; ?>
<?php if (!empty($sucesso)): ?><p role="status" style="color:#4ade80"><?= htmlspecialchars($sucesso, ENT_QUOTES) ?></p><?php endif; ?>
<p>Não chegou o link? Informe seu endereço para solicitar outro.</p>
<input type="email" name="email" maxlength="190" autocomplete="email" placeholder="Seu e-mail" required>
<button type="submit">Reenviar confirmação</button>
<p class="cadastro"><a href="<?= BASE_URL ?>/?rota=login">Voltar para o login</a></p>
</form></div></div></body></html>
