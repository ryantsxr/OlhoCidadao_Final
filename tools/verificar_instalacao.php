<?php
/**
 * Confere se o ambiente está pronto e seguro.
 *   php tools/verificar_instalacao.php                      (verificação local)
 *   php tools/verificar_instalacao.php https://meusite.com.br/OlhoCidadao
 *       (também testa, pela internet, se arquivos internos estão bloqueados)
 */
require __DIR__ . '/_bootstrap_cli.php';
$problemas = 0;
function verificar(bool $ok, string $rotulo, string $dica = ''): void {
    global $problemas;
    echo ($ok ? '[OK]       ' : '[ATENÇÃO]  ') . $rotulo . (!$ok && $dica !== '' ? PHP_EOL . '           → ' . $dica : '') . PHP_EOL;
    if (!$ok) $problemas++;
}
$raiz = dirname(__DIR__);
echo "== PHP\n";
verificar(PHP_VERSION_ID >= 80100, 'PHP ' . PHP_VERSION . ' (8.1 ou mais novo)', 'Atualize o XAMPP/servidor.');
foreach (['openssl', 'fileinfo', 'json', 'mbstring'] as $ext) verificar(extension_loaded($ext), 'Extensão ' . $ext, "Habilite extension=$ext no php.ini.");
verificar(Uploader::gdDisponivel('image/jpeg') && Uploader::gdDisponivel('image/png'), 'Extensão GD (apaga o GPS/EXIF das fotos)',
    'Sem GD as fotos ainda perdem o GPS/EXIF, mas não são reduzidas. No php.ini troque ";extension=gd" por "extension=gd" e reinicie o Apache.');
verificar(defined('PASSWORD_ARGON2ID'), 'Argon2id disponível para as senhas', 'Sem ele o sistema usa bcrypt (também seguro).');

echo "\n== Pastas e chaves\n";
$dir = $raiz . '/storage';
verificar(is_dir($dir) && is_writable($dir), 'storage/ gravável');
if (!is_dir($dir . '/uploads')) @mkdir($dir . '/uploads', 0750, true);
verificar(is_dir($dir . '/uploads') && is_writable($dir . '/uploads'), 'storage/uploads gravável');
verificar(is_file($dir . '/.htaccess'), 'storage/.htaccess presente (bloqueia o navegador)');
verificar(is_file($raiz . '/.htaccess') && is_file($raiz . '/public/.htaccess'), '.htaccess da raiz e de public/ presentes');
verificar(is_file($raiz . '/public/vendor/leaflet/leaflet.js') && is_file($raiz . '/public/vendor/fontawesome/css/all.min.css'),
    'Bibliotecas locais em public/vendor (funciona sem internet)');
foreach (['.cpf.key' => 'CPF', '.totp.key' => '2FA', 'integridade.key' => 'selos do histórico'] as $arq => $uso) {
    if (is_file($dir . '/' . $arq)) {
        $perm = fileperms($dir . '/' . $arq) & 0777;
        verificar(DIRECTORY_SEPARATOR === '\\' || ($perm & 0007) === 0, "Chave $arq ($uso) sem leitura para outros usuários",
            "chmod 600 storage/$arq");
    }
}
echo "           (Guarde cópia das chaves FORA do servidor: sem elas CPFs e 2FA não podem ser recuperados.)\n";

echo "\n== Banco de dados\n";
if (DB_DRIVER === 'mysql') {
    verificar(extension_loaded('pdo_mysql'), 'Driver PDO MySQL habilitado');
    if (extension_loaded('pdo_mysql')) {
        try {
            $db = Database::getConnection();
            verificar((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0, 'Usuários MySQL acessíveis');
            verificar((int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0, 'Pelo menos 1 administrador');
        } catch (Throwable $e) { verificar(false, 'Conexão ou esquema MySQL', $e->getMessage()); }
    }
    verificar(DB_USER !== 'root' && DB_PASS !== '', 'Banco com usuário próprio e senha (não "root")',
        'Rode sql/criar_usuario_mysql.sql (troque a senha) e ajuste DB_USER/DB_PASS no config.local.php. No localhost é só um aviso.');
} else {
    verificar(false, 'Modo JSON (apenas para testes)', 'Use DB_DRIVER mysql em uso real.');
}

echo "\n== Backup\n";
$pastas = array_values(array_filter(glob(BackupDiario::raiz() . '/*', GLOB_ONLYDIR) ?: [], static fn($p) => preg_match('/^\d{4}-\d{2}-\d{2}$/', basename($p))));
rsort($pastas);
verificar($pastas && !BackupDiario::verificar($pastas[0]), 'Backup mais recente íntegro' . ($pastas ? ' (' . basename($pastas[0]) . ')' : ''),
    'Ainda não há backup: ele é feito na primeira visita do dia ou com php tools/backup_diario.php.');
verificar(BackupDiario::ultimaFalha() === null, 'Sem falha recente de backup', (string) BackupDiario::ultimaFalha());

echo "\n== Uso público (necessário só quando for para a internet)\n";
verificar(APP_URL !== '', 'APP_URL configurada', 'Ex.: https://meusite.com.br/OlhoCidadao/public');
verificar(str_starts_with((string) APP_URL, 'https://'), 'APP_URL com https:// (liga redirecionamento HTTPS e HSTS)');
verificar(SMTP_HOST !== '', 'SMTP configurado (recuperação de senha e avisos de segurança)');
verificar(LGPD_CONTATO !== '', 'Contato de privacidade/segurança (LGPD_CONTATO)', 'Aparece nos Termos e em /.well-known/security.txt');

$base = $argv[1] ?? '';
if ($base !== '' && preg_match('#^https?://#', $base)) {
    echo "\n== Teste pela internet em $base\n";
    $base = rtrim($base, '/');
    foreach (['storage/users.json', 'storage/.cpf.key', 'app/config.php', 'sql/schema_v1_mysql.sql', 'README.md', '.git/HEAD'] as $alvo) {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true, 'timeout' => 8, 'follow_location' => 0]]);
        $corpo = @file_get_contents($base . '/' . $alvo, false, $ctx);
        $status = 0;
        foreach (($http_response_header ?? []) as $h) if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $status = (int) $m[1];
        $vazou = $status === 200 && $corpo !== false && !str_contains((string) $corpo, '<html');
        verificar(!$vazou, "/$alvo bloqueado (HTTP $status)", 'Esse arquivo está público! Aponte o domínio para public/ ou ative o AllowOverride All.');
    }
}

echo PHP_EOL . ($problemas ? "$problemas ponto(s) para revisar." : 'Tudo certo.') . PHP_EOL;
exit($problemas ? 1 : 0);
