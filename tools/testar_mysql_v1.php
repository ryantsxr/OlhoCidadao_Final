<?php
/** Teste somente-leitura da configuração MySQL; executar após a importação. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/MySqlStore.php';
if (!MySqlStore::ativo()) { fwrite(STDERR, "Configure DB_DRIVER=mysql antes do teste.\n"); exit(2); }
try {
    $db = Database::getConnection();
    foreach (['users','denuncias','denuncia_historico','denuncia_apoios','notificacoes','auditoria','login_attempts','rate_limits','app_settings'] as $tabela) {
        $quantidade = $db->query('SELECT COUNT(*) FROM `' . $tabela . '`')->fetchColumn();
        echo $tabela . ': ' . $quantidade . "\n";
    }
    $users = MySqlStore::ler(__DIR__ . '/../storage/users.json');
    $admins = array_filter($users, static fn($u) => ($u['role'] ?? '') === 'admin');
    if (!$admins) throw new RuntimeException('Nenhum administrador encontrado.');
    foreach ($admins as $a) {
        if (!str_starts_with((string)$a['senha'], '$2') && !str_starts_with((string)$a['senha'], '$argon')) throw new RuntimeException('Hash de senha de admin inválido.');
    }
    echo 'Leitura MySQL e hashes administrativos: OK' . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha: ' . $e->getMessage() . "\n"); exit(1);
}
