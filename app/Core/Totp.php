<?php
/** TOTP RFC 6238, SHA-1/30s/6 dígitos, compatível com autenticadores offline. */
final class Totp {
    public static function segredo(): string {
        return self::base32(random_bytes(20));
    }
    private static function base32(string $bytes): string {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = ''; foreach (str_split($bytes) as $b) $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        $saida = '';
        foreach (str_split($bits, 5) as $grupo) $saida .= $alfabeto[bindec(str_pad($grupo, 5, '0'))];
        return $saida;
    }
    private static function decodificar(string $segredo): string {
        $segredo = strtoupper(str_replace(' ', '', $segredo));
        if ($segredo === '' || preg_match('/[^A-Z2-7]/', $segredo)) throw new RuntimeException('Segredo TOTP inválido');
        $bits=''; foreach (str_split($segredo) as $c) $bits .= str_pad(decbin(strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $c)), 5, '0', STR_PAD_LEFT);
        $bytes=''; foreach (str_split($bits, 8) as $grupo) if (strlen($grupo) === 8) $bytes .= chr(bindec($grupo));
        return $bytes;
    }
    public static function codigo(string $segredo, int $passo): string {
        if ($passo < 0) return '';
        $h = hash_hmac('sha1', pack('N2', ($passo >> 32) & 0xFFFFFFFF, $passo & 0xFFFFFFFF), self::decodificar($segredo), true);
        $off = ord(substr($h, -1)) & 0x0F;
        $num = (unpack('N', substr($h, $off, 4))[1] & 0x7FFFFFFF) % 1000000;
        return str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }
    /** Retorna o contador aceito (para impedir reutilização), ou null. */
    public static function passoValido(string $segredo, string $codigo, int $ultimo = -1, ?int $agora = null): ?int {
        if (!preg_match('/^\d{6}$/D', $codigo)) return null;
        $atual = intdiv($agora ?? time(), 30);
        foreach ([$atual - 1, $atual, $atual + 1] as $passo) {
            if ($passo > $ultimo && hash_equals(self::codigo($segredo, $passo), $codigo)) return $passo;
        }
        return null;
    }
    public static function uri(string $segredo, string $email): string {
        return 'otpauth://totp/' . rawurlencode('Olho Cidadão:' . $email)
            . '?secret=' . rawurlencode($segredo) . '&issuer=' . rawurlencode('Olho Cidadão')
            . '&algorithm=SHA1&digits=6&period=30';
    }
    /**
     * A chave pertence à instalação e NÃO ao banco de dados. Nunca usamos
     * ArquivoLock aqui: a verificação TOTP ocorre dentro da transação de users
     * e iniciar outra transação MySQL causava o erro HTTP 500.
     *
     * A criação só é autorizada durante a PRIMEIRA ativação do 2FA; ao decifrar
     * segredos existentes uma chave ausente deve falhar, jamais ser recriada.
     */
    private static function chave(bool $permitirCriacao = false): string {
        $arquivo = __DIR__ . '/../../storage/.totp.key';
        if ($permitirCriacao && !is_file($arquivo)) {
            // Trava do sistema de arquivos, independente de transações MySQL.
            $arquivoTrava = __DIR__ . '/../../storage/.totp-key.lock';
            $trava = @fopen($arquivoTrava, 'c');
            if ($trava === false) throw new RuntimeException('Não foi possível proteger a chave privada do 2FA.');
            @chmod($arquivoTrava, 0600);
            try {
                if (!flock($trava, LOCK_EX)) throw new RuntimeException('Falha na trava da chave 2FA.');
                if (!is_file($arquivo)) {
                    // Se há segredos TOTP existentes, exigir a chave original.
                    // Nunca substituir uma chave perdida por outra aleatória.
                    $usuarios = LeituraJson::array(__DIR__ . '/../../storage/users.json');
                    foreach ($usuarios as $usuario) {
                        if (!empty($usuario['totp_segredo'])) {
                            throw new RuntimeException('Chave 2FA original ausente. Restaure a cópia de segurança da instalação.');
                        }
                    }
                    $tmp = $arquivo . '.' . bin2hex(random_bytes(8)) . '.tmp';
                    try {
                        if (@file_put_contents($tmp, random_bytes(32), LOCK_EX) !== 32) {
                            throw new RuntimeException('Falha ao gerar a chave privada do 2FA.');
                        }
                        @chmod($tmp, 0600);
                        if (!@rename($tmp, $arquivo)) {
                            throw new RuntimeException('Falha ao instalar a chave privada do 2FA.');
                        }
                    } finally {
                        @unlink($tmp);
                    }
                }
            } finally {
                flock($trava, LOCK_UN);
                fclose($trava);
            }
        }
        $chave = @file_get_contents($arquivo);
        if ($chave === false || strlen($chave) !== 32) {
            throw new RuntimeException('Chave privada do 2FA ausente ou inválida. Restaure a chave original.');
        }
        return $chave;
    }
    public static function cifrar(string $segredo): string {
        $iv=random_bytes(12); $tag='';
        $cifrado=openssl_encrypt($segredo, 'aes-256-gcm', self::chave(true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cifrado === false) throw new RuntimeException('Erro ao cifrar 2FA');
        return base64_encode($iv.$tag.$cifrado);
    }
    public static function decifrar(string $conteudo): string {
        $bytes=base64_decode($conteudo, true);
        if ($bytes === false || strlen($bytes) < 29) throw new RuntimeException('2FA inválido');
        $plain=openssl_decrypt(substr($bytes,28), 'aes-256-gcm', self::chave(), OPENSSL_RAW_DATA, substr($bytes,0,12), substr($bytes,12,16));
        if ($plain === false) throw new RuntimeException('Não foi possível abrir o segredo 2FA');
        return $plain;
    }
}
