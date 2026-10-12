<?php
/**
 * Faxina diária automática (roda na primeira visita do dia, junto do backup):
 *  - apaga registros vencidos de limite de tentativas (guardam IPs);
 *  - gira o log de erros quando passa de 2 MB e apaga logs antigos;
 *  - apaga arquivos temporários esquecidos.
 * Prazo configurável em RETENCAO_LOGS_DIAS (LGPD: guardar só o necessário).
 */
final class Manutencao {
    public static function diaria(): void {
        $storage = __DIR__ . '/../../storage';
        // Barato (4 verificações de arquivo): roda em toda visita, não só uma vez por dia.
        self::protegerPastas($storage);
        $marca = $storage . '/logs/.manutencao-' . date('Y-m-d');
        if (is_file($marca)) return;
        if (!is_dir($storage . '/logs')) @mkdir($storage . '/logs', 0700, true);
        @file_put_contents($marca, date(DATE_ATOM));
        foreach (glob($storage . '/logs/.manutencao-*') ?: [] as $antiga) {
            if ($antiga !== $marca) @unlink($antiga);
        }

        RateLimiter::limparAntigos();
        self::girarLogs($storage . '/logs');
        foreach (glob($storage . '/*.tmp') ?: [] as $tmp) {
            if (filemtime($tmp) < time() - 3600) @unlink($tmp);
        }
    }

    /**
     * Recria o .htaccess que bloqueia o navegador nas pastas privadas, caso
     * tenha sumido (ex.: copiar a pasta sem os arquivos ocultos).
     */
    public static function protegerPastas(string $storage): array {
        $bloqueio = "# Pasta privada: nunca deve abrir pelo navegador.\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
        $recriados = [];
        foreach (['', '/backups', '/logs', '/uploads'] as $sub) {
            $pasta = $storage . $sub;
            if (!is_dir($pasta)) continue;
            if (!is_file($pasta . '/.htaccess') && @file_put_contents($pasta . '/.htaccess', $bloqueio) !== false) {
                $recriados[] = 'storage' . $sub;
            }
        }
        if ($recriados) error_log('[SEGURANCA] .htaccess de proteção recriado em: ' . implode(', ', $recriados));
        return $recriados;
    }

    private static function girarLogs(string $pasta): void {
        $dias = max(7, (int)(defined('RETENCAO_LOGS_DIAS') ? RETENCAO_LOGS_DIAS : 90));
        $log = $pasta . '/php-errors.log';
        if (is_file($log) && filesize($log) > 2 * 1024 * 1024) {
            @rename($log, $pasta . '/php-errors-' . date('Ymd-His') . '.log');
        }
        foreach (glob($pasta . '/php-errors-*.log') ?: [] as $antigo) {
            if (filemtime($antigo) < time() - $dias * 86400) @unlink($antigo);
        }
    }
}
