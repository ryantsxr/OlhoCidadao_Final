<?php
/** Leitura estrita: um JSON danificado nunca deve virar uma lista vazia e apagar registros. */
class LeituraJson {
    public static function array(string $caminho): array {
        if (MySqlStore::ativo()) return MySqlStore::ler($caminho);
        if (!is_file($caminho) || !is_readable($caminho)) {
            throw new RuntimeException('Arquivo de dados ausente ou sem permissão: ' . basename($caminho));
        }
        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            throw new RuntimeException('Falha ao ler o arquivo de dados: ' . basename($caminho));
        }
        try {
            $dados = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $erro) {
            throw new RuntimeException('Arquivo de dados inválido: ' . basename($caminho) . '. Gravação interrompida para evitar perda.', 0, $erro);
        }
        if (!is_array($dados)) {
            throw new RuntimeException('Estrutura de dados inválida: ' . basename($caminho) . '. Gravação interrompida.');
        }
        return $dados;
    }
}
