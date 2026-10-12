<?php
class Notificacao {
    private string $file;

    public function __construct() {
        $this->file = __DIR__ . '/../../storage/notificacoes.json';
    }

    private function all(): array {
        return LeituraJson::array($this->file);
    }

    private function saveAll(array $itens): void {
        GravacaoSegura::json($this->file, $itens);
    }

    /** Cria uma notificação para o dono da denúncia (chamado quando o admin muda o status). */
    public function criar(int $userId, string $mensagem, int $denunciaId = 0): void {
        ArquivoLock::executar('notificacoes', function () use ($userId, $mensagem, $denunciaId) {
            $itens = $this->all();
            $ids = array_column($itens, 'id');
            $nextId = MySqlStore::ativo() ? MySqlStore::proximoId('notificacoes') : (empty($ids) ? 1 : ((int) max($ids) + 1));

            $itens[] = [
                'id' => $nextId,
                'usuario_id' => $userId,
                'denuncia_id' => $denunciaId,
                'mensagem' => $mensagem,
                'lida' => false,
                'criada_em' => date('Y-m-d H:i:s')
            ];

            $this->saveAll($itens);
        });
    }

    /** Notificações de um usuário, mais recentes primeiro. */
    public function porUsuario(int $userId): array {
        $itens = array_values(array_filter(
            $this->all(),
            fn($n) => (int) ($n['usuario_id'] ?? 0) === $userId
        ));

        usort($itens, fn($a, $b) => strcmp($b['criada_em'] ?? '', $a['criada_em'] ?? ''));

        return $itens;
    }

    public function contarNaoLidas(int $userId): int {
        return count(array_filter(
            $this->porUsuario($userId),
            fn($n) => empty($n['lida'])
        ));
    }

    public function marcarTodasComoLidas(int $userId): void {
        ArquivoLock::executar('notificacoes', function () use ($userId) {
            $itens = $this->all();
            foreach ($itens as &$n) {
                if ((int) ($n['usuario_id'] ?? 0) === $userId) {
                    $n['lida'] = true;
                }
            }
            unset($n);
            $this->saveAll($itens);
        });
    }

    /** Remove todas as notificações de um usuário (usado na exclusão de conta). */
    public function apagarDoUsuario(int $userId): void {
        ArquivoLock::executar('notificacoes', function () use ($userId) {
            $itens = array_values(array_filter($this->all(), fn($n) => (int) ($n['usuario_id'] ?? 0) !== $userId));
            $this->saveAll($itens);
        });
    }

    /** Remove as notificações ligadas a uma denúncia (usado quando o admin exclui a denúncia). */
    public function apagarDaDenuncia(int $denunciaId): void {
        ArquivoLock::executar('notificacoes', function () use ($denunciaId) {
            $itens = array_values(array_filter($this->all(), fn($n) => (int) ($n['denuncia_id'] ?? 0) !== $denunciaId));
            $this->saveAll($itens);
        });
    }
}
