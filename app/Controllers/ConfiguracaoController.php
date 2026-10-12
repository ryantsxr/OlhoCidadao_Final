<?php
class ConfiguracaoController extends Controller {
    private const PADRAO = [
        'temaEscuro' => true,
        'idioma' => 'pt-br',
        'fonte' => 'padrao',
        'altoContraste' => false,
        'modoDaltonico' => false,
        'fonteLeitura' => false
    ];

    public function index(): void {
        $id = $this->auth();
        $usuario = (new User)->find($id);
        $ehAdmin = ($usuario['role'] ?? 'cidadao') === 'admin';
        // Config vem da conta (persiste entre navegadores/aparelhos); se o
        // usuário nunca salvou nada ainda, cai no padrão.
        $cfg = !empty($usuario['config']) ? array_merge(self::PADRAO, $usuario['config']) : self::PADRAO;

        $erro=$_SESSION['error']??null;unset($_SESSION['error']);
        $sucesso=$_SESSION['sucesso']??null;unset($_SESSION['sucesso']);
        $segredoPendente=$ehAdmin ? ($_SESSION['totp_pendente']??null) : null;
        $codigosRecuperacao=$ehAdmin ? ($_SESSION['totp_codigos']??null) : null;
        unset($_SESSION['totp_codigos']); // mostrar uma única vez, nunca armazenar em arquivo/sessão persistente
        $this->view('configuracoes/index', compact('usuario', 'cfg', 'ehAdmin', 'erro', 'sucesso', 'segredoPendente', 'codigosRecuperacao'));
    }

    public function save(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=configuracoes');

        $idiomasValidos = ['pt-br', 'en', 'es'];
        $fontesValidas = ['padrao', 'grande', 'extra-grande'];

        $idioma = $_POST['idioma'] ?? 'pt-br';
        $fonte = $_POST['fonte'] ?? 'padrao';

        $config = [
            'temaEscuro' => isset($_POST['temaEscuro']),
            'idioma' => in_array($idioma, $idiomasValidos, true) ? $idioma : 'pt-br',
            'fonte' => in_array($fonte, $fontesValidas, true) ? $fonte : 'padrao',
            'altoContraste' => isset($_POST['altoContraste']),
            'modoDaltonico' => isset($_POST['modoDaltonico']),
            'fonteLeitura' => isset($_POST['fonteLeitura'])
        ];

        // Salva na conta (não só na sessão) — assim a preferência acompanha
        // o usuário mesmo trocando de navegador ou de aparelho.
        (new User)->update($id, ['config' => $config]);

        $this->redirect(BASE_URL . '/?rota=configuracoes');
    }
    public function iniciar2fa(): void {
        $id=$this->authAdmin();
        $this->csrfCheck(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        $limite=new RateLimiter('2fa-iniciar', maxTentativas:5, bloqueioSegundos:900);
        if ($limite->bloqueado()>0) {
            $_SESSION['error']='Muitas tentativas. Aguarde 15 minutos.';
            $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        }
        $limite->registrarTentativa();
        $u=(new User)->find($id);
        if (SegurancaConta::doisFatoresAtivo($u)) {
            $_SESSION['error']='A verificação em duas etapas já está ativa.';
        } elseif (empty($_POST['senha_atual']) || !PoliticaSenha::verificar((string)$_POST['senha_atual'], $u['senha']??'')) {
            $_SESSION['error']='Informe sua senha atual para configurar o 2FA.';
        } else {
            $limite->limpar();
            $_SESSION['totp_pendente']=['segredo'=>Totp::segredo(),'id'=>$id,'expira'=>time()+600];
            $_SESSION['sucesso']='Cadastre a chave no aplicativo autenticador e confirme o código.';
        }
        $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
    }

    public function ativar2fa(): void {
        $id=$this->authAdmin();
        $this->csrfCheck(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        $pendente=$_SESSION['totp_pendente']??null;
        if (!is_array($pendente) || ($pendente['id']??0)!==$id || ($pendente['expira']??0)<time()
            || Totp::passoValido((string)($pendente['segredo']??''),trim((string)($_POST['codigo']??'')))===null) {
            $_SESSION['error']='Código inválido ou cadastro expirado. Solicite uma nova chave.';
            $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        }
        $codigos=SegurancaConta::ativar2fa($id,$pendente['segredo']);
        unset($_SESSION['totp_pendente']);
        if (!$codigos) {
            $_SESSION['error']='Não foi possível ativar o 2FA.';
        } else {
            $_SESSION['auth_epoch']=(int)((new User)->find($id)['auth_epoch']??0);
            $_SESSION['admin_totp_ok']=true;
            $_SESSION['totp_codigos']=$codigos;
            (new LogAuditoria)->registrar($id,(string)((new User)->find($id)['nome']??'Admin'),'Ativou o 2FA administrativo');
            SegurancaConta::alertar((new User)->find($id) ?? [], 'Verificação em duas etapas ativada');
            $_SESSION['sucesso']='2FA ativado. Guarde seus códigos de recuperação em lugar seguro.';
        }
        $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
    }

    /** Renova backups sem mudar o segredo do Google Authenticator. */
    public function regenerarCodigos2fa(): void {
        $voltar = BASE_URL . '/?rota=configuracoes#seguranca-conta';
        $id = $this->authAdmin();
        $this->csrfCheck($voltar);
        $limite = new RateLimiter('2fa-regenerar', maxTentativas:5, bloqueioSegundos:900,
            identificador:'conta-' . $id, janelaSegundos:900);
        if ($limite->bloqueado() > 0) {
            $_SESSION['error'] = 'Muitas tentativas. Aguarde 15 minutos para tentar novamente.';
            $this->redirect($voltar);
        }
        $u = (new User)->find($id);
        if (!SegurancaConta::doisFatoresAtivo($u)) {
            $_SESSION['error'] = 'Ative a verificação em duas etapas antes de gerar códigos.';
            $this->redirect($voltar);
        }
        if (($_POST['confirmar_substituicao'] ?? '') !== '1') {
            $_SESSION['error'] = 'Confirme que os códigos antigos serão invalidados.';
            $this->redirect($voltar);
        }
        if (!is_string($_POST['senha_atual'] ?? null)
            || !PoliticaSenha::verificar($_POST['senha_atual'], $u['senha'] ?? null)) {
            $limite->registrarTentativa();
            $_SESSION['error'] = 'Senha atual incorreta.';
            $this->redirect($voltar);
        }
        try {
            $codigos = SegurancaConta::regenerarCodigos2fa($id, (string)($_POST['codigo'] ?? ''));
        } catch (\RuntimeException $e) {
            $limite->registrarTentativa();
            error_log('[2FA] Falha ao renovar códigos: ' . $e->getMessage());
            $_SESSION['error'] = 'Não foi possível validar o autenticador. Verifique a chave privada do 2FA ou use um código de recuperação válido.';
            $this->redirect($voltar);
        }
        if (!$codigos) {
            $limite->registrarTentativa();
            $_SESSION['error'] = 'Código do autenticador inválido, reutilizado ou código de recuperação já utilizado.';
            $this->redirect($voltar);
        }
        // Já houve gravação atômica. Entregar os novos códigos mesmo se a
        // auditoria/e-mail falharem, evitando a perda de acesso ao administrador.
        $_SESSION['auth_epoch'] = (int)((new User)->find($id)['auth_epoch'] ?? 0);
        $_SESSION['admin_totp_ok'] = true;
        session_regenerate_id(true);
        $_SESSION['totp_codigos'] = $codigos;
        $_SESSION['sucesso'] = 'Novos 8 códigos gerados. Todos os anteriores foram invalidados. Guarde os novos em local seguro.';
        try {
            $limite->limpar();
        } catch (\Throwable $e) {
            error_log('[2FA] Falha ao limpar limitador da regeneração: ' . $e->getMessage());
        }
        try {
            (new LogAuditoria)->registrar($id, (string)($u['nome'] ?? 'Admin'), 'Regenerou os códigos de recuperação do 2FA');
        } catch (\Throwable $e) {
            error_log('[2FA] Falha na auditoria da regeneração: ' . $e->getMessage());
        }
        try {
            SegurancaConta::alertar($u, 'Códigos de recuperação do 2FA regenerados');
        } catch (\Throwable $e) {
            error_log('[2FA] Falha no aviso da regeneração: ' . $e->getMessage());
        }
        $this->redirect($voltar);
    }

    public function desativar2fa(): void {
        $id=$this->authAdmin();
        $this->csrfCheck(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        $limite=new RateLimiter('2fa-desativar', maxTentativas:5, bloqueioSegundos:900);
        if ($limite->bloqueado()>0) {
            $_SESSION['error']='Muitas tentativas. Aguarde 15 minutos.';
            $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        }
        $u=(new User)->find($id);
        if (Ambiente::admin2faObrigatorio()) {
            $_SESSION['error']='A verificação em duas etapas é obrigatória para administradores neste servidor e não pode ser desativada.';
            $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
        }
        if (empty($_POST['senha_atual']) || !PoliticaSenha::verificar((string)$_POST['senha_atual'],$u['senha']??'')) {
            $limite->registrarTentativa();
            $_SESSION['error']='Senha atual incorreta.';
        } else {
            try {
                $fatorValido = SegurancaConta::verificar2fa($id, (string)($_POST['codigo'] ?? ''));
            } catch (\RuntimeException $e) {
                error_log('[2FA] Falha na confirmação para desativação: ' . $e->getMessage());
                $fatorValido = false;
                $_SESSION['error'] = 'Não foi possível validar o autenticador. Restaure a chave privada original do 2FA.';
            }
            if (!$fatorValido) {
                $limite->registrarTentativa();
                $_SESSION['error'] = $_SESSION['error'] ?? 'Código 2FA inválido ou já utilizado.';
            } elseif (SegurancaConta::desativar2fa($id)) {
                $limite->limpar();
                (new LogAuditoria)->registrar($id,(string)($u['nome']??'Admin'),'Desativou o 2FA administrativo');
                SegurancaConta::alertar($u, 'Verificação em duas etapas desativada');
                $_SESSION['auth_epoch']=(int)((new User)->find($id)['auth_epoch']??0);
                unset($_SESSION['admin_totp_ok']);
                $_SESSION['sucesso']='Verificação em duas etapas desativada.';
            }
        }
        $this->redirect(BASE_URL.'/?rota=configuracoes#seguranca-conta');
    }

}
