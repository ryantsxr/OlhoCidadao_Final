<?php
/**
 * Backup manual ou agendado (funciona em JSON e em MySQL).
 *   php tools/backup_diario.php
 * No Windows dá para agendar no "Agendador de Tarefas" chamando
 *   C:\xampp\php\php.exe C:\xampp\htdocs\OlhoCidadao\tools\backup_diario.php
 */
require __DIR__ . '/_bootstrap_cli.php';
try {
    $migrados = PrivacidadeCpf::migrarExistentes();
    $novo = BackupDiario::executar();
    $pasta = BackupDiario::raiz() . '/' . date('Y-m-d');
    $problemas = BackupDiario::verificar($pasta);
    if ($problemas) { fwrite(STDERR, "Backup com problema:\n - " . implode("\n - ", $problemas) . PHP_EOL); exit(1); }
    echo 'Modo: ' . (MySqlStore::ativo() ? 'MySQL' : 'JSON') . '; CPF(s) migrados: ' . $migrados
        . '; backup de hoje ' . ($novo ? 'criado' : 'já existia') . ' e conferido em ' . realpath($pasta) . PHP_EOL;
    echo 'Lembrete: copie também storage/.cpf.key, storage/.totp.key e storage/integridade.key para um lugar seguro FORA do servidor.' . PHP_EOL;
} catch (Throwable $e) {
    error_log('[BACKUP] ' . (string) $e);
    fwrite(STDERR, 'Erro no backup: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
