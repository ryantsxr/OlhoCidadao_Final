<?php
/**
 * Rate limit genérico por IP, reutilizável em qualquer ação sensível a abuso
 * (cadastro, pedido de redefinição de senha, etc.) — mesma ideia do
 * LoginAttempt, só que parametrizável em vez de fixa para o login.
 *
 * Uso:
 *   $limiter = new RateLimiter('cadastro', maxTentativas: 8, bloqueioSegundos: 600);
 *   if ($limiter->bloqueado() > 0) { ...avisa e redireciona... }
 *   $limiter->registrarTentativa();
 */
class RateLimiter {
    private string $file;
    private string $acao;
    private int $maxTentativas;
    private int $bloqueioSegundos;
    private ?string $identificador;
    private ?int $janelaSegundos;

    /**
     * @param string|null $identificador  quem está sendo limitado; padrão = IP. Ex.: 'u42' limita a conta 42.
     * @param int|null    $janelaSegundos se informado, a contagem zera depois desse tempo sem bloqueio
     *                                    (ex.: "no máximo 10 por dia").
     */
    public function __construct(string $acao, int $maxTentativas = 5, int $bloqueioSegundos = 300, ?string $identificador = null, ?int $janelaSegundos = null) {
        $this->acao = $acao;
        $this->maxTentativas = $maxTentativas;
        $this->bloqueioSegundos = $bloqueioSegundos;
        $this->identificador = $identificador;
        $this->janelaSegundos = $janelaSegundos;
        $this->file = __DIR__ . '/../../storage/rate_limits.json';
        if (!MySqlStore::ativo() && !file_exists($this->file)) {
            file_put_contents($this->file, '{}', LOCK_EX);
        }
    }

    private function all(): array {
        return LeituraJson::array($this->file);
    }

    private function saveAll(array $data): void {
        GravacaoSegura::json($this->file, $data);
    }

    /** Identifica quem está tentando: ação + IP (independe do e-mail digitado). */
    private function chave(): string {
        $quem = $this->identificador ?? Ambiente::ip();
        return $this->acao . ':' . $quem;
    }

    /** Segundos restantes de bloqueio pra essa ação+IP (0 = liberado). */
    public function bloqueado(): int {
        $data = $this->all();
        $chave = $this->chave();
        if (empty($data[$chave]['bloqueado_ate'])) {
            return 0;
        }
        $restante = $data[$chave]['bloqueado_ate'] - time();
        return $restante > 0 ? $restante : 0;
    }

    /** Registra mais uma tentativa e bloqueia se passar do limite. */
    public function registrarTentativa(): void {
        ArquivoLock::executar('rate_limits', function () {
            $data = $this->all();
            $chave = $this->chave();

            if ($this->janelaSegundos !== null
                && time() - (int)($data[$chave]['inicio'] ?? 0) > $this->janelaSegundos) {
                $data[$chave] = ['tentativas' => 0, 'inicio' => time()];
            }
            $tentativas = ($data[$chave]['tentativas'] ?? 0) + 1;

            if ($tentativas >= $this->maxTentativas) {
                $data[$chave] = [
                    'tentativas' => 0,
                    'inicio' => time(),
                    'bloqueado_ate' => time() + $this->bloqueioSegundos,
                ];
            } else {
                $data[$chave]['tentativas'] = $tentativas;
                $data[$chave]['inicio'] ??= time();
                $data[$chave]['ultima'] = time();
            }

            $this->saveAll($data);
        });
    }

    /** Quantas ações ainda cabem antes do bloqueio (para avisar o usuário). */
    public function restantes(): int {
        $r = $this->all()[$this->chave()] ?? [];
        if (!empty($r['bloqueado_ate']) && $r['bloqueado_ate'] > time()) return 0;
        if ($this->janelaSegundos !== null && time() - (int)($r['inicio'] ?? 0) > $this->janelaSegundos) return $this->maxTentativas;
        return max(0, $this->maxTentativas - (int)($r['tentativas'] ?? 0));
    }

    /**
     * Remove registros vencidos (bloqueio já passou e sem atividade há mais de 1 dia).
     * Evita que a tabela cresça para sempre guardando IPs (LGPD: minimização).
     */
    public static function limparAntigos(int $idadeMaxima = 86400): int {
        $arquivo = __DIR__ . '/../../storage/rate_limits.json';
        if (!MySqlStore::ativo() && !is_file($arquivo)) return 0;
        return ArquivoLock::executar('rate_limits', static function () use ($arquivo, $idadeMaxima) {
            $data = LeituraJson::array($arquivo);
            $antes = count($data);
            $agora = time();
            foreach ($data as $chave => $r) {
                $ultimo = max((int)($r['bloqueado_ate'] ?? 0), (int)($r['ultima'] ?? 0), (int)($r['inicio'] ?? 0));
                if ($ultimo === 0 || $agora - $ultimo > $idadeMaxima) unset($data[$chave]);
            }
            if (count($data) !== $antes) GravacaoSegura::json($arquivo, $data);
            return $antes - count($data);
        });
    }

    /** Limpa o histórico dessa ação+IP (chamar depois de uma tentativa legítima bem-sucedida). */
    public function limpar(): void {
        ArquivoLock::executar('rate_limits', function () {
            $data = $this->all();
            unset($data[$this->chave()]);
            $this->saveAll($data);
        });
    }
}
