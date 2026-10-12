<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Transparência | Olho Cidadão</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=0.52.9">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
<style>
body{background:#111827;color:#e5e7eb;font-family:system-ui,Arial,sans-serif}
.transparencia{max-width:1030px;margin:0 auto;padding:36px 18px}
.transparencia a{color:#62d68b}.transparencia h1{font-size:clamp(24px,4vw,38px)}
.transparencia .grade{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin:28px 0}
.transparencia .cartao{background:#1f2937;border:1px solid #374151;padding:22px;border-radius:14px}
.transparencia .numero{font-size:32px;font-weight:700;color:#8ee0aa}
.transparencia table{width:100%;border-collapse:collapse;background:#1f2937;border-radius:12px;overflow:hidden}
.transparencia td,.transparencia th{text-align:left;padding:12px 16px;border-bottom:1px solid #374151}
.transparencia .bloco{margin:26px 0}
</style>
<script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n-dicionario.js?v=1.0.6" defer></script><script nonce="<?= CSP_NONCE ?>" src="<?= BASE_URL ?>/js/i18n.js?v=1.0.6" defer></script>
</head><body>
<main class="transparencia">
<a href="<?= BASE_URL ?>/?rota=mapa">← Voltar ao mapa</a>
<h1>Transparência do Olho Cidadão</h1><p>Dados gerais de ocorrências registradas, sem identificação de cidadãos e sem endereços específicos.</p>
<section class="grade" aria-label="Indicadores">
<div class="cartao"><div class="numero"><?= $total ?></div><div>Denúncias registradas</div></div>
<div class="cartao"><div class="numero"><?= $resolvidas ?></div><div>Resolvidas e confirmadas</div></div>
<div class="cartao"><div class="numero"><?= number_format($percentual,1,',','.') ?>%</div><div>Taxa de resolução*</div></div>
<div class="cartao"><div class="numero"><?= (int)$abertas ?></div><div>Em atendimento ou aguardando confirmação</div></div>
<div class="cartao"><div class="numero"><?= (int)$atrasadas ?></div><div>Com prazo previsto ultrapassado</div></div>
</section>
<section class="bloco"><h2>Por situação</h2><table><thead><tr><th>Situação</th><th>Quantidade</th></tr></thead><tbody>
<?php foreach($status as $situacao=>$quantidade): ?><tr><td><?= htmlspecialchars($situacao,ENT_QUOTES,'UTF-8') ?></td><td><?= $quantidade ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="bloco"><h2>Por categoria</h2><table><thead><tr><th>Categoria</th><th>Quantidade</th></tr></thead><tbody>
<?php if (!$categorias): ?><tr><td colspan="2">Nenhuma denúncia registrada ainda.</td></tr><?php endif; ?>
<?php foreach($categorias as $categoria=>$quantidade): ?><tr><td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$categoria)),ENT_QUOTES,'UTF-8') ?></td><td><?= (int)$quantidade ?></td></tr><?php endforeach; ?>
</tbody></table></section><p><small>* Taxa calculada sobre as denúncias não canceladas, como no painel interno. Prazos são estimativas de atendimento, não compromissos oficiais. Os indicadores não representam métricas oficiais da prefeitura sem validação institucional.</small></p>
</main></body></html>