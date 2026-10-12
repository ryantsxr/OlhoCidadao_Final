<?php
/**
 * Controla o número de tentativas de login por e-mail e aplica um bloqueio
 * temporário depois de várias tentativas seguidas incorretas.
 */
class LoginAttempt {
    private string $file;
    private const MAX_TENTATIVAS = 5;
    private const BLOQUEIO_SEGUNDOS = 300; // 5 minutos
    /** Registros sem atividade há mais que isso são apagados (guardam e-mail + IP). */
    private const RETENCAO_SEGUNDOS = 86400;

    public function __construct() {
        $this->file = __DIR__ . '/../../storage/login_attempts.json';
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

    /** Segundos restantes de bloqueio para este e-mail (0 = liberado). */
    public function segundosBloqueado(string $email): int {
        $data = $this->all();
        $chave = strtolower(trim($email));
        if (empty($data[$chave]['bloqueado_ate'])) {
            return 0;
        }
        $restante = $data[$chave]['bloqueado_ate'] - time();
        return $restante > 0 ? $restante : 0;
    }

    /** Registra uma tentativa incorreta e bloqueia o e-mail se passar do limite. */
    public function registrarFalha(string $email): void {
        ArquivoLock::executar('login_attempts', function () use ($email) {
            $data = $this->all();
            $chave = strtolower(trim($email));

            $tentativas = ($data[$chave]['tentativas'] ?? 0) + 1;

            if ($tentativas >= self::MAX_TENTATIVAS) {
                $data[$chave] = [
                    'tentativas' => 0,
                    'bloqueado_ate' => time() + self::BLOQUEIO_SEGUNDOS,
                    'ultima' => time(),
                    'bloqueios' => (int)($data[$chave]['bloqueios'] ?? 0) + 1,
                ];
            } else {
                $data[$chave]['tentativas'] = $tentativas;
                $data[$chave]['ultima'] = time();
            }
            // Aproveita a gravação para descartar registros antigos (minimização de dados).
            foreach ($data as $k => $r) {
                $ultimo = max((int)($r['ultima'] ?? 0), (int)($r['bloqueado_ate'] ?? 0));
                if ($ultimo > 0 && time() - $ultimo > self::RETENCAO_SEGUNDOS) unset($data[$k]);
            }

            $this->saveAll($data);
        });
    }

    /** Resumo para o painel de segurança do admin: falhas e bloqueios nas últimas 24 h. */
    public function resumo(): array {
        $falhas = 0; $bloqueadosAgora = 0; $bloqueios = 0; $alvos = [];
        foreach ($this->all() as $chave => $r) {
            if (time() - (int)($r['ultima'] ?? 0) > self::RETENCAO_SEGUNDOS) continue;
            $falhas += (int)($r['tentativas'] ?? 0) + (int)($r['bloqueios'] ?? 0) * self::MAX_TENTATIVAS;
            $bloqueios += (int)($r['bloqueios'] ?? 0);
            if ((int)($r['bloqueado_ate'] ?? 0) > time()) $bloqueadosAgora++;
            $email = explode('|', (string)$chave)[0];
            if ($email !== '') $alvos[$email] = ($alvos[$email] ?? 0) + (int)($r['tentativas'] ?? 0) + (int)($r['bloqueios'] ?? 0) * self::MAX_TENTATIVAS;
        }
        arsort($alvos);
        return ['falhas' => $falhas, 'bloqueios' => $bloqueios, 'bloqueados_agora' => $bloqueadosAgora, 'alvos' => array_slice($alvos, 0, 5, true)];
    }

    /** Limpa o histórico de tentativas (chamar após um login bem-sucedido). */
    public function limpar(string $email): void {
        ArquivoLock::executar('login_attempts', function () use ($email) {
            $data = $this->all();
            unset($data[strtolower(trim($email))]);
            $this->saveAll($data);
        });
    }
}
