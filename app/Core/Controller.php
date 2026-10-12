<?php
abstract class Controller {
    /**
     * Sanitiza texto de entrada sem destruir espaços internos válidos.
     * A validação definitiva continua sendo feita no servidor.
     */
    protected function textoPost(string $campo, int $maximo = 0): string {
        $valor = trim((string)($_POST[$campo] ?? ''));
        if ($maximo > 0) {
            $valor = mb_substr($valor, 0, $maximo);
        }
        return $valor;
    }

    protected function emailPost(string $campo = 'email'): string {
        return strtolower(trim((string)($_POST[$campo] ?? '')));
    }

    protected function somenteDigitos(string $valor): string {
        return preg_replace('/\\D+/', '', $valor) ?? '';
    }

    protected function telefoneValido(string $telefone): bool {
        $digitos = $this->somenteDigitos($telefone);
        return strlen($digitos) === 10 || strlen($digitos) === 11;
    }

    protected function cpfValido(string $cpf): bool {
        $cpf = $this->somenteDigitos($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\\d)\\1{10}$/', $cpf)) return false;
        for ($t = 9; $t <= 10; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += (int)$cpf[$i] * (($t + 1) - $i);
            $digito = ((10 * $soma) % 11) % 10;
            if ((int)$cpf[$t] !== $digito) return false;
        }
        return true;
    }

    protected function cepValido(string $cep): bool {
        $cep = $this->somenteDigitos($cep);
        return strlen($cep) === 8;
    }
    protected function view(string $view, array $data = []): void {
        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
    protected function redirect(string $path): never {
        header('Location: ' . $path);
        exit;
    }
    /** Rotas que o administrador pode acessar normalmente, além do painel dele. */
    private const ROTAS_PERMITIDAS_ADMIN = ['admin', 'admin-status', 'admin-encaminhar', 'admin-excluir-denuncia', 'admin-kanban', 'admin-kanban-mover', 'admin-telao', 'admin-busca', 'admin-massa', 'admin-historico', 'admin-auditoria', 'admin-exportar-csv', 'admin-relatorio', 'admin-estatisticas', 'admin-usuarios', 'admin-usuario-alterar', 'admin-integridade', 'admin-seguranca', 'configuracoes', 'seguranca-2fa-iniciar', 'seguranca-2fa-ativar', 'seguranca-2fa-desativar', 'seguranca-2fa-regenerar', 'anexo', 'denuncia-relatorio', 'denuncia-ver', 'denuncia-apoiar', 'logout'];

    protected function auth(): int {
        if (empty($_SESSION['user_id'])) {
            $this->redirect(BASE_URL . '/?rota=login');
        }
        $id = (int) $_SESSION['user_id'];

        // Conta suspensa (ou apagada) perde o acesso na hora, mesmo com a sessão ainda aberta.
        $conta=(new User)->find($id);
        $protegida=($conta['role']??'cidadao')==='admin' && SegurancaConta::doisFatoresAtivo($conta);
        if (!User::estaAtivo($conta)
            || (int)($_SESSION['auth_epoch']??0) !== (int)($conta['auth_epoch']??0)
            || ($protegida && empty($_SESSION['admin_totp_ok']))
            || (SegurancaConta::verificacaoHabilitada() && !SegurancaConta::emailVerificado($conta))) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['error'] = 'Sua conta não está ativa. Procure a administração.';
            $this->redirect(BASE_URL . '/?rota=login');
        }

        $rotaAtual = $_GET['rota'] ?? '';
        // Administrador sem 2FA, onde ela é obrigatória: só pode ativá-la ou sair.
        if (($conta['role']??'cidadao')==='admin' && !SegurancaConta::doisFatoresAtivo($conta)
            && Ambiente::admin2faObrigatorio()
            && !in_array($rotaAtual, ['configuracoes', 'seguranca-2fa-iniciar', 'seguranca-2fa-ativar', 'logout'], true)) {
            $_SESSION['error'] = 'Por segurança, ative a verificação em duas etapas para usar o painel administrativo.';
            $this->redirect(BASE_URL . '/?rota=configuracoes#seguranca-conta');
        }
        if (!in_array($rotaAtual, self::ROTAS_PERMITIDAS_ADMIN, true)) {
            $usuario = (new User)->find($id);
            if (($usuario['role'] ?? 'cidadao') === 'admin') {
                $this->redirect(BASE_URL . '/?rota=admin');
            }
        }

        return $id;
    }

    /** Gera (ou reaproveita) o token CSRF da sessão atual. Usar nos forms: <?= $this->csrfField() ?> */
    protected function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Retorna o input hidden pronto para colocar dentro do <form>. */
    protected function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->csrfToken()) . '">';
    }

    /** Valida o token enviado no POST. Se inválido/ausente, redireciona com erro. */
    protected function csrfCheck(string $redirectTo): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $_SESSION['error'] = 'Sua sessão expirou. Tente novamente.';
            $this->redirect($redirectTo);
        }
    }

    /**
     * Limite diário por conta contra spam/robôs (ex.: no máximo 10 denúncias por dia).
     * Chame antes da ação; depois de concluir com sucesso, chame ?->registrarTentativa() (null = limite desligado).
     */
    protected function limiteDiario(string $acao, int $usuarioId, int $maximo, string $voltar, string $oQue): ?RateLimiter {
        if ($maximo <= 0) return null; // 0 no config = sem limite
        $limite = new RateLimiter($acao, maxTentativas: $maximo, bloqueioSegundos: 86400,
            identificador: 'u' . $usuarioId, janelaSegundos: 86400);
        if ($limite->bloqueado() > 0 || $limite->restantes() <= 0) {
            $_SESSION['error'] = "Você atingiu o limite de {$maximo} {$oQue} por dia. Tente novamente amanhã.";
            $this->redirect($voltar);
        }
        return $limite;
    }

    /** Garante que o usuário logado é administrador; senão manda pro dashboard normal. */
    protected function authAdmin(): int {
        $id = $this->auth();
        $usuario = (new User)->find($id);
        if (($usuario['role'] ?? 'cidadao') !== 'admin') {
            $this->redirect(BASE_URL . '/?rota=dashboard');
        }
        return $id;
    }

    /**
     * Processa um upload de imagem opcional vindo de $_FILES[$campo].
     * Toda a validação (tipo real via finfo, tamanho, remoção de EXIF) fica na
     * classe Uploader — aqui só adaptamos o resultado para os controllers.
     *
     * Retorna:
     *   - '' se nenhum arquivo foi enviado (campo opcional, tudo certo);
     *   - a URL pública do arquivo salvo, se o upload deu certo;
     *   - null se veio um arquivo inválido. Nesse caso a mensagem amigável já
     *     fica em $_SESSION['error'] (quem chamou pode trocar por outra).
     */
    protected function processarUploadImagem(string $campo, string $prefixoArquivo): ?string {
        try {
            return Uploader::salvarImagemEnviada($campo, $prefixoArquivo);
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }
}
