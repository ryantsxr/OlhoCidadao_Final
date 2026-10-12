<?php
/**
 * Persistência MySQL: uma linha por entidade e colunas consultáveis/indexadas.
 * A coluna payload preserva atributos opcionais das versões antigas (histórico,
 * preferências, 2FA etc.) sem alterar os contratos dos Models MVC.
 * Escritas ocorrem dentro de uma transação com lock de app_locks.
 */
final class MySqlStore {
    private const TABLES = [
        'users' => 'users', 'denuncias' => 'denuncias',
        'notificacoes' => 'notificacoes', 'auditoria' => 'auditoria',
        'login_attempts' => 'login_attempts', 'rate_limits' => 'rate_limits',
    ];

    public static function ativo(): bool {
        return defined('DB_DRIVER') && DB_DRIVER === 'mysql';
    }

    private static function tabela(string $arquivo): string {
        $chave = pathinfo(basename($arquivo), PATHINFO_FILENAME);
        if (!isset(self::TABLES[$chave])) throw new InvalidArgumentException('Coleção desconhecida: ' . $chave);
        return $chave;
    }

    public static function ler(string $arquivo): array {
        $tabela = self::tabela($arquivo);
        $db = Database::getConnection();
        if ($tabela === 'login_attempts' || $tabela === 'rate_limits') {
            $registros = $db->query('SELECT chave, payload FROM `' . $tabela . '` ORDER BY chave')->fetchAll();
            $saida = [];
            foreach ($registros as $r) $saida[$r['chave']] = json_decode($r['payload'], true, 512, JSON_THROW_ON_ERROR);
            return $saida;
        }
        $ordem = $tabela === 'auditoria' ? 'seq_id' : 'id';
        $registros = $db->query('SELECT payload FROM `' . $tabela . '` ORDER BY `' . $ordem . '`')->fetchAll(PDO::FETCH_COLUMN);
        return array_map(static fn($p) => json_decode($p, true, 512, JSON_THROW_ON_ERROR), $registros);
    }

    /** Nunca permite gravar fora de uma transação, para evitar gravação perdida. */
    public static function gravar(string $arquivo, array $registros): void {
        $tabela = self::tabela($arquivo);
        $db = Database::getConnection();
        if (!$db->inTransaction()) throw new RuntimeException('Gravação MySQL sem transação (lock obrigatório).');
        if ($tabela === 'auditoria') {
            $db->exec('DELETE FROM auditoria');
            $stmt = $db->prepare('INSERT INTO auditoria (admin_id, denuncia_id, criada_em, payload) VALUES (?, ?, ?, ?)');
            foreach ($registros as $r) $stmt->execute([
                (int)($r['admin_id'] ?? 0), empty($r['denuncia_id']) ? null : (int)$r['denuncia_id'],
                self::data($r['data'] ?? null), self::json($r),
            ]);
            return;
        }
        if ($tabela === 'login_attempts' || $tabela === 'rate_limits') {
            $stmt = $db->prepare('INSERT INTO `' . $tabela . '` (chave, payload) VALUES (?, ?) ON DUPLICATE KEY UPDATE payload = VALUES(payload)');
            foreach ($registros as $chave => $r) $stmt->execute([(string)$chave, self::json($r)]);
            self::apagarAusentes($db, $tabela, 'chave', array_keys($registros));
            return;
        }
        $colunas = match($tabela) {
            'users' => ['id','email','cpf_hash','role','ativo','payload'],
            'denuncias' => ['id','usuario_id','categoria','status','prioridade','orgao','criada_em','latitude','longitude','payload'],
            'notificacoes' => ['id','usuario_id','denuncia_id','lida','criada_em','payload'],
        };
        // Nunca usar ON DUPLICATE KEY aqui: colisão de e-mail/CPF deve falhar,
        // e jamais sobrescrever a linha de outro usuário.
        $insert = $db->prepare('INSERT INTO `' . $tabela . '` (`' . implode('`,`', $colunas) . '`) VALUES (' . implode(',', array_fill(0,count($colunas),'?')) . ')');
        $campos = array_slice($colunas,1);
        $update = $db->prepare('UPDATE `' . $tabela . '` SET ' . implode(', ',array_map(static fn($c) => '`' . $c . '`=?', $campos)) . ' WHERE id=?');
        $existente = $db->prepare('SELECT id FROM `' . $tabela . '` WHERE id=? FOR UPDATE');
        $ids = [];
        foreach ($registros as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id < 1 || isset($ids[$id])) throw new RuntimeException('ID duplicado ou inválido ao persistir ' . $tabela);
            $ids[$id] = true;
            $valores = match($tabela) {
                'users' => [$id, strtolower(trim((string)($r['email'] ?? ''))), ($r['cpf_hash'] ?? null) ?: null, ($r['role'] ?? 'cidadao'), !empty($r['ativo']) || !array_key_exists('ativo', $r) ? 1 : 0, self::json($r)],
                'denuncias' => [$id,(int)($r['usuario_id'] ?? 0) ?: null,($r['categoria'] ?? 'outros'),($r['status'] ?? 'Pendente'),($r['prioridade'] ?? 'Normal'),($r['orgao'] ?? null),self::data($r['criada_em'] ?? null),$r['latitude'] ?? null,$r['longitude'] ?? null,self::json($r)],
                'notificacoes' => [$id,(int)($r['usuario_id'] ?? 0),empty($r['denuncia_id']) ? null : (int)$r['denuncia_id'],!empty($r['lida']) ? 1 : 0,self::data($r['criada_em'] ?? null),self::json($r)],
            };
            $existente->execute([$id]);
            if ($existente->fetchColumn() !== false) {
                $update->execute(array_merge(array_slice($valores,1),[$id]));
            } else {
                $insert->execute($valores);
            }
        }
        self::apagarAusentes($db, $tabela, 'id', array_keys($ids));
        if ($tabela === 'denuncias') self::sincronizarHistorico($db, $registros);
    }

    private static function sincronizarHistorico(PDO $db, array $denuncias): void {
        // Histórico e apoios também possuem tabelas relacionais consultáveis.
        $hist = $db->prepare('INSERT INTO denuncia_historico (denuncia_id, sequencia, status, data, payload) VALUES (?, ?, ?, ?, ?)');
        $apoio = $db->prepare('INSERT INTO denuncia_apoios (denuncia_id, usuario_id) VALUES (?, ?)');
        // A exclusão dos filhos fica na mesma transação que a escrita dos pais.
        $db->exec('DELETE FROM denuncia_historico');
        $db->exec('DELETE FROM denuncia_apoios');
        foreach ($denuncias as $d) {
            $id = (int)$d['id'];
            foreach (($d['historico'] ?? []) as $indice => $h) {
                $hist->execute([$id, $indice+1, (string)($h['status'] ?? ''), self::data($h['data'] ?? null), self::json($h)]);
            }
            foreach (array_unique(array_map('intval', $d['apoios'] ?? [])) as $uid) {
                if ($uid > 0) $apoio->execute([$id, $uid]);
            }
        }
    }

    private static function apagarAusentes(PDO $db, string $tabela, string $coluna, array $presentes): void {
        if (!$presentes) { $db->exec('DELETE FROM `' . $tabela . '`'); return; }
        // Chunk de deletes para evitar placeholders em excesso: tabela inteira
        // protegida por transação, sem apagar registros presentes.
        $presentes = array_values($presentes);
        $stmt = $db->prepare('SELECT `' . $coluna . '` FROM `' . $tabela . '`');
        $stmt->execute();
        $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $mapa = array_fill_keys(array_map('strval', $presentes), true);
        $del = $db->prepare('DELETE FROM `' . $tabela . '` WHERE `' . $coluna . '` = ?');
        foreach ($existentes as $id) if (!isset($mapa[(string)$id])) $del->execute([$id]);
    }

    public static function proximoId(string $tabela): int {
        if (!in_array($tabela, ['users','denuncias','notificacoes'], true)) throw new InvalidArgumentException('Tabela inválida.');
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $stmt->execute([$tabela]);
        $auto = (int)$stmt->fetchColumn();
        $max = (int)$db->query('SELECT COALESCE(MAX(id),0) FROM `' . $tabela . '`')->fetchColumn();
        return max($auto, $max + 1, 1);
    }

    public static function lerConfiguracao(string $chave): ?array {
        $stmt = Database::getConnection()->prepare('SELECT payload FROM app_settings WHERE chave=?');
        $stmt->execute([$chave]);
        $val = $stmt->fetchColumn();
        return $val === false ? null : json_decode($val, true, 512, JSON_THROW_ON_ERROR);
    }
    public static function gravarConfiguracao(string $chave, array $dados): void {
        $stmt = Database::getConnection()->prepare('INSERT INTO app_settings (chave,payload) VALUES (?, ?) ON DUPLICATE KEY UPDATE payload=VALUES(payload)');
        $stmt->execute([$chave,self::json($dados)]);
    }
    private static function json(array $value): string { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
    private static function data($v): ?string {
        if (!$v || !is_string($v)) return null;
        $time = strtotime($v);
        return $time === false ? null : date('Y-m-d H:i:s', $time);
    }
}
