<?php
class AdminController extends Controller {
    public function index(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);

        $denunciaModel = new Denuncia;
        $userModel = new User;

        $todas = array_reverse($denunciaModel->todas());

        $mapaFiltro = [
            'pendente' => 'pendente',
            'analise' => 'em análise',
            'aguardando' => 'aguardando confirmação',
            'resolvido' => 'resolvido',
            'rejeitada' => 'rejeitada',
            'cancelada' => 'cancelada'
        ];

        $filtro = $_GET['status'] ?? 'todas';
        if (!isset($mapaFiltro[$filtro]) && $filtro !== 'atrasadas') {
            $filtro = 'todas';
        }

        $categoriasValidas = ['iluminacao', 'sinalizacao', 'lixo', 'buracos', 'esgoto', 'agua', 'arborizacao', 'outros'];
        $categoriaFiltro = $_GET['categoria'] ?? 'todas';
        if (!in_array($categoriaFiltro, $categoriasValidas, true)) {
            $categoriaFiltro = 'todas';
        }

        if ($filtro === 'todas') {
            $denuncias = $todas;
        } elseif ($filtro === 'atrasadas') {
            // Só as que estouraram o prazo da categoria, da mais antiga pra mais recente.
            $denuncias = array_values(array_filter($todas, fn($d) => Denuncia::statusPrazo($d)['atrasada']));
            usort($denuncias, fn($a, $b) => Denuncia::statusPrazo($b)['dias'] <=> Denuncia::statusPrazo($a)['dias']);
        } else {
            $denuncias = array_values(array_filter(
                $todas,
                fn($d) => strtolower($d['status'] ?? '') === $mapaFiltro[$filtro]
            ));
        }

        if ($categoriaFiltro !== 'todas') {
            $denuncias = array_values(array_filter(
                $denuncias,
                fn($d) => ($d['categoria'] ?? '') === $categoriaFiltro
            ));
        }

        $orgaoFiltro = (string) ($_GET['orgao'] ?? 'todos');
        if (!isset(Denuncia::ORGAOS[$orgaoFiltro])) $orgaoFiltro = 'todos';
        if ($orgaoFiltro !== 'todos') {
            $denuncias = array_values(array_filter($denuncias, fn($d) => ($d['orgao'] ?? '') === $orgaoFiltro));
        }

        // Anexa o nome de quem denunciou ANTES da busca por texto, pra poder
        // pesquisar também pelo nome do cidadão (não só categoria/descrição).
        $cacheUsuarios = [];
        foreach ($denuncias as &$d) {
            $uid = (int)($d['usuario_id'] ?? 0);
            if (!isset($cacheUsuarios[$uid])) {
                $cacheUsuarios[$uid] = $userModel->find($uid);
            }
            $d['usuario_nome'] = $cacheUsuarios[$uid]['nome'] ?? 'Usuário removido';
        }
        unset($d);

        // Busca por texto: procura o termo na descrição, na categoria ou no nome do cidadão.
        $busca = trim($_GET['busca'] ?? '');
        if ($busca !== '') {
            $termo = mb_strtolower($busca);
            $denuncias = array_values(array_filter(
                $denuncias,
                fn($d) =>
                    str_contains(mb_strtolower($d['descricao'] ?? ''), $termo) ||
                    str_contains(mb_strtolower($d['categoria'] ?? ''), $termo) ||
                    str_contains(mb_strtolower($d['usuario_nome'] ?? ''), $termo) ||
                    str_contains(mb_strtolower(Denuncia::protocolo($d)), $termo) ||
                    $termo === '#' . (int) ($d['id'] ?? 0)
            ));
        }

        // Ordenação opcional: mais urgentes primeiro (sort estável: dentro de cada nível mantém a ordem atual).
        $ordem = ($_GET['ordem'] ?? '') === 'prioridade' ? 'prioridade' : 'recentes';
        if ($ordem === 'prioridade') {
            usort($denuncias, fn($a, $b) => Denuncia::pesoPrioridade($b) <=> Denuncia::pesoPrioridade($a));
        }

        // Paginação: só fatia a lista já filtrada, pra não sobrecarregar a tela
        // quando o volume de denúncias crescer.
        $porPagina = 10;
        $totalFiltrado = count($denuncias);
        $totalPaginas = max(1, (int) ceil($totalFiltrado / $porPagina));
        $pagina = (int) ($_GET['pagina'] ?? 1);
        if ($pagina < 1) $pagina = 1;
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;
        $denuncias = array_slice($denuncias, ($pagina - 1) * $porPagina, $porPagina);

        // Badge de "novo": conta quantas denúncias (no total, sem filtro) o admin
        // ainda não viu, e marca como "eh_novo" as que estão sendo exibidas agora.
        $totalNaoVistas = count(array_filter($todas, fn($d) => empty($d['vista_admin'])));

        foreach ($denuncias as &$d) {
            $d['eh_novo'] = empty($d['vista_admin']);
        }
        unset($d);

        // Só depois de já ter marcado quem é "novo" pra exibir agora é que
        // gravamos como vista — assim, na próxima visita, o selo já some.
        $denunciaModel->marcarVistas(array_column($denuncias, 'id'));

        $tempoMedio = $denunciaModel->tempoMedioResolucaoHoras();

        $stats = [
            'total' => count($todas),
            'pendentes' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'pendente')),
            'analise' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'em análise')),
            'aguardando' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'aguardando confirmação')),
            'resolvidas' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'resolvido')),
            'atrasadas' => count(array_filter($todas, fn($d) => Denuncia::statusPrazo($d)['atrasada'])),
            'rejeitadas' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'rejeitada')),
            'canceladas' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'cancelada')),
            'tempo_medio_texto' => $this->formatarTempoMedio($tempoMedio)
        ];

        $this->view('admin/index', compact('usuario', 'denuncias', 'stats', 'filtro', 'categoriaFiltro', 'orgaoFiltro', 'busca', 'pagina', 'totalPaginas', 'totalFiltrado', 'totalNaoVistas', 'ordem', 'error', 'sucesso'));
    }

    /** Transforma horas decimais em um texto curto tipo "2 dias e 4h" ou "5h30" para o card do painel. */
    private function formatarTempoMedio(?float $horas): string {
        if ($horas === null) return '—';

        $totalMinutos = (int) round($horas * 60);
        $dias = intdiv($totalMinutos, 1440);
        $horasRestantes = intdiv($totalMinutos % 1440, 60);

        if ($dias > 0) {
            return $dias . ($dias === 1 ? ' dia' : ' dias') . ($horasRestantes > 0 ? ' e ' . $horasRestantes . 'h' : '');
        }

        $minutosRestantes = $totalMinutos % 60;
        return $horasRestantes . 'h' . str_pad((string)$minutosRestantes, 2, '0', STR_PAD_LEFT);
    }

    /** Lista cronológica de todas as denúncias (qualquer status, incluindo canceladas), com o histórico de status de cada uma. */
    public function historico(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $userModel = new User;

        $todas = array_reverse((new Denuncia)->todas());

        $cacheUsuarios = [];
        foreach ($todas as &$d) {
            $uid = (int)($d['usuario_id'] ?? 0);
            if (!isset($cacheUsuarios[$uid])) {
                $cacheUsuarios[$uid] = $userModel->find($uid);
            }
            $d['usuario_nome'] = $cacheUsuarios[$uid]['nome'] ?? 'Usuário removido';
        }
        unset($d);

        $this->view('admin/historico', compact('usuario', 'todas'));
    }

    /** Página de estatísticas: números gerais e gráficos simples (barras feitas em CSS, sem biblioteca). */
    public function estatisticas(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $est = (new Denuncia)->estatisticas();
        $est['tempo_medio_texto'] = $this->formatarTempoMedio((new Denuncia)->tempoMedioResolucaoHoras());
        $est['total_usuarios'] = count(array_filter((new User)->todos(), fn($u) => ($u['role'] ?? 'cidadao') !== 'admin'));
        // Pontos para o mapa de calor (só denúncias com localização, sem canceladas).
        $pontosCalor = array_values(array_map(
            fn($d) => [(float) $d['latitude'], (float) $d['longitude'], min(3, 1 + count($d['apoios'] ?? []) * 0.5)],
            array_filter(
                (new Denuncia)->todas(),
                fn($d) => !empty($d['latitude']) && !empty($d['longitude']) && !in_array(strtolower($d['status'] ?? ''), ['cancelada', 'rejeitada'], true)
            )
        ));
        $comparacao = (new Denuncia)->comparacaoMensal();
        $atividadeDias = (new Denuncia)->atividadePorDia(26);
        $this->view('admin/estatisticas', compact('usuario', 'est', 'pontosCalor', 'comparacao', 'atividadeDias'));
    }

    /** Gestão de usuários: lista com busca, promover/rebaixar administrador e suspender/reativar contas. */
    /**
     * Verificação do histórico à prova de alteração (v0.50): confere a
     * corrente de selos de todas as denúncias e mostra se alguma foi mexida
     * direto no arquivo, fora do sistema.
     */
    /** Painel de segurança: confere configuração, backups, 2FA, tentativas de invasão etc. */
    public function seguranca(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $painel = PainelSeguranca::verificar();
        $this->view('admin/seguranca', compact('usuario', 'painel'));
    }

    public function integridade(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $todas = (new Denuncia)->todas();
        $resultado = Integridade::verificar($todas);
        $total = count($todas);
        $this->view('admin/integridade', compact('usuario', 'resultado', 'total'));
    }

    public function usuarios(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);

        $busca = trim((string)($_GET['busca'] ?? ''));
        $lista = (new User)->todos();
        if ($busca !== '') {
            $termo = mb_strtolower($busca);
            $lista = array_values(array_filter($lista, fn($u) =>
                str_contains(mb_strtolower($u['nome'] ?? ''), $termo) ||
                str_contains(mb_strtolower($u['email'] ?? ''), $termo)));
        }

        // Quantas denúncias cada pessoa já fez.
        $contagem = [];
        foreach ((new Denuncia)->todas() as $d) {
            $uid = (int)($d['usuario_id'] ?? 0);
            $contagem[$uid] = ($contagem[$uid] ?? 0) + 1;
        }

        $this->view('admin/usuarios', compact('usuario', 'lista', 'contagem', 'busca', 'error', 'sucesso'));
    }

    /** Ações sobre uma conta: tornar/remover admin, suspender/reativar. Nunca vale para a própria conta do admin. */
    public function alterarUsuario(): void {
        $adminId = $this->authAdmin();
        $this->csrfCheck(BASE_URL . '/?rota=admin-usuarios');

        $alvoId = (int)($_POST['id'] ?? 0);
        $acao = (string)($_POST['acao'] ?? '');
        $userModel = new User;
        $alvo = $userModel->find($alvoId);

        if (!$alvo || !in_array($acao, ['promover', 'rebaixar', 'suspender', 'reativar'], true)) {
            $_SESSION['error'] = 'Ação inválida ou usuário não encontrado.';
            $this->redirect(BASE_URL . '/?rota=admin-usuarios');
        }
        if ($alvoId === $adminId) {
            $_SESSION['error'] = 'Você não pode alterar a sua própria conta por aqui (evita ficar sem nenhum administrador).';
            $this->redirect(BASE_URL . '/?rota=admin-usuarios');
        }

        [$role, $ativo, $texto] = match ($acao) {
            'promover'  => ['admin', null, 'Tornou administrador:'],
            'rebaixar'  => ['cidadao', null, 'Removeu o acesso de administrador de'],
            'suspender' => [null, false, 'Suspendeu a conta de'],
            'reativar'  => [null, true, 'Reativou a conta de'],
        };
        $userModel->alterarAcesso($alvoId, $role, $ativo);

        $admin = $userModel->find($adminId);
        (new LogAuditoria)->registrar($adminId, $admin['nome'] ?? ('Admin #' . $adminId), sprintf('%s %s (#%d)', $texto, $alvo['nome'] ?? '—', $alvoId));

        $_SESSION['sucesso'] = 'Usuário atualizado com sucesso.';
        $this->redirect(BASE_URL . '/?rota=admin-usuarios');
    }

    /** Tela de auditoria: últimas ações administrativas registradas no sistema. */
    public function auditoria(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);

        $registros = (new LogAuditoria)->ultimos(200);

        $this->view('admin/auditoria', compact('usuario', 'registros'));
    }

    public function atualizarStatus(): void {
        $adminId = $this->authAdmin();
        $this->csrfCheck(BASE_URL . '/?rota=admin');

        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        if ($id <= 0 || !in_array($status, ['Pendente','Em análise','Aguardando confirmação','Rejeitada'], true)) {
            $_SESSION['error'] = 'Dados inválidos para atualização da denúncia.';
            $this->redirect(BASE_URL . '/?rota=admin');
        }
        // Mensagem curta e opcional do admin explicando a mudança (ex.: "equipe já foi enviada ao local").
        $mensagem = trim(mb_substr(trim($_POST['mensagem'] ?? ''), 0, 300));
        if ($status === 'Rejeitada' && mb_strlen($mensagem) < 10) {
            $_SESSION['error'] = 'Para rejeitar, informe um motivo com pelo menos 10 caracteres.';
            $this->redirect(BASE_URL . '/?rota=admin');
        }

        // Monta a query de volta (filtros atuais) já aqui em cima, pra poder
        // usar tanto num redirect antecipado (foto inválida) quanto no final.
        $filtro = $_POST['filtro'] ?? 'todas';
        $categoria = $_POST['categoria_filtro'] ?? 'todas';
        $query = '';
        if ($filtro !== 'todas') $query .= '&status=' . urlencode($filtro);
        if ($categoria !== 'todas') $query .= '&categoria=' . urlencode($categoria);
        $orgaoFiltro = (string) ($_POST['orgao_filtro'] ?? 'todos');
        if (isset(Denuncia::ORGAOS[$orgaoFiltro])) $query .= '&orgao=' . urlencode($orgaoFiltro);

        // Foto de comprovação (opcional): mesma validação de MIME/tamanho usada
        // na foto da própria denúncia. Se vier um arquivo inválido, avisa e não
        // aplica nada — pra não perder a mensagem/status por causa de um upload ruim.
        $fotoResolucao = $this->processarUploadImagem('foto_resolucao', 'resolucao_' . $id);
        if ($fotoResolucao === null) {
            $_SESSION['error'] = 'Não foi possível enviar a foto de comprovação (formato ou tamanho inválido — use JPG, PNG ou WEBP de até 5 MB).';
            $this->redirect(BASE_URL . '/?rota=admin' . $query);
        }

        $denunciaModel = new Denuncia;
        $denunciaAntes = $denunciaModel->find($id);
        try {
            $ok = $denunciaModel->atualizarStatus($id, $status, $mensagem, $fotoResolucao);
        } catch (Throwable $erro) {
            if ($fotoResolucao !== '') Uploader::apagarUploadPrivado($fotoResolucao);
            throw $erro;
        }

        if (!$ok) {
            if ($fotoResolucao !== '') Uploader::apagarUploadPrivado($fotoResolucao);
            // Cobre tanto "denúncia não encontrada" quanto "status inválido"
            // (o Model já rejeita qualquer valor fora da lista permitida).
            $_SESSION['error'] = 'Não foi possível atualizar essa denúncia. Status inválido ou denúncia não encontrada.';
        }

        // Só notifica o cidadão (e registra na auditoria) se o status realmente mudou.
        if ($ok && $denunciaAntes && (($denunciaAntes['status'] ?? '') !== $status || $mensagem !== '' || $fotoResolucao !== '')) {
            $_SESSION['sucesso'] = ($denunciaAntes['status'] ?? '') !== $status ? 'Status atualizado com sucesso.' : 'Observação registrada no histórico.';
            $admin = $this->registrarMudancaStatus($adminId, $denunciaAntes, $status, $mensagem, $fotoResolucao !== '');
        }

        // Prioridade (opcional no formulário): só grava e registra se realmente mudou.
        $prioridade = trim((string)($_POST['prioridade'] ?? ''));
        if ($ok && $prioridade !== '' && $denunciaAntes && $prioridade !== ($denunciaAntes['prioridade'] ?? 'Normal')
            && $denunciaModel->definirPrioridade($id, $prioridade)) {
            $admin = $admin ?? (new User)->find($adminId);
            (new LogAuditoria)->registrar(
                $adminId,
                $admin['nome'] ?? ('Admin #' . $adminId),
                sprintf('Mudou a prioridade da denúncia #%d de "%s" para "%s"', $id, $denunciaAntes['prioridade'] ?? 'Normal', $prioridade),
                $id
            );
            if (empty($_SESSION['sucesso']) && empty($_SESSION['error'])) {
                $_SESSION['sucesso'] = 'Prioridade atualizada.';
            }
        }

        $this->redirect(BASE_URL . '/?rota=admin' . $query);
    }

    /**
     * Encaminha a denúncia para um órgão da prefeitura (Obras, Trânsito...).
     * Grava o prazo de resposta do órgão, avisa o cidadão e registra na auditoria.
     */
    /**
     * Exclui uma denúncia definitivamente (denúncia de teste, spam ou
     * duplicada). Exige a caixa de confirmação marcada, apaga as fotos e as
     * notificações ligadas a ela e registra a ação na auditoria.
     */
    public function excluirDenuncia(): void {
        $adminId = $this->authAdmin();

        $filtro = $_POST['filtro'] ?? 'todas';
        $categoria = $_POST['categoria_filtro'] ?? 'todas';
        $query = '';
        if ($filtro !== 'todas') $query .= '&status=' . urlencode((string) $filtro);
        if ($categoria !== 'todas') $query .= '&categoria=' . urlencode((string) $categoria);
        $orgaoFiltro = (string) ($_POST['orgao_filtro'] ?? 'todos');
        if (isset(Denuncia::ORGAOS[$orgaoFiltro])) $query .= '&orgao=' . urlencode($orgaoFiltro);
        $voltar = BASE_URL . '/?rota=admin' . $query;

        $this->csrfCheck($voltar);

        $id = (int) ($_POST['id'] ?? 0);
        if (($_POST['confirmar'] ?? '') !== '1') {
            $_SESSION['error'] = 'Marque a confirmação para excluir a denúncia.';
            $this->redirect($voltar);
        }

        $removida = (new Denuncia)->excluir($id);
        if ($removida === null) {
            $_SESSION['error'] = 'Denúncia não encontrada.';
            $this->redirect($voltar);
        }

        // Fotos guardadas fora da pasta pública
        $fotos = array_merge(
            [(string) ($removida['foto'] ?? ''), (string) ($removida['foto_resolucao'] ?? '')],
            array_map('strval', is_array($removida['fotos'] ?? null) ? $removida['fotos'] : [])
        );
        foreach (array_unique(array_filter($fotos)) as $url) {
            Uploader::apagarUploadPrivado($url);
        }
        (new Notificacao)->apagarDaDenuncia($id);

        $protocolo = Denuncia::protocolo($removida);
        $admin = (new User)->find($adminId);
        (new LogAuditoria)->registrar(
            $adminId,
            $admin['nome'] ?? ('Admin #' . $adminId),
            'Excluiu a denúncia ' . $protocolo . ' (' . ($removida['categoria'] ?? '') . ')',
            $id
        );

        $_SESSION['sucesso'] = 'Denúncia ' . $protocolo . ' excluída.';
        $this->redirect($voltar);
    }

    public function encaminhar(): void {
        $adminId = $this->authAdmin();

        $filtro = $_POST['filtro'] ?? 'todas';
        $categoria = $_POST['categoria_filtro'] ?? 'todas';
        $query = '';
        if ($filtro !== 'todas') $query .= '&status=' . urlencode($filtro);
        if ($categoria !== 'todas') $query .= '&categoria=' . urlencode($categoria);
        $orgaoFiltro = (string) ($_POST['orgao_filtro'] ?? 'todos');
        if (isset(Denuncia::ORGAOS[$orgaoFiltro])) $query .= '&orgao=' . urlencode($orgaoFiltro);

        $this->csrfCheck(BASE_URL . '/?rota=admin' . $query);

        $id = (int)($_POST['id'] ?? 0);
        $orgao = (string)($_POST['orgao'] ?? '');
        $prazoTexto = trim((string) ($_POST['prazo_dias'] ?? ''));
        $prazoDias = null;
        if ($prazoTexto !== '') {
            if (!ctype_digit($prazoTexto) || (int) $prazoTexto < 1 || (int) $prazoTexto > 180) {
                $_SESSION['error'] = 'Informe um prazo previsto entre 1 e 180 dias.';
                $this->redirect(BASE_URL . '/?rota=admin' . $query);
            }
            $prazoDias = (int) $prazoTexto;
        }
        $denunciaModel = new Denuncia;
        $antes = $denunciaModel->find($id);

        if (!$antes || !isset(Denuncia::ORGAOS[$orgao])) {
            $_SESSION['error'] = 'Órgão ou denúncia inválidos.';
            $this->redirect(BASE_URL . '/?rota=admin' . $query);
        }
        if (($antes['orgao'] ?? null) === $orgao && ($prazoDias === null || $prazoDias === (int) ($antes['prazo_dias_encaminhamento'] ?? Denuncia::ORGAOS[$orgao]['prazo']))) {
            $_SESSION['error'] = 'Essa denúncia já está com esse órgão e prazo.';
            $this->redirect(BASE_URL . '/?rota=admin' . $query);
        }

        $texto = $denunciaModel->encaminhar($id, $orgao, '', $prazoDias);
        if ($texto === null) {
            $_SESSION['error'] = 'Não foi possível encaminhar essa denúncia (talvez já esteja resolvida ou cancelada).';
            $this->redirect(BASE_URL . '/?rota=admin' . $query);
        }

        $admin = (new User)->find($adminId);
        (new LogAuditoria)->registrar(
            $adminId,
            $admin['nome'] ?? ('Admin #' . $adminId),
            sprintf('Encaminhou a denúncia #%d para %s', $id, Denuncia::ORGAOS[$orgao]['nome']),
            $id
        );

        $aviso = 'Sua denúncia de "' . ($antes['categoria'] ?? '') . '" foi ' . lcfirst($texto);
        (new Notificacao)->criar((int) ($antes['usuario_id'] ?? 0), $aviso, $id);
        $dono = (new User)->find((int) ($antes['usuario_id'] ?? 0));
        if ($dono) {
            Mailer::avisar($dono, 'Sua denúncia foi encaminhada - Olho Cidadão', 'Sua denúncia foi encaminhada', [$aviso], 'Acompanhar denúncia', '/?rota=denuncia-ver&id=' . $id);
        }

        $_SESSION['sucesso'] = $texto;
        $this->redirect(BASE_URL . '/?rota=admin' . $query);
    }

    /**
     * Depois que o status muda: registra na auditoria, cria a notificação para o
     * cidadão e manda o e-mail (se o SMTP estiver configurado). Usado pelo
     * formulário da lista e pelo quadro kanban. Devolve o admin (para reuso).
     */
    private function registrarMudancaStatus(int $adminId, array $denunciaAntes, string $status, string $mensagem = '', bool $comFoto = false, string $origem = ''): ?array {
        $id = (int) $denunciaAntes['id'];
        $admin = (new User)->find($adminId);
        $descricaoLog = ($denunciaAntes['status'] ?? '') === $status
            ? sprintf('Registrou uma observação na denúncia #%d (status "%s")', $id, $status)
            : sprintf('Alterou o status da denúncia #%d de "%s" para "%s"', $id, $denunciaAntes['status'] ?? '—', $status);
        if ($comFoto) $descricaoLog .= ' (com foto de comprovação anexada)';
        if ($origem !== '') $descricaoLog .= ' ' . $origem;
        (new LogAuditoria)->registrar($adminId, $admin['nome'] ?? ('Admin #' . $adminId), $descricaoLog, $id);

        if ($status === 'Aguardando confirmação') {
            $texto = 'A prefeitura marcou sua denúncia de "' . $denunciaAntes['categoria'] . '" como resolvida. Confirma que o problema foi mesmo solucionado?';
        } else {
            $texto = 'Sua denúncia de "' . $denunciaAntes['categoria'] . '" mudou para "' . $status . '".';
        }
        if ($mensagem !== '') {
            $texto .= ' Observação da prefeitura: "' . $mensagem . '"';
        }
        (new Notificacao)->criar((int) ($denunciaAntes['usuario_id'] ?? 0), $texto, $id);

        $dono = (new User)->find((int) ($denunciaAntes['usuario_id'] ?? 0));
        if ($dono) {
            Mailer::avisar($dono, 'Atualização da sua denúncia - Olho Cidadão', 'Sua denúncia foi atualizada', [$texto], 'Acompanhar denúncia', '/?rota=denuncia-ver&id=' . $id);
        }
        return $admin;
    }

    /** Quadro kanban: uma coluna por status; o admin arrasta os cartões entre as colunas. */
    public function kanban(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $userModel = new User;

        $colunas = ['Pendente' => [], 'Em análise' => [], 'Aguardando confirmação' => [], 'Resolvido' => []];
        $filtroCategoria = (string) ($_GET['categoria'] ?? 'todas');
        $categorias = ['iluminacao', 'sinalizacao', 'lixo', 'buracos', 'esgoto', 'agua', 'arborizacao', 'outros'];
        if (!in_array($filtroCategoria, $categorias, true)) $filtroCategoria = 'todas';
        $filtroOrgao = (string) ($_GET['orgao'] ?? 'todos');
        if (!isset(Denuncia::ORGAOS[$filtroOrgao])) $filtroOrgao = 'todos';
        $filtroPrioridade = (string) ($_GET['prioridade'] ?? 'todas');
        if (!in_array($filtroPrioridade, Denuncia::PRIORIDADES, true)) $filtroPrioridade = 'todas';
        $apenasAtrasadas = ($_GET['prazo'] ?? '') === 'atrasadas';
        $buscaKanban = mb_substr(trim((string) ($_GET['busca'] ?? '')), 0, 80);
        $todas = (new Denuncia)->todas();
        $todas = array_values(array_filter($todas, static function ($d) use ($filtroCategoria, $filtroOrgao, $filtroPrioridade, $apenasAtrasadas, $buscaKanban) {
            if ($filtroCategoria !== 'todas' && ($d['categoria'] ?? '') !== $filtroCategoria) return false;
            if ($filtroOrgao !== 'todos' && ($d['orgao'] ?? '') !== $filtroOrgao) return false;
            if ($filtroPrioridade !== 'todas' && ($d['prioridade'] ?? 'Normal') !== $filtroPrioridade) return false;
            if ($apenasAtrasadas && !Denuncia::statusPrazo($d)['atrasada']) return false;
            if ($buscaKanban !== '') {
                $conteudo = mb_strtolower(Denuncia::protocolo($d) . ' ' . ($d['descricao'] ?? '') . ' ' . ($d['endereco'] ?? ''));
                if (!str_contains($conteudo, mb_strtolower($buscaKanban))) return false;
            }
            return true;
        }));
        usort($todas, fn($a, $b) => (Denuncia::pesoPrioridade($b) <=> Denuncia::pesoPrioridade($a)) ?: strcmp($b['criada_em'] ?? '', $a['criada_em'] ?? ''));
        $cache = [];
        foreach ($todas as $d) {
            $st = $d['status'] ?? 'Pendente';
            if (!isset($colunas[$st])) continue; // canceladas ficam fora do quadro
            $uid = (int) ($d['usuario_id'] ?? 0);
            $cache[$uid] = $cache[$uid] ?? $userModel->find($uid);
            $d['usuario_nome'] = $cache[$uid]['nome'] ?? 'Usuário removido';
            $colunas[$st][] = $d;
        }
        $totalResolvidas = count($colunas['Resolvido']);
        $colunas['Resolvido'] = array_slice($colunas['Resolvido'], 0, 12);

        $this->view('admin/kanban', compact('usuario', 'colunas', 'totalResolvidas', 'filtroCategoria', 'filtroOrgao', 'filtroPrioridade', 'apenasAtrasadas', 'buscaKanban'));
    }

    /** Recebe o "soltar" do kanban (via fetch) e muda o status. Responde em JSON. */
    public function kanbanMover(): void {
        $adminId = $this->authAdmin();
        header('Content-Type: application/json; charset=UTF-8');

        $token = $_POST['csrf_token'] ?? '';
        if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'erro' => 'Sua sessão expirou. Recarregue a página.']);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['Pendente', 'Em análise', 'Aguardando confirmação'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Só o cidadão marca como "Resolvido" (confirmando a solução).']);
            return;
        }

        $model = new Denuncia;
        $antes = $model->find($id);
        if (!$antes || in_array(strtolower($antes['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Essa denúncia não pode mais mudar de status.']);
            return;
        }
        if (($antes['status'] ?? '') === $status) {
            echo json_encode(['ok' => true, 'status' => $status]);
            return;
        }

        $mensagem = trim(mb_substr(trim((string) ($_POST['mensagem'] ?? '')), 0, 300));
        if (!$model->atualizarStatus($id, $status, $mensagem)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'erro' => 'Não foi possível atualizar a denúncia.']);
            return;
        }
        $this->registrarMudancaStatus($adminId, $antes, $status, $mensagem, false, '(pelo quadro kanban)');

        echo json_encode(['ok' => true, 'status' => $status, 'protocolo' => Denuncia::protocolo($antes)], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Modo telão: números grandes para projetar na apresentação. A página se
     * atualiza sozinha (busca os números de novo a cada 30 s em ?formato=json).
     */
    public function telao(): void {
        $this->authAdmin();
        $model = new Denuncia;
        $est = $model->estatisticas();
        $todas = $model->todas();
        usort($todas, fn($a, $b) => strcmp($b['criada_em'] ?? '', $a['criada_em'] ?? ''));
        $abertas = count(array_filter($todas, fn($d) => !in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)));
        $rot = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
        $dados = [
            'total' => $est['total'],
            'abertas' => $abertas,
            'resolvidas' => $est['por_status']['Resolvido'] ?? 0,
            'taxa' => $est['taxa_resolucao'],
            'tempo' => $this->formatarTempoMedio($model->tempoMedioResolucaoHoras()),
            'atrasadas' => $est['atrasadas'],
            'categorias' => array_map(fn($k) => ['nome' => $rot[$k], 'qtd' => (int) ($est['por_categoria'][$k] ?? 0), 'chave' => $k], array_keys($rot)),
            'ultimas' => array_map(fn($d) => [
                'protocolo' => Denuncia::protocolo($d),
                'categoria' => $rot[$d['categoria'] ?? 'outros'] ?? 'Outros',
                'chave' => $d['categoria'] ?? 'outros',
                'status' => $d['status'] ?? '',
                'endereco' => mb_strimwidth((string) ($d['endereco'] ?? ''), 0, 60, '…'),
                'quando' => date('d/m H:i', strtotime($d['criada_em'] ?? 'now')),
            ], array_slice(array_values(array_filter($todas, fn($d) => !in_array(strtolower($d['status'] ?? ''), ['cancelada', 'rejeitada'], true))), 0, 6)),
            'atualizado' => date('H:i:s'),
        ];
        if (($_GET['formato'] ?? '') === 'json') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($dados, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->view('admin/telao', compact('dados'));
    }

    /** Busca rápida (Ctrl+K): devolve denúncias que combinam com o termo, em JSON. */
    public function buscaRapida(): void {
        $this->authAdmin();
        header('Content-Type: application/json; charset=UTF-8');
        $termo = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
        if (mb_strlen($termo) < 2) { echo '[]'; return; }
        $userModel = new User;
        $cache = [];
        $saida = [];
        foreach (array_reverse((new Denuncia)->todas()) as $d) {
            $uid = (int) ($d['usuario_id'] ?? 0);
            $cache[$uid] = $cache[$uid] ?? ($userModel->find($uid)['nome'] ?? '');
            $texto = mb_strtolower(Denuncia::protocolo($d) . ' #' . $d['id'] . ' ' . ($d['descricao'] ?? '') . ' ' . ($d['endereco'] ?? '') . ' ' . ($d['categoria'] ?? '') . ' ' . $cache[$uid]);
            if (!str_contains($texto, $termo)) continue;
            $saida[] = [
                'id' => (int) $d['id'],
                'protocolo' => Denuncia::protocolo($d),
                'categoria' => $d['categoria'] ?? 'outros',
                'status' => $d['status'] ?? '',
                'descricao' => mb_strimwidth((string) ($d['descricao'] ?? ''), 0, 80, '…'),
                'cidadao' => $cache[$uid],
            ];
            if (count($saida) >= 8) break;
        }
        echo json_encode($saida, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ações em massa na lista do admin: mudar status, encaminhar ou mudar a
     * prioridade de várias denúncias de uma vez.
     */
    public function acoesEmMassa(): void {
        $adminId = $this->authAdmin();
        $this->csrfCheck(BASE_URL . '/?rota=admin');
        $idsRecebidos = $_POST['ids'] ?? [];
        $ids = is_array($idsRecebidos) ? array_values(array_unique(array_filter(array_map('intval', array_slice($idsRecebidos, 0, 100)), fn($id) => $id > 0))) : [];
        $acao = (string) ($_POST['acao'] ?? '');
        $valor = (string) ($_POST['valor'] ?? '');
        $volta = BASE_URL . '/?rota=admin' . (!empty($_POST['volta']) ? '&' . preg_replace('/[^a-zA-Z0-9_=&%-]/', '', (string) $_POST['volta']) : '');

        if (!$ids || !in_array($acao, ['status', 'orgao', 'prioridade'], true)) {
            $_SESSION['error'] = 'Escolha pelo menos uma denúncia e uma ação.';
            $this->redirect($volta);
        }

        $model = new Denuncia;
        $ok = 0;
        foreach ($ids as $id) {
            $antes = $model->find($id);
            if (!$antes || in_array(strtolower($antes['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) continue;
            if ($acao === 'status') {
                if (!in_array($valor, ['Pendente', 'Em análise', 'Aguardando confirmação'], true) || ($antes['status'] ?? '') === $valor) continue;
                if ($model->atualizarStatus($id, $valor)) {
                    $this->registrarMudancaStatus($adminId, $antes, $valor, '', false, '(ação em massa)');
                    $ok++;
                }
            } elseif ($acao === 'orgao') {
                if (!isset(Denuncia::ORGAOS[$valor]) || ($antes['orgao'] ?? null) === $valor) continue;
                $texto = $model->encaminhar($id, $valor);
                if ($texto !== null) {
                    $admin = (new User)->find($adminId);
                    (new LogAuditoria)->registrar($adminId, $admin['nome'] ?? 'Admin', sprintf('Encaminhou a denúncia #%d para %s (ação em massa)', $id, Denuncia::ORGAOS[$valor]['nome']), $id);
                    (new Notificacao)->criar((int) ($antes['usuario_id'] ?? 0), 'Sua denúncia de "' . ($antes['categoria'] ?? '') . '" foi ' . lcfirst($texto), $id);
                    $ok++;
                }
            } elseif ($acao === 'prioridade') {
                if (($antes['prioridade'] ?? 'Normal') === $valor) continue;
                if ($model->definirPrioridade($id, $valor)) {
                    $admin = (new User)->find($adminId);
                    (new LogAuditoria)->registrar($adminId, $admin['nome'] ?? 'Admin', sprintf('Mudou a prioridade da denúncia #%d para "%s" (ação em massa)', $id, $valor), $id);
                    $ok++;
                }
            }
        }
        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? $ok . ' denúncia' . ($ok === 1 ? ' atualizada' : 's atualizadas') . ' de uma vez.'
            : 'Nenhuma denúncia mudou (talvez já estivessem assim, ou resolvidas/canceladas).';
        $this->redirect($volta);
    }

    /** Filtros válidos de categoria/status usados no relatório geral e no CSV. */
    private const CATEGORIAS_RELATORIO = ['iluminacao', 'sinalizacao', 'lixo', 'buracos', 'esgoto', 'agua', 'arborizacao', 'outros'];
    private const STATUS_RELATORIO = ['Pendente', 'Em análise', 'Aguardando confirmação', 'Resolvido', 'Cancelada', 'Rejeitada'];

    /**
     * Aplica o filtro de período (por data de criação), categoria e status
     * sobre uma lista de denúncias. Usado tanto no relatório geral (tela)
     * quanto na exportação em CSV, pra manter os dois sempre em sincronia.
     */
    private function filtrarRelatorio(array $todas, string $dataInicio, string $dataFim, string $categoria, string $status): array {
        return array_values(array_filter($todas, function ($d) use ($dataInicio, $dataFim, $categoria, $status) {
            $criadaEm = $d['criada_em'] ?? '';
            if ($dataInicio !== '' && $criadaEm < $dataInicio . ' 00:00:00') return false;
            if ($dataFim !== '' && $criadaEm > $dataFim . ' 23:59:59') return false;
            if ($categoria !== 'todas' && ($d['categoria'] ?? '') !== $categoria) return false;
            if ($status !== 'todas' && strtolower($d['status'] ?? '') !== strtolower($status)) return false;
            return true;
        }));
    }

    /** Aceita apenas datas reais no formato ISO YYYY-MM-DD. */
    private function dataRelatorioValida(string $data): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return false;
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        return $dt !== false && $dt->format('Y-m-d') === $data;
    }

    /** Lê e valida da query string os filtros usados no relatório geral e no CSV. */
    private function lerFiltrosRelatorio(): array {
        $categoria = $_GET['categoria'] ?? 'todas';
        if (!in_array($categoria, self::CATEGORIAS_RELATORIO, true)) $categoria = 'todas';

        $status = $_GET['status'] ?? 'todas';
        if (!in_array($status, self::STATUS_RELATORIO, true)) $status = 'todas';

        $dataInicio = trim((string) ($_GET['data_inicio'] ?? ''));
        $dataFim = trim((string) ($_GET['data_fim'] ?? ''));
        $valido = ($dataInicio === '' || $this->dataRelatorioValida($dataInicio))
            && ($dataFim === '' || $this->dataRelatorioValida($dataFim))
            && ($dataInicio === '' || $dataFim === '' || $dataInicio <= $dataFim);
        return compact('dataInicio', 'dataFim', 'categoria', 'status', 'valido');
    }

    /**
     * Relatório geral por período — tela imprimível (PDF via "Imprimir") com
     * resumo por categoria/status, e um link pra exportar os mesmos dados em CSV.
     */
    public function relatorioGeral(): void {
        $id = $this->authAdmin();
        $usuario = (new User)->find($id);
        $userModel = new User;

        ['dataInicio' => $dataInicio, 'dataFim' => $dataFim, 'categoria' => $categoria, 'status' => $status, 'valido' => $valido] = $this->lerFiltrosRelatorio();
        if (!$valido) {
            http_response_code(422);
            echo 'Período inválido: informe datas reais, com início anterior ou igual ao fim.';
            return;
        }

        $todas = (new Denuncia)->todas();
        $filtradas = $this->filtrarRelatorio($todas, $dataInicio, $dataFim, $categoria, $status);

        $cacheUsuarios = [];
        foreach ($filtradas as &$d) {
            $uid = (int)($d['usuario_id'] ?? 0);
            if (!isset($cacheUsuarios[$uid])) {
                $cacheUsuarios[$uid] = $userModel->find($uid);
            }
            $d['usuario_nome'] = $cacheUsuarios[$uid]['nome'] ?? 'Usuário removido';
        }
        unset($d);

        $porCategoria = array_fill_keys(self::CATEGORIAS_RELATORIO, 0);
        $porStatus = array_fill_keys(self::STATUS_RELATORIO, 0);
        $porOrgao = array_fill_keys(array_keys(Denuncia::ORGAOS), 0);
        $semEncaminhamento = 0;
        $atrasadasFiltradas = 0;
        foreach ($filtradas as $d) {
            if (isset($porCategoria[$d['categoria'] ?? ''])) $porCategoria[$d['categoria']]++;
            if (isset($porStatus[$d['status'] ?? ''])) $porStatus[$d['status']]++;
            if (isset($porOrgao[$d['orgao'] ?? ''])) $porOrgao[$d['orgao']]++;
            else $semEncaminhamento++;
            if (Denuncia::statusPrazo($d)['atrasada']) $atrasadasFiltradas++;
        }

        $this->view('admin/relatorio-geral', compact(
            'usuario', 'filtradas', 'dataInicio', 'dataFim', 'categoria', 'status', 'porCategoria', 'porStatus', 'porOrgao', 'semEncaminhamento', 'atrasadasFiltradas'
        ));
    }

    /**
     * Protege contra "CSV injection": se um texto digitado por usuário começar
     * com = + - @ (ou tab/enter), o Excel/Sheets pode tratar a célula como
     * fórmula. Colocar um apóstrofo na frente faz virar texto comum.
     * Usar só em campos de TEXTO livre — nunca em números (latitude negativa
     * começa com "-" e não pode ser alterada).
     */
    private function csvSeguro($valor): string {
        $texto = (string) $valor;
        $texto = str_replace(["\r", "\n", "\0"], [' ', ' ', ''], $texto);
        if ($texto !== '' && preg_match('/^[\s]*[=+\-@]/u', $texto)) {
            return "'" . $texto;
        }
        return $texto;
    }

    /** Baixa um CSV com as denúncias do sistema, respeitando os mesmos filtros do relatório geral. */
    public function exportarCsv(): void {
        $this->authAdmin();

        $userModel = new User;
        ['dataInicio' => $dataInicio, 'dataFim' => $dataFim, 'categoria' => $categoria, 'status' => $status, 'valido' => $valido] = $this->lerFiltrosRelatorio();
        if (!$valido) {
            http_response_code(422);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Período inválido: confira as datas do relatório.';
            return;
        }

        $todas = $this->filtrarRelatorio((new Denuncia)->todas(), $dataInicio, $dataFim, $categoria, $status);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="olho-cidadao-denuncias-' . date('Y-m-d') . '.csv"');

        $saida = fopen('php://output', 'w');
        // BOM UTF-8 pra acentuação abrir certo no Excel.
        fwrite($saida, "\xEF\xBB\xBF");

        fputcsv($saida, ['ID', 'Protocolo', 'Categoria', 'Descrição', 'Status', 'Cidadão', 'Endereço', 'Latitude', 'Longitude', 'Criada em', 'Órgão responsável', 'Prazo de resposta', 'Prioridade', 'Atrasada'], ';', '"', '');

        $cacheUsuarios = [];
        foreach ($todas as $d) {
            $uid = (int)($d['usuario_id'] ?? 0);
            if (!isset($cacheUsuarios[$uid])) {
                $cacheUsuarios[$uid] = $userModel->find($uid);
            }

            fputcsv($saida, [
                $d['id'] ?? '',
                Denuncia::protocolo($d),
                $this->csvSeguro($d['categoria'] ?? ''),
                $this->csvSeguro($d['descricao'] ?? ''),
                $this->csvSeguro($d['status'] ?? ''),
                $this->csvSeguro($cacheUsuarios[$uid]['nome'] ?? 'Usuário removido'),
                $this->csvSeguro($d['endereco'] ?? ''),
                $d['latitude'] ?? '',
                $d['longitude'] ?? '',
                $d['criada_em'] ?? '',
                Denuncia::infoEncaminhamento($d)['orgao'] ?? '',
                $d['prazo_resposta'] ?? '',
                $this->csvSeguro($d['prioridade'] ?? 'Normal'),
                Denuncia::statusPrazo($d)['atrasada'] ? 'Sim' : 'Não'
            ], ';', '"', '');
        }

        fclose($saida);
        exit;
    }
}
