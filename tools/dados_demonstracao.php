<?php
/**
 * Dados de DEMONSTRAÇÃO para apresentar o sistema (banca, feira, testes).
 *   php tools/dados_demonstracao.php               (só explica o que faria)
 *   php tools/dados_demonstracao.php --confirmar   (cria os dados)
 *
 * Cria 5 moradores fictícios (e-mails @exemplo.com) e 18 denúncias espalhadas
 * por Guajará-Mirim, em todos os status, com encaminhamentos, apoios,
 * complementos, rejeição com recurso e resoluções confirmadas — datas
 * distribuídas nos últimos meses para os gráficos ficarem interessantes.
 *
 * Por segurança só roda num banco SEM denúncias (instalação nova ou de teste).
 * Faz um backup antes. Nunca use em um sistema com dados reais.
 */
require __DIR__ . '/_bootstrap_cli.php';
if (!defined('BASE_URL')) define('BASE_URL', '');

$confirmar = in_array('--confirmar', $argv, true);
$den = new Denuncia;
$usuarios = new User;
if (count($den->todas()) > 0) {
    fwrite(STDERR, "Já existem denúncias neste banco. Os dados de demonstração só podem ser criados numa instalação vazia.\n");
    exit(1);
}
$admins = $usuarios->admins();
if (!$admins) { fwrite(STDERR, "Nenhum administrador encontrado.\n"); exit(1); }
$adminId = (int) $admins[0]['id'];

if (!$confirmar) {
    echo "Vai criar 5 moradores fictícios (senha Demo#Cidade2026) e 18 denúncias de exemplo.\n";
    echo "Rode de novo com --confirmar para criar. Use só em banco de teste/apresentação.\n";
    exit(0);
}

BackupDiario::criar(BackupDiario::pastaAntesRestauracao(), 'antes-demonstracao');

$senha = 'Demo#Cidade2026';
$pessoas = [];
foreach ([['Ana Souza', 'ana'], ['Bruno Lima', 'bruno'], ['Carla Mendes', 'carla'], ['Diego Rocha', 'diego'], ['Elisa Prado', 'elisa']] as [$nome, $login]) {
    $email = $login . '.demo@exemplo.com';
    $u = $usuarios->findByEmail($email) ?? $usuarios->create(['nome' => $nome, 'email' => $email, 'senha' => $senha]);
    $pessoas[] = (int) $u['id'];
}

// Centro de Guajará-Mirim ≈ -10.7889, -65.3296
$base = [-10.7889, -65.3296];
$roteiro = [
    // [pessoa, categoria, descrição, endereço, dLat, dLng, destino, órgão, dias atrás]
    [0, 'iluminacao', 'Poste apagado há uma semana, a rua fica toda escura à noite.', 'Av. 15 de Novembro, Centro', .002, .001, 'resolvido', 'iluminacao', 120],
    [1, 'buracos', 'Buraco grande no asfalto em frente à escola, carros desviando pela contramão.', 'Av. Costa Marques, Centro', -.003, .002, 'resolvido', 'obras', 105],
    [2, 'lixo', 'Lixo acumulado no terreno baldio da esquina, com mau cheiro e mosquitos.', 'Rua Marechal Deodoro, Serraria', .006, -.004, 'resolvido', 'limpeza', 95],
    [3, 'esgoto', 'Bueiro entupido; quando chove a água invade as calçadas.', 'Av. Dr. Mendonça Lima, Tamandaré', -.007, -.005, 'resolvido', 'obras', 80],
    [4, 'arborizacao', 'Galho grande quebrado em cima da fiação, risco de cair em quem passa.', 'Rua Bolívia, Centro', .001, .006, 'resolvido', 'limpeza', 70],
    [0, 'sinalizacao', 'Placa de PARE caída no cruzamento, motoristas não param.', 'Av. Princesa Isabel, Jardim das Esmeraldas', .009, .003, 'resolvido', 'transito', 60],
    [1, 'agua', 'Vazamento de água na calçada há vários dias, desperdício enorme.', 'Rua Benjamin Constant, 10 de Abril', -.004, -.008, 'aguardando', 'obras', 25],
    [2, 'iluminacao', 'Três postes seguidos apagados perto da praça.', 'Praça Mário Corrêa, Centro', .0005, -.0015, 'aguardando', 'iluminacao', 18],
    [3, 'buracos', 'Cratera na rua de terra depois das chuvas, moto quase caiu.', 'Rua Duque de Caxias, Próspero', .011, .007, 'analise', 'obras', 40],
    [4, 'lixo', 'Coleta de lixo não passa no bairro há duas semanas.', 'Rua 1º de Maio, Planalto', -.010, .004, 'analise', 'limpeza', 12],
    [0, 'sinalizacao', 'Faixa de pedestre apagada em frente ao hospital.', 'Av. Leopoldo de Matos, Centro', .003, -.006, 'analise', 'transito', 9],
    [1, 'esgoto', 'Esgoto a céu aberto correndo pela sarjeta.', 'Rua São Paulo, São José', -.012, -.002, 'analise', 'obras', 50],
    [2, 'iluminacao', 'Lâmpada do poste piscando a noite inteira.', 'Rua Rio Grande do Sul, Santa Luzia', .008, .009, 'pendente', null, 4],
    [3, 'arborizacao', 'Árvore inclinada na calçada, com raiz levantando o piso.', 'Av. Antônio Correia da Costa, Liberdade', -.002, .010, 'pendente', null, 2],
    [4, 'buracos', 'Calçada quebrada, cadeirante não consegue passar.', 'Rua Pedro Álvares Cabral, Centro', .004, .004, 'pendente', null, 1],
    [0, 'outros', 'Ponto de ônibus sem cobertura, pessoas tomando sol e chuva.', 'Av. 15 de Novembro, Centro', .0015, .0005, 'rejeitada_recurso', null, 30],
    [1, 'lixo', 'Descarte de entulho na beira do rio.', 'Orla do Rio Mamoré', -.001, -.011, 'rejeitada', null, 35],
    [2, 'agua', 'Falta de água no bairro todas as tardes.', 'Rua Mato Grosso, Triângulo', .013, -.007, 'cancelada', null, 20],
];

$ids = [];
foreach ($roteiro as $i => [$p, $cat, $desc, $end, $dLat, $dLng, $destino, $orgao, $diasAtras]) {
    $uid = $pessoas[$p];
    $d = $den->create($uid, $cat, $desc, '', round($base[0] + $dLat, 6), round($base[1] + $dLng, 6), $end, $i % 5 === 3);
    $id = (int) $d['id'];
    $ids[$id] = $diasAtras;
    // Apoios de outros moradores (as mais antigas recebem mais).
    $quantos = $destino === 'pendente' ? $i % 2 : min(4, 1 + intdiv($diasAtras, 30));
    foreach (array_slice(array_values(array_diff($pessoas, [$uid])), 0, $quantos) as $outro) $den->apoiar($id, $outro);

    switch ($destino) {
        case 'resolvido':
        case 'aguardando':
            $den->encaminhar($id, $orgao);
            if ($i % 3 === 0) $den->complementar($id, $uid, 'Hoje a situação piorou, por favor verifiquem logo.');
            $den->atualizarStatus($id, 'Aguardando confirmação', 'Equipe esteve no local e o problema foi resolvido.');
            if ($destino === 'resolvido') $den->confirmarResolucao($id, $uid, [5, 4, 5, 5, 4, 5][$i % 6]);
            break;
        case 'analise':
            $den->encaminhar($id, $orgao);
            if ($i % 2 === 0) $den->complementar($id, $uid, 'Mais vizinhos reclamaram do mesmo problema.');
            break;
        case 'rejeitada':
            $den->atualizarStatus($id, 'Rejeitada', 'Local fora da área urbana atendida pelo município; repassado ao órgão estadual.');
            break;
        case 'rejeitada_recurso':
            $den->atualizarStatus($id, 'Rejeitada', 'Pedido de obra nova, não é manutenção. Encaminhe pela ouvidoria.');
            $den->recorrerRejeicao($id, $uid, 'O ponto tinha cobertura e ela caiu; é manutenção, não obra nova.');
            break;
        case 'cancelada':
            $den->cancelar($id, $uid);
            break;
    }
    (new LogAuditoria)->registrar($adminId, (string) ($admins[0]['nome'] ?? 'Admin'), 'Dados de demonstração: denúncia ' . $id, $id);
}

// Espalha as datas: cada denúncia começa "X dias atrás" e cada passo do histórico
// acontece alguns dias depois do anterior. Depois sela tudo de novo (banco novo).
ArquivoLock::executar('denuncias', static function () use ($ids) {
    $arquivo = __DIR__ . '/../storage/denuncias.json';
    $todas = LeituraJson::array($arquivo);
    foreach ($todas as &$d) {
        $id = (int) $d['id'];
        if (!isset($ids[$id])) continue;
        $t = strtotime('-' . $ids[$id] . ' days 09:00') + ($id * 1373) % 30000;
        $d['criada_em'] = date('Y-m-d H:i:s', $t);
        foreach ($d['historico'] as $k => &$h) {
            if ($k > 0) $t = min(time() - 3600, $t + 86400 * (1 + ($id + $k) % 4) + 3600 * (($id * $k) % 7));
            $h['data'] = date('Y-m-d H:i:s', $t);
            unset($h['selo']);
            if (isset($h['orgao'])) {
                $d['encaminhada_em'] = $h['data'];
                $d['prazo_resposta'] = date('Y-m-d', $t + 86400 * (int) ($d['prazo_dias_encaminhamento'] ?? 7));
            }
        }
        unset($h);
        foreach (($d['complementos'] ?? []) as &$c) $c['data'] = $d['historico'][min(2, count($d['historico']) - 1)]['data'];
        unset($c);
        $d['vista_admin'] = !in_array($d['status'], ['Pendente'], true);
    }
    unset($d);
    Integridade::selar($todas, MySqlStore::ativo());
    GravacaoSegura::json($arquivo, $todas);
    if (!MySqlStore::ativo()) Integridade::selar($todas);
});

$r = Integridade::verificar($den->todas());
echo "Pronto: 5 moradores (senha {$senha}) e " . count($ids) . " denúncias de demonstração.\n";
echo 'Histórico íntegro: ' . (count($r['problemas']) === 0 ? 'sim' : 'NÃO (' . count($r['problemas']) . ')') . "\n";
echo "Exemplo de login de morador: ana.demo@exemplo.com / {$senha}\n";
