<?php
class PerfilController extends Controller {
    public function index(): void {
        $usuario=(new User)->find($this->auth()); $error=$_SESSION['error']??null; unset($_SESSION['error']);
        $sucesso=$_SESSION['sucesso']??null; unset($_SESSION['sucesso']);
        $minhas = (new Denuncia)->byUser((int) $usuario['id']);
        $resumoPerfil = [
            'denuncias' => count($minhas),
            'resolvidas' => count(array_filter($minhas, fn($d) => ($d['status'] ?? '') === 'Resolvido')),
            'apoios' => array_sum(array_map(fn($d) => count($d['apoios'] ?? []), $minhas)),
            'desde' => !empty($usuario['criado_em']) ? date('m/Y', strtotime($usuario['criado_em'])) : null,
        ];
        $this->view('perfil/index',compact('usuario','error','sucesso','resumoPerfil'));
    }
    public function update(): void {
        $id=$this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=perfil');
        $usuarioAtual = (new User)->find($id);
        $data=['nome'=>$this->textoPost('nome', 150),'email'=>$this->emailPost(),'cidade'=>$this->textoPost('cidade', 150),'telefone'=>$this->textoPost('telefone', 30),'cpf'=>$this->textoPost('cpf', 14),'cep'=>$this->textoPost('cep', 9)];
        if (strcasecmp((string)($usuarioAtual['email']??''), $data['email'])!==0
            && (empty($_POST['senha_atual']) || !PoliticaSenha::verificar((string)$_POST['senha_atual'],$usuarioAtual['senha']??''))) {
            $_SESSION['error']='Informe a senha atual para trocar seu e-mail.';
            $this->redirect(BASE_URL.'/?rota=perfil');
        }
        if (!empty($_POST['nova_senha'])) {
            // Exige a senha atual antes de trocar — sem isso, quem pegasse uma
            // sessão aberta (computador compartilhado, etc.) trocaria a senha
            // sem precisar saber a antiga.
            if (empty($_POST['senha_atual']) || !PoliticaSenha::verificar((string)$_POST['senha_atual'], $usuarioAtual['senha'] ?? '')) {
                $_SESSION['error']='Senha atual incorreta.'; $this->redirect(BASE_URL . '/?rota=perfil');
            }
            if (($erroSenha = PoliticaSenha::erro((string)$_POST['nova_senha'])) !== null) {
                $_SESSION['error'] = $erroSenha; $this->redirect(BASE_URL . '/?rota=perfil');
            }
            if ($_POST['nova_senha'] !== ($_POST['confirma_nova_senha'] ?? '')) { $_SESSION['error']='As senhas não coincidem.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
            $data['senha']=$_POST['nova_senha'];
        }
        if ($data['nome'] === '' || $data['email'] === '') { $_SESSION['error']='Nome e e-mail são obrigatórios.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if (mb_strlen($data['nome']) < 2 || !preg_match('/^[\p{L}0-9 ._\-]+$/u', $data['nome'])) { $_SESSION['error']='Informe um nome válido.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 190) { $_SESSION['error']='Informe um e-mail válido.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if ($data['cidade'] !== '' && !preg_match('/^[\p{L}0-9 ._\-]+$/u', $data['cidade'])) { $_SESSION['error']='Informe uma cidade válida.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if (!$this->telefoneValido($data['telefone'])) { $_SESSION['error']='Informe um telefone válido com DDD.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if (!$this->cpfValido($data['cpf'])) { $_SESSION['error']='Informe um CPF válido.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if (!$this->cepValido($data['cep'])) { $_SESSION['error']='Informe um CEP válido com 8 dígitos.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        $data['telefone'] = $this->somenteDigitos($data['telefone']);
        $data['cpf'] = $this->somenteDigitos($data['cpf']);
        $data['cep'] = $this->somenteDigitos($data['cep']);
        if ((new User)->cpfEmUsoPorOutro($data['cpf'], $id)) { $_SESSION['error']='Esse CPF já está cadastrado em outra conta.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        if ((new User)->emailEmUsoPorOutro($data['email'], $id)) { $_SESSION['error']='Esse e-mail já está sendo usado por outra conta.'; $this->redirect(BASE_URL . '/?rota=perfil'); }
        // A foto só é salva depois que todo o resto passou na validação —
        // assim, se algo der errado acima, não sobra arquivo solto em uploads/.
        $resultadoUpload = $this->processarUploadImagem('foto', 'avatar_' . $id);
        if ($resultadoUpload === null) {
            $_SESSION['error'] = 'Não foi possível enviar a foto (formato ou tamanho inválido — use JPG, PNG ou WEBP de até 5 MB).';
            $this->redirect(BASE_URL . '/?rota=perfil');
        }
        if ($resultadoUpload !== '') {
            $data['foto'] = $resultadoUpload;
        }
        try {
            $atualizado = (new User)->update($id, $data);
        } catch (DomainException $erro) {
            if ($resultadoUpload !== '') Uploader::apagarUploadPrivado($resultadoUpload);
            $_SESSION['error'] = $erro->getMessage();
            $this->redirect(BASE_URL . '/?rota=perfil');
        } catch (Throwable $erro) {
            if ($resultadoUpload !== '') Uploader::apagarUploadPrivado($resultadoUpload);
            throw $erro;
        }
        if (!$atualizado) {
            if ($resultadoUpload !== '') Uploader::apagarUploadPrivado($resultadoUpload);
            $_SESSION['error'] = 'Não foi possível atualizar o perfil.';
            $this->redirect(BASE_URL . '/?rota=perfil');
        }
        $mensagem='Perfil atualizado com sucesso.';
        if (SegurancaConta::verificacaoHabilitada()
            && strcasecmp((string)($usuarioAtual['email']??''), $data['email'])!==0
            && ($atualizado['role']??'cidadao')!=='admin') {
            $enviado=SegurancaConta::enviarVerificacao($atualizado);
            $mensagem=$enviado ? 'Perfil atualizado. Confirme seu novo e-mail pelo link enviado.'
                : 'Perfil atualizado, mas o e-mail de confirmação não pôde ser enviado. Solicite novo link.';
        }
        if (!empty($data['senha'])) {
            $_SESSION['auth_epoch']=(int)($atualizado['auth_epoch']??0);
            SegurancaConta::alertar($atualizado, 'Sua senha foi alterada', ['As outras sessões abertas da conta foram encerradas.']);
        }
        if (strcasecmp((string)($usuarioAtual['email']??''), $data['email'])!==0) {
            // Avisa o e-mail ANTIGO: se alguém roubou a sessão e trocou o e-mail, o dono fica sabendo.
            SegurancaConta::alertar($usuarioAtual, 'O e-mail da sua conta foi alterado', ['Novo e-mail cadastrado: ' . $data['email'] . '.']);
        }
        $_SESSION['sucesso'] = $mensagem;
        if (SegurancaConta::verificacaoHabilitada() && !SegurancaConta::emailVerificado($atualizado)) {
            $_SESSION = ['sucesso'=>$mensagem];
            session_regenerate_id(true);
            $this->redirect(BASE_URL . '/?rota=reenviar-verificacao');
        }
        $this->redirect(BASE_URL . '/?rota=perfil');
    }

    /** Baixa um JSON com tudo que o sistema guarda sobre a pessoa (direito de acesso — LGPD). */
    public function exportarDados(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=perfil');
        $u = (new User)->find($id) ?? [];
        unset($u['cpf_ilegivel'], $u['senha'], $u['reset_token'], $u['reset_expira'], $u['email_token_hash'], $u['email_token_expira'],
            $u['totp_segredo'], $u['totp_backups'], $u['totp_ultimo_passo'], $u['auth_epoch']);

        $pacote = [
            'gerado_em' => date('Y-m-d H:i:s'),
            'conta' => $u,
            'denuncias' => (new Denuncia)->byUser($id),
            'notificacoes' => (new Notificacao)->porUsuario($id),
        ];
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="meus-dados-olho-cidadao.json"');
        echo json_encode($pacote, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Exclui a conta (pede a senha). As denúncias ficam registradas, mas sem ligação com a pessoa. */
    public function excluirConta(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=perfil');
        $u = (new User)->find($id);

        if (($u['role'] ?? 'cidadao') === 'admin') {
            $_SESSION['error'] = 'Contas de administrador não podem ser excluídas por aqui.';
            $this->redirect(BASE_URL . '/?rota=perfil');
        }
        if (empty($_POST['senha_exclusao']) || !PoliticaSenha::verificar((string)$_POST['senha_exclusao'], $u['senha'] ?? '')) {
            $_SESSION['error'] = 'Senha incorreta. A conta não foi excluída.';
            $this->redirect(BASE_URL . '/?rota=perfil');
        }

        // Mantém ocorrências para acompanhamento, mas sem associação direta ao titular.
        (new Denuncia)->desvincularUsuario($id);
        (new User)->excluir($id);
        (new Notificacao)->apagarDoUsuario($id);
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['sucesso'] = 'Sua conta foi excluída.';
        $this->redirect(BASE_URL . '/?rota=login');
    }
}
