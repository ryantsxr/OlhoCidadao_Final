<?php
/**
 * Olhinho, o assistente do Olho Cidadão (v0.50).
 *
 * Como ele responde, nesta ordem:
 *   1) Número de protocolo na pergunta (OC-2026-000012)? Consulta o status.
 *   2) "Minhas denúncias" com alguém logado? Resume as denúncias da pessoa.
 *   3) Respostas prontas: procura palavras-chave na pergunta (lista RESPOSTAS
 *      abaixo — é só editar para mudar ou acrescentar respostas).
 *   4) A pessoa descreveu um problema ("poste apagado na rua 15")? Sugere a
 *      categoria e oferece o botão para denunciar.
 *   5) Nada disso e tem IA configurada no config.local.php? Pergunta para a IA
 *      (Claude ou outro serviço compatível com a API da OpenAI).
 *   6) Sem IA: diz que não entendeu e mostra sugestões.
 *
 * Funciona 100% sem internet e sem chave nenhuma (passos 1 a 4 e 6).
 */
class Assistente {
    public const NOME = 'Olhinho';

    /**
     * Respostas prontas. Cada item:
     *   'chaves'  => palavras ou trechos que ativam a resposta (sem acento, minúsculas);
     *   'texto'   => a resposta;
     *   'acoes'   => botões opcionais [rótulo, rota];
     *   'humor'   => como o mascote reage (feliz, pensando, preocupado, comemorar).
     */
    private const RESPOSTAS = [
        'saudacao' => [
            'chaves' => ['oi', 'ola', 'oie', 'bom dia', 'boa tarde', 'boa noite', 'eae', 'e ai', 'hey', 'opa', 'salve'],
            'texto' => 'Oi! Eu sou o Olhinho, o assistente do Olho Cidadão. Posso te ajudar a fazer uma denúncia, explicar os status ou consultar um protocolo. O que você precisa?',
            'humor' => 'acenar',
        ],
        'quem_e' => [
            'chaves' => ['quem e voce', 'quem e vc', 'seu nome', 'como voce se chama', 'voce e um robo', 'voce e humano', 'o que voce e', 'o que voce faz'],
            'texto' => 'Sou o Olhinho, o mascote do Olho Cidadão! Sou um assistente automático: respondo dúvidas sobre o sistema e consulto protocolos. Não sou da prefeitura e posso errar, tá?',
            'humor' => 'feliz',
        ],
        'como_denunciar' => [
            'chaves' => ['como denuncia', 'como faco uma denuncia', 'como faco denuncia', 'fazer denuncia', 'fazer uma denuncia', 'nova denuncia', 'registrar', 'reclamar', 'abrir denuncia', 'relatar', 'como funciona', 'quero denunciar', 'como denunciar', 'passo a passo'],
            'texto' => 'São só 3 passos: 1) escolha a categoria do problema; 2) descreva, coloque uma foto e marque o local no mapa (foto e local são opcionais, mas ajudam muito); 3) pronto! Você recebe um número de protocolo para acompanhar.',
            'acoes' => [['Fazer denúncia agora', 'denuncia-passo1']],
            'humor' => 'feliz',
        ],
        'categoria' => [
            'chaves' => ['qual categoria', 'que categoria', 'categoria escolho', 'nao sei a categoria', 'categorias', 'qual opcao'],
            'texto' => 'As categorias são: Iluminação (poste apagado, lâmpada queimada), Sinalização (semáforo, placa, faixa de pedestre), Lixo (entulho, lixo acumulado) e Outros (buraco, esgoto, árvore caída e o que não se encaixar). Na dúvida, use "Outros" e explique na descrição.',
            'humor' => 'pensando',
        ],
        'foto' => [
            'chaves' => ['foto', 'imagem', 'anexar', 'camera', 'print'],
            'texto' => 'A foto é opcional, mas ajuda muito a equipe! Pode ser JPG, PNG ou WEBP de até 5 MB. Dica: tire de dia e de perto. A localização gravada dentro da foto é apagada automaticamente, por privacidade.',
            'humor' => 'feliz',
        ],
        'localizacao' => [
            'chaves' => ['localizacao', 'mapa', 'endereco', 'gps', 'marcar o local', 'marcar local', 'onde fica', 'ponto de referencia'],
            'texto' => 'No passo 2, toque em "Adicionar localização": dá para usar a sua localização atual ou tocar no mapa no ponto exato. Se já existir uma denúncia parecida a até 100 metros, eu aviso para você apoiar a que já existe.',
            'humor' => 'pensando',
        ],
        'voz' => [
            'chaves' => ['falar', 'microfone', 'ditar', 'por voz', 'audio', 'nao sei escrever', 'dificuldade para escrever'],
            'texto' => 'Dá para descrever falando! No passo 2, toque no botão do microfone ao lado da descrição e fale normalmente; o texto aparece sozinho. Funciona no Chrome e no Edge.',
            'humor' => 'feliz',
        ],
        'anonima' => [
            'chaves' => ['anonim', 'denunciar anonim', 'sem me identificar', 'meu nome aparece', 'identidade', 'sigilo', 'ninguem saber', 'esconder meu nome'],
            'texto' => 'Pode ficar tranquilo: no passo 2 tem a opção "Denunciar anonimamente". Aí seu nome não aparece para os outros moradores. Só a administração da prefeitura consegue ver quem enviou.',
            'humor' => 'feliz',
        ],
        'protocolo' => [
            'chaves' => ['protocolo', 'numero da denuncia', 'comprovante', 'acompanhar', 'ingresso', 'consultar'],
            'texto' => 'Toda denúncia recebe um protocolo no formato OC-2026-000012. Me mande o número aqui que eu digo como ela está, ou use a página de consulta (não precisa de login).',
            'acoes' => [['Consultar protocolo', 'consulta']],
            'humor' => 'pensando',
        ],
        'status_pendente' => [
            'chaves' => ['pendente'],
            'texto' => '"Pendente" quer dizer que a denúncia chegou e está na fila para a prefeitura analisar. Enquanto está pendente, você ainda pode editar ou cancelar.',
        ],
        'status_analise' => [
            'chaves' => ['em analise', 'analisando'],
            'texto' => '"Em análise" quer dizer que a prefeitura já está cuidando: a denúncia foi vista e, muitas vezes, já encaminhada para o setor responsável, com um prazo de resposta.',
        ],
        'status_aguardando' => [
            'chaves' => ['aguardando confirmacao', 'aguardando', 'confirmar resolucao', 'confirmacao'],
            'texto' => '"Aguardando confirmação" quer dizer que a prefeitura diz que resolveu. Agora é com você: se resolveu mesmo, confirme (e dê estrelinhas); se não, é só reabrir que volta para análise.',
            'acoes' => [['Minhas denúncias', 'minhas-denuncias']],
        ],
        'status_resolvido' => [
            'chaves' => ['resolvido', 'resolvida', 'concluida', 'finalizada'],
            'texto' => '"Resolvido" só aparece quando o próprio cidadão confirma que o problema foi solucionado. A prefeitura não consegue fechar a denúncia sozinha. Isso deixa os números mais honestos!',
            'humor' => 'comemorar',
        ],
        'status_cancelada' => [
            'chaves' => ['cancelada'],
            'texto' => '"Cancelada" é quando o próprio cidadão desistiu da denúncia enquanto ela ainda estava pendente.',
        ],
        'status' => [
            'chaves' => ['status', 'situacao', 'o que significa', 'etapas'],
            'texto' => 'Os status são: Pendente (na fila), Em análise (a prefeitura está cuidando), Aguardando confirmação (a prefeitura diz que resolveu e você confirma), Resolvido (você confirmou) e Cancelada (você desistiu). Quer que eu explique algum?',
            'humor' => 'pensando',
        ],
        'prazo' => [
            'chaves' => ['prazo', 'quanto tempo', 'demora', 'quando vao resolver', 'vai resolver', 'quando resolve', 'vao arrumar', 'vai arrumar', 'atrasad', 'demorando', 'ninguem resolve', 'ninguem resolveu'],
            'texto' => 'Cada tipo tem um prazo esperado: Lixo 3 dias, Iluminação 7, Sinalização 10 e Outros 15. Quando a denúncia é encaminhada a um setor, vale o prazo do setor. Passou do prazo? Ela aparece como atrasada para a prefeitura e no telão.',
            'humor' => 'pensando',
        ],
        'cancelar' => [
            'chaves' => ['cancelar', 'desistir', 'apagar denuncia', 'excluir denuncia', 'remover denuncia'],
            'texto' => 'Você pode cancelar enquanto a denúncia estiver "Pendente": vá em Minhas denúncias e toque em "Cancelar denúncia". Depois que a prefeitura começa a analisar, não dá mais para cancelar.',
            'acoes' => [['Minhas denúncias', 'minhas-denuncias']],
        ],
        'editar' => [
            'chaves' => ['editar', 'corrigir', 'mudar a descricao', 'alterar denuncia', 'errei', 'escrevi errado'],
            'texto' => 'Enquanto estiver "Pendente", dá para corrigir a descrição e o endereço: em Minhas denúncias, toque em "Editar".',
            'acoes' => [['Minhas denúncias', 'minhas-denuncias']],
        ],
        'reabrir' => [
            'chaves' => ['nao foi resolvido', 'nao resolveram', 'reabrir', 'continua quebrado', 'continua igual', 'voltou o problema', 'voltou a quebrar', 'mentira'],
            'texto' => 'Se a prefeitura marcou como resolvido mas não foi, toque em "Não foi resolvido, reabrir" na sua denúncia (enquanto estiver "Aguardando confirmação"). Se o problema voltou depois, faça uma nova denúncia e marque que é reincidência.',
            'acoes' => [['Minhas denúncias', 'minhas-denuncias']],
            'humor' => 'preocupado',
        ],
        'apoiar' => [
            'chaves' => ['apoiar', 'apoio', 'eu tambem', 'mesmo problema', 'duplicada', 'ja existe', 'ja denunciaram', 'vizinho denunciou'],
            'texto' => 'Se alguém já denunciou o mesmo problema, apoie a denúncia dela em vez de criar outra: abra pelo mapa público e toque em "Eu também sofro com isso". Com 3 apoios ela ganha o selo "verificado por moradores".',
            'acoes' => [['Ver o mapa', 'mapa']],
            'humor' => 'feliz',
        ],
        'notificacoes' => [
            'chaves' => ['notificac', 'aviso', 'avisar', 'email', 'e-mail', 'sino'],
            'texto' => 'Sempre que o status muda, você recebe um aviso no sininho do painel e, se o e-mail estiver configurado no sistema, também por e-mail.',
        ],
        'senha' => [
            'chaves' => ['senha', 'nao consigo entrar', 'login', 'entrar na conta'],
            'texto' => 'Esqueceu a senha? Na tela de login toque em "Esqueceu a senha?" e informe seu e-mail. Para trocar uma senha que você lembra, vá em Perfil.',
            'acoes' => [['Recuperar senha', 'esqueci-senha']],
        ],
        'privacidade' => [
            'chaves' => ['excluir conta', 'apagar conta', 'apagar minha conta', 'meus dados', 'lgpd', 'privacidade', 'dados pessoais', 'baixar meus dados'],
            'texto' => 'Em Perfil você pode baixar todos os seus dados e excluir a conta quando quiser (LGPD). Os termos explicam o que guardamos e por quê.',
            'acoes' => [['Ir para o perfil', 'perfil'], ['Ler os termos', 'termos']],
        ],
        'mapa_publico' => [
            'chaves' => ['mapa publico', 'denuncias da cidade', 'ver denuncias', 'outras denuncias', 'mapa de calor'],
            'texto' => 'O mapa público mostra as denúncias da cidade (sem nomes), com filtros por categoria, status e período, e o mapa de calor das regiões com mais problemas. Não precisa de login.',
            'acoes' => [['Abrir o mapa', 'mapa']],
        ],
        'vitorias' => [
            'chaves' => ['vitoria', 'resolvidos', 'antes e depois', 'o que ja foi resolvido', 'funciona mesmo', 'adianta'],
            'texto' => 'Adianta sim! O Mural de vitórias mostra os problemas que já foram resolvidos, com a foto de antes e a de depois.',
            'acoes' => [['Ver o mural de vitórias', 'vitorias']],
            'humor' => 'comemorar',
        ],
        'quem_resolve' => [
            'chaves' => ['quem resolve', 'quem recebe', 'prefeitura', 'secretaria', 'orgao', 'setor', 'quem ve'],
            'texto' => 'A administração da prefeitura recebe a denúncia e encaminha para o setor certo: Obras, Iluminação Pública, Trânsito ou Limpeza Urbana. Cada setor tem um prazo de resposta.',
        ],
        'emergencia' => [
            'chaves' => ['emergencia', 'incendio', 'fogo', 'acidente', 'ferido', 'socorro', 'fio caido', 'fio solto', 'choque', 'afogando', 'urgente', 'perigo de vida'],
            'texto' => 'Se tem alguém em perigo agora, não use o Olho Cidadão: ligue 193 (Bombeiros), 192 (SAMU), 190 (Polícia) ou 199 (Defesa Civil). Fio de energia caído: fique longe e chame a concessionária de energia. Depois, se quiser, registre aqui para a prefeitura acompanhar.',
            'humor' => 'preocupado',
        ],
        'app' => [
            'chaves' => ['instalar', 'aplicativo', 'baixar o app', 'app', 'tela inicial', 'play store'],
            'texto' => 'Dá para instalar como aplicativo! No celular, abra o site no Chrome, toque nos três pontinhos e em "Adicionar à tela inicial" (ou "Instalar app").',
        ],
        'acessibilidade' => [
            'chaves' => ['libras', 'surdo', 'fonte grande', 'letra grande', 'contraste', 'enxergar', 'acessibilidade', 'tema escuro', 'tema claro', 'idioma', 'ingles', 'espanhol'],
            'texto' => 'Em Configurações você muda o tema (claro ou escuro), o tamanho da letra, o alto contraste e o idioma. O botão azul com a mãozinha traduz o site para Libras.',
            'acoes' => [['Configurações', 'configuracoes']],
        ],
        'agradecimento' => [
            'chaves' => ['obrigad', 'valeu', 'vlw', 'brigado', 'tmj', 'show', 'top', 'perfeito'],
            'texto' => 'De nada! Obrigado você por cuidar da cidade. 💚',
            'humor' => 'comemorar',
        ],
        'ajuda' => [
            'chaves' => ['ajuda', 'duvida', 'o que voce sabe', 'me ajuda', 'help', 'socorro olhinho'],
            'texto' => 'Posso te ajudar com: como fazer uma denúncia, o que significa cada status, prazos, cancelar ou editar, consultar um protocolo (é só me mandar o número) e privacidade. Pergunte do seu jeito!',
            'humor' => 'feliz',
        ],
    ];

    /** Palavras que indicam cada categoria quando a pessoa descreve um problema. */
    private const PISTAS_CATEGORIA = [
        'iluminacao' => ['poste', 'lampada', 'luz', 'escuro', 'apagado', 'iluminacao', 'luminaria'],
        'sinalizacao' => ['semaforo', 'sinal', 'placa', 'faixa', 'transito', 'quebra-mola', 'lombada'],
        'lixo' => ['lixo', 'entulho', 'sujeira', 'sujo', 'coleta', 'lixeira', 'descarte', 'mau cheiro', 'fedor'],
        'outros' => ['buraco', 'esgoto', 'bueiro', 'alagamento', 'alagado', 'arvore', 'galho', 'mato', 'calcada', 'asfalto', 'cratera', 'vazamento', 'cano'],
    ];

    private const ROTULO_CATEGORIA = [
        'iluminacao' => 'Iluminação',
        'sinalizacao' => 'Sinalização',
        'lixo' => 'Lixo',
        'outros' => 'Outros',
    ];

    /** Sugestões que aparecem como botões rápidos no chat. */
    public const SUGESTOES = [
        'Como faço uma denúncia?',
        'O que é "Em análise"?',
        'Quanto tempo demora?',
        'Posso denunciar anônimo?',
    ];

    /**
     * Responde uma pergunta. $historico: últimas mensagens [['de'=>'eu'|'olhinho','texto'=>...]]
     * (só a IA usa). $userId: quem pergunta (0 = visitante).
     * Retorna ['texto' => ..., 'acoes' => [[rótulo, url]], 'humor' => ..., 'fonte' => 'faq'|'protocolo'|'ia'|'nao-entendi'].
     */
    public static function responder(string $pergunta, array $historico = [], int $userId = 0): array {
        $pergunta = trim(mb_substr($pergunta, 0, 500));
        $norm = self::normalizar($pergunta);

        if ($norm === '') {
            return self::resposta('Pode perguntar! Escreva sua dúvida aqui embaixo.', [], 'pensando', 'faq');
        }

        // 1) Protocolo na pergunta.
        if (preg_match('/OC\s*-?\s*(\d{4})\s*-?\s*(\d{1,9})/i', $pergunta, $m)) {
            return self::consultarProtocolo('OC-' . $m[1] . '-' . str_pad($m[2], 6, '0', STR_PAD_LEFT));
        }

        // 2) Minhas denúncias (logado).
        if (preg_match('/\b(minhas denuncias|minha denuncia|quantas denuncias|meus protocolos|como estao minhas|status das minhas)\b/', $norm)) {
            return self::resumoDoUsuario($userId);
        }

        // 3) Respostas prontas.
        $melhor = null;
        $melhorPontos = 0;
        foreach (self::RESPOSTAS as $chave => $r) {
            $pontos = 0;
            foreach ($r['chaves'] as $palavra) {
                if (self::contem($norm, $palavra)) {
                    // Trechos maiores valem mais ("em analise" > "analise").
                    $pontos += 1 + substr_count($palavra, ' ') * 1.5 + mb_strlen($palavra) / 20;
                }
            }
            if ($pontos > $melhorPontos) {
                $melhorPontos = $pontos;
                $melhor = $chave;
            }
        }

        // Saudação/agradecimento junto com outra pergunta: a outra pergunta ganha.
        $categoria = self::adivinharCategoria($norm);
        $ehConversa = in_array($melhor, ['saudacao', 'agradecimento'], true);

        if ($melhor !== null && !($ehConversa && $categoria !== null)) {
            $r = self::RESPOSTAS[$melhor];
            return self::resposta($r['texto'], $r['acoes'] ?? [], $r['humor'] ?? 'feliz', 'faq');
        }

        // 4) Descreveu um problema.
        if ($categoria !== null) {
            return self::resposta(
                'Parece um problema de "' . self::ROTULO_CATEGORIA[$categoria] . '". Quer registrar a denúncia? É rapidinho: escolha essa categoria no passo 1 e descreva do jeito que você me contou.',
                [['Fazer denúncia', 'denuncia-passo1']],
                'pensando',
                'faq'
            );
        }

        // 4b) Falou de denúncia mas não deu pra entender o quê: explica como denunciar.
        if (str_contains($norm, 'denunci')) {
            $r = self::RESPOSTAS['como_denunciar'];
            return self::resposta($r['texto'], $r['acoes'], 'feliz', 'faq');
        }

        // 5) IA (se configurada).
        if (self::iaConfigurada()) {
            $resp = self::perguntarIa($pergunta, $historico);
            if ($resp !== null) {
                return self::resposta($resp, [], 'feliz', 'ia');
            }
        }

        // 6) Não entendeu.
        return self::resposta(
            'Hmm, essa eu não sei responder. Tente perguntar de outro jeito, ou escolha uma das sugestões abaixo. Se for uma dúvida sobre a sua denúncia, me mande o número do protocolo.',
            [],
            'pensando',
            'nao-entendi'
        );
    }

    private static function resposta(string $texto, array $acoes, string $humor, string $fonte): array {
        $acoesUrl = array_map(fn($a) => ['rotulo' => $a[0], 'url' => BASE_URL . '/?rota=' . $a[1]], $acoes);
        return ['texto' => $texto, 'acoes' => $acoesUrl, 'humor' => $humor, 'fonte' => $fonte];
    }

    /** Minúsculas, sem acento e sem pontuação — "Está em ANÁLISE?" vira "esta em analise". */
    public static function normalizar(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        $semAcento = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($semAcento === false || $semAcento === '') {
            $semAcento = strtr($texto, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e','í'=>'i','ì'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ú'=>'u','ü'=>'u','ç'=>'c']);
        }
        $semAcento = preg_replace('/[^a-z0-9\- ]+/', ' ', $semAcento) ?? '';
        return trim(preg_replace('/\s+/', ' ', $semAcento) ?? '');
    }

    /** A palavra/trecho aparece como palavra inteira (ou começo de palavra, se terminar sem espaço). */
    private static function contem(string $texto, string $palavra): bool {
        $p = preg_quote($palavra, '/');
        // Chaves curtas (até 3 letras, ex.: "oi", "app") precisam ser a palavra exata.
        $fim = mb_strlen($palavra) <= 3 ? '\b' : '';
        return (bool) preg_match('/\b' . $p . $fim . '/', $texto);
    }

    private static function adivinharCategoria(string $norm): ?string {
        $melhor = null;
        $max = 0;
        foreach (self::PISTAS_CATEGORIA as $cat => $pistas) {
            $n = 0;
            foreach ($pistas as $p) {
                if (self::contem($norm, $p)) $n++;
            }
            if ($n > $max) { $max = $n; $melhor = $cat; }
        }
        return $melhor;
    }

    private static function consultarProtocolo(string $protocolo): array {
        $limiter = new RateLimiter('assistente-protocolo', maxTentativas: 20, bloqueioSegundos: 300);
        if ($limiter->bloqueado() > 0) {
            return self::resposta('Muitas consultas seguidas. Espere uns minutinhos e tente de novo.', [], 'preocupado', 'protocolo');
        }
        $limiter->registrarTentativa();

        $d = (new Denuncia)->findByProtocolo($protocolo);
        if (!$d) {
            return self::resposta('Não encontrei o protocolo ' . $protocolo . '. Confira se digitou certinho (formato OC-2026-000012).', [['Consultar protocolo', 'consulta']], 'preocupado', 'protocolo');
        }

        $status = $d['status'] ?? 'Pendente';
        $desde = '';
        foreach (array_reverse($d['historico'] ?? []) as $h) {
            if (($h['status'] ?? '') === $status && !empty($h['data'])) { $desde = date('d/m/Y', strtotime($h['data'])); }
            else if ($desde !== '') break;
        }
        $texto = sprintf('A denúncia %s (%s) está "%s"%s.', $protocolo, self::ROTULO_CATEGORIA[$d['categoria'] ?? 'outros'] ?? 'Outros', $status, $desde !== '' ? ' desde ' . $desde : '');

        $enc = Denuncia::infoEncaminhamento($d);
        if ($enc && !$enc['finalizada']) {
            $texto .= ' Está com: ' . $enc['orgao'] . ($enc['prazo_data'] ? ', prazo até ' . date('d/m/Y', strtotime($enc['prazo_data'])) : '') . '.';
            if ($enc['vencido']) $texto .= ' O prazo já passou, e a prefeitura está vendo isso como atrasada.';
        }
        if ($status === 'Aguardando confirmação') $texto .= ' Se for sua, confirme se foi resolvido mesmo!';
        if ($status === 'Resolvido') $texto .= ' Problema resolvido! 🎉';

        $humor = $status === 'Resolvido' ? 'comemorar' : (($enc['vencido'] ?? false) ? 'preocupado' : 'feliz');
        return self::resposta($texto, [['Ver detalhes', 'consulta&protocolo=' . urlencode($protocolo)]], $humor, 'protocolo');
    }

    private static function resumoDoUsuario(int $userId): array {
        if ($userId <= 0) {
            return self::resposta('Para eu ver as suas denúncias, entre na sua conta. Se tiver o número do protocolo, pode me mandar aqui mesmo.', [['Entrar', 'login']], 'pensando', 'faq');
        }
        $minhas = (new Denuncia)->byUser($userId);
        if (!$minhas) {
            return self::resposta('Você ainda não fez nenhuma denúncia. Bora fazer a primeira?', [['Fazer denúncia', 'denuncia-passo1']], 'feliz', 'faq');
        }
        $porStatus = [];
        foreach ($minhas as $d) {
            $st = $d['status'] ?? 'Pendente';
            $porStatus[$st] = ($porStatus[$st] ?? 0) + 1;
        }
        $nomes = [
            'Pendente' => ['pendente', 'pendentes'],
            'Em análise' => ['em análise', 'em análise'],
            'Aguardando confirmação' => ['aguardando sua confirmação', 'aguardando sua confirmação'],
            'Resolvido' => ['resolvida', 'resolvidas'],
            'Cancelada' => ['cancelada', 'canceladas'],
        ];
        $partes = [];
        foreach ($porStatus as $st => $n) {
            $partes[] = $n . ' ' . ($nomes[$st][$n > 1 ? 1 : 0] ?? mb_strtolower($st));
        }
        $texto = 'Você tem ' . count($minhas) . ' denúncia' . (count($minhas) > 1 ? 's' : '') . ': ' . implode(', ', $partes) . '.';
        if (!empty($porStatus['Aguardando confirmação'])) {
            $texto .= ' Tem denúncia esperando você confirmar se foi resolvida!';
        }
        return self::resposta($texto, [['Minhas denúncias', 'minhas-denuncias']], 'feliz', 'faq');
    }

    // =====================================================================
    // IA opcional
    // =====================================================================

    public static function iaConfigurada(): bool {
        return defined('ASSISTENTE_IA_PROVEDOR') && ASSISTENTE_IA_PROVEDOR !== ''
            && defined('ASSISTENTE_IA_CHAVE') && ASSISTENTE_IA_CHAVE !== '';
    }

    /** Tira e-mail, CPF e telefone da pergunta antes de mandar para fora (LGPD). */
    public static function anonimizar(string $texto): string {
        $texto = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/u', '[e-mail]', $texto) ?? $texto;
        $texto = preg_replace('/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/', '[cpf]', $texto) ?? $texto;
        $texto = preg_replace('/\(?\b\d{2}\)?\s?9?\d{4}-?\d{4}\b/', '[telefone]', $texto) ?? $texto;
        return $texto;
    }

    /** Instruções fixas para a IA: só falar do Olho Cidadão, curto, sem inventar. */
    private static function instrucoesIa(): string {
        $base = [];
        foreach (self::RESPOSTAS as $r) $base[] = '- ' . $r['texto'];
        return "Você é o Olhinho, mascote e assistente do Olho Cidadão, um sistema (projeto escolar) de denúncias de problemas urbanos de Guajará-Mirim/RO.\n"
            . "Regras:\n"
            . "- Responda em português do Brasil, de forma simpática, em no máximo 3 frases curtas.\n"
            . "- Fale só sobre o Olho Cidadão e problemas urbanos da cidade. Para outros assuntos, diga com gentileza que só ajuda com o Olho Cidadão.\n"
            . "- Use apenas as informações abaixo. Se não souber, diga que não sabe e sugira falar com a prefeitura. Nunca invente prazos, telefones ou promessas.\n"
            . "- Em emergência (pessoa em perigo), mande ligar 193, 192, 190 ou 199.\n"
            . "- Nunca peça dados pessoais (CPF, telefone, endereço da pessoa).\n\n"
            . "Informações do sistema:\n" . implode("\n", $base);
    }

    private static function perguntarIa(string $pergunta, array $historico): ?string {
        // Limite para ninguém gastar os créditos da IA: por IP e por sessão.
        $limiter = new RateLimiter('assistente-ia', maxTentativas: 25, bloqueioSegundos: 3600);
        if ($limiter->bloqueado() > 0) return null;
        $limiter->registrarTentativa();

        $mensagens = [];
        foreach (array_slice($historico, -6) as $h) {
            $texto = self::anonimizar(mb_substr((string) ($h['texto'] ?? ''), 0, 500));
            if ($texto === '') continue;
            $mensagens[] = ['role' => ($h['de'] ?? '') === 'olhinho' ? 'assistant' : 'user', 'content' => $texto];
        }
        $mensagens[] = ['role' => 'user', 'content' => self::anonimizar($pergunta)];
        // As APIs exigem que a conversa comece com o usuário e alterne os papéis.
        while ($mensagens && $mensagens[0]['role'] !== 'user') array_shift($mensagens);
        $limpas = [];
        foreach ($mensagens as $m) {
            if ($limpas && end($limpas)['role'] === $m['role']) {
                $limpas[count($limpas) - 1]['content'] .= "\n" . $m['content'];
            } else {
                $limpas[] = $m;
            }
        }

        $provedor = strtolower((string) ASSISTENTE_IA_PROVEDOR);
        try {
            if ($provedor === 'anthropic' || $provedor === 'claude') {
                $resp = self::post('https://api.anthropic.com/v1/messages', [
                    'x-api-key: ' . ASSISTENTE_IA_CHAVE,
                    'anthropic-version: 2023-06-01',
                ], [
                    'model' => (defined('ASSISTENTE_IA_MODELO') && ASSISTENTE_IA_MODELO !== '') ? ASSISTENTE_IA_MODELO : 'claude-haiku-4-5-20251001',
                    'max_tokens' => 300,
                    'system' => self::instrucoesIa(),
                    'messages' => $limpas,
                ]);
                $texto = $resp['content'][0]['text'] ?? null;
            } else {
                // Qualquer serviço compatível com a API da OpenAI (a própria OpenAI, Groq, OpenRouter...).
                $url = (defined('ASSISTENTE_IA_URL') && ASSISTENTE_IA_URL !== '') ? ASSISTENTE_IA_URL : 'https://api.openai.com/v1/chat/completions';
                $resp = self::post($url, ['Authorization: Bearer ' . ASSISTENTE_IA_CHAVE], [
                    'model' => (defined('ASSISTENTE_IA_MODELO') && ASSISTENTE_IA_MODELO !== '') ? ASSISTENTE_IA_MODELO : 'gpt-4o-mini',
                    'max_tokens' => 300,
                    'messages' => array_merge([['role' => 'system', 'content' => self::instrucoesIa()]], $limpas),
                ]);
                $texto = $resp['choices'][0]['message']['content'] ?? null;
            }
        } catch (\Throwable $e) {
            error_log('[Assistente] Falha na IA: ' . $e->getMessage());
            return null;
        }

        $texto = is_string($texto) ? trim($texto) : '';
        return $texto !== '' ? mb_substr($texto, 0, 900) : null;
    }

    private static function post(string $url, array $cabecalhos, array $corpo): array {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('A extensão cURL do PHP não está ativa.');
        }
        $ch = curl_init($url);
        $verificar = !defined('ASSISTENTE_IA_VERIFY_SSL') || ASSISTENTE_IA_VERIFY_SSL;
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $cabecalhos),
            CURLOPT_POSTFIELDS => json_encode($corpo, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => $verificar,
            CURLOPT_SSL_VERIFYHOST => $verificar ? 2 : 0,
        ]);
        $saida = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($saida === false) throw new \RuntimeException('Sem resposta: ' . $erro);
        $json = json_decode((string) $saida, true);
        if ($codigo >= 400 || !is_array($json)) {
            throw new \RuntimeException('HTTP ' . $codigo . ': ' . mb_substr((string) $saida, 0, 300));
        }
        return $json;
    }
}
