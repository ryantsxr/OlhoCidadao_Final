<?php
/**
 * Caixa "Encaminhada para a Secretaria X, prazo de N dias".
 * Espera $denuncia no escopo. Não mostra nada se ainda não foi encaminhada.
 */
$enc = Denuncia::infoEncaminhamento($denuncia);
if ($enc):
    $percentual = 100;
    if (!$enc['finalizada'] && $enc['prazo_dias'] > 0 && $enc['dias_restantes'] !== null) {
        $passados = $enc['prazo_dias'] - $enc['dias_restantes'];
        $percentual = max(4, min(100, (int) round($passados / $enc['prazo_dias'] * 100)));
    }
?>
<div class="orgao-destaque <?= $enc['vencido'] ? 'vencido' : '' ?>">
    <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
    <div style="flex:1">
        <strong>Encaminhada para <?= htmlspecialchars($enc['orgao']) ?></strong>
        <?php if ($enc['finalizada']): ?>
            <p>Encaminhada em <?= htmlspecialchars(date('d/m/Y', strtotime($enc['encaminhada_em']))) ?>.</p>
        <?php else: ?>
            <p>
                Prazo de resposta: <?= (int) $enc['prazo_dias'] ?> dias (até <?= htmlspecialchars(date('d/m/Y', strtotime($enc['prazo_data']))) ?>)
                <?php if ($enc['vencido']): ?>
                    — prazo vencido há <?= abs($enc['dias_restantes']) ?> dia<?= abs($enc['dias_restantes']) === 1 ? '' : 's' ?>
                <?php elseif ($enc['dias_restantes'] === 0): ?>
                    — vence hoje
                <?php else: ?>
                    — faltam <?= $enc['dias_restantes'] ?> dia<?= $enc['dias_restantes'] === 1 ? '' : 's' ?>
                <?php endif; ?>
            </p>
            <div class="orgao-barra" role="progressbar" aria-label="Tempo do prazo já usado" aria-valuenow="<?= $percentual ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= $percentual ?>%"></span></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
