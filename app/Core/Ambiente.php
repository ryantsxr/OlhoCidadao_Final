<?php
/** Decisões que dependem de onde o sistema está rodando (localhost ou servidor real). */
final class Ambiente {
    /**
     * true quando acessado pelo próprio computador (XAMPP local, testes, apresentação).
     * Não basta o nome do site (o cabeçalho Host pode ser forjado por qualquer um):
     * a conexão precisa vir do próprio computador (127.0.0.1/::1) e não pode ter
     * passado por um proxy.
     */
    public static function local(): bool {
        if (PHP_SAPI === 'cli') return true;
        $nomeLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1', '[::1]'], true);
        $conexaoLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
        $semProxy = empty($_SERVER['HTTP_X_FORWARDED_FOR']) && empty($_SERVER['HTTP_FORWARDED']) && empty($_SERVER['HTTP_X_REAL_IP']);
        return $nomeLocal && $conexaoLocal && $semProxy;
    }

    /** O pedido veio de um proxy configurado em PROXY_CONFIAVEL (ex.: Cloudflare/nginx na frente)? */
    private static function viaProxyConfiavel(): bool {
        $lista = defined('PROXY_CONFIAVEL') ? array_filter(array_map('trim', explode(',', (string) PROXY_CONFIAVEL))) : [];
        return $lista && in_array($_SERVER['REMOTE_ADDR'] ?? '', $lista, true);
    }

    public static function https(): bool {
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
        // Atrás de um proxy confiável que fala HTTPS com o visitante (evita redirecionamento infinito).
        return self::viaProxyConfiavel() && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function admin2faObrigatorio(): bool {
        $v = defined('ADMIN_2FA_OBRIGATORIO') ? ADMIN_2FA_OBRIGATORIO : 'auto';
        if ($v === 'auto') return !self::local();
        return (bool) $v;
    }

    public static function forcarHttps(): bool {
        $v = defined('FORCAR_HTTPS') ? FORCAR_HTTPS : 'auto';
        if ($v === 'auto') {
            return !self::local() && str_starts_with(strtolower((string)(defined('APP_URL') ? APP_URL : '')), 'https://');
        }
        return (bool) $v;
    }

    /** IP de quem fez a requisição. X-Forwarded-For só é aceito vindo de um PROXY_CONFIAVEL. */
    public static function ip(): string {
        if (self::viaProxyConfiavel() && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** Navegador resumido, para avisos de segurança ("Chrome no Windows"). */
    public static function navegador(): string {
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $nav = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'navegador desconhecido',
        };
        $so = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'sistema desconhecido',
        };
        return $nav . ' no ' . $so;
    }
}
