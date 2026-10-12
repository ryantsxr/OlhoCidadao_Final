<?php
class DashboardController extends Controller {
    public function index(): void {
        $id=$this->auth();
        $usuario=(new User)->find($id);
        $denuncias=(new Denuncia)->byUser($id);
        $stats=[
            'total'=>count($denuncias),
            'analise'=>count(array_filter($denuncias,fn($d)=>($d['status']??'')==='Em análise')),
            'resolvidas'=>count(array_filter($denuncias,fn($d)=>($d['status']??'')==='Resolvido'))
        ];

        $notificacaoModel = new Notificacao;
        $notificacoes = $notificacaoModel->porUsuario($id);
        $naoLidas = $notificacaoModel->contarNaoLidas($id);

        // ---- Cartão de impacto: o que as denúncias do cidadão já causaram
        $todas = (new Denuncia)->todas();
        $impacto = [
            'apoios_recebidos' => array_sum(array_map(fn($d) => count($d['apoios'] ?? []), $denuncias)),
            'resolvidos' => $stats['resolvidas'],
            'verificadas' => count(array_filter($denuncias, fn($d) => Denuncia::ehVerificada($d))),
            'apoios_dados' => count(array_filter($todas, fn($d) => in_array($id, $d['apoios'] ?? [], true))),
        ];

        // ---- Mini gráficos (últimos 6 meses) para os cartões de número
        $meses = [];
        for ($i = 5; $i >= 0; $i--) $meses[date('Y-m', strtotime("first day of -$i month"))] = 0;
        $serieCriadas = $meses; $serieAnalise = $meses; $serieResolvidas = $meses;
        foreach ($denuncias as $d) {
            $m = substr($d['criada_em'] ?? '', 0, 7);
            if (isset($serieCriadas[$m])) $serieCriadas[$m]++;
            foreach ($d['historico'] ?? [] as $h) {
                $mh = substr($h['data'] ?? '', 0, 7);
                if (!isset($meses[$mh])) continue;
                if (($h['status'] ?? '') === 'Em análise') $serieAnalise[$mh]++;
                if (($h['status'] ?? '') === 'Resolvido') $serieResolvidas[$mh]++;
            }
        }
        $series = ['total' => array_values($serieCriadas), 'analise' => array_values($serieAnalise), 'resolvidas' => array_values($serieResolvidas)];

        // ---- Linha do tempo de atividade (últimos acontecimentos nas denúncias do cidadão)
        $atividade = [];
        foreach ($denuncias as $d) {
            foreach ($d['historico'] ?? [] as $h) {
                $atividade[] = [
                    'data' => $h['data'] ?? '',
                    'status' => $h['status'] ?? '',
                    'mensagem' => $h['mensagem'] ?? '',
                    'categoria' => $d['categoria'] ?? 'outros',
                    'id' => (int) $d['id'],
                    'protocolo' => Denuncia::protocolo($d),
                ];
            }
        }
        usort($atividade, fn($a, $b) => strcmp($b['data'], $a['data']));
        $atividade = array_slice($atividade, 0, 6);

        // ---- Mini mapa "perto de você": denúncias ativas de todo mundo (sem dados pessoais)
        $pontosPerto = array_values(array_map(fn($d) => [
            'id' => (int) $d['id'],
            'categoria' => $d['categoria'] ?? 'outros',
            'status' => $d['status'] ?? '',
            'lat' => round((float) $d['latitude'], 2),
            'lng' => round((float) $d['longitude'], 2),
        ], array_filter($todas, fn($d) => !empty($d['latitude']) && !empty($d['longitude'])
            && !in_array(strtolower($d['status'] ?? ''), ['resolvido', 'cancelada', 'rejeitada'], true))));

        $this->view('dashboard/index',compact('usuario','denuncias','stats','notificacoes','naoLidas','impacto','series','atividade','pontosPerto'));
    }

    public function marcarNotificacoesLidas(): void {
        $id = $this->auth();
        $this->csrfCheck(BASE_URL . '/?rota=dashboard');
        (new Notificacao)->marcarTodasComoLidas($id);
        $this->redirect(BASE_URL . '/?rota=dashboard');
    }
}
