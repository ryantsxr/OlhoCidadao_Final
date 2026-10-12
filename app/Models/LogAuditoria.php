<?php
/**
 * Registro simples de ações administrativas relevantes (ex.: mudança de
 * status de uma denúncia), pra dar rastreabilidade de quem fez o quê e
 * quando — útil assim que houver mais de um administrador no sistema.
 */
class LogAuditoria {
    private string $file;

    /** Mantém só os últimos N registros, pra o arquivo não crescer pra sempre. */
    private const MAX_REGISTROS = 5000;

    public function __construct() {
        $this->file = __DIR__ . '/../../storage/auditoria.json';
        if (!MySqlStore::ativo() && !file_exists($this->file)) {
            file_put_contents($this->file, '[]', LOCK_EX);
        }
    }

    private function all(): array {
        return LeituraJson::array($this->file);
    }

    /** Registra uma ação administrativa. $denunciaId é opcional (nem toda ação está ligada a uma denúncia). */
    public function registrar(int $adminId, string $adminNome, string $acao, ?int $denunciaId = null): void {
        ArquivoLock::executar('auditoria', function () use ($adminId, $adminNome, $acao, $denunciaId) {
            $registros = $this->all();

            $registros[] = [
                'data' => date('Y-m-d H:i:s'),
                'admin_id' => $adminId,
                'admin_nome' => $adminNome,
                'acao' => $acao,
                'denuncia_id' => $denunciaId,
            ];

            if (count($registros) > self::MAX_REGISTROS) {
                $registros = array_slice($registros, -self::MAX_REGISTROS);
            }

            GravacaoSegura::json($this->file, $registros);
        });
    }

    /** Últimos registros, do mais recente pro mais antigo. */
    public function ultimos(int $quantidade = 200): array {
        return array_reverse(array_slice($this->all(), -$quantidade));
    }
}
