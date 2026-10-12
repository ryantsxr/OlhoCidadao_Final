<?php
/** Escrita atômica de JSON, sempre dentro da trava ArquivoLock do respectivo model. */
class GravacaoSegura {
    public static function json(string $arquivo, array $dados): void {
        if (MySqlStore::ativo()) { MySqlStore::gravar($arquivo, $dados); return; }
        $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $temporario = $arquivo . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (file_put_contents($temporario, $json, LOCK_EX) === false || !rename($temporario, $arquivo)) {
            @unlink($temporario);
            throw new RuntimeException('Não foi possível gravar dados. Arquivo anterior mantido.');
        }
        @chmod($arquivo, 0600);
    }
}
