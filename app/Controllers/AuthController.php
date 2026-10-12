<?php
class AuthController extends Controller {
    public function login(): void {
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);
        $tipoAtivo = $_SESSION['tipo_login_ativo'] ?? 'cidadao'; unset($_SESSION['tipo_login_ativo']);
        $this->view('auth/login', compact('error', 'sucesso', 'tipoAtivo'));
    }

    public function authenticate(): void {
        $this->csrfCheck(BASE_URL . '/?rota=login');

        $email = $this->emailPost();
        $senha = (string)($_POST['senha'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
            $_SESSION['error'] = 'Informe um e-mail válido e uma senha.';
            $this->redirect(BASE_URL . '/?rota=login');
        }
        $tipoLogin = ($_POST['tipo_login'] ?? 'cidadao') === 'admin' ? 'admin' : 'cidadao';
        $_SESSION['tipo_login_ativo'] = $tipoLogin;

        // O bloqueio vale para "e-mail + IP" (e não só e-mail): assim, alguém de
        // fora não consegue travar a conta de outra pessoa errando a senha de
        // propósito. Além disso, cada IP tem um limite geral de tentativas.
        $ip = Ambiente::ip();
        $chaveTentativa = $email . '|' . $ip;
        $limiteIp = new RateLimiter('login', maxTentativas: 20, bloqueioSegundos: 300);
        $tentativas = new LoginAttempt();

        $bloqueado = max($tentativas->segundosBloqueado($chaveTentativa), $limiteIp->bloqueado());
        if ($bloqueado > 0) {
            $minutos = (int) ceil($bloqueado / 60);
            $_SESSION['error'] = "Muitas tentativas incorretas. Tente novamente em {$minutos} minuto(s).";
            $this->redirect(BASE_URL . '/?rota=login');
        }

        // Limite por conta (qualquer IP): segura ataques distribuídos em muitos IPs
        // contra a mesma conta. Para ninguém conseguir TRAVAR a conta de outra
        // pessoa, ele só vale para IPs que já erraram a senha recentemente: o
        // dono, entrando do próprio aparelho (sem erros), continua conseguindo.
        $limiteConta = new RateLimiter('login-conta', maxTentativas: 30, bloqueioSegundos: 900,
            identificador: hash('sha256', $email), janelaSegundos: 1800);
        if ($limiteConta->bloqueado() > 0 && $limiteIp->restantes() < 20) {
            $bloqueado = max($bloqueado, $limiteConta->bloqueado());
        }
        if ($bloqueado > 0) {
            $minutos = (int) ceil($bloqueado / 60);
            $_SESSION['error'] = "Muitas tentativas incorretas. Tente novamente em {$minutos} minuto(s).";
            $this->redirect(BASE_URL . '/?rota=login');
        }

        $user = (new User)->findByEmail($email);
        // Mesmo tempo de resposta exista ou não a conta (não revela e-mails cadastrados).
        if (!PoliticaSenha::verificar($senha, $user['senha'] ?? null)) {
            $tentativas->registrarFalha($chaveTentativa);
            $limiteIp->registrarTentativa();
            $limiteConta->registrarTentativa();
            $_SESSION['error'] = 'E-mail ou senha incorretos.';
            $this->redirect(BASE_URL . '/?rota=login');
        }
        // Hash antigo (bcrypt) é atualizado para Argon2id de forma transparente.
        if (PoliticaSenha::precisaRehash((string)$user['senha'])) {
            (new User)->atualizarHashSenha((int)$user['id'], PoliticaSenha::hash($senha));
        }

        if (!User::estaAtivo($user)) {
            $_SESSION['error'] = 'Essa conta está suspensa. Procure a administração.';
            $this->redirect(BASE_URL . '/?rota=login');
        }

        if (SegurancaConta::verificacaoHabilitada() && !SegurancaConta::emailVerificado($user)) {
            $_SESSION['error'] = 'Confirme seu e-mail antes de entrar. Você pode solicitar um novo link de confirmação.';
            $this->redirect(BASE_URL . '/?rota=reenviar-verificacao');
        }

        $role = $user['role'] ?? 'cidadao';

        // A aba escolhida precisa bater com o tipo real da conta.
        if ($tipoLogin === 'admin' && $role !== 'admin') {
            $_SESSION['error'] = 'Essa conta não tem acesso de administrador.';
            $this->redirect(BASE_URL . '/?rota=login');
        }
        if ($tipoLogin === 'cidadao' && $role === 'admin') {
            $_SESSION['error'] = 'Essa é uma conta de administrador. Entre pela aba "Sou Admin".';
            $this->redirect(BASE_URL . '/?rota=login');
        }

        $tentativas->limpar($chaveTentativa);
        $limiteIp->limpar();
        $limiteConta->limpar();
        session_regenerate_id(true);
        if ($role === 'admin' && SegurancaConta::doisFatoresAtivo($user)) {
            unset($_SESSION['user_id'], $_SESSION['admin_totp_ok']);
            $_SESSION['pending_2fa'] = ['id'=>(int)$user['id'], 'expira'=>time()+300, 'tentativas'=>0];
            $this->redirect(BASE_URL . '/?rota=segundo-fator');
        }
        unset($_SESSION['pending_2fa']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['auth_epoch'] = (int)($user['auth_epoch']??0);
        $_SESSION['ultima_atividade'] = time();
        unset($_SESSION['tipo_login_ativo']);

        if ($role === 'admin') {
            SegurancaConta::alertar($user, 'Novo acesso ao painel administrativo');
            if (Ambiente::admin2faObrigatorio()) {
                // Sem 2FA, o admin só consegue abrir a tela para ativá-la (ver Controller::auth).
                $_SESSION['error'] = 'Por segurança, ative a verificação em duas etapas para usar o painel administrativo.';
                $this->redirect(BASE_URL . '/?rota=configuracoes#seguranca-conta');
            }
            $this->redirect(BASE_URL . '/?rota=admin');
        }
        $this->redirect(BASE_URL . '/?rota=dashboard');
    }

    public function cadastro(): void {
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $this->view('auth/cadastro', compact('error'));
    }

    public function cadastrar(): void {
        $this->csrfCheck(BASE_URL . '/?rota=cadastro');

        // Limite por IP: no máximo 8 tentativas de cadastro a cada 10 minutos,
        // pra dificultar criação em massa de contas por script.
        $limiter = new RateLimiter('cadastro', maxTentativas: 8, bloqueioSegundos: 600);
        $bloqueado = $limiter->bloqueado();
        if ($bloqueado > 0) {
            $minutos = (int) ceil($bloqueado / 60);
            $_SESSION['error'] = "Muitas tentativas de cadastro. Tente novamente em {$minutos} minuto(s).";
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }
        $limiter->registrarTentativa();

        $nome = $this->textoPost('nome', 150);
        $email = $this->emailPost();
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '' || empty($_POST['termos'])) {
            $_SESSION['error'] = 'Preencha os campos e aceite os termos.';
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        if (mb_strlen($nome) < 2 || !preg_match('/^[\p{L}0-9 ._\-]+$/u', $nome)) {
            $_SESSION['error'] = 'Informe um nome válido (mínimo de 2 caracteres).';
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Informe um e-mail válido.';
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        if (($erroSenha = PoliticaSenha::erro((string)$senha)) !== null) {
            $_SESSION['error'] = $erroSenha;
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        if (!hash_equals($senha, $confirmarSenha)) {
            $_SESSION['error'] = 'As senhas não são iguais. Digite a mesma senha nos dois campos.';
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        if ((new User)->findByEmail($email)) {
            $_SESSION['error'] = 'Este e-mail já está cadastrado.';
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }

        try {
            (new User)->create(compact('nome', 'email', 'senha'));
        } catch (DomainException $erro) {
            $_SESSION['error'] = $erro->getMessage();
            $this->redirect(BASE_URL . '/?rota=cadastro');
        }
        $limiter->limpar();
        $novo=(new User)->findByEmail($email);
        if (SegurancaConta::verificacaoHabilitada() && $novo && !SegurancaConta::emailVerificado($novo)) {
            $enviado=SegurancaConta::enviarVerificacao($novo);
            $_SESSION['sucesso'] = $enviado
                ? 'Cadastro realizado! Confirme o link enviado ao seu e-mail antes de entrar.'
                : 'Cadastro criado, mas não foi possível enviar a confirmação. Solicite um novo link.';
            $this->redirect(BASE_URL . '/?rota=login');
        }

        Mailer::avisar(
            ['nome' => $nome, 'email' => $email],
            'Bem-vindo ao Olho Cidadão',
            'Sua conta foi criada!',
            [
                'Agora você pode registrar denúncias, acompanhar o andamento pelo protocolo e apoiar problemas do seu bairro.',
                'Você receberá um e-mail sempre que o status de uma denúncia sua mudar.',
            ],
            'Entrar no sistema',
            '/?rota=login'
        );
        $_SESSION['sucesso'] = 'Conta criada com sucesso! Faça login para continuar.';
        $this->redirect(BASE_URL . '/?rota=login');
    }

    /** Alias mantido para compatibilidade com versões anteriores do projeto. */
    public function registrar(): void {
        $this->cadastrar();
    }

    public function esqueciSenha(): void {
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);
        $linkTeste = $_SESSION['link_teste'] ?? null; unset($_SESSION['link_teste']);
        $this->view('auth/esqueci-senha', compact('error', 'sucesso', 'linkTeste'));
    }

    public function enviarLinkReset(): void {
        $this->csrfCheck(BASE_URL . '/?rota=esqueci-senha');

        // Limite por IP: no máximo 5 pedidos a cada 10 minutos — evita que
        // alguém use esse formulário pra "bombardear" a caixa de entrada de
        // terceiros com e-mails de redefinição, ou pra ficar testando e-mails.
        $limiter = new RateLimiter('esqueci-senha', maxTentativas: 5, bloqueioSegundos: 600);
        $bloqueado = $limiter->bloqueado();
        if ($bloqueado > 0) {
            $minutos = (int) ceil($bloqueado / 60);
            $_SESSION['error'] = "Muitos pedidos de redefinição. Tente novamente em {$minutos} minuto(s).";
            $this->redirect(BASE_URL . '/?rota=esqueci-senha');
        }
        $limiter->registrarTentativa();

        $email = $this->emailPost();
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Informe um e-mail válido.';
            $this->redirect(BASE_URL . '/?rota=esqueci-senha');
        }
        $user = (new User)->findByEmail($email);

        // Não revela se o e-mail existe ou não — mesma mensagem nos dois casos.
        $_SESSION['sucesso'] = 'Se esse e-mail estiver cadastrado, enviamos um link de redefinição de senha para ele.';

        $ambienteLocal = Ambiente::local();
        if ($user && (Mailer::smtpConfigurado() || $ambienteLocal)) {
            $token = (new User)->gerarTokenReset((int) $user['id']);
            // Link ABSOLUTO (com o endereço do site): num e-mail, "/?rota=..." sozinho não abre.
            $link = Mailer::url('/?rota=redefinir-senha&token=' . $token);

            $primeiroNome = explode(' ', trim((string) $user['nome']))[0];
            $paragrafos = [
                'Recebemos um pedido para redefinir a senha da sua conta no Olho Cidadão.',
                'O link abaixo vale por 1 hora. Se não foi você quem pediu, ignore este e-mail: sua senha continua a mesma.',
            ];
            $texto = "Olá, {$primeiroNome}!\n\n" . implode("\n\n", $paragrafos)
                . "\n\nRedefinir senha: {$link}\n\n— Olho Cidadão";

            $enviado = Mailer::smtpConfigurado() ? Mailer::enviar(
                $user['email'],
                $user['nome'],
                'Redefinição de senha - Olho Cidadão',
                $texto,
                Mailer::modelo('Redefinição de senha', "Olá, {$primeiroNome}!", $paragrafos, 'Redefinir minha senha', $link)
            ) : false;

            // Só mostramos o link direto na tela em "modo de teste" (sem SMTP
            // configurado ainda) — é o que permite testar o fluxo localmente
            // sem precisar de e-mail de verdade. Com SMTP já configurado, uma
            // falha de envio NUNCA expõe o link na tela (evitaria vazar o link
            // de redefinição pra alguém que não é o dono do e-mail); ela só
            // fica registrada no log do servidor pra você investigar depois.
            // Em hospedagem pública NUNCA mostrar token de redefinição sem e-mail.
            if (!$enviado && !Mailer::smtpConfigurado() && $ambienteLocal) {
                $_SESSION['link_teste'] = $link;
            }
        }

        $this->redirect(BASE_URL . '/?rota=esqueci-senha');
    }

    public function redefinirSenha(): void {
        $token = $_GET['token'] ?? '';
        $user = $token ? (new User)->findByTokenReset($token) : null;

        if (!$user) {
            $_SESSION['error'] = 'Este link de redefinição é inválido ou já expirou. Peça um novo.';
            $this->redirect(BASE_URL . '/?rota=esqueci-senha');
        }

        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $this->view('auth/redefinir-senha', compact('error', 'token'));
    }

    public function salvarNovaSenha(): void {
        $token = $_POST['token'] ?? '';
        $redirTokenUrl = BASE_URL . '/?rota=redefinir-senha&token=' . urlencode($token);
        $this->csrfCheck($redirTokenUrl);

        $user = $token ? (new User)->findByTokenReset($token) : null;
        if (!$user) {
            $_SESSION['error'] = 'Este link de redefinição é inválido ou já expirou. Peça um novo.';
            $this->redirect(BASE_URL . '/?rota=esqueci-senha');
        }

        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (($erroSenha = PoliticaSenha::erro((string)$senha)) !== null) {
            $_SESSION['error'] = $erroSenha;
            $this->redirect($redirTokenUrl);
        }
        if (!hash_equals($senha, $confirmarSenha)) {
            $_SESSION['error'] = 'As senhas não são iguais. Digite a mesma senha nos dois campos.';
            $this->redirect($redirTokenUrl);
        }

        (new User)->redefinirSenha((int) $user['id'], $senha);
        error_log('[SEGURANCA] Senha redefinida pelo link de e-mail | conta #' . (int) $user['id'] . ' | IP ' . Ambiente::ip());

        // Aviso de segurança: se não foi a própria pessoa, ela percebe na hora.
        Mailer::avisar(
            $user,
            'Sua senha foi alterada - Olho Cidadão',
            'Senha alterada',
            [
                'A senha da sua conta no Olho Cidadão acabou de ser alterada.',
                'Se foi você, não precisa fazer nada. Se não foi, peça uma nova redefinição de senha imediatamente.',
            ],
            'Ir para o login',
            '/?rota=login'
        );

        $_SESSION['sucesso'] = 'Senha redefinida com sucesso! Faça login com a nova senha.';
        $this->redirect(BASE_URL . '/?rota=login');
    }

    /** Confirmação do endereço, sem identificar usuário pelo token na URL. */
    public function verificarEmail(): void {
        $ok=SegurancaConta::confirmarEmail((string)($_GET['token']??''));
        $_SESSION[$ok?'sucesso':'error']=$ok
            ? 'E-mail confirmado! Agora você pode entrar.'
            : 'Link inválido ou expirado. Solicite outro link de confirmação.';
        $this->redirect(BASE_URL . '/?rota=login');
    }

    public function reenviarVerificacao(): void {
        $erro=$_SESSION['error']??null;unset($_SESSION['error']);
        $sucesso=$_SESSION['sucesso']??null;unset($_SESSION['sucesso']);
        $this->view('auth/reenviar-verificacao', compact('erro','sucesso'));
    }

    public function solicitarVerificacao(): void {
        $this->csrfCheck(BASE_URL . '/?rota=reenviar-verificacao');
        $limite=new RateLimiter('reenviar-verificacao', maxTentativas: 4, bloqueioSegundos: 900);
        if ($limite->bloqueado() > 0) {
            $_SESSION['error']='Muitas solicitações. Tente mais tarde.';
            $this->redirect(BASE_URL.'/?rota=reenviar-verificacao');
        }
        $limite->registrarTentativa();
        $email=$this->emailPost();
        if (filter_var($email,FILTER_VALIDATE_EMAIL) && SegurancaConta::verificacaoHabilitada()) {
            $u=(new User)->findByEmail($email);
            if ($u && !SegurancaConta::emailVerificado($u)) SegurancaConta::enviarVerificacao($u);
        }
        $_SESSION['sucesso']='Se houver uma conta pendente, enviaremos uma nova confirmação.';
        $this->redirect(BASE_URL.'/?rota=reenviar-verificacao');
    }

    public function segundoFator(): void {
        $pendente=$_SESSION['pending_2fa']??null;
        if (!is_array($pendente) || ($pendente['expira']??0)<time()) {
            unset($_SESSION['pending_2fa']);
            $_SESSION['error']='Autenticação expirada. Entre novamente.';
            $this->redirect(BASE_URL.'/?rota=login');
        }
        $erro=$_SESSION['error']??null;unset($_SESSION['error']);
        $this->view('auth/segundo-fator', compact('erro'));
    }

    public function validarSegundoFator(): void {
        $this->csrfCheck(BASE_URL.'/?rota=segundo-fator');
        $pendente=$_SESSION['pending_2fa']??null;
        if (!is_array($pendente) || ($pendente['expira']??0)<time() || ($pendente['tentativas']??0)>=5) {
            unset($_SESSION['pending_2fa']);
            $_SESSION['error']='Validação expirada ou limite de tentativas atingido.';
            $this->redirect(BASE_URL.'/?rota=login');
        }
        $limiter=new RateLimiter('segundo-fator', maxTentativas:10, bloqueioSegundos:900);
        if ($limiter->bloqueado()>0) {
            unset($_SESSION['pending_2fa']);
            $_SESSION['error']='Muitas tentativas de autenticação. Aguarde 15 minutos.';
            $this->redirect(BASE_URL.'/?rota=login');
        }
        $id=(int)$pendente['id'];
        $u=(new User)->find($id);
        $codigo=trim((string)($_POST['codigo']??''));
        $valido = false;
        $chaveIndisponivel = false;
        if ($u && User::estaAtivo($u) && ($u['role'] ?? '') === 'admin') {
            try {
                $valido = SegurancaConta::verificar2fa($id, $codigo);
            } catch (\RuntimeException $e) {
                // Não revelar exceções/paths na tela nem liberar acesso por falha.
                // O usuário ainda pode usar um backup válido, se houver.
                $chaveIndisponivel = true;
                error_log('[2FA] Validação não concluída: ' . $e->getMessage());
            }
        }
        if (!$valido) {
            $_SESSION['pending_2fa']['tentativas']++;
            $limiter->registrarTentativa();
            $_SESSION['error'] = $chaveIndisponivel
                ? 'A chave privada do 2FA não pôde ser lida. Restaure a chave original da instalação para usar o Google Authenticator.'
                : 'Código inválido ou já utilizado.';
            $this->redirect(BASE_URL.'/?rota=segundo-fator');
        }
        $limiter->limpar();
        session_regenerate_id(true);
        unset($_SESSION['pending_2fa']);
        $_SESSION['user_id']=$id;
        $_SESSION['auth_epoch']=(int)($u['auth_epoch']??0);
        $_SESSION['admin_totp_ok']=true;
        $_SESSION['ultima_atividade']=time();
        SegurancaConta::alertar($u, 'Novo acesso ao painel administrativo');
        $this->redirect(BASE_URL.'/?rota=admin');
    }

    public function logout(): void {
        // O link de "Sair" carrega um token (t=...). Sem ele, outro site poderia
        // deslogar o usuário só com uma imagem/link apontando pra cá.
        if (!empty($_SESSION['user_id'])) {
            $token = (string)($_GET['t'] ?? '');
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->redirect(BASE_URL . '/?rota=dashboard');
            }
        }
        $_SESSION = [];
        session_destroy();
        $this->redirect(BASE_URL . '/?rota=login');
    }
}
