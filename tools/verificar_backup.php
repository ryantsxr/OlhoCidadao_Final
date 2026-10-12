<?php
/** Uso: php tools/verificar_backup.php 2026-10-08   (sem data = hoje; "todos" = todos) */
require __DIR__ . '/_bootstrap_cli.php';
$arg = $argv[1] ?? date('Y-m-d');
$pastas = $arg === 'todos'
    ? (glob(BackupDiario::raiz() . '/*', GLOB_ONLYDIR) ?: [])
    : [BackupDiario::raiz() . '/' . $arg];
if ($arg !== 'todos' && !preg_match('/^\d{4}-\d{2}-\d{2}(?:_antes-restauracao-\d{6})?$/D', $arg)) { fwrite(STDERR, "Data inválida. Use AAAA-MM-DD.\n"); exit(1); }
$erro = false;
foreach ($pastas as $pasta) {
    $problemas = BackupDiario::verificar($pasta);
    if ($problemas) {
        $erro = true;
        fwrite(STDERR, basename($pasta) . ": PROBLEMA\n - " . implode("\n - ", $problemas) . PHP_EOL);
    } else {
        $m = json_decode((string) file_get_contents($pasta . '/manifesto.json'), true);
        echo basename($pasta) . ': OK (' . count($m['arquivos']) . ' arquivos conferidos por SHA-256, modo ' . ($m['modo'] ?? 'json') . ")\n";
    }
}
if (!$pastas) echo "Nenhum backup encontrado.\n";
exit($erro ? 1 : 0);
