<?php
/** Testes de regressão sem tocar no banco real: copia projeto para diretório temporário. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$raiz = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/olho_smoke_' . bin2hex(random_bytes(6));
function copiar(string $origem, string $destino): void {
    if (is_dir($origem)) {
        mkdir($destino, 0700, true);
        foreach (scandir($origem) as $nome) {
            if ($nome === '.' || $nome === '..' || in_array($nome,['backups','logs','uploads','vendor'],true)) continue;
            copiar($origem.'/'.$nome, $destino.'/'.$nome);
        }
    } elseif (is_file($origem)) {
        if (str_ends_with($origem,'config.local.php')) return; // não copiar senhas para o sandbox
        copy($origem,$destino);
    }
}
function limpar(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $nome) {
        if ($nome === '.' || $nome === '..') continue;
        $p = $dir.'/'.$nome;
        if (is_dir($p)) limpar($p); else @unlink($p);
    }
    @rmdir($dir);
}
try {
    foreach (['app','storage','tests'] as $pasta) copiar($raiz.'/'.$pasta,$temp.'/'.$pasta);
    file_put_contents($temp.'/tests/.sandbox_test_enabled','1');
    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg($temp.'/tests/runner_interno.php');
    passthru($cmd, $codigo);
    exit($codigo);
} finally { limpar($temp); }
