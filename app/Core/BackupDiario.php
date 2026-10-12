<?php
/**
 * Backup diário, por acesso web (primeira visita do dia) ou por CLI.
 *
 * Funciona nos dois modos de armazenamento:
 *  - JSON:  copia storage/*.json;
 *  - MySQL: exporta cada tabela de dados para um JSON (mesmo formato que os
 *           Models usam), dentro de uma transação — fotografia consistente.
 * Em ambos copia as fotos de storage/uploads e grava um manifesto com o
 * SHA-256 de cada arquivo, o que permite conferir e restaurar depois
 * (tools/verificar_backup.php e tools/restaurar_backup.php).
 *
 * Não copia as chaves de cifragem (.cpf.key / .totp.key): elas devem ser
 * guardadas à parte, fora do servidor. Sem elas os CPFs e a 2FA não voltam.
 */
class BackupDiario {
    /** Coleções exportadas no modo MySQL. Tentativas de login e limites são temporários e ficam de fora. */
    public const COLECOES = ['users', 'denuncias', 'notificacoes', 'auditoria'];
    public const DIAS_RETENCAO = 30;

    public static function raiz(): string {
        return __DIR__ . '/../../storage/backups';
    }

    public static function executar(): bool {
        $raiz = self::raiz();
        $dia = date('Y-m-d');
        $destino = $raiz . '/' . $dia;
        // Se o backup de hoje já terminou, não abre o lock em cada visita.
        if (is_file($destino . '/manifesto.json')) return false;
        // Se falhou há pouco, espera 1 hora antes de tentar de novo — senão cada
        // visita repetiria a exportação inteira e deixaria o site lento.
        $marcaFalha = __DIR__ . '/../../storage/logs/.backup-falhou';
        if (is_file($marcaFalha) && filemtime($marcaFalha) > time() - 3600) return false;
        try {
            $criado = self::criar($destino, $dia);
            @unlink($marcaFalha);
            return $criado;
        } catch (\Throwable $e) {
            if (!is_dir(dirname($marcaFalha))) @mkdir(dirname($marcaFalha), 0700, true);
            @file_put_contents($marcaFalha, date(DATE_ATOM) . ' ' . $e->getMessage());
            throw $e;
        }
    }

    /** Última falha de backup (para o Painel de segurança), ou null. */
    public static function ultimaFalha(): ?string {
        $marca = __DIR__ . '/../../storage/logs/.backup-falhou';
        return is_file($marca) ? trim((string) @file_get_contents($marca)) : null;
    }

    /** Pasta para guardar o estado atual antes de restaurar/criar dados (nunca reaproveita uma existente). */
    public static function pastaAntesRestauracao(): string {
        do {
            $pasta = self::raiz() . '/' . date('Y-m-d') . '_antes-restauracao-' . date('His');
            if (!is_dir($pasta)) return $pasta;
            sleep(1);
        } while (true);
    }

    /** Cria um backup completo na pasta indicada (usado também antes de uma restauração). */
    public static function criar(string $destino, string $rotulo): bool {
        $storage = __DIR__ . '/../../storage';
        $raiz = self::raiz();
        if (!is_dir($raiz) && !mkdir($raiz, 0700, true) && !is_dir($raiz)) {
            throw new RuntimeException('Sem permissão para criar storage/backups.');
        }
        return ArquivoLock::executar('backup-diario', static function () use ($storage, $raiz, $destino, $rotulo): bool {
            if (is_file($destino . '/manifesto.json')) return false;
            if (!is_dir($destino) && !mkdir($destino, 0700, true) && !is_dir($destino)) {
                throw new RuntimeException('Sem permissão para criar pasta de backup.');
            }
            $manifesto = [
                'data' => $rotulo,
                'criado_em' => date(DATE_ATOM),
                'modo' => MySqlStore::ativo() ? 'mysql' : 'json',
                'versao' => trim((string) @file_get_contents(__DIR__ . '/../../VERSION')),
                'arquivos' => [],
            ];

            if (MySqlStore::ativo()) {
                foreach (self::COLECOES as $colecao) {
                    $dados = MySqlStore::ler($colecao . '.json');
                    self::gravarArquivo($destino, $colecao . '.json', self::json($dados), $manifesto);
                }
                $integridade = MySqlStore::lerConfiguracao('integridade');
                if ($integridade !== null) {
                    self::gravarArquivo($destino, 'integridade.json', self::json($integridade), $manifesto);
                }
            } else {
                foreach (glob($storage . '/*.json') ?: [] as $arquivo) {
                    if (!is_file($arquivo)) continue;
                    $nome = basename($arquivo);
                    $in = @fopen($arquivo, 'rb');
                    if ($in === false) throw new RuntimeException('Falha ao abrir JSON de backup: ' . $nome);
                    try {
                        flock($in, LOCK_SH);
                        $conteudo = stream_get_contents($in);
                        flock($in, LOCK_UN);
                    } finally {
                        fclose($in);
                    }
                    if ($conteudo === false) throw new RuntimeException('Falha ao ler JSON: ' . $nome);
                    self::gravarArquivo($destino, $nome, $conteudo, $manifesto);
                }
            }

            // Fotos junto com os dados: sem elas uma restauração deixaria imagens quebradas.
            $uploads = $storage . '/uploads';
            if (is_dir($uploads)) {
                $copiaDir = $destino . '/uploads';
                if (!is_dir($copiaDir) && !mkdir($copiaDir, 0700, true)) {
                    throw new RuntimeException('Falha ao criar backup de fotos.');
                }
                foreach (glob($uploads . '/*') ?: [] as $original) {
                    if (!is_file($original) || !preg_match('/^[a-zA-Z0-9_-]+\.(jpg|png|webp)$/', basename($original))) continue;
                    $nome = basename($original);
                    if (!copy($original, $copiaDir . '/' . $nome)) throw new RuntimeException('Falha ao copiar anexo: ' . $nome);
                    @chmod($copiaDir . '/' . $nome, 0600);
                    $manifesto['arquivos']['uploads/' . $nome] = hash_file('sha256', $original);
                }
            }
            if (!$manifesto['arquivos']) throw new RuntimeException('Não há dados para fazer backup.');

            // O manifesto é o último arquivo: só existe quando o backup está completo.
            if (file_put_contents($destino . '/manifesto.json', self::json($manifesto), LOCK_EX) === false) {
                throw new RuntimeException('Falha ao concluir manifesto do backup.');
            }
            @chmod($destino . '/manifesto.json', 0600);
            self::aplicarRetencao($raiz);
            return true;
        });
    }

    /** Confere o manifesto de um backup. Retorna a lista de problemas (vazia = íntegro). */
    public static function verificar(string $pasta): array {
        $m = json_decode((string) @file_get_contents($pasta . '/manifesto.json'), true);
        if (!is_array($m) || empty($m['arquivos']) || !is_array($m['arquivos'])) {
            return ['Backup inexistente ou incompleto (sem manifesto).'];
        }
        $problemas = [];
        foreach ($m['arquivos'] as $nome => $hash) {
            if (!is_string($nome) || !preg_match('#^(?:uploads/)?[a-zA-Z0-9_.-]+$#D', $nome) || str_contains($nome, '..')) {
                $problemas[] = 'Nome de arquivo inválido no manifesto: ' . $nome;
                continue;
            }
            $caminho = $pasta . '/' . $nome;
            $atual = is_file($caminho) && is_readable($caminho) ? hash_file('sha256', $caminho) : false;
            if ($atual === false || !hash_equals((string) $hash, $atual)) {
                $problemas[] = 'Arquivo ausente ou alterado: ' . $nome;
            }
        }
        return $problemas;
    }

    private static function gravarArquivo(string $destino, string $nome, string $conteudo, array &$manifesto): void {
        $temp = $destino . '/.' . $nome . '.tmp';
        if (file_put_contents($temp, $conteudo, LOCK_EX) === false || !rename($temp, $destino . '/' . $nome)) {
            @unlink($temp);
            throw new RuntimeException('Falha ao salvar backup: ' . $nome);
        }
        @chmod($destino . '/' . $nome, 0600);
        $manifesto['arquivos'][$nome] = hash('sha256', $conteudo);
    }

    /** Apaga backups diários com mais de DIAS_RETENCAO dias (LGPD: dados excluídos não ficam para sempre). */
    private static function aplicarRetencao(string $raiz): void {
        $limite = strtotime('-' . self::DIAS_RETENCAO . ' days midnight');
        foreach (glob($raiz . '/*', GLOB_ONLYDIR) ?: [] as $pasta) {
            $nome = basename($pasta);
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})(?:_antes-restauracao-\d{6})?$/', $nome, $m)) continue;
            if (strtotime($m[1] . ' 00:00:00') >= $limite) continue;
            self::apagarPasta($pasta);
        }
    }

    private static function apagarPasta(string $pasta): void {
        foreach (glob($pasta . '/uploads/*') ?: [] as $item) if (is_file($item)) @unlink($item);
        if (is_dir($pasta . '/uploads')) @rmdir($pasta . '/uploads');
        foreach (array_merge(glob($pasta . '/*') ?: [], glob($pasta . '/.*.tmp') ?: []) as $item) if (is_file($item)) @unlink($item);
        @rmdir($pasta);
    }

    private static function json($dados): string {
        return json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
