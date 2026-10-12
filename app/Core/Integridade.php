<?php
/**
 * Histórico à prova de alteração (v0.50) — um "blockchain" simplificado.
 *
 * Toda vez que o sistema grava as denúncias, cada entrada nova do histórico
 * (mudança de status, encaminhamento, edição...) recebe um SELO: um código
 * HMAC-SHA256 calculado a partir do conteúdo da entrada + o selo da entrada
 * anterior + uma chave secreta (storage/integridade.key).
 *
 * Como cada selo depende do anterior, as entradas ficam "encadeadas": mudar
 * uma data, um status antigo ou apagar uma linha no meio quebra a corrente
 * dali para frente. Além disso, guardamos em storage/integridade.json uma
 * "âncora" por denúncia (último selo + quantidade de entradas + os dados
 * principais), então dá para perceber também se alguém apagou a última
 * entrada, trocou a descrição ou o status direto no arquivo.
 *
 * Limite honesto: quem tiver acesso à chave secreta E aos dois arquivos
 * consegue refazer os selos. Por isso a chave fica em storage/ (bloqueada
 * para o navegador) e, num servidor de verdade, deveria ficar fora do site.
 */
class Integridade {
    private static function pasta(): string {
        return __DIR__ . '/../../storage/';
    }

    private static function chave(): string {
        static $chave = null;
        if ($chave !== null) return $chave;
        $arq = self::pasta() . 'integridade.key';
        if (is_file($arq)) {
            $c = trim((string) @file_get_contents($arq));
            if (strlen($c) >= 64) {
                if (DIRECTORY_SEPARATOR === '/' && (fileperms($arq) & 0077)) @chmod($arq, 0600);
                return $chave = $c;
            }
        }
        // Criação exclusiva ("x"): se duas visitas chegarem juntas, só uma chave é gravada
        // e a outra visita relê a que ficou. Permissão 600: só o servidor lê.
        $c = bin2hex(random_bytes(32));
        $h = @fopen($arq, 'x');
        if ($h !== false) {
            fwrite($h, $c);
            fclose($h);
            @chmod($arq, 0600);
            return $chave = $c;
        }
        $existente = trim((string) @file_get_contents($arq));
        if (strlen($existente) < 64 && is_file($arq) && @filemtime($arq) > time() - 5) {
            usleep(300000); // outra visita pode estar terminando de gravar a chave agora
            $existente = trim((string) @file_get_contents($arq));
        }
        if (strlen($existente) >= 64) return $chave = $existente;
        // Arquivo vazio/estragado: guarda o defeituoso e cria uma chave nova. As denúncias
        // antigas vão aparecer como "com sinal de alteração" no painel (é o aviso certo),
        // mas o sistema continua funcionando.
        if (is_file($arq) && @filemtime($arq) <= time() - 5) {
            @rename($arq, $arq . '.corrompida-' . date('YmdHis'));
            error_log('[INTEGRIDADE] Chave storage/integridade.key estava corrompida; uma nova foi criada.');
            $h = @fopen($arq, 'x');
            if ($h !== false) {
                fwrite($h, $c); fclose($h); @chmod($arq, 0600);
                return $chave = $c;
            }
        }
        throw new RuntimeException('Não foi possível criar a chave de integridade em storage/ (verifique as permissões).');
    }

    private static function hmac(string $texto): string {
        return hash_hmac('sha256', $texto, self::chave());
    }

    /** Texto canônico de uma entrada (sem o próprio selo, chaves em ordem). */
    private static function canonica(array $entrada): string {
        unset($entrada['selo']);
        ksort($entrada);
        return json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Os dados principais da denúncia que não podem mudar "por fora". */
    private static function nucleo(array $d): string {
        return json_encode([
            (int) ($d['id'] ?? 0),
            (int) ($d['usuario_id'] ?? 0),
            (string) ($d['categoria'] ?? ''),
            (string) ($d['descricao'] ?? ''),
            (string) ($d['status'] ?? ''),
            (string) ($d['criada_em'] ?? ''),
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function seloEntrada(int $id, string $anterior, array $entrada): string {
        return self::hmac($id . '|' . $anterior . '|' . self::canonica($entrada));
    }

    private static function seloAncora(int $id, string $ultimo, int $qtd, array $d): string {
        return self::hmac('ancora|' . $id . '|' . $ultimo . '|' . $qtd . '|' . self::nucleo($d));
    }

    /**
     * Chamado pelo Denuncia::saveAll() antes de gravar: sela as entradas
     * novas do histórico e atualiza as âncoras. Entradas que já têm selo
     * NÃO são recalculadas (senão uma alteração antiga seria "lavada").
     */
    public static function selar(array &$denuncias, bool $persistirAncora = true): void {
        $ancoras = [];
        foreach ($denuncias as &$d) {
            $id = (int) ($d['id'] ?? 0);
            $anterior = 'inicio';
            if (!isset($d['historico']) || !is_array($d['historico'])) $d['historico'] = [];
            foreach ($d['historico'] as &$h) {
                if (empty($h['selo'])) {
                    $h['selo'] = self::seloEntrada($id, $anterior, $h);
                }
                $anterior = $h['selo'];
            }
            unset($h);
            $ancoras[$id] = self::seloAncora($id, $anterior, count($d['historico']), $d);
        }
        unset($d);

        // No armazenamento JSON, as âncoras só devem ser persistidas DEPOIS
        // que a gravação atômica de denuncias.json tiver sido concluída.
        // Caso contrário, um erro de disco deixaria a âncora apontando para
        // um estado que nunca chegou a ser salvo.
        if (!$persistirAncora) return;

        if (MySqlStore::ativo()) {
            MySqlStore::gravarConfiguracao('integridade', ['atualizado_em' => date('Y-m-d H:i:s'), 'ancoras' => $ancoras]);
            return;
        }
        @file_put_contents(
            self::pasta() . 'integridade.json',
            json_encode(['atualizado_em' => date('Y-m-d H:i:s'), 'ancoras' => $ancoras], JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    /**
     * Confere tudo. Retorna:
     *   'ok'        => quantas denúncias estão íntegras,
     *   'problemas' => [['id' => 12, 'protocolo' => ..., 'motivo' => '...'], ...],
     *   'sem_selo'  => quantas ainda não foram seladas (criadas antes da v0.50
     *                  e não mexidas desde então — são seladas na próxima gravação),
     *   'verificado_em' => data/hora.
     */
    public static function verificar(array $denuncias): array {
        $arq = self::pasta() . 'integridade.json';
        $dados = MySqlStore::ativo() ? MySqlStore::lerConfiguracao('integridade') : (is_file($arq) ? json_decode((string) file_get_contents($arq), true) : null);
        $ancoras = is_array($dados['ancoras'] ?? null) ? $dados['ancoras'] : [];

        $ok = 0; $semSelo = 0; $problemas = [];
        $vistos = [];
        foreach ($denuncias as $d) {
            $id = (int) ($d['id'] ?? 0);
            $vistos[$id] = true;
            $hist = is_array($d['historico'] ?? null) ? $d['historico'] : [];
            $temAncora = isset($ancoras[$id]) || isset($ancoras[(string) $id]);

            $algumSelo = false;
            foreach ($hist as $h) { if (!empty($h['selo'])) { $algumSelo = true; break; } }
            if (!$algumSelo && !$temAncora) { $semSelo++; continue; }

            $anterior = 'inicio';
            $motivo = null;
            foreach ($hist as $i => $h) {
                if (empty($h['selo'])) { $motivo = 'a entrada ' . ($i + 1) . ' do histórico perdeu o selo'; break; }
                if (!hash_equals(self::seloEntrada($id, $anterior, $h), (string) $h['selo'])) {
                    $motivo = 'a entrada ' . ($i + 1) . ' do histórico (' . ($h['status'] ?? '?') . ', ' . ($h['data'] ?? '?') . ') foi alterada ou removida';
                    break;
                }
                $anterior = (string) $h['selo'];
            }
            if ($motivo === null) {
                $ancora = $ancoras[$id] ?? $ancoras[(string) $id] ?? null;
                if ($ancora === null) {
                    $motivo = 'a denúncia não está registrada na âncora (pode ter sido inserida direto no arquivo)';
                } elseif (!hash_equals(self::seloAncora($id, $anterior, count($hist), $d), (string) $ancora)) {
                    $motivo = 'dados principais (descrição, status, categoria ou dono) ou o fim do histórico foram mudados fora do sistema';
                }
            }

            if ($motivo === null) {
                $ok++;
            } else {
                $problemas[] = ['id' => $id, 'protocolo' => Denuncia::protocolo($d), 'motivo' => $motivo];
            }
        }

        foreach ($ancoras as $id => $_) {
            if (!isset($vistos[(int) $id])) {
                $problemas[] = ['id' => (int) $id, 'protocolo' => 'OC-????-' . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT), 'motivo' => 'a denúncia sumiu do arquivo (foi apagada fora do sistema)'];
            }
        }

        return [
            'ok' => $ok,
            'problemas' => $problemas,
            'sem_selo' => $semSelo,
            'verificado_em' => date('Y-m-d H:i:s'),
            'ancora_em' => $dados['atualizado_em'] ?? null,
        ];
    }

    /** Resultado de UMA denúncia (usado no selo "histórico íntegro" da consulta pública). */
    public static function verificarUma(array $d): string {
        $r = self::verificar([$d]);
        // Na verificação isolada, âncoras de outras denúncias aparecem como "sumidas"; ignora.
        $proprios = array_filter($r['problemas'], fn($p) => $p['id'] === (int) ($d['id'] ?? 0));
        if ($proprios) return 'alterada';
        return $r['ok'] === 1 ? 'integra' : 'sem_selo';
    }
}
