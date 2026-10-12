<?php
/** Regra central para TODAS as operações que criam ou alteram senhas. */
class PoliticaSenha {
    /**
     * Algoritmo de hash: Argon2id (recomendado pela OWASP) quando o PHP
     * oferece; senão bcrypt. Senhas antigas em bcrypt são convertidas
     * sozinhas no próximo login (precisaRehash).
     */
    public static function algoritmo(): string|int|null {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    public static function hash(string $senha): string {
        return password_hash($senha, self::algoritmo());
    }

    public static function precisaRehash(string $hash): bool {
        return password_needs_rehash($hash, self::algoritmo());
    }

    /**
     * Confere a senha. Quando a conta não existe, compara com um hash falso
     * para gastar o mesmo tempo — assim ninguém descobre quais e-mails estão
     * cadastrados medindo quanto o login demora.
     */
    public static function verificar(string $senha, ?string $hash): bool {
        // Hashes pré-calculados (mesmos parâmetros padrão do PHP) de uma senha qualquer.
        $falso = defined('PASSWORD_ARGON2ID')
            ? '$argon2id$v=19$m=65536,t=4,p=1$Q25tMmVwaE54aDIvVlVqeg$eyTTq+MhUTrTIZBfSwPLqvW3x1+Y2RXDmglgBAhAIBM'
            : '$2y$10$PxffM5o5B7qgXqY2emjAPucInQSkBIgSIJeW2F563ryY0zfUbhC3K';
        if ($hash === null || $hash === '') {
            password_verify($senha, $falso);
            return false;
        }
        $ok = password_verify($senha, $hash);
        // Conta antiga ainda em bcrypt (mais rápido): gasta o tempo do Argon2id
        // também, para o tempo não denunciar quais contas existem.
        if (defined('PASSWORD_ARGON2ID') && (password_get_info($hash)['algoName'] ?? '') !== 'argon2id') {
            password_verify($senha, $falso);
        }
        return $ok;
    }

    public static function erro(string $senha): ?string {
        $tamanho = strlen($senha);
        if ($tamanho < 8 || $tamanho > 72) {
            return 'A senha deve ter entre 8 e 72 caracteres.';
        }
        $normalizada = function_exists('mb_strtolower') ? mb_strtolower(trim($senha), 'UTF-8') : strtolower(trim($senha));
        $comum = preg_replace('/[^a-z0-9]/', '', (function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizada) : false) ?: $normalizada) ?? '';
        $fracas = [
            '123456', '1234567', '12345678', '123456789', '1234567890',
            '87654321', 'qwerty', 'qwerty123', 'qwertyuiop', 'senha', 'senha123',
            'senha1234', 'password', 'password123', 'admin123', 'admin1234',
            'administrador', 'iloveyou', 'abc12345', '11111111', '00000000',
            '123123123', '65432100', 'brasil123', 'letmein123',
        ];
        if (in_array($comum, $fracas, true)
            || preg_match('/^(.)\1{7,}$/us', $normalizada)
            || preg_match('/^(?:0123456789|9876543210|abcdefghijklmnopqrstuvwxyz|qwertyuiop)$/', $comum)) {
            return 'Essa senha é muito comum. Escolha uma senha menos previsível.';
        }
        return null;
    }
}
