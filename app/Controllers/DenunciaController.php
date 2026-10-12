<?php
class DenunciaController extends Controller {
    public function passo1(): void {
        $this->auth();
        unset($_SESSION['ultimo_protocolo']);
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $this->view('denuncia/passo1', compact('error'));
    }

    public function passo2(): void {
        $this->auth();
        if (empty($_SESSION['categoria_denuncia'])) {
            $this->redirect(BASE_URL . '/?rota=denuncia-passo1');
        }
        $categoria = $_SESSION['categoria_denuncia'];
        // Últimas denúncias já resolvidas dessa categoria, pra o cidadão poder
        // vincular caso o problema já tivesse sido resolvido antes e voltou
        // (reincidência) — em vez de abrir uma denúncia "do zero" sem esse contexto.
        $resolvidasAnteriores = (new Denuncia)->resolvidasPorCategoria($categoria);
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $this->view('denuncia/passo2', compact('categoria', 'resolvidasAnteriores', 'error'));
    }

    public function salvarPasso1(): void {
        $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=denuncia-passo1');
        $cat = $_POST['categoria'] ?? '';

        if (!in_array($cat, ['iluminacao', 'sinalizacao', 'lixo', 'buracos', 'esgoto', 'agua', 'arborizacao', 'outros'], true)) {
            $_SESSION['error'] = 'Escolha uma categoria para continuar.';
            $this->redirect(BASE_URL . '/?rota=denuncia-passo1');
        }

        $_SESSION['categoria_denuncia'] = $cat;
        $this->redirect(BASE_URL . '/?rota=denuncia-passo2');
    }

    public function salvarPasso2(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=denuncia-passo2');
        $desc = $this->textoPost('descricao', 2000);

        if ($desc === '' || mb_strlen($desc) < 5 || empty($_SESSION['categoria_denuncia'])) {
            $_SESSION['error'] = 'Descreva o problema com pelo menos 5 caracteres.';
            $this->redirect(BASE_URL . '/?rota=denuncia-passo2');
        }
        $limite = $this->limiteDiario('denuncia-nova', $id, (int) LIMITE_DENUNCIAS_DIA, BASE_URL . '/?rota=denuncia-passo2', 'denúncias');

        $resultadoUpload = $this->processarUploadImagem('foto', 'denuncia_' . $id);
        if ($resultadoUpload === null) {
            $this->redirect(BASE_URL . '/?rota=denuncia-passo2');
        }
        // Restaurado o fluxo anterior: uma única foto opcional.
        // O modelo ainda aceita o campo fotos para ler registros mais recentes.
        $foto = $resultadoUpload;
        $fotos = $foto !== '' ? [$foto] : [];

        // Reincidência: se o cidadão vinculou a uma denúncia antiga já resolvida
        // dessa mesma categoria, confere que ela existe de verdade antes de salvar.
        $denunciaAnteriorId = null;
        if (!empty($_POST['denuncia_anterior_id'])) {
            $anterior = (new Denuncia)->find((int) $_POST['denuncia_anterior_id']);
            if ($anterior && strtolower($anterior['status'] ?? '') === 'resolvido' && ($anterior['categoria'] ?? '') === $_SESSION['categoria_denuncia']) {
                $denunciaAnteriorId = (int) $anterior['id'];
            }
        }

        try {
            $nova = (new Denuncia)->create(
            $id,
            $_SESSION['categoria_denuncia'],
            $desc,
            $foto,
            isset($_POST['latitude']) && $_POST['latitude'] !== '' && is_numeric($_POST['latitude']) && (float)$_POST['latitude'] >= -90 && (float)$_POST['latitude'] <= 90 ? (float)$_POST['latitude'] : null,
            isset($_POST['longitude']) && $_POST['longitude'] !== '' && is_numeric($_POST['longitude']) && (float)$_POST['longitude'] >= -180 && (float)$_POST['longitude'] <= 180 ? (float)$_POST['longitude'] : null,
            $this->textoPost('endereco', 255),
            isset($_POST['anonima']),
            $denunciaAnteriorId,
            $fotos
            );
        } catch (Throwable $erro) {
            if ($foto !== '') Uploader::apagarUploadPrivado($foto);
            throw $erro;
        }

        $limite?->registrarTentativa();
        unset($_SESSION['categoria_denuncia']);
        $protocoloNovo = Denuncia::protocolo($nova);
        $_SESSION['ultimo_protocolo'] = $protocoloNovo;

        Mailer::avisar(
            (new User)->find($id) ?? [],
            'Denúncia registrada - protocolo ' . $protocoloNovo,
            'Recebemos a sua denúncia',
            [
                'Sua denúncia foi registrada com sucesso e já está com a equipe responsável.',
                'Guarde o seu protocolo: ' . $protocoloNovo . '. Com ele você acompanha o andamento a qualquer momento.',
            ],
            'Consultar protocolo',
            '/?rota=consulta&protocolo=' . urlencode($protocoloNovo)
        );

        $this->redirect(BASE_URL . '/?rota=denuncia-passo3');
    }

    /**
     * JSON usado no passo 2: quando o cidadão marca o local no mapa, procura
     * denúncias ativas da mesma categoria a até 100 m e devolve um resumo, pra
     * tela oferecer "apoiar a existente" em vez de criar uma duplicada.
     */
    public function proximas(): void {
        $userId = $this->auth();
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        $lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_GET['lng'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            http_response_code(400);
            echo json_encode(['erro' => 'Coordenadas inválidas.']);
            return;
        }

        $categoria = $_SESSION['categoria_denuncia'] ?? null;
        $lista = (new Denuncia)->proximas((float) $lat, (float) $lng, $categoria);

        $rotulos = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
        $saida = array_map(function ($d) use ($userId, $rotulos) {
            $desc = (string) ($d['descricao'] ?? '');
            return [
                'id' => (int) $d['id'],
                'protocolo' => Denuncia::protocolo($d),
                'categoria' => $rotulos[$d['categoria'] ?? ''] ?? 'Outros',
                'descricao' => 'Ocorrência semelhante próxima (detalhes protegidos).',
                'endereco' => 'Localização aproximada',
                'status' => (string) ($d['status'] ?? ''),
                'distancia_m' => (int) $d['distancia_m'],
                'apoios' => count($d['apoios'] ?? []),
                'latitude' => round((float) $d['latitude'], 2),
                'longitude' => round((float) $d['longitude'], 2),
                'propria' => (int) ($d['usuario_id'] ?? 0) === $userId,
                'ja_apoiou' => in_array($userId, $d['apoios'] ?? [], true),
                'criada_em' => date('d/m/Y', strtotime($d['criada_em'] ?? 'now')),
            ];
        }, $lista);

        echo json_encode(['raio' => Denuncia::RAIO_DENUNCIA_PARECIDA_M, 'denuncias' => $saida], JSON_UNESCAPED_UNICODE);
    }

    public function passo3(): void {
        $this->auth();
        $protocolo = $_SESSION['ultimo_protocolo'] ?? null;
        // Mantém o protocolo na sessão para recarga da tela de confirmação.
        $denuncia = $protocolo ? (new Denuncia)->findByProtocolo($protocolo) : null;
        // Primeira denúncia do cidadão? (a tela comemora com confete)
        $primeira = $denuncia && count((new Denuncia)->byUser((int) $_SESSION['user_id'])) === 1;
        $this->view('denuncia/passo3', compact('protocolo', 'denuncia', 'primeira'));
    }

    public function complementar(): void {
        $uid = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=minhas-denuncias');
        $id = (int) ($_POST['id'] ?? 0);
        $limite = $this->limiteDiario('denuncia-complemento', $uid, (int) LIMITE_COMPLEMENTOS_DIA, BASE_URL . '/?rota=denuncia-ver&id=' . $id, 'complementos');
        $texto = trim((string) ($_POST['complemento'] ?? ''));
        $ok = (new Denuncia)->complementar($id, $uid, $texto);
        if ($ok) {
            $limite?->registrarTentativa();
            foreach ((new User)->admins() as $admin) {
                (new Notificacao)->criar((int) $admin['id'], 'O cidadão adicionou novas informações à denúncia ' . $id . '. Consulte o histórico.', $id);
            }
        }
        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Informação complementar registrada no histórico.'
            : 'Não foi possível complementar. Confira o tamanho (10 a 1000 caracteres) e o status da denúncia.';
        $this->redirect(BASE_URL . '/?rota=denuncia-ver&id=' . $id);
    }

    /** O autor apresenta justificativa e pede nova análise de uma denúncia rejeitada. */
    public function recorrerRejeicao(): void {
        $uid = $this->auth();
        $id = (int) ($_POST['id'] ?? 0);
        $this->csrfCheck(BASE_URL . '/?rota=minhas-denuncias');
        $limite = $this->limiteDiario('denuncia-recurso', $uid, (int) LIMITE_COMPLEMENTOS_DIA, BASE_URL . '/?rota=denuncia-ver&id=' . $id, 'pedidos de revisão');
        $justificativa = trim((string) ($_POST['justificativa'] ?? ''));
        $ok = (new Denuncia)->recorrerRejeicao($id, $uid, $justificativa);
        if ($ok) {
            $limite?->registrarTentativa();
            foreach ((new User)->admins() as $admin) {
                (new Notificacao)->criar((int) $admin['id'], 'Recurso recebido: a denúncia ' . $id . ' voltou para análise. Consulte a justificativa no histórico.', $id);
            }
        }
        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Seu pedido de revisão foi registrado e a denúncia voltou para análise.'
            : 'Não foi possível solicitar revisão. Verifique se a denúncia é sua, está rejeitada e se a justificativa tem de 20 a 1000 caracteres.';
        $this->redirect(BASE_URL . '/?rota=denuncia-ver&id=' . $id);
    }

    public function cancelar(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=minhas-denuncias');

        $denunciaId = (int)($_POST['id'] ?? 0);
        $ok = (new Denuncia)->cancelar($denunciaId, $id);

        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Denúncia cancelada com sucesso.'
            : 'Não foi possível cancelar essa denúncia.';

        $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
    }

    /** O cidadão confirma que a denúncia marcada como "Aguardando confirmação" foi realmente resolvida. */
    public function confirmar(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=minhas-denuncias');

        $denunciaId = (int)($_POST['id'] ?? 0);
        $avaliacao = isset($_POST['avaliacao']) && $_POST['avaliacao'] !== '' ? (int) $_POST['avaliacao'] : null;
        if ($avaliacao !== null && ($avaliacao < 1 || $avaliacao > 5)) {
            $_SESSION['error'] = 'A avaliação deve estar entre 1 e 5 estrelas.';
            $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
        }
        $ok = (new Denuncia)->confirmarResolucao($denunciaId, $id, $avaliacao);

        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Obrigado por confirmar! Denúncia marcada como resolvida.'
            : 'Não foi possível confirmar essa denúncia.';

        $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
    }

    /**
     * Um cidadão apoia a denúncia de outra pessoa ("eu também sofro com
     * isso") em vez de criar uma denúncia duplicada. Notifica o dono (sem
     * dizer quem apoiou — só a contagem importa).
     */
    public function apoiar(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=denuncia-ver');

        $denunciaId = (int)($_POST['id'] ?? 0);
        $limite = $this->limiteDiario('denuncia-apoio', $id, (int) LIMITE_APOIOS_DIA, BASE_URL . '/?rota=denuncia-ver&id=' . $denunciaId, 'apoios');
        $denunciaModel = new Denuncia;
        $denuncia = $denunciaModel->find($denunciaId);
        $ok = $denunciaModel->apoiar($denunciaId, $id);
        if ($ok) $limite?->registrarTentativa();

        if ($ok && $denuncia) {
            $totalApoios = count($denuncia['apoios'] ?? []) + 1;
            $texto = 'Mais um morador apoiou sua denúncia de "' . $denuncia['categoria'] . '" (' . $totalApoios . ' no total).';
            if ($totalApoios === 3) {
                $texto = 'Sua denúncia de "' . $denuncia['categoria'] . '" atingiu 3 apoios e agora está verificada por moradores!';
            }
            (new Notificacao)->criar((int) ($denuncia['usuario_id'] ?? 0), $texto, $denunciaId);
        }

        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Você apoiou essa denúncia. Obrigado por contribuir!'
            : 'Não foi possível registrar seu apoio (talvez você já tenha apoiado, seja o autor, ou ela já esteja resolvida/cancelada).';

        $this->redirect(BASE_URL . '/?rota=denuncia-ver&id=' . $denunciaId);
    }

    /**
     * Detalhe de uma denúncia pra outros cidadãos verem e apoiarem — respeita
     * a denúncia anônima (esconde o nome de quem não é o dono nem admin).
     */
    public function ver(): void {
        $userId = $this->auth();
        $usuario = (new User)->find($userId);
        $ehAdmin = ($usuario['role'] ?? 'cidadao') === 'admin';

        $denunciaId = (int)($_GET['id'] ?? 0);
        $denuncia = (new Denuncia)->find($denunciaId);

        if (!$denuncia) {
            $this->redirect(BASE_URL . '/?rota=mapa');
        }

        $ehDono = (int) ($denuncia['usuario_id'] ?? 0) === $userId;
        $mostrarAutor = $ehAdmin || $ehDono || empty($denuncia['anonima']);
        $autor = $mostrarAutor ? (new User)->find((int) $denuncia['usuario_id']) : null;

        $jaApoiou = in_array($userId, $denuncia['apoios'] ?? [], true);
        $podeApoiar = !$ehDono && !$jaApoiou && !in_array(strtolower($denuncia['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true);

        $anteriorVinculada = !empty($denuncia['denuncia_anterior_id']) ? (new Denuncia)->find((int) $denuncia['denuncia_anterior_id']) : null;

        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);

        $this->view('denuncia/ver', [
            'error' => $error,
            'sucesso' => $sucesso,
            'denuncia' => $denuncia,
            'autor' => $autor,
            'mostrarAutor' => $mostrarAutor,
            'ehDono' => $ehDono,
            'ehAdmin' => $ehAdmin,
            'jaApoiou' => $jaApoiou,
            'podeApoiar' => $podeApoiar,
            'verificada' => Denuncia::ehVerificada($denuncia),
            'prazo' => Denuncia::statusPrazo($denuncia),
            'anteriorVinculada' => $anteriorVinculada,
        ]);
    }

    /**
     * Consulta pública por protocolo — qualquer pessoa (sem login) acompanha o andamento.
     * Mostra só categoria, endereço, status e datas; nunca nome, anônima ou não.
     */
    public function consultaProtocolo(): void {
        $protocolo = trim((string)($_GET['protocolo'] ?? ''));
        $denuncia = null;
        $buscou = $protocolo !== '';
        $bloqueado = 0;

        if ($buscou) {
            // Limite por IP para ninguém varrer protocolos em sequência.
            $limiter = new RateLimiter('consulta', maxTentativas: 30, bloqueioSegundos: 300);
            $bloqueado = $limiter->bloqueado();
            if ($bloqueado === 0) {
                $limiter->registrarTentativa();
                $denuncia = (new Denuncia)->findByProtocolo($protocolo);
            }
        }

        $prazo = $denuncia ? Denuncia::statusPrazo($denuncia) : null;
        $this->view('denuncia/consulta', compact('protocolo', 'denuncia', 'buscou', 'bloqueado', 'prazo'));
    }

    /** Formulário para o cidadão corrigir a descrição/endereço de uma denúncia ainda pendente. */
    public function editar(): void {
        $userId = $this->auth();
        $denuncia = (new Denuncia)->find((int)($_GET['id'] ?? 0));
        if (!$denuncia || (int)($denuncia['usuario_id'] ?? 0) !== $userId || strtolower($denuncia['status'] ?? '') !== 'pendente') {
            $_SESSION['error'] = 'Só é possível editar as suas denúncias enquanto estiverem pendentes.';
            $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
        }
        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $this->view('denuncia/editar', compact('denuncia', 'error'));
    }

    public function salvarEdicao(): void {
        $userId = $this->auth();
        $denunciaId = (int)($_POST['id'] ?? 0);
        $this->csrfCheck(BASE_URL . '/?rota=denuncia-editar&id=' . $denunciaId);

        $desc = $this->textoPost('descricao', 2000);
        if (mb_strlen($desc) < 5) {
            $_SESSION['error'] = 'Descreva o problema com pelo menos 5 caracteres.';
            $this->redirect(BASE_URL . '/?rota=denuncia-editar&id=' . $denunciaId);
        }

        $ok = (new Denuncia)->editar($denunciaId, $userId, $desc, $this->textoPost('endereco', 255));
        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Denúncia atualizada.'
            : 'Não foi possível editar essa denúncia (ela precisa estar pendente e ser sua).';
        $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
    }

    /** O cidadão diz que o problema não foi resolvido de verdade — a denúncia volta para "Em análise". */
    public function reabrir(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=minhas-denuncias');

        $denunciaId = (int)($_POST['id'] ?? 0);
        $motivo = trim(mb_substr(trim($_POST['motivo'] ?? ''), 0, 300));

        $denunciaModel = new Denuncia;
        $denuncia = $denunciaModel->find($denunciaId);
        $ok = $denunciaModel->reabrir($denunciaId, $id, $motivo);

        if ($ok && $denuncia) {
            $texto = 'O cidadão reabriu a denúncia de "' . $denuncia['categoria'] . '" — o problema não foi resolvido.';
            if ($motivo !== '') {
                $texto .= ' Motivo: "' . $motivo . '"';
            }
            foreach ((new User)->admins() as $admin) {
                (new Notificacao)->criar((int) $admin['id'], $texto, $denunciaId);
            }
        }

        $_SESSION[$ok ? 'sucesso' : 'error'] = $ok
            ? 'Denúncia reaberta. A prefeitura foi notificada.'
            : 'Não foi possível reabrir essa denúncia.';

        $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
    }

    /** Página de relatório de uma denúncia, pronta para impressão/"Salvar como PDF" pelo navegador. Só o admin pode gerar. */
    public function relatorio(): void {
        $id = $this->auth();
        $usuario = (new User)->find($id);
        $ehAdmin = ($usuario['role'] ?? 'cidadao') === 'admin';

        if (!$ehAdmin) {
            $this->redirect(BASE_URL . '/?rota=minhas-denuncias');
        }

        $denunciaId = (int)($_GET['id'] ?? 0);
        $denuncia = (new Denuncia)->find($denunciaId);

        if (!$denuncia) {
            $this->redirect(BASE_URL . '/?rota=admin');
        }

        $autor = (new User)->find((int)$denuncia['usuario_id']);

        $this->view('denuncia/relatorio', [
            'denuncia' => $denuncia,
            'autor' => $autor,
            'geradoPor' => $usuario
        ]);
    }

    /**
     * Mapa público com todas as denúncias que têm localização, sem exigir login.
     * Não expõe dados do autor — só categoria, status, endereço e data.
     */
    public function mapaPublico(): void {
        $todas = (new Denuncia)->todas();

        $pontos = array_values(array_map(
            fn($d) => [
                'id' => (int) $d['id'],
                'categoria' => $d['categoria'],
                'status' => $d['status'],
                'endereco' => 'Localização aproximada (privacidade)',
                'criada_em' => $d['criada_em'] ?? '',
                'latitude' => round((float) $d['latitude'], 2),
                'longitude' => round((float) $d['longitude'], 2),
                'apoios' => count($d['apoios'] ?? []),
                'verificada' => Denuncia::ehVerificada($d),
                'prioridade' => $d['prioridade'] ?? 'Normal',
                'orgao' => Denuncia::infoEncaminhamento($d)['orgao'] ?? '',
                'foto' => '', // Fotos privadas devem ser revisadas antes de qualquer publicação.
                'descricao' => 'Ocorrência registrada na categoria informada.',
            ],
            array_filter(
                $todas,
                fn($d) => !empty($d['latitude']) && !empty($d['longitude']) && !in_array(strtolower($d['status'] ?? ''), ['cancelada', 'rejeitada'], true)
            )
        ));

        $logado = !empty($_SESSION['user_id']);
        $ehAdmin = false;
        if ($logado) {
            $usuario = (new User)->find((int) $_SESSION['user_id']);
            $ehAdmin = ($usuario['role'] ?? 'cidadao') === 'admin';
        }

        $this->view('denuncia/mapa-publico', compact('pontos', 'logado', 'ehAdmin'));
    }

    public function minhas(): void {
        $id = $this->auth();
        $usuario = (new User)->find($id);

        $todas = array_reverse((new Denuncia)->byUser($id));

        $mapaFiltro = [
            'pendente' => 'pendente',
            'analise' => 'em análise',
            'aguardando' => 'aguardando confirmação',
            'resolvido' => 'resolvido',
            'rejeitada' => 'rejeitada'
        ];

        $filtro = $_GET['status'] ?? 'todas';
        if (!isset($mapaFiltro[$filtro])) {
            $filtro = 'todas';
        }

        $denuncias = $filtro === 'todas'
            ? $todas
            : array_values(array_filter(
                $todas,
                fn($d) => strtolower($d['status'] ?? '') === $mapaFiltro[$filtro]
            ));

        $stats = [
            'total' => count($todas),
            'pendentes' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'pendente')),
            'analise' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'em análise')),
            'aguardando' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'aguardando confirmação')),
            'resolvidas' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'resolvido')),
            'rejeitadas' => count(array_filter($todas, fn($d) => strtolower($d['status'] ?? '') === 'rejeitada'))
        ];

        $error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
        $sucesso = $_SESSION['sucesso'] ?? null; unset($_SESSION['sucesso']);

        $totalFiltrado = count($denuncias);
        $porPagina = 8;
        $totalPaginas = max(1, (int) ceil($totalFiltrado / $porPagina));
        $pagina = max(1, min($totalPaginas, (int) ($_GET['pagina'] ?? 1)));
        $denuncias = array_slice($denuncias, ($pagina - 1) * $porPagina, $porPagina);
        $this->view('denuncia/minhas', compact('usuario', 'denuncias', 'stats', 'filtro', 'error', 'sucesso', 'pagina', 'totalPaginas', 'totalFiltrado'));
    }
}
