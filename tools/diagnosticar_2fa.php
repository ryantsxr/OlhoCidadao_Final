<?php
/**
 * Descobre por que o código da 2FA está sendo recusado (não altera nada).
 *   php tools/diagnosticar_2fa.php admin@olhocidadao.com
 * Ele pede o código que está aparecendo AGORA no Google Authenticator e diz se
 * o problema é o relógio do computador, uma conta errada no app, ou a chave.
 */
require __DIR__ . '/_bootstrap_cli.php';
$email = strtolower(trim($argv[1] ?? ''));
if ($email === '') { fwrite(STDERR, "Uso: php tools/diagnosticar_2fa.php email-do-admin\n"); exit(1); }
$conta = null;
foreach (LeituraJson::array(__DIR__ . '/../storage/users.json') as $u) {
    if (strtolower((string)($u['email'] ?? '')) === $email) { $conta = $u; break; }
}
if (!$conta) { echo "Conta não encontrada.\n"; exit(1); }
echo "Conta #{$conta['id']} ({$conta['role']})\n";
if (empty($conta['totp_ativo'])) { echo "Essa conta NÃO está com 2FA ativa.\n"; exit(0); }
echo 'Códigos de recuperação restantes: ' . count($conta['totp_backups'] ?? []) . "\n";
echo 'Hora do servidor (UTC): ' . gmdate('d/m/Y H:i:s') . '  |  fuso do sistema: ' . date_default_timezone_get() . ' ' . date('H:i:s') . "\n";
try {
    $segredo = Totp::decifrar((string) $conta['totp_segredo']);
    echo "Chave .totp.key: OK (o segredo abriu normalmente)\n";
} catch (Throwable $e) {
    echo "Chave .totp.key: PROBLEMA — " . $e->getMessage() . "\nUse um código de recuperação para entrar.\n";
    exit(1);
}
$passoAgora = intdiv(time(), 30);
$ultimo = (int)($conta['totp_ultimo_passo'] ?? -1);
if ($ultimo >= $passoAgora) {
    echo "Atenção: o último código aceito é deste mesmo intervalo de 30 s (ou de um futuro). Espere o próximo código.\n";
}
echo "\nDigite o código de 6 dígitos que aparece AGORA no aplicativo: ";
$codigo = trim((string) fgets(STDIN));
if (!preg_match('/^\d{6}$/', $codigo)) { echo "Código precisa ter 6 dígitos.\n"; exit(1); }
for ($d = 0; $d <= 120; $d++) {   // procura até 1 hora para frente e para trás
    foreach (array_unique([$d, -$d]) as $desvio) {
        if (hash_equals(Totp::codigo($segredo, $passoAgora + $desvio), $codigo)) {
            $seg = $desvio * 30;
            if (abs($desvio) <= 1) {
                echo "\nO código CONFERE com o relógio. " . ($passoAgora + $desvio <= $ultimo
                    ? "Mas esse código já foi usado — espere o próximo (30 s) e tente de novo.\n"
                    : "Tente entrar de novo; se falhar, apague o cache do navegador.\n");
            } else {
                echo "\nO código confere, mas o RELÓGIO do computador está " . ($seg > 0 ? 'atrasado' : 'adiantado') . ' cerca de ' . abs($seg) . " segundos em relação ao celular.\n"
                   . "Corrija: Configurações do Windows > Hora e idioma > Data e hora > \"Sincronizar agora\".\n";
            }
            exit(0);
        }
    }
}
echo "\nO código NÃO bate com essa conta nem com 1 hora de diferença no relógio.\n"
   . "Provável: o app tem uma entrada ANTIGA do Olho Cidadão (de antes de reativar a 2FA).\n"
   . "Apague as entradas antigas no app, entre com um código de recuperação, desative e ative a 2FA de novo lendo o QR code novo.\n";
exit(1);
