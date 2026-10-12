<?php
/**
 * Etapa 6: diagnóstico SOMENTE LEITURA da conexão MySQL usada no site.
 * Uso no Windows: C:\xampp\php\php.exe tools\diagnosticar_mysql_etapa6.php
 * Não cria, não altera e não exclui nenhum registro.
 * Não exibe senhas, hashes, CPF, segredos ou configurações privadas.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Somente linha de comando.'); }
$raiz = dirname(__DIR__);
require_once $raiz . '/app/config.php';
require_once $raiz . '/app/Core/Database.php';
if (!defined('DB_DRIVER') || DB_DRIVER !== 'mysql') {
    fwrite(STDERR, "FALHA: DB_DRIVER não está em mysql.\n"); exit(2);
}
if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "FALHA: a extensão pdo_mysql não está habilitada neste PHP.\n"); exit(2);
}
$falhas = 0;
function validarEtapa6(bool $ok, string $mensagem): void {
    global $falhas;
    echo ($ok ? '[OK] ' : '[FALHA] ') . $mensagem . PHP_EOL;
    if (!$ok) $falhas++;
}
try {
    $db = Database::getConnection();
    echo "Diagnóstico MySQL Etapa 6 — somente leitura\n";
    $porta = (int) $db->query('SELECT @@port')->fetchColumn();
    $banco = (string) $db->query('SELECT DATABASE()')->fetchColumn();
    echo 'Banco: ' . $banco . ' | Porta: ' . $porta . PHP_EOL;
    validarEtapa6($banco === DB_NAME, 'Conectado ao banco esperado');
    validarEtapa6($porta === (defined('DB_PORT') ? (int)DB_PORT : 3306), 'Porta do servidor corresponde à configuração');
    $esperadas = ['app_locks','app_settings','users','denuncias','denuncia_historico','denuncia_apoios','notificacoes','auditoria','login_attempts','rate_limits'];
    $lista = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $faltantes = array_diff($esperadas,$lista);
    validarEtapa6(!$faltantes, 'As dez tabelas principais existem' . ($faltantes ? ': faltam ' . implode(', ', $faltantes) : ''));
    if (!$faltantes) {
        $admins = $db->query("SELECT email, role, ativo, payload FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_ASSOC);
        validarEtapa6(count($admins)>=1, 'Existe pelo menos uma conta administrativa');
        $adminOriginal = false;
        foreach ($admins as $admin) {
            if (strcasecmp((string)$admin['email'],'admin@olhocidadao.com')!==0) continue;
            $payload = json_decode((string)$admin['payload'],true);
            $adminOriginal = is_array($payload) && !empty($payload['senha']) && password_get_info((string)$payload['senha'])['algo'] !== null && !empty($admin['ativo']);
        }
        validarEtapa6($adminOriginal, 'Administrador original ativo com hash de senha armazenado');
        $usuarios = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $denuncias = (int)$db->query('SELECT COUNT(*) FROM denuncias')->fetchColumn();
        echo "Registros atuais: {$usuarios} usuários e {$denuncias} denúncias.\n";
        $invalidos = 0;
        foreach (['users','denuncias','notificacoes','auditoria'] as $tabela) {
            $stmt = $db->query("SELECT payload FROM `{$tabela}`");
            while (($payload = $stmt->fetchColumn()) !== false) {
                if (!is_array(json_decode((string)$payload,true))) $invalidos++;
            }
        }
        validarEtapa6($invalidos===0, 'Payload JSON válido em usuários, denúncias, notificações e auditoria');
        $orfaoHistorico = (int)$db->query('SELECT COUNT(*) FROM denuncia_historico h LEFT JOIN denuncias d ON d.id=h.denuncia_id WHERE d.id IS NULL')->fetchColumn();
        $orfaoApoio = (int)$db->query('SELECT COUNT(*) FROM denuncia_apoios a LEFT JOIN denuncias d ON d.id=a.denuncia_id WHERE d.id IS NULL')->fetchColumn();
        validarEtapa6($orfaoHistorico===0 && $orfaoApoio===0, 'Nenhum histórico ou apoio órfão');
    }
    echo $falhas===0 ? "RESULTADO: verificações aprovadas.\n" : "RESULTADO: {$falhas} falha(s).\n";
    exit($falhas===0?0:1);
} catch (Throwable $e) {
    // Não revelar DSN, credenciais nem stack trace em logs compartilhados.
    fwrite(STDERR, 'FALHA: conexão ou consulta não concluída ('.get_class($e).'). Confira serviço MySQL, porta, banco e esquema. '.PHP_EOL);
    exit(2);
}
