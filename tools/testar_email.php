<?php
/**
 * Teste rápido do envio de e-mail (Gmail/SMTP) — rode no terminal:
 *
 *     php tools/testar_email.php seuemail@gmail.com
 *
 * Se algo estiver errado (senha de app, porta, certificado), a mensagem do
 * servidor aparece logo abaixo do "FALHOU".
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute este arquivo pelo terminal.');
}

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/Core/Mailer.php';
require __DIR__ . '/../app/Core/Ambiente.php';

$para = trim((string) ($argv[1] ?? ''));
if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php tools/testar_email.php seuemail@gmail.com\n");
    exit(1);
}

if (!Mailer::smtpConfigurado()) {
    fwrite(STDERR, "O SMTP ainda não está configurado.\nCopie app/config.local.exemplo.php para app/config.local.php e preencha SMTP_USER e SMTP_PASS.\n");
    exit(1);
}

echo 'Enviando para ' . $para . ' via ' . SMTP_HOST . ':' . SMTP_PORT . " ...\n";

$paragrafos = [
    'Se você está lendo isto, o envio de e-mail do Olho Cidadão está funcionando.',
    'A redefinição de senha e os avisos de denúncia vão chegar por aqui.',
];
$texto = "Olá!\n\n" . implode("\n\n", $paragrafos) . "\n\n— Olho Cidadão";
$html = Mailer::modelo('Teste de e-mail', 'Olá!', $paragrafos, 'Abrir o sistema', Mailer::url('/?rota=login'));

$ok = Mailer::enviar($para, 'Teste', 'Teste de e-mail - Olho Cidadão', $texto, $html);

if ($ok) {
    echo "OK! E-mail enviado. Confira a caixa de entrada (e o spam).\n";
    exit(0);
}

echo "FALHOU. O motivo aparece na linha [Mailer] acima (log do PHP).\n";
echo "Causas comuns: senha de app errada ou sem verificação em duas etapas; SMTP_SECURE/porta trocados (587 com 'tls' ou 465 com 'ssl'); erro de certificado no XAMPP (use SMTP_VERIFY_SSL = false só no seu computador).\n";
exit(1);
