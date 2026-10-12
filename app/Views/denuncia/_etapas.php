<?php
/**
 * Etapas compactas de uma denúncia: Recebida → Em análise → Resolvida.
 * Espera $d (a denúncia). Usado no painel e em "Minhas denúncias".
 */
$_st = strtolower($d['status'] ?? 'pendente');
if ($_st === 'cancelada'):
?>
<div class="etapas etapas-cancelada" aria-label="Denúncia cancelada"><span class="etapa feita"><i class="fa-solid fa-ban"></i> Cancelada</span></div>
<?php else:
    $_nivel = match ($_st) { 'em análise' => 2, 'aguardando confirmação' => 2.5, 'resolvido' => 3, default => 1 };
    $_etapas = [[1, 'Recebida', 'fa-inbox'], [2, 'Em análise', 'fa-magnifying-glass'], [3, 'Resolvida', 'fa-circle-check']];
?>
<ol class="etapas" aria-label="Andamento: <?= htmlspecialchars($d['status'] ?? 'Pendente') ?>">
    <?php foreach ($_etapas as [$_n, $_rot, $_ic]):
        $_classe = $_nivel >= $_n ? 'feita' : ($_nivel + 0.5 >= $_n ? 'atual' : '');
        if ($_n === 3 && $_st === 'aguardando confirmação') { $_classe = 'atual'; $_rot = 'Confirmar'; }
    ?>
        <li class="etapa <?= $_classe ?>"><span class="etapa-ponto"><i class="fa-solid <?= $_ic ?>"></i></span><span class="etapa-rotulo"><?= $_rot ?></span></li>
    <?php endforeach; ?>
</ol>
<?php endif; ?>
