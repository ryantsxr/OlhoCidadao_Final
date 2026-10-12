<?php
/**
 * CPF cifrado com AES-256-GCM + índice HMAC-SHA256 para detectar duplicidade.
 * A chave privada é gerada na instalação em storage/.cpf.key (NUNCA versionar,
 * publicar nem armazenar junto ao backup dos JSON). Perder essa chave impede
 * recuperar os CPFs cifrados. Requer extensão OpenSSL.
 */
class PrivacidadeCpf {
    private static ?string $chave = null;
    private const CHAVE_ARQUIVO = __DIR__ . '/../../storage/.cpf.key';

    private static function chave(): string {
        if (self::$chave !== null) return self::$chave;
        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('A extensão OpenSSL é necessária para proteger CPFs.');
        }
        $arq = self::CHAVE_ARQUIVO;
        if (!is_file($arq)) {
            // Criação exclusiva: duas primeiras requisições simultâneas não podem gerar chaves diferentes.
            $fp = @fopen($arq, 'x');
            if ($fp !== false) {
                @chmod($arq, 0600);
                $secret = bin2hex(random_bytes(32));
                if (fwrite($fp, $secret) !== 64 || !fflush($fp)) {
                    fclose($fp);
                    @unlink($arq);
                    throw new RuntimeException('Não foi possível criar a chave privada de CPF.');
                }
                fclose($fp);
            }
        }
        $conteudo = @file_get_contents($arq);
        if ($conteudo === false || !preg_match('/^[a-f0-9]{64}$/D', trim($conteudo))) {
            throw new RuntimeException('Chave privada de CPF ausente ou inválida. Restaure a chave original.');
        }
        return self::$chave = hex2bin(trim($conteudo));
    }

    private static function subchave(string $uso): string {
        return hash_hmac('sha256', 'olho-cidadao-v051-' . $uso, self::chave(), true);
    }

    public static function digitos(string $cpf): string {
        return preg_replace('/\D+/', '', $cpf) ?? '';
    }

    public static function indice(string $cpf): string {
        return hash_hmac('sha256', self::digitos($cpf), self::subchave('indice'));
    }

    public static function cifrar(string $cpf): string {
        $cpf = self::digitos($cpf);
        if ($cpf === '') return '';
        $iv = random_bytes(12);
        $tag = '';
        $texto = openssl_encrypt($cpf, 'aes-256-gcm', self::subchave('cifra'), OPENSSL_RAW_DATA, $iv, $tag);
        if ($texto === false) throw new RuntimeException('Falha ao cifrar CPF.');
        return 'v1:' . base64_encode($iv . $tag . $texto);
    }

    public static function decifrar(string $cifrado): string {
        if ($cifrado === '') return '';
        if (!str_starts_with($cifrado, 'v1:')) throw new RuntimeException('Formato de CPF cifrado inválido.');
        $pacote = base64_decode(substr($cifrado, 3), true);
        if ($pacote === false || strlen($pacote) < 29) throw new RuntimeException('CPF cifrado corrompido.');
        $cpf = openssl_decrypt(substr($pacote, 28), 'aes-256-gcm', self::subchave('cifra'), OPENSSL_RAW_DATA, substr($pacote, 0, 12), substr($pacote, 12, 16));
        if ($cpf === false) throw new RuntimeException('Não foi possível decifrar CPF. Verifique a chave da instalação.');
        return $cpf;
    }

    private static bool $avisouChave = false;

    /**
     * Devolve CPF apenas na memória, sem carregar cifrado/hash à camada de visualização.
     * Se o CPF não puder ser lido (ex.: a pasta do sistema foi trocada e a chave
     * storage/.cpf.key não veio junto), o site NÃO cai: o CPF aparece vazio, o
     * dado cifrado fica intacto no banco e o Painel de segurança avisa o admin.
     * Basta copiar a chave original de volta para tudo voltar ao normal.
     */
    public static function paraAplicacao(array $usuario): array {
        $usuario['cpf_ilegivel'] = false;
        if (isset($usuario['cpf_cifrado']) && $usuario['cpf_cifrado'] !== '') {
            try {
                $usuario['cpf'] = self::decifrar((string)$usuario['cpf_cifrado']);
            } catch (RuntimeException $e) {
                $usuario['cpf'] = '';
                $usuario['cpf_ilegivel'] = true;
                if (!self::$avisouChave) {
                    self::$avisouChave = true;
                    error_log('[CPF] CPF cifrado não pôde ser lido (conta #' . (int)($usuario['id'] ?? 0) . '): ' . $e->getMessage()
                        . ' Copie o arquivo storage/.cpf.key da instalação original.');
                }
            }
        } else {
            $usuario['cpf'] = self::digitos((string)($usuario['cpf'] ?? ''));
        }
        // Não propagar segredos de autenticação às Views/JSON de apresentação.
        unset($usuario['cpf_cifrado'], $usuario['cpf_hash'],
            $usuario['reset_token'], $usuario['reset_expira'],
            $usuario['email_token_hash'], $usuario['email_token_expira'],
            $usuario['totp_segredo'], $usuario['totp_backups'], $usuario['totp_ultimo_passo']);
        return $usuario;
    }

    /** Altera o registro salvo: nunca mantém campo 'cpf' em texto simples. */
    public static function prepararRegistro(array &$usuario, string $cpf): void {
        $cpf = self::digitos($cpf);
        if ($cpf === '') {
            $usuario['cpf_cifrado'] = '';
            $usuario['cpf_hash'] = '';
        } else {
            $usuario['cpf_cifrado'] = self::cifrar($cpf);
            $usuario['cpf_hash'] = self::indice($cpf);
        }
        unset($usuario['cpf']);
    }

    /** Migração automática, idempotente e protegida por trava compartilhada com User::update. */
    public static function migrarExistentes(): int {
        if (MySqlStore::ativo()) return 0; // CPF migrado pelo importador, não tocar em JSON antigo.
        $arquivo = __DIR__ . '/../../storage/users.json';
        if (!is_file($arquivo)) return 0;
        $dados = json_decode((string)file_get_contents($arquivo), true);
        if (!is_array($dados)) throw new RuntimeException('users.json inválido; migração interrompida sem alterar dados.');
        $pendentes = array_filter($dados, fn($u) => array_key_exists('cpf', $u));
        if (!$pendentes) {
            // Se já existem CPFs cifrados, a chave não pode ser criada do zero.
            if (array_filter($dados, fn($u) => !empty($u['cpf_cifrado'])) && !is_file(self::CHAVE_ARQUIVO)) {
                throw new RuntimeException('Chave privada perdida: CPFs já cifrados não podem ser lidos.');
            }
            return 0;
        }
        return ArquivoLock::executar('users', static function () use ($arquivo) {
            $dados = json_decode((string) file_get_contents($arquivo), true);
            if (!is_array($dados)) throw new RuntimeException('users.json inválido.');
            $alterados = 0;
            foreach ($dados as &$usuario) {
                if (!array_key_exists('cpf', $usuario)) continue;
                // Não substitui um CPF já cifrado por possível campo legado vazio.
                if (empty($usuario['cpf_cifrado'])) {
                    self::prepararRegistro($usuario, (string)$usuario['cpf']);
                } else {
                    unset($usuario['cpf']);
                }
                $alterados++;
            }
            unset($usuario);
            if ($alterados) {
                $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (file_put_contents($arquivo, $json, LOCK_EX) === false) {
                    throw new RuntimeException('Falha ao gravar migração dos CPFs.');
                }
            }
            return $alterados;
        });
    }
}
