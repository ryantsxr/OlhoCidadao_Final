<?php
class Denuncia {
    private string $file;

    /** Quantidade de apoios necessária pra uma denúncia ganhar o selo "verificado por moradores". */
    private const APOIOS_PARA_VERIFICAR = 3;

    /** Prazo esperado de resolução por categoria, em dias — usado só pra mostrar um contador visual, não bloqueia nada. */
    private const PRAZO_DIAS_POR_CATEGORIA = [
        'iluminacao' => 7,
        'sinalizacao' => 10,
        'lixo' => 3,
        'buracos' => 15,
        'esgoto' => 10,
        'agua' => 7,
        'arborizacao' => 12,
        'outros' => 15,
    ];

    /** Níveis de prioridade que o administrador pode atribuir (do menos ao mais urgente). */
    public const PRIORIDADES = ['Baixa', 'Normal', 'Alta', 'Urgente'];

    /**
     * Órgãos da prefeitura para onde o admin pode encaminhar uma denúncia,
     * com o prazo de resposta (em dias) que cada um se compromete a cumprir.
     * Para adaptar a outra cidade, basta editar esta lista.
     */
    public const ORGAOS = [
        'obras'      => ['nome' => 'Secretaria Municipal de Obras',                 'prazo' => 15],
        'iluminacao' => ['nome' => 'Setor de Iluminação Pública',                   'prazo' => 7],
        'transito'   => ['nome' => 'Departamento Municipal de Trânsito',            'prazo' => 10],
        'limpeza'    => ['nome' => 'Secretaria de Meio Ambiente e Limpeza Urbana',  'prazo' => 5],
    ];

    /** Órgão já sugerido no formulário do admin, conforme a categoria da denúncia. */
    private const ORGAO_SUGERIDO_POR_CATEGORIA = [
        'iluminacao'  => 'iluminacao',
        'sinalizacao' => 'transito',
        'lixo'        => 'limpeza',
        'buracos' => 'obras',
        'esgoto' => 'obras',
        'agua' => 'obras',
        'arborizacao' => 'limpeza',
        'outros'      => 'obras',
    ];

    /** Raio (em metros) usado para avisar que já existe uma denúncia parecida por perto. */
    public const RAIO_DENUNCIA_PARECIDA_M = 100;

    public function __construct() {
        $this->file = __DIR__ . '/../../storage/denuncias.json';
    }

    private function all(): array {
        $data = LeituraJson::array($this->file);

        // Denúncias criadas antes do histórico existir não têm essa chave no JSON.
        // Preenche com uma entrada baseada no status/data atuais, pra linha do
        // tempo sempre ter pelo menos um item em vez de sumir da tela.
        foreach ($data as &$d) {
            // Fotos sempre no endereço atual do site (não mexe no histórico, que é selado).
            foreach (['foto', 'foto_resolucao'] as $campoFoto) {
                if (!empty($d[$campoFoto]) && is_string($d[$campoFoto])) $d[$campoFoto] = Uploader::urlAtual($d[$campoFoto]);
            }
            if (!empty($d['fotos']) && is_array($d['fotos'])) $d['fotos'] = array_map(static fn($f) => Uploader::urlAtual((string) $f), $d['fotos']);
            if (empty($d['historico'])) {
                $d['historico'] = [[
                    'status' => $d['status'] ?? 'Pendente',
                    'data' => $d['criada_em'] ?? date('Y-m-d H:i:s')
                ]];
            }
            // Campos adicionados depois de já existirem denúncias no sistema —
            // preenche com o padrão pra todo o resto do código poder confiar
            // que essas chaves sempre existem.
            $d['anonima'] = $d['anonima'] ?? false;
            $d['apoios'] = $d['apoios'] ?? [];
            $d['avaliacao'] = $d['avaliacao'] ?? null;
            $d['denuncia_anterior_id'] = $d['denuncia_anterior_id'] ?? null;
            $d['prioridade'] = $d['prioridade'] ?? 'Normal';
            $d['orgao'] = $d['orgao'] ?? null;
            $d['encaminhada_em'] = $d['encaminhada_em'] ?? null;
            $d['prazo_resposta'] = $d['prazo_resposta'] ?? null;
            $d['motivo_rejeicao'] = $d['motivo_rejeicao'] ?? null;
            $d['recursos'] = $d['recursos'] ?? [];
            $d['complementos'] = $d['complementos'] ?? [];
            $d['fotos'] = $d['fotos'] ?? (empty($d['foto']) ? [] : [$d['foto']]);
        }
        unset($d);

        return $data;
    }

    /** Distância em metros entre dois pontos (fórmula de Haversine). */
    public static function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /**
     * Denúncias ainda ativas (não resolvidas nem canceladas) a até $raio metros
     * do ponto, da mais próxima para a mais distante. Usada para avisar o cidadão
     * "já existe uma denúncia aqui perto" e oferecer apoiar a existente.
     * Se $categoria vier preenchida, só considera a mesma categoria.
     */
    public function proximas(float $lat, float $lng, ?string $categoria = null, int $raio = self::RAIO_DENUNCIA_PARECIDA_M, int $limite = 5): array {
        $achadas = [];
        foreach ($this->all() as $d) {
            if (empty($d['latitude']) || empty($d['longitude'])) continue;
            if (in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) continue;
            if ($categoria !== null && ($d['categoria'] ?? '') !== $categoria) continue;
            $dist = self::distanciaMetros($lat, $lng, (float) $d['latitude'], (float) $d['longitude']);
            if ($dist <= $raio) {
                $d['distancia_m'] = (int) round($dist);
                $achadas[] = $d;
            }
        }
        usort($achadas, fn($a, $b) => $a['distancia_m'] <=> $b['distancia_m']);
        return array_slice($achadas, 0, $limite);
    }

    /** Chave do órgão sugerido para uma categoria. */
    public static function orgaoSugerido(string $categoria): string {
        return self::ORGAO_SUGERIDO_POR_CATEGORIA[$categoria] ?? 'obras';
    }

    /**
     * Dados do encaminhamento para exibir na tela, ou null se a denúncia ainda
     * não foi encaminhada a nenhum órgão.
     */
    public static function infoEncaminhamento(array $d): ?array {
        $chave = $d['orgao'] ?? null;
        if (!$chave || !isset(self::ORGAOS[$chave])) return null;

        $orgao = self::ORGAOS[$chave];
        $prazoData = $d['prazo_resposta'] ?? null;
        $statusAtual = strtolower($d['status'] ?? '');
        $finalizada = in_array($statusAtual, ['resolvido', 'cancelada', 'rejeitada'], true);

        $diasRestantes = null;
        if ($prazoData) {
            $hoje = new DateTimeImmutable('today');
            $limite = new DateTimeImmutable($prazoData);
            $diasRestantes = (int) $hoje->diff($limite)->format('%r%a');
        }

        return [
            'chave' => $chave,
            'orgao' => $orgao['nome'],
            'prazo_dias' => (int) ($d['prazo_dias_encaminhamento'] ?? $orgao['prazo']),
            'encaminhada_em' => $d['encaminhada_em'] ?? null,
            'prazo_data' => $prazoData,
            'dias_restantes' => $diasRestantes,
            'vencido' => !$finalizada && $diasRestantes !== null && $diasRestantes < 0,
            'finalizada' => $finalizada,
        ];
    }

    /**
     * O admin encaminha a denúncia para um órgão. Grava o prazo de resposta,
     * registra no histórico e, se ainda estiver "Pendente", passa para "Em análise".
     * Retorna o texto do encaminhamento (para notificação) ou null se falhar.
     */
    public function encaminhar(int $id, string $orgao, string $observacao = '', ?int $prazoDias = null): ?string {
        if (!isset(self::ORGAOS[$orgao])) return null;
        if ($prazoDias !== null && ($prazoDias < 1 || $prazoDias > 180)) return null;
        $observacao = trim(mb_substr($observacao, 0, 300));
        return ArquivoLock::executar('denuncias', function () use ($id, $orgao, $observacao, $prazoDias) {
            $items = $this->all();
            $texto = null;
            foreach ($items as &$d) {
                if ((int) $d['id'] !== $id) continue;
                if (in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) return null;

                $info = self::ORGAOS[$orgao];
                $agora = date('Y-m-d H:i:s');
                $dias = $prazoDias ?? (int) $info['prazo'];
                $prazo = date('Y-m-d', strtotime('+' . $dias . ' days'));

                $d['orgao'] = $orgao;
                $d['encaminhada_em'] = $agora;
                $d['prazo_resposta'] = $prazo;
                $d['prazo_dias_encaminhamento'] = $dias;
                if (strtolower($d['status'] ?? '') === 'pendente') {
                    $d['status'] = 'Em análise';
                }

                $texto = sprintf('Encaminhada para %s, prazo previsto de %d dias (até %s).', $info['nome'], $dias, date('d/m/Y', strtotime($prazo)));
                $entrada = ['status' => $d['status'], 'data' => $agora, 'mensagem' => $texto . ($observacao !== '' ? ' ' . $observacao : ''), 'orgao' => $orgao, 'prazo_dias' => $dias];
                $d['historico'][] = $entrada;
                break;
            }
            unset($d);
            if ($texto === null) return null;
            $this->saveAll($items);
            return $texto;
        });
    }

    private function saveAll(array $items): void {
        // Sela as entradas novas do histórico (corrente de selos à prova de
        // alteração — ver app/Core/Integridade.php).
        if (class_exists('Integridade')) {
            Integridade::selar($items, MySqlStore::ativo());
        }
        if (MySqlStore::ativo()) { MySqlStore::gravar($this->file, $items); return; }
        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $this->file)) {
            @unlink($tmp);
            throw new RuntimeException('Falha ao gravar as denúncias. Dados anteriores preservados.');
        }
        @chmod($this->file, 0600);
        // Os dados já estão persistidos: a âncora de integridade não deve
        // apontar para uma versão ainda não gravada do arquivo de denúncias.
        if (class_exists('Integridade')) {
            Integridade::selar($items);
        }
    }

    public function create(
        int $userId,
        string $categoria,
        string $descricao,
        string $foto = '',
        ?float $latitude = null,
        ?float $longitude = null,
        string $endereco = '',
        bool $anonima = false,
        ?int $denunciaAnteriorId = null,
        array $fotos = []
    ): array {
        return ArquivoLock::executar('denuncias', function () use ($userId, $categoria, $descricao, $foto, $latitude, $longitude, $endereco, $anonima, $denunciaAnteriorId, $fotos) {
            $items = $this->all();

            $ids = array_column($items, 'id');
            $nextId = MySqlStore::ativo() ? MySqlStore::proximoId('denuncias') : (empty($ids) ? 1 : ((int)max($ids) + 1));
            $agora = date('Y-m-d H:i:s');

            $item = [
                'id' => $nextId,
                'usuario_id' => $userId,
                'categoria' => $categoria,
                'descricao' => $descricao,
                'foto' => $foto,
                'fotos' => $fotos ?: ($foto ? [$foto] : []),
                'foto_resolucao' => '',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'endereco' => $endereco,
                'status' => 'Pendente',
                'prioridade' => 'Normal',
                'recursos' => [],
                'motivo_rejeicao' => null,
                'complementos' => [],
                'orgao' => null,
                'encaminhada_em' => null,
                'prazo_resposta' => null,
                'prazo_dias_encaminhamento' => null,
                'anonima' => $anonima,
                'apoios' => [],
                'avaliacao' => null,
                'denuncia_anterior_id' => $denunciaAnteriorId,
                'criada_em' => $agora,
                'historico' => [
                    ['status' => 'Pendente', 'data' => $agora]
                ]
            ];

            $items[] = $item;
            $this->saveAll($items);

            return $item;
        });
    }

    /** Complementação pela pessoa autora, sem alterar a descrição original. */
    public function complementar(int $id, int $userId, string $texto): bool {
        $texto = trim($texto);
        if (mb_strlen($texto) < 10 || mb_strlen($texto) > 1000) return false;
        return ArquivoLock::executar('denuncias', function () use ($id, $userId, $texto) {
            $items = $this->all();
            foreach ($items as &$d) {
                if ((int)($d['id'] ?? 0) !== $id) continue;
                if ((int)($d['usuario_id'] ?? 0) !== $userId) return false;
                if (in_array(strtolower($d['status'] ?? ''), ['cancelada', 'rejeitada', 'resolvido'], true)) return false;
                if (count($d['complementos'] ?? []) >= 10) return false;
                $agora = date('Y-m-d H:i:s');
                $d['complementos'][] = ['texto' => $texto, 'data' => $agora];
                $d['historico'][] = ['status' => $d['status'], 'data' => $agora, 'mensagem' => 'Complemento do cidadão: ' . $texto];
                $this->saveAll($items);
                return true;
            }
            return false;
        });
    }

    /** Revisão de rejeição solicitada somente pelo autor, com justificativa e histórico preservado.
     *  O recurso reabre a análise; não apaga a decisão anterior nem seus motivos.
     */
    public function recorrerRejeicao(int $id, int $userId, string $justificativa): bool {
        $justificativa = trim($justificativa);
        if (mb_strlen($justificativa) < 20 || mb_strlen($justificativa) > 1000) return false;
        return ArquivoLock::executar('denuncias', function () use ($id, $userId, $justificativa) {
            $items = $this->all();
            foreach ($items as &$d) {
                if ((int) ($d['id'] ?? 0) !== $id) continue;
                if ((int) ($d['usuario_id'] ?? 0) !== $userId || ($d['status'] ?? '') !== 'Rejeitada') return false;

                $agora = date('Y-m-d H:i:s');
                $d['recursos'][] = [
                    'data' => $agora,
                    'justificativa' => $justificativa,
                    'motivo_rejeicao_original' => $d['motivo_rejeicao'] ?? '',
                ];
                $d['historico'][] = [
                    'status' => 'Em análise',
                    'data' => $agora,
                    'mensagem' => 'Recurso do cidadão contra a rejeição: ' . $justificativa,
                    'tipo' => 'recurso_rejeicao',
                ];
                $d['status'] = 'Em análise';
                $d['motivo_rejeicao'] = null;
                // O encaminhamento anterior não deve deixar um prazo expirado no novo ciclo.
                $d['orgao'] = null;
                $d['encaminhada_em'] = null;
                $d['prazo_resposta'] = null;
                $d['prazo_dias_encaminhamento'] = null;
                $this->saveAll($items);
                return true;
            }
            return false;
        });
    }

    /** Ao excluir a conta, elimina referência interna ao titular e os apoios dele.
     * Não remove o histórico técnico do atendimento, que exige política de retenção própria.
     */
    public function desvincularUsuario(int $userId): void {
        ArquivoLock::executar('denuncias', function () use ($userId): void {
            $items=$this->all();
            $alterado=false;
            foreach ($items as &$d) {
                if ((int)($d['usuario_id']??0)===$userId) {
                    $d['usuario_id']=0;
                    $d['anonima']=true;
                    // Compatibilidade com versões antigas que persistiam identificação no relato.
                    unset($d['usuario_nome'], $d['usuario_email'], $d['cpf']);
                    $alterado=true;
                }
                $apoios=$d['apoios']??[];
                $novos=array_values(array_filter($apoios, static fn($id)=>(int)$id!==$userId));
                if (count($novos)!==count($apoios)) {
                    $d['apoios']=$novos;
                    $alterado=true;
                }
            }
            unset($d);
            if ($alterado) $this->saveAll($items);
        });
    }

    public function byUser(int $userId): array {
        return array_values(
            array_filter($this->all(), fn($d) => (int)($d['usuario_id'] ?? 0) === $userId)
        );
    }

    /** Usada pelo painel do administrador: todas as denúncias do sistema. */
    public function todas(): array {
        return $this->all();
    }

    public function find(int $id): ?array {
        foreach ($this->all() as $d) {
            if ((int)$d['id'] === $id) return $d;
        }
        return null;
    }

    /**
     * Últimas denúncias já RESOLVIDAS de uma categoria — usada no formulário de
     * nova denúncia pra o cidadão poder vincular ("isso já tinha acontecido e
     * foi resolvido, mas voltou"), alimentando a reincidência.
     */
    public function resolvidasPorCategoria(string $categoria, int $limite = 15): array {
        $resolvidas = array_values(array_filter(
            $this->all(),
            fn($d) => ($d['categoria'] ?? '') === $categoria && strtolower($d['status'] ?? '') === 'resolvido'
        ));

        usort($resolvidas, fn($a, $b) => strtotime($b['criada_em'] ?? '') <=> strtotime($a['criada_em'] ?? ''));

        return array_slice($resolvidas, 0, $limite);
    }

    /**
     * Cancela uma denúncia do próprio usuário.
     * Só é permitido enquanto o status ainda for "Pendente" — depois que
     * a prefeitura começa a analisar, a denúncia não pode mais ser cancelada.
     */
    public function cancelar(int $id, int $userId): bool {
        return ArquivoLock::executar('denuncias', function () use ($id, $userId) {
            $items = $this->all();
            $encontrado = false;

            foreach ($items as &$d) {
                if ((int)$d['id'] === $id) {
                    if ((int)($d['usuario_id'] ?? 0) !== $userId) {
                        return false; // não é dono da denúncia
                    }
                    if (strtolower($d['status'] ?? '') !== 'pendente') {
                        return false; // só pode cancelar enquanto pendente
                    }
                    $d['status'] = 'Cancelada';
                    $d['historico'][] = ['status' => 'Cancelada', 'data' => date('Y-m-d H:i:s')];
                    $encontrado = true;
                    break;
                }
            }
            unset($d);

            if (!$encontrado) return false;

            $this->saveAll($items);
            return true;
        });
    }

    /**
     * Exclui a denúncia de vez (uso do administrador — denúncia de teste,
     * spam ou duplicada). Devolve os dados da denúncia apagada, para o
     * controller remover as fotos e registrar a auditoria, ou null se não
     * existir. As âncoras de integridade são refeitas no saveAll(), então a
     * exclusão feita pelo sistema não aparece como adulteração.
     */
    public function excluir(int $id): ?array {
        return ArquivoLock::executar('denuncias', function () use ($id) {
            $items = $this->all();
            $removida = null;
            $restantes = [];
            foreach ($items as $d) {
                if ((int) ($d['id'] ?? 0) === $id && $removida === null) {
                    $removida = $d;
                    continue;
                }
                $restantes[] = $d;
            }
            if ($removida === null) return null;
            $this->saveAll($restantes);
            return $removida;
        });
    }

    /**
     * Um cidadão (que não seja o dono) apoia a denúncia — "eu também sofro
     * com isso" — em vez de criar uma denúncia duplicada. Cada usuário só
     * pode apoiar uma vez, e só enquanto o problema ainda estiver ativo.
     */
    public function apoiar(int $id, int $userId): bool {
        return ArquivoLock::executar('denuncias', function () use ($id, $userId) {
            $items = $this->all();
            $encontrado = false;

            foreach ($items as &$d) {
                if ((int)$d['id'] === $id) {
                    if ((int)($d['usuario_id'] ?? 0) === $userId) return false; // não pode apoiar a própria denúncia
                    $statusAtual = strtolower($d['status'] ?? '');
                    if (in_array($statusAtual, ['cancelada', 'resolvido', 'rejeitada'], true)) return false; // já não faz sentido apoiar

                    $apoios = $d['apoios'] ?? [];
                    if (in_array($userId, $apoios, true)) return false; // já apoiou antes

                    $apoios[] = $userId;
                    $d['apoios'] = $apoios;
                    $encontrado = true;
                    break;
                }
            }
            unset($d);

            if (!$encontrado) return false;

            $this->saveAll($items);
            return true;
        });
    }

    /** Peso numérico da prioridade, usado para ordenar (Urgente = 3 ... Baixa = 0). */
    public static function pesoPrioridade(array $d): int {
        $i = array_search($d['prioridade'] ?? 'Normal', self::PRIORIDADES, true);
        return $i === false ? 1 : (int) $i;
    }

    /** Define a prioridade de uma denúncia (só o admin chama). Retorna false se o valor ou a denúncia forem inválidos. */
    public function definirPrioridade(int $id, string $prioridade): bool {
        if (!in_array($prioridade, self::PRIORIDADES, true)) return false;
        return ArquivoLock::executar('denuncias', function () use ($id, $prioridade) {
            $items = $this->all();
            $achou = false;
            foreach ($items as &$d) {
                if ((int)$d['id'] === $id) {
                    if (in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) return false;
                    if (($d['prioridade'] ?? 'Normal') === $prioridade) return true;
                    $anterior = $d['prioridade'] ?? 'Normal';
                    $d['prioridade'] = $prioridade;
                    $d['historico'][] = ['status' => $d['status'], 'data' => date('Y-m-d H:i:s'), 'mensagem' => 'Prioridade alterada de ' . $anterior . ' para ' . $prioridade . '.', 'tipo' => 'prioridade'];
                    $achou = true;
                    break;
                }
            }
            unset($d);
            if (!$achou) return false;
            $this->saveAll($items);
            return true;
        });
    }

    /**
     * O cidadão corrige a própria denúncia (descrição e endereço). Só vale
     * enquanto ela ainda está "Pendente" — depois que a prefeitura começa a
     * analisar, o texto não muda mais. A edição entra no histórico.
     */
    public function editar(int $id, int $userId, string $descricao, string $endereco): bool {
        return ArquivoLock::executar('denuncias', function () use ($id, $userId, $descricao, $endereco) {
            $items = $this->all();
            $achou = false;
            foreach ($items as &$d) {
                if ((int)$d['id'] !== $id) continue;
                if ((int)($d['usuario_id'] ?? 0) !== $userId) return false;
                if (strtolower($d['status'] ?? '') !== 'pendente') return false;
                $d['descricao'] = $descricao;
                $d['endereco'] = $endereco;
                $d['editada_em'] = date('Y-m-d H:i:s');
                $d['historico'][] = [
                    'status' => $d['status'],
                    'data' => $d['editada_em'],
                    'mensagem' => 'Descrição editada pelo cidadão.',
                ];
                $achou = true;
                break;
            }
            unset($d);
            if (!$achou) return false;
            $this->saveAll($items);
            return true;
        });
    }

    /** Busca pelo número de protocolo (ex.: OC-2026-000012). Aceita minúsculas e espaços ao redor. */
    public function findByProtocolo(string $protocolo): ?array {
        $protocolo = strtoupper(trim($protocolo));
        if (!preg_match('/^OC-\d{4}-\d{1,9}$/', $protocolo)) return null;
        $id = (int) substr($protocolo, strrpos($protocolo, '-') + 1);
        $d = $this->find($id);
        return ($d && self::protocolo($d) === $protocolo) ? $d : null;
    }

    /** Estatísticas gerais para a página do admin (por categoria, por mês, avaliação, mais apoiadas). */
    public function estatisticas(): array {
        $todas = $this->all();
        $porCategoria = [];
        $porStatus = [];
        $porPrioridade = array_fill_keys(self::PRIORIDADES, 0);
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $meses[date('Y-m', strtotime("first day of -$i month"))] = 0;
        }
        $somaAv = 0; $qtdAv = 0; $atrasadas = 0; $reincidencias = 0;

        foreach ($todas as $d) {
            $cat = $d['categoria'] ?? 'outros';
            $porCategoria[$cat] = ($porCategoria[$cat] ?? 0) + 1;
            $st = $d['status'] ?? 'Pendente';
            $porStatus[$st] = ($porStatus[$st] ?? 0) + 1;
            if (strtolower($st) !== 'cancelada') {
                $porPrioridade[$d['prioridade']] = ($porPrioridade[$d['prioridade']] ?? 0) + 1;
            }
            $mes = substr($d['criada_em'] ?? '', 0, 7);
            if (isset($meses[$mes])) $meses[$mes]++;
            if (!empty($d['avaliacao'])) { $somaAv += (int)$d['avaliacao']; $qtdAv++; }
            if (self::statusPrazo($d)['atrasada']) $atrasadas++;
            if (!empty($d['denuncia_anterior_id'])) $reincidencias++;
        }

        $maisApoiadas = array_values(array_filter($todas, fn($d) => count($d['apoios'] ?? []) > 0
            && !in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)));
        usort($maisApoiadas, fn($a, $b) => count($b['apoios']) <=> count($a['apoios']));

        $validas = count($todas) - ($porStatus['Cancelada'] ?? 0);
        $resolvidas = $porStatus['Resolvido'] ?? 0;

        return [
            'total' => count($todas),
            'por_categoria' => $porCategoria,
            'por_status' => $porStatus,
            'por_prioridade' => $porPrioridade,
            'por_mes' => $meses,
            'avaliacao_media' => $qtdAv > 0 ? round($somaAv / $qtdAv, 1) : null,
            'avaliacoes' => $qtdAv,
            'atrasadas' => $atrasadas,
            'reincidencias' => $reincidencias,
            'taxa_resolucao' => $validas > 0 ? round($resolvidas / $validas * 100) : 0,
            'mais_apoiadas' => array_slice($maisApoiadas, 0, 5),
        ];
    }

    /**
     * Comparação deste mês com o anterior: novas denúncias e resolvidas.
     * Cada item: ['atual' => n, 'anterior' => n, 'variacao' => % ou null].
     */
    public function comparacaoMensal(): array {
        $mesAtual = date('Y-m');
        $mesAnterior = date('Y-m', strtotime('first day of -1 month'));
        $c = ['novas' => [0, 0], 'resolvidas' => [0, 0], 'apoios' => [0, 0]];
        foreach ($this->all() as $d) {
            $m = substr($d['criada_em'] ?? '', 0, 7);
            if ($m === $mesAtual) $c['novas'][0]++;
            if ($m === $mesAnterior) $c['novas'][1]++;
            foreach ($d['historico'] ?? [] as $h) {
                if (($h['status'] ?? '') !== 'Resolvido') continue;
                $mh = substr($h['data'] ?? '', 0, 7);
                if ($mh === $mesAtual) $c['resolvidas'][0]++;
                if ($mh === $mesAnterior) $c['resolvidas'][1]++;
            }
        }
        $saida = [];
        foreach ($c as $k => [$atual, $anterior]) {
            $saida[$k] = [
                'atual' => $atual,
                'anterior' => $anterior,
                'variacao' => $anterior > 0 ? (int) round(($atual - $anterior) / $anterior * 100) : null,
            ];
        }
        return $saida;
    }

    /** Quantidade de denúncias criadas por dia (Y-m-d => n) nas últimas $semanas semanas. */
    public function atividadePorDia(int $semanas = 26): array {
        $inicio = strtotime('monday this week -' . ($semanas - 1) . ' weeks');
        $dias = [];
        for ($t = $inicio; $t <= time(); $t += 86400) $dias[date('Y-m-d', $t)] = 0;
        foreach ($this->all() as $d) {
            $dia = substr($d['criada_em'] ?? '', 0, 10);
            if (isset($dias[$dia])) $dias[$dia]++;
        }
        return $dias;
    }

    /** true quando a denúncia já tem apoios suficientes pra ganhar o selo "verificado por moradores". */
    public static function ehVerificada(array $d): bool {
        return count($d['apoios'] ?? []) >= self::APOIOS_PARA_VERIFICAR;
    }

    /**
     * Número de protocolo legível da denúncia (ex.: OC-2026-000123).
     * É derivado do ano de criação + id, então não precisa de coluna nova
     * nem de migração — funciona igual com JSON e com MySQL.
     */
    public static function protocolo(array $d): string {
        $ano = !empty($d['criada_em']) ? date('Y', strtotime($d['criada_em'])) : date('Y');
        return 'OC-' . $ano . '-' . str_pad((string) (int) ($d['id'] ?? 0), 6, '0', STR_PAD_LEFT);
    }

    /** Prazo esperado (em dias) pra essa categoria ser resolvida. */
    public static function prazoDiasPorCategoria(string $categoria): int {
        return self::PRAZO_DIAS_POR_CATEGORIA[$categoria] ?? 15;
    }

    /**
     * Quantos dias a denúncia já está em aberto (da criação até hoje, ou até
     * a resolução, se já tiver sido resolvida) — e se isso já passou do prazo
     * esperado da categoria. Só é usado pra exibição, não bloqueia nada.
     */
    public static function statusPrazo(array $d): array {
        $inicio = strtotime($d['criada_em'] ?? 'now');
        $statusAtual = strtolower($d['status'] ?? '');
        $fim = time();

        // Se já foi resolvida, conta até a data da resolução (não até hoje).
        if ($statusAtual === 'resolvido') {
            foreach (($d['historico'] ?? []) as $h) {
                if (strtolower($h['status'] ?? '') === 'resolvido') {
                    $fim = strtotime($h['data'] ?? 'now');
                }
            }
        }

        $diasEmAberto = max(0, (int) floor(($fim - $inicio) / 86400));
        $prazo = self::prazoDiasPorCategoria($d['categoria'] ?? '');

        // Se já foi encaminhada a um órgão, vale o prazo de resposta do órgão.
        $enc = self::infoEncaminhamento($d);
        if ($enc) {
            return [
                'dias' => $diasEmAberto,
                'prazo' => $enc['prazo_dias'],
                'atrasada' => $enc['vencido'],
            ];
        }

        return [
            'dias' => $diasEmAberto,
            'prazo' => $prazo,
            'atrasada' => $diasEmAberto > $prazo && !in_array($statusAtual, ['resolvido', 'cancelada', 'rejeitada'], true),
        ];
    }

    /**
     * Usada pelo painel do administrador para mudar o status de uma denúncia.
     * O admin não marca "Resolvido" diretamente — ele marca "Aguardando
     * confirmação", e só vira "Resolvido" de verdade quando o próprio
     * cidadão confirma (ver confirmarResolucao()). Isso dá mais credibilidade
     * ao sistema: ninguém fecha a própria denúncia.
     * $mensagem é uma observação curta e opcional do admin (ex.: "equipe já foi
     * enviada ao local"), guardada junto da entrada do histórico e mostrada
     * pro cidadão junto da notificação.
     */
    public function atualizarStatus(int $id, string $status, string $mensagem = '', string $fotoResolucao = ''): bool {
        return ArquivoLock::executar('denuncias', function () use ($id, $status, $mensagem, $fotoResolucao) {
            $validos = ['Pendente', 'Em análise', 'Aguardando confirmação', 'Rejeitada'];
            if ($status === 'Rejeitada' && mb_strlen(trim($mensagem)) < 10) return false;
            if (!in_array($status, $validos, true)) return false;

            $items = $this->all();
            $encontrado = false;
            foreach ($items as &$d) {
                if ((int)$d['id'] === $id) {
                    // Só registra uma nova entrada no histórico se o status realmente mudou
                    // (evita poluir o histórico se o admin reenviar o mesmo status).
                    if (in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true)) return false;
                    if (($d['status'] ?? '') === $status && $mensagem === '' && $fotoResolucao === '') return true;
                    if (($d['status'] ?? '') !== $status || $mensagem !== '' || $fotoResolucao !== '') {
                        $entrada = ['status' => $status, 'data' => date('Y-m-d H:i:s')];
                        if ($mensagem !== '') {
                            $entrada['mensagem'] = $mensagem;
                        }
                        if ($fotoResolucao !== '') {
                            $entrada['foto'] = $fotoResolucao;
                        }
                        $d['historico'][] = $entrada;
                    }
                    $d['status'] = $status;
                    if ($status === 'Rejeitada') $d['motivo_rejeicao'] = $mensagem;
                    if ($fotoResolucao !== '') {
                        $d['foto_resolucao'] = $fotoResolucao;
                    }
                    $encontrado = true;
                    break;
                }
            }
            unset($d);

            if (!$encontrado) return false;

            $this->saveAll($items);
            return true;
        });
    }

    /**
     * O cidadão confirma que o problema foi realmente resolvido. Só é permitido
     * enquanto a denúncia estiver "Aguardando confirmação" e for do próprio usuário.
     * $avaliacao é opcional (1 a 5 estrelas) — nota de satisfação sobre a resolução.
     */
    public function confirmarResolucao(int $id, int $userId, ?int $avaliacao = null): bool {
        if ($avaliacao !== null && ($avaliacao < 1 || $avaliacao > 5)) {
            $avaliacao = null;
        }
        return $this->mudarStatusPeloCidadao($id, $userId, 'Aguardando confirmação', 'Resolvido', '', '', $avaliacao);
    }

    /**
     * O cidadão diz que o problema NÃO foi resolvido — a denúncia volta para
     * "Em análise" e some da lista de aguardando confirmação. $motivo é opcional.
     */
    public function reabrir(int $id, int $userId, string $motivo = ''): bool {
        return $this->mudarStatusPeloCidadao($id, $userId, 'Aguardando confirmação', 'Em análise', $motivo, 'Reaberta pelo cidadão');
    }

    /** Lógica compartilhada por confirmarResolucao() e reabrir(). */
    private function mudarStatusPeloCidadao(
        int $id,
        int $userId,
        string $statusEsperado,
        string $novoStatus,
        string $motivo = '',
        string $prefixoMensagem = '',
        ?int $avaliacao = null
    ): bool {
        return ArquivoLock::executar('denuncias', function () use ($id, $userId, $statusEsperado, $novoStatus, $motivo, $prefixoMensagem, $avaliacao) {
            $items = $this->all();
            $encontrado = false;

            foreach ($items as &$d) {
                if ((int)$d['id'] === $id) {
                    if ((int)($d['usuario_id'] ?? 0) !== $userId) return false; // não é dono da denúncia
                    if (($d['status'] ?? '') !== $statusEsperado) return false; // só nesse status específico

                    $entrada = ['status' => $novoStatus, 'data' => date('Y-m-d H:i:s')];
                    $mensagem = trim($prefixoMensagem . ($motivo !== '' ? ': ' . $motivo : ($prefixoMensagem !== '' ? '.' : '')));
                    if ($mensagem !== '') {
                        $entrada['mensagem'] = $mensagem;
                    }

                    $d['historico'][] = $entrada;
                    $d['status'] = $novoStatus;
                    if ($avaliacao !== null) {
                        $d['avaliacao'] = $avaliacao;
                    }
                    $encontrado = true;
                    break;
                }
            }
            unset($d);

            if (!$encontrado) return false;

            $this->saveAll($items);
            return true;
        });
    }

    /**
     * Tempo médio, em horas, entre a denúncia virar "Pendente" e virar
     * "Resolvido", considerando só as denúncias que já estão resolvidas hoje.
     * Retorna null se ainda não existe nenhuma denúncia resolvida.
     */
    public function tempoMedioResolucaoHoras(): ?float {
        $totalHoras = 0.0;
        $qtd = 0;

        foreach ($this->all() as $d) {
            if (strtolower($d['status'] ?? '') !== 'resolvido') continue;

            $inicio = null;
            $fim = null;
            foreach (($d['historico'] ?? []) as $h) {
                $statusHist = strtolower($h['status'] ?? '');
                if ($statusHist === 'pendente' && $inicio === null) {
                    $inicio = $h['data'] ?? null;
                }
                if ($statusHist === 'resolvido') {
                    $fim = $h['data'] ?? null; // fica com a última ocorrência
                }
            }

            if ($inicio && $fim) {
                $horas = (strtotime($fim) - strtotime($inicio)) / 3600;
                if ($horas >= 0) {
                    $totalHoras += $horas;
                    $qtd++;
                }
            }
        }

        return $qtd > 0 ? $totalHoras / $qtd : null;
    }

    /**
     * Marca as denúncias como já vistas pelo admin (usado pro badge de "novo").
     * Chamado depois que o admin carrega a lista — assim, na próxima visita,
     * essas denúncias não aparecem mais com o selo de novidade.
     */
    public function marcarVistas(array $ids): void {
        ArquivoLock::executar('denuncias', function () use ($ids) {
            if (empty($ids)) return;

            $items = $this->all();
            $alterou = false;

            foreach ($items as &$d) {
                if (in_array((int)$d['id'], $ids, true) && empty($d['vista_admin'])) {
                    $d['vista_admin'] = true;
                    $alterou = true;
                }
            }
            unset($d);

            if (!$alterou) return;

            $this->saveAll($items);
        });
    }
}
