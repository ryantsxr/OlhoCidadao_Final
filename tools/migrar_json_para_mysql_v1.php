<?php
/**
 * Migração v1 (CLI apenas): preserva IDs, hashes, preferências, histórico,
 * recursos, fotos (paths), permissões, sessões invalidadas e notificações.
 * NÃO escreve na origem JSON. Destino deve estar vazio.
 * Uso: php tools/migrar_json_para_mysql_v1.php --conferir
 *      php tools/migrar_json_para_mysql_v1.php --importar
 * Exige SQL schema_v1_mysql.sql instalado e credenciais DB_* no config.local.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Disponível apenas na linha de comando.'); }
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/MySqlStore.php';
require __DIR__ . '/../app/Core/PrivacidadeCpf.php';

$importar = in_array('--importar', $argv, true);
$conferir = in_array('--conferir', $argv, true);
if (!$importar && !$conferir) { fwrite(STDERR, "Use --conferir ou --importar.\n"); exit(2); }
$origem = realpath(__DIR__ . '/../storage');
if (!$origem) { fwrite(STDERR, "Pasta storage não encontrada.\n"); exit(2); }

$colecoes = ['users','denuncias','notificacoes','auditoria','login_attempts','rate_limits'];
$dados = [];
try {
    foreach ($colecoes as $colecao) {
        $arq = $origem . '/' . $colecao . '.json';
        if (!is_file($arq)) throw new RuntimeException('Arquivo ausente: ' . basename($arq));
        $dados[$colecao] = json_decode((string)file_get_contents($arq), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($dados[$colecao])) throw new RuntimeException('JSON não é lista/objeto: ' . basename($arq));
    }
    $ids = [];
    $emails = [];
    foreach ($dados['users'] as &$u) {
        $id = (int)($u['id'] ?? 0);
        if ($id < 1 || isset($ids[$id])) throw new RuntimeException('ID de usuário duplicado/inválido');
        $ids[$id] = true;
        $email = strtolower(trim((string)($u['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($emails[$email])) throw new RuntimeException('E-mail duplicado/inválido em users.json');
        $emails[$email] = true;
        if (!empty($u['cpf_cifrado']) && !is_file($origem . '/.cpf.key')) {
            throw new RuntimeException('CPF cifrado sem storage/.cpf.key. Restaure a chave da instalação original.');
        }
        if (array_key_exists('cpf', $u)) {
            // Alguns pacotes antigos persistiram cpf em texto puro.
            if (empty($u['cpf_cifrado'])) PrivacidadeCpf::prepararRegistro($u, (string)$u['cpf']);
            else unset($u['cpf']);
        }
    }
    unset($u);
    $ids = [];
    foreach ($dados['denuncias'] as $d) {
        $id = (int)($d['id'] ?? 0);
        if ($id < 1 || isset($ids[$id])) throw new RuntimeException('ID de denúncia duplicado/inválido');
        $ids[$id] = true;
    }
    $ancora = null;
    if (is_file($origem . '/integridade.json')) {
        $ancora = json_decode((string)file_get_contents($origem . '/integridade.json'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($ancora)) throw new RuntimeException('Âncoras de integridade inválidas');
        if (!is_file($origem . '/integridade.key')) throw new RuntimeException('Chave do histórico ausente; restaure integridade.key.');
    }
    echo "Conferência JSON original (sem alterar arquivos):\n";
    foreach ($colecoes as $nome) echo $nome . ': ' . count($dados[$nome]) . " registros\n";
    if (!$importar) { echo "Conferência básica OK. Para importar, execute --importar com o esquema no banco.\n"; exit(0); }
    $db = Database::getConnection();
    foreach (['users','denuncias','notificacoes','auditoria','login_attempts','rate_limits'] as $tabela) {
        $cols = $db->query('SHOW COLUMNS FROM `' . $tabela . '`')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('payload', $cols, true)) throw new RuntimeException('Esquema incompatível em ' . $tabela . '. Use um banco NOVO com sql/schema.sql.');
    }
    foreach ($colecoes as $colecao) {
        $n = (int)$db->query('SELECT COUNT(*) FROM `' . $colecao . '`')->fetchColumn();
        if ($n !== 0) throw new RuntimeException('Destino não está vazio: ' . $colecao . ' (' . $n . '). Nada importado.');
    }
    $n = (int)$db->query('SELECT COUNT(*) FROM app_settings')->fetchColumn();
    if ($n !== 0) throw new RuntimeException('Configurações já presentes: banco deve estar vazio.');
    $db->beginTransaction();
    try {
        // Primeiro usuários, depois denúncias por causa dos relacionamentos.
        foreach ($colecoes as $colecao) {
            MySqlStore::gravar($origem . '/' . $colecao . '.json', $dados[$colecao]);
        }
        if ($ancora !== null) MySqlStore::gravarConfiguracao('integridade', $ancora);
        foreach ($colecoes as $colecao) {
            $lidos = MySqlStore::ler($origem . '/' . $colecao . '.json');
            if (count($lidos) !== count($dados[$colecao])) throw new RuntimeException('Divergência de quantidade em ' . $colecao);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    echo "Importação MySQL concluída. Originais JSON preservados.\n";
    echo "PRÓXIMO: ativar DB_DRIVER=mysql no config.local.php e executar tools/testar_mysql_v1.php.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FALHA: ' . $e->getMessage() . "\nBanco não deve ser ativado até resolver a falha.\n");
    exit(1);
}
