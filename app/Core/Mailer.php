<?php
/**
 * Envio de e-mail do Olho Cidadão (redefinição de senha, avisos de status, etc.).
 *
 * Três modos, escolhidos automaticamente:
 *   1) SMTP configurado (SMTP_HOST preenchido) + PHPMailer instalado → usa o PHPMailer.
 *   2) SMTP configurado, sem PHPMailer → usa o cliente SMTP nativo desta classe
 *      (funciona com Gmail sem instalar nada com Composer).
 *   3) SMTP não configurado → "modo de teste": tenta o mail() do PHP e, na
 *      redefinição de senha, mostra o link na tela (só nesse modo).
 *
 * Nenhum envio pode quebrar a página: toda falha é registrada em error_log()
 * e a função devolve false.
 */
class Mailer {

    // ------------------------------------------------------------------
    // API pública
    // ------------------------------------------------------------------

    /**
     * Envia um e-mail. $corpo é o texto puro; $html (opcional) é a versão
     * bonita — quando existe, o e-mail vai nos dois formatos.
     */
    public static function enviar(string $paraEmail, string $paraNome, string $assunto, string $corpo, ?string $html = null): bool {
        $paraEmail = self::limpar($paraEmail);
        $paraNome  = self::limpar($paraNome);
        $assunto   = self::limpar($assunto);

        if (!filter_var($paraEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (self::smtpConfigurado()) {
            $seguranca = defined('SMTP_SECURE') ? strtolower(trim((string)SMTP_SECURE)) : 'tls';
            if (!in_array($seguranca, ['tls','ssl'], true)) {
                error_log('[Mailer] SMTP exige TLS ou SSL. Envio bloqueado.');
                return false;
            }
            if (self::phpMailerDisponivel()) {
                return self::enviarViaPhpMailer($paraEmail, $paraNome, $assunto, $corpo, $html);
            }
            return self::enviarViaSmtpNativo($paraEmail, $paraNome, $assunto, $corpo, $html);
        }

        return self::enviarViaMailNativo($paraEmail, $assunto, $corpo);
    }

    /**
     * Aviso "não essencial" para um usuário (status da denúncia mudou, protocolo
     * registrado, boas-vindas...). Só sai se o SMTP estiver configurado — assim
     * ninguém fica esperando o mail() do PHP no ambiente local — e NUNCA lança erro.
     *
     * @param array<string,mixed> $usuario  registro do usuário (precisa de 'email' e 'nome')
     * @param string[]            $paragrafos
     */
    public static function avisar(array $usuario, string $assunto, string $titulo, array $paragrafos, ?string $botaoTexto = null, ?string $botaoCaminho = null): void {
        try {
            if (!self::smtpConfigurado()) {
                return;
            }
            $email = (string) ($usuario['email'] ?? '');
            $nome  = (string) ($usuario['nome'] ?? '');
            if ($email === '') {
                return;
            }

            $primeiroNome = explode(' ', trim($nome))[0] ?? '';
            $saudacao = $primeiroNome !== '' ? "Olá, {$primeiroNome}!" : 'Olá!';
            $link = $botaoCaminho !== null ? self::url($botaoCaminho) : null;

            $texto = $saudacao . "\n\n" . implode("\n\n", $paragrafos);
            if ($link !== null) {
                $texto .= "\n\n" . ($botaoTexto ?? 'Abrir') . ': ' . $link;
            }
            $texto .= "\n\n— Olho Cidadão";

            self::enviar($email, $nome, $assunto, $texto, self::modelo($titulo, $saudacao, $paragrafos, $botaoTexto, $link));
        } catch (\Throwable $e) {
            error_log('[Mailer] Falha ao montar aviso: ' . $e->getMessage());
        }
    }

    /** true só se o administrador já preencheu o host SMTP no config. */
    public static function smtpConfigurado(): bool {
        return defined('SMTP_HOST') && trim((string) SMTP_HOST) !== '';
    }

    /**
     * Endereço público do site (sem barra no final), usado nos links dos e-mails.
     *
     * Por segurança o cabeçalho Host da requisição só é aceito em ambiente local
     * (localhost, IP de rede interna ou .local). Em servidor de verdade defina
     * APP_URL no config — senão alguém poderia forjar o Host e fazer o link de
     * redefinição de senha apontar para outro site.
     */
    public static function urlBase(): string {
        if (defined('APP_URL') && trim((string) APP_URL) !== '') {
            return rtrim(trim((string) APP_URL), '/');
        }

        $base = defined('BASE_URL') ? (string) BASE_URL : '';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $semPorta = strtolower((string) preg_replace('/:\d+$/', '', $host));

        $local = in_array($semPorta, ['localhost', '127.0.0.1', '[::1]'], true)
            || preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/', $semPorta) === 1
            || str_ends_with($semPorta, '.local');

        if ($local && preg_match('/^[a-z0-9.\-\[\]:]+$/i', $host) === 1) {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            return ($https ? 'https://' : 'http://') . $host . $base;
        }

        error_log('[Mailer] APP_URL não está configurado: os links dos e-mails ficarão sem o endereço do site. Defina APP_URL em app/config.local.php.');
        return $base;
    }

    /** Monta o link completo a partir de um caminho como "/?rota=login". */
    public static function url(string $caminho): string {
        return self::urlBase() . $caminho;
    }

    /**
     * Modelo HTML simples (estilos inline, funciona em Gmail/Outlook/celular).
     * Tudo que entra aqui é escapado.
     *
     * @param string[] $paragrafos
     */
    public static function modelo(string $titulo, string $saudacao, array $paragrafos, ?string $botaoTexto = null, ?string $botaoLink = null): string {
        $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $corpo = '';
        foreach ($paragrafos as $p) {
            $corpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#344054;">' . $e((string) $p) . '</p>';
        }

        $botao = '';
        if ($botaoTexto !== null && $botaoLink !== null && $botaoLink !== '') {
            $botao = '<p style="margin:24px 0;text-align:center;">'
                . '<a href="' . $e($botaoLink) . '" style="display:inline-block;background:#019B38;color:#ffffff;'
                . 'text-decoration:none;font-weight:600;font-size:15px;padding:14px 28px;border-radius:8px;">'
                . $e($botaoTexto) . '</a></p>'
                . '<p style="margin:0 0 16px;font-size:12px;line-height:1.5;color:#667085;">Se o botão não funcionar, copie e cole este endereço no navegador:<br>'
                . '<span style="word-break:break-all;">' . $e($botaoLink) . '</span></p>';
        }

        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;padding:24px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="background:#181f2c;padding:22px 28px;color:#ffffff;font-size:20px;font-weight:700;">Olho <span style="color:#019B38;">Cidadão</span></td></tr>'
            . '<tr><td style="padding:28px;">'
            . '<h1 style="margin:0 0 18px;font-size:20px;color:#1c2431;">' . $e($titulo) . '</h1>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#344054;">' . $e($saudacao) . '</p>'
            . $corpo . $botao
            . '</td></tr>'
            . '<tr><td style="padding:16px 28px;background:#f9fafb;font-size:12px;color:#667085;">Este é um e-mail automático do Olho Cidadão. Por favor, não responda a esta mensagem.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    // ------------------------------------------------------------------
    // Modo 1: PHPMailer (opcional)
    // ------------------------------------------------------------------

    /** true só se o pacote phpmailer/phpmailer foi instalado via Composer. */
    private static function phpMailerDisponivel(): bool {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        return class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    }

    private static function enviarViaPhpMailer(string $paraEmail, string $paraNome, string $assunto, string $corpo, ?string $html): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->Port       = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
            $mail->SMTPAuth   = self::smtpUsuario() !== '';
            $mail->Username   = self::smtpUsuario();
            $mail->Password   = self::smtpSenha();
            $mail->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE !== '')
                ? SMTP_SECURE
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(self::remetenteEmail(), self::remetenteNome());
            $mail->addAddress($paraEmail, $paraNome);

            $mail->Subject = $assunto;
            if ($html !== null) {
                $mail->isHTML(true);
                $mail->Body    = $html;
                $mail->AltBody = $corpo;
            } else {
                $mail->isHTML(false);
                $mail->Body = $corpo;
            }

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[Mailer] Falha ao enviar via PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Modo 2: cliente SMTP nativo (Gmail e similares, sem dependências)
    // ------------------------------------------------------------------

    private static function enviarViaSmtpNativo(string $paraEmail, string $paraNome, string $assunto, string $texto, ?string $html): bool {
        $host      = trim((string) SMTP_HOST);
        $porta     = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
        $seguranca = defined('SMTP_SECURE') ? strtolower(trim((string) SMTP_SECURE)) : 'tls';
        $usuario   = self::smtpUsuario();
        $senha     = self::smtpSenha();
        $de        = self::remetenteEmail();
        $servidorLocal = Ambiente::local();
        $verificar = !$servidorLocal || !defined('SMTP_VERIFY_SSL') || (bool) SMTP_VERIFY_SSL;

        $contexto = stream_context_create(['ssl' => [
            'verify_peer'       => $verificar,
            'verify_peer_name'  => $verificar,
            'allow_self_signed' => !$verificar,
            'peer_name'         => $host,
        ]]);

        $destino = ($seguranca === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $porta;
        $socket = @stream_socket_client($destino, $codigoErro, $textoErro, 10, STREAM_CLIENT_CONNECT, $contexto);
        if (!$socket) {
            error_log("[Mailer] Não conectou em {$host}:{$porta} ({$codigoErro}) {$textoErro}");
            return false;
        }
        stream_set_timeout($socket, 10);

        try {
            self::esperar($socket, [220]);

            $nomeMaquina = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) gethostname());
            $ehlo = 'EHLO ' . (($nomeMaquina !== '' && $nomeMaquina !== null) ? $nomeMaquina : 'localhost');
            self::comando($socket, $ehlo, [250]);

            if ($seguranca === 'tls') {
                self::comando($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Não foi possível iniciar a conexão segura (TLS). Se estiver no XAMPP, veja SMTP_VERIFY_SSL no config.');
                }
                self::comando($socket, $ehlo, [250]);
            }

            if ($usuario !== '') {
                self::comando($socket, 'AUTH LOGIN', [334]);
                self::comando($socket, base64_encode($usuario), [334]);
                self::comando($socket, base64_encode($senha), [235]);
            }

            self::comando($socket, 'MAIL FROM:<' . $de . '>', [250]);
            self::comando($socket, 'RCPT TO:<' . $paraEmail . '>', [250, 251]);
            self::comando($socket, 'DATA', [354]);

            $mensagem = self::montarMensagem($de, $paraEmail, $paraNome, $assunto, $texto, $html);
            fwrite($socket, $mensagem . "\r\n.\r\n");
            self::esperar($socket, [250]);

            try {
                self::comando($socket, 'QUIT', [221]);
            } catch (\Throwable $e) {
                // O e-mail já foi aceito; falhar ao fechar a conversa não importa.
            }
            return true;
        } catch (\Throwable $e) {
            // A mensagem vem do servidor SMTP (nunca inclui a senha) — fica só no log do servidor.
            error_log('[Mailer] Falha ao enviar via SMTP: ' . $e->getMessage());
            return false;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /** Envia uma linha de comando e confere o código de resposta. */
    private static function comando($socket, string $linha, array $codigosAceitos): string {
        fwrite($socket, $linha . "\r\n");
        return self::esperar($socket, $codigosAceitos);
    }

    /** Lê a resposta do servidor (que pode ter várias linhas) e confere o código. */
    private static function esperar($socket, array $codigosAceitos): string {
        $resposta = '';
        while (($linha = fgets($socket, 515)) !== false) {
            $resposta .= $linha;
            // Linha final de uma resposta: "250 texto" (o 4º caractere é espaço). "250-texto" continua.
            if (strlen($linha) < 4 || $linha[3] === ' ') {
                break;
            }
        }

        $codigo = (int) substr($resposta, 0, 3);
        if ($resposta === '' || !in_array($codigo, $codigosAceitos, true)) {
            throw new \RuntimeException('O servidor SMTP respondeu: ' . ($resposta !== '' ? trim($resposta) : 'sem resposta (tempo esgotado)'));
        }
        return $resposta;
    }

    private static function montarMensagem(string $de, string $paraEmail, string $paraNome, string $assunto, string $texto, ?string $html): string {
        $dominio = substr(strrchr($de, '@') ?: '@localhost', 1);

        $cabecalhos = [
            'Date: ' . date('r'),
            'From: ' . self::nomeEndereco(self::remetenteNome()) . ' <' . $de . '>',
            'To: ' . ($paraNome !== '' ? self::nomeEndereco($paraNome) . ' ' : '') . '<' . $paraEmail . '>',
            'Subject: ' . self::codificarCabecalho($assunto),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $dominio . '>',
            'MIME-Version: 1.0',
        ];

        if ($html === null) {
            $cabecalhos[] = 'Content-Type: text/plain; charset=UTF-8';
            $cabecalhos[] = 'Content-Transfer-Encoding: base64';
            $corpo = chunk_split(base64_encode($texto));
        } else {
            $limite = 'oc_' . bin2hex(random_bytes(12));
            $cabecalhos[] = 'Content-Type: multipart/alternative; boundary="' . $limite . '"';
            $corpo = '--' . $limite . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($texto))
                . '--' . $limite . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html))
                . '--' . $limite . '--';
        }

        $mensagem = implode("\r\n", $cabecalhos) . "\r\n\r\n" . $corpo;
        // Regra do protocolo SMTP: linha começando com "." ganha outro "." na frente.
        return (string) preg_replace('/^\./m', '..', $mensagem);
    }

    // ------------------------------------------------------------------
    // Modo 3: mail() nativo (modo de teste)
    // ------------------------------------------------------------------

    private static function enviarViaMailNativo(string $paraEmail, string $assunto, string $corpo): bool {
        return @mail(
            $paraEmail,
            self::codificarCabecalho($assunto),
            $corpo,
            'From: ' . self::nomeEndereco(self::remetenteNome()) . ' <' . self::remetenteEmail() . ">\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
        );
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private static function smtpUsuario(): string {
        return defined('SMTP_USER') ? trim((string) SMTP_USER) : '';
    }

    /** A "senha de app" do Google aparece com espaços ("abcd efgh ijkl mnop"); eles não fazem parte da senha. */
    private static function smtpSenha(): string {
        $senha = defined('SMTP_PASS') ? (string) SMTP_PASS : '';
        $host = defined('SMTP_HOST') ? strtolower((string) SMTP_HOST) : '';
        if (str_contains($host, 'gmail') || str_contains($host, 'google')) {
            $senha = str_replace(' ', '', $senha);
        }
        return $senha;
    }

    /**
     * O Gmail só aceita enviar "em nome de" o próprio e-mail autenticado (ou aliases
     * cadastrados). Se o remetente configurado for o padrão fictício, usa o SMTP_USER.
     */
    private static function remetenteEmail(): string {
        $de = defined('SMTP_FROM_EMAIL') ? self::limpar((string) SMTP_FROM_EMAIL) : '';
        $valido = filter_var($de, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($de), '.local');
        if ($valido) {
            return $de;
        }
        $usuario = self::smtpUsuario();
        if (filter_var($usuario, FILTER_VALIDATE_EMAIL)) {
            return $usuario;
        }
        return 'nao-responda@olhocidadao.local';
    }

    private static function remetenteNome(): string {
        $nome = defined('SMTP_FROM_NOME') ? self::limpar((string) SMTP_FROM_NOME) : '';
        return $nome !== '' ? $nome : 'Olho Cidadão';
    }

    /** Tira quebras de linha (impede "injeção de cabeçalho" por nome/assunto/e-mail malicioso). */
    private static function limpar(string $valor): string {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $valor));
    }

    /** Assunto com acento vira "=?UTF-8?B?...?=" para qualquer leitor de e-mail entender. */
    private static function codificarCabecalho(string $valor): string {
        if (preg_match('/[^\x20-\x7E]/', $valor) === 1) {
            return '=?UTF-8?B?' . base64_encode($valor) . '?=';
        }
        return $valor;
    }

    /** Nome que acompanha um endereço: com aspas se for ASCII, codificado se tiver acento. */
    private static function nomeEndereco(string $nome): string {
        if (preg_match('/[^\x20-\x7E]/', $nome) === 1) {
            return '=?UTF-8?B?' . base64_encode($nome) . '?=';
        }
        return '"' . addcslashes($nome, "\"\\") . '"';
    }
}
