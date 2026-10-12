<?php
/**
 * Restaura um backup diário.
 *   php tools/restaurar_backup.php 2026-10-08              (só mostra o que faria)
 *   php tools/restaurar_backup.php 2026-10-08 --confirmar  (restaura de verdade)
 *
 * Antes de restaurar, faz automaticamente um backup do estado atual
 * (pasta AAAA-MM-DD_antes-restauracao-HHMMSS), para dar para voltar atrás.
 * Funciona nos modos JSON e MySQL, inclusive restaurar um backup JSON no MySQL.
 * Chaves (.cpf.key, .totp.key, integridade.key) não fazem parte do backup:
 * restaure no mesmo servidor ou copie as chaves guardadas antes.
 */
require __DIR__ . '/_bootstrap_cli.php';
$data = $argv[1] ?? '';
$confirmar = in_array('--confirmar', $argv, true);
if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:_antes-restauracao-\d{6})?$/D', $data)) {
    fwrite(STDERR, "Uso: php tools/restaurar_backup.php AAAA-MM-DD [--confirmar]\n"); exit(1);
}
$pasta = BackupDiario::raiz() . '/' . $data;
$problemas = BackupDiario::verificar($pasta);
if ($problemas) { fwrite(STDERR, "Backup não pode ser restaurado:\n - " . implode("\n - ", $problemas) . PHP_EOL); exit(1); }

$permitidas = ['users', 'denuncias', 'notificacoes', 'auditoria', 'login_attempts', 'rate_limits'];
$dados = [];
foreach ($permitidas as $c) {
    if (!is_file("$pasta/$c.json")) continue;
    $conteudo = json_decode((string) file_get_contents("$pasta/$c.json"), true);
    if (!is_array($conteudo)) { fwrite(STDERR, "Arquivo $c.json inválido.\n"); exit(1); }
    $dados[$c] = $conteudo;
}
if (!isset($dados['users'], $dados['denuncias'])) { fwrite(STDERR, "Backup sem users.json/denuncias.json.\n"); exit(1); }
$integridade = is_file("$pasta/integridade.json") ? json_decode((string) file_get_contents("$pasta/integridade.json"), true) : null;
$fotos = glob("$pasta/uploads/*") ?: [];

echo "Backup $data íntegro. Conteúdo:\n";
foreach ($dados as $c => $lista) echo "  - $c: " . count($lista) . " registro(s)\n";
echo '  - fotos: ' . count($fotos) . "\n";
echo '  - destino: ' . (MySqlStore::ativo() ? 'banco MySQL ' . DB_NAME : 'arquivos JSON em storage/') . "\n";
if (!$confirmar) { echo "\nNada foi alterado. Para restaurar, rode de novo com --confirmar\n"; exit(0); }

try {
    $seguranca = BackupDiario::pastaAntesRestauracao();
    BackupDiario::criar($seguranca, basename($seguranca));
    echo "Cópia do estado atual salva em " . basename($seguranca) . "\n";

    $storage = __DIR__ . '/../storage';
    foreach ($dados as $c => $lista) {
        ArquivoLock::executar($c, static function () use ($c, $lista, $storage) {
            if (MySqlStore::ativo() && in_array($c, ['users', 'denuncias', 'notificacoes'], true)) {
                // Limpa antes para não esbarrar em e-mail/CPF únicos trocados entre IDs.
                Database::getConnection()->exec('DELETE FROM `' . $c . '`');
            }
            GravacaoSegura::json($storage . '/' . $c . '.json', $lista);
        });
    }
    // Âncoras dos selos voltam junto. Se o backup não tinha nenhuma (sistema ainda
    // sem denúncias), zera as atuais para não acusar "denúncia apagada" à toa.
    if (!is_array($integridade)) $integridade = ['atualizado_em' => date('Y-m-d H:i:s'), 'ancoras' => []];
    if (MySqlStore::ativo()) MySqlStore::gravarConfiguracao('integridade', $integridade);
    else file_put_contents($storage . '/integridade.json', json_encode($integridade, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    if (!is_dir($storage . '/uploads')) mkdir($storage . '/uploads', 0700, true);
    foreach ($fotos as $foto) {
        if (!preg_match('/^[a-zA-Z0-9_-]+\.(jpg|png|webp)$/', basename($foto))) continue;
        copy($foto, $storage . '/uploads/' . basename($foto));
    }
    echo "Restauração concluída.\n";
} catch (Throwable $e) {
    error_log('[RESTAURACAO] ' . (string) $e);
    fwrite(STDERR, 'Erro na restauração: ' . $e->getMessage() . "\nO backup do estado anterior continua disponível.\n");
    exit(1);
}
