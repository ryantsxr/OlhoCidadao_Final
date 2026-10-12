<?php
/**
 * Cria a conta de ADMINISTRADOR numa instalação nova.
 *   php tools/criar_admin.php "Nome do Admin" admin@seudominio.com
 *
 * A senha é pedida no terminal (não fica no histórico de comandos) e precisa
 * seguir a política do sistema (8 a 72 caracteres, sem senhas comuns).
 * Depois de entrar no painel, ative a verificação em duas etapas.
 */
require __DIR__ . '/_bootstrap_cli.php';
if (!defined('BASE_URL')) define('BASE_URL', '');

$nome = trim((string) ($argv[1] ?? ''));
$email = strtolower(trim((string) ($argv[2] ?? '')));
if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php tools/criar_admin.php \"Nome do Admin\" email@dominio.com\n");
    exit(1);
}

$usuarios = new User;
if ($usuarios->findByEmail($email)) {
    fwrite(STDERR, "Já existe uma conta com esse e-mail.\n");
    exit(1);
}

echo "Senha do administrador: ";
$senha = rtrim((string) fgets(STDIN), "\r\n");
echo "Repita a senha: ";
$repetida = rtrim((string) fgets(STDIN), "\r\n");
if (!hash_equals($senha, $repetida)) {
    fwrite(STDERR, "As senhas não são iguais.\n");
    exit(1);
}
if (($erro = PoliticaSenha::erro($senha)) !== null) {
    fwrite(STDERR, $erro . "\n");
    exit(1);
}

$conta = $usuarios->create(['nome' => $nome, 'email' => $email, 'senha' => $senha, 'role' => 'admin']);
echo "Administrador criado: {$email} (conta #" . (int) ($conta['id'] ?? 0) . ").\n";
echo "Entre em ?rota=login e ative a verificação em duas etapas em Configurações.\n";
