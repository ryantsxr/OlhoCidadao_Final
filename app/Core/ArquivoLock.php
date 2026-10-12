<?php
/**
 * Trava simples para os arquivos JSON.
 *
 * Problema que resolve: os Models leem o JSON, mudam alguma coisa e gravam de
 * volta. Se duas pessoas fazem isso no mesmo instante, uma das alterações pode
 * se perder. Aqui, quem chega primeiro "segura a chave" e o outro espera.
 *
 * Uso:
 *   return ArquivoLock::executar('denuncias', function () {
 *       // ler, alterar e gravar aqui dentro
 *   });
 *
 * Importante: não chame executar() com o mesmo nome dentro de outro executar()
 * (ficaria esperando por ele mesmo).
 */
class ArquivoLock {
    public static function executar(string $nome, callable $acao) {
        $nome = preg_replace('/[^a-zA-Z0-9_-]/', '', $nome);
        if (MySqlStore::ativo()) {
            $db = Database::getConnection();
            if ($db->inTransaction()) throw new RuntimeException('Transação MySQL aninhada não suportada.');
            $db->beginTransaction();
            try {
                $stmt = $db->prepare('INSERT IGNORE INTO app_locks (nome) VALUES (?)');
                $stmt->execute([$nome]);
                $stmt = $db->prepare('SELECT nome FROM app_locks WHERE nome = ? FOR UPDATE');
                $stmt->execute([$nome]);
                $retorno = $acao();
                $db->commit();
                return $retorno;
            } catch (\Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
        }

        $dir = __DIR__ . '/../../storage/';
        $caminho = $dir . '.lock_' . $nome;

        $arquivo = @fopen($caminho, 'c');
        if ($arquivo === false) {
            // Sem uma trava válida, falhar em segurança para evitar perder dados concorrentes.
            throw new RuntimeException('Diretório storage sem permissão de gravação (lock).');
        }

        if (!flock($arquivo, LOCK_EX)) {
            fclose($arquivo);
            throw new RuntimeException('Não foi possível bloquear o arquivo de dados.');
        }
        try {
            return $acao();
        } finally {
            flock($arquivo, LOCK_UN);
            fclose($arquivo);
        }
    }
}
