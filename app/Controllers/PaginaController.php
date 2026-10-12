<?php
/** Páginas públicas institucionais — não exigem login. */
class PaginaController extends Controller {
    /**
     * /.well-known/security.txt (padrão RFC 9116): diz a quem achar uma falha
     * como avisar a equipe. Só aparece quando LGPD_CONTATO está preenchido.
     */
    public function securityTxt(): void {
        $contato = defined('LGPD_CONTATO') ? trim((string) LGPD_CONTATO) : '';
        if ($contato === '') { http_response_code(404); header('Content-Type: text/plain; charset=UTF-8'); echo "Contato de segurança não configurado.\n"; return; }
        if (filter_var($contato, FILTER_VALIDATE_EMAIL)) $contato = 'mailto:' . $contato;
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=86400');
        $linhas = [
            'Contact: ' . preg_replace('/[\r\n]+/', ' ', $contato),
            'Expires: ' . gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year')),
            'Preferred-Languages: pt-BR, en',
        ];
        if (defined('APP_URL') && APP_URL !== '') $linhas[] = 'Canonical: ' . rtrim((string) APP_URL, '/') . '/.well-known/security.txt';
        echo implode("\n", $linhas) . "\n";
    }

    public function sobre(): void {
        $this->view('paginas/sobre');
    }

    public function termos(): void {
        $this->view('paginas/termos');
    }

    /**
     * Mural de vitórias (v0.50): só os problemas já RESOLVIDOS (confirmados
     * pelo cidadão), com a foto de antes e a de depois. Público, sem login,
     * e sem nenhum nome de quem denunciou.
     */
    /** Indicadores públicos exclusivamente agregados, sem dados pessoais. */
    public function transparencia(): void {
        $model = new Denuncia;
        $itens = $model->todas();
        $status = ['Pendente' => 0, 'Em análise' => 0, 'Aguardando confirmação' => 0,
            'Resolvido' => 0, 'Rejeitada' => 0, 'Cancelada' => 0];
        $categorias = [];
        foreach ($itens as $d) {
            $s = (string) ($d['status'] ?? 'Pendente');
            if (isset($status[$s])) $status[$s]++;
            $c = (string) ($d['categoria'] ?? 'outros');
            $categorias[$c] = ($categorias[$c] ?? 0) + 1;
        }
        arsort($categorias);
        $total = count($itens);
        $resolvidas = $status['Resolvido'];
        $percentual = $model->estatisticas()['taxa_resolucao']; // mesma base usada no painel: exclui canceladas
        $atrasadas = count(array_filter($itens, fn($d) => Denuncia::statusPrazo($d)['atrasada']));
        $abertas = $status['Pendente'] + $status['Em análise'] + $status['Aguardando confirmação'];
        $this->view('paginas/transparencia', compact('status', 'categorias', 'total', 'resolvidas', 'percentual', 'atrasadas', 'abertas'));
    }

    public function vitorias(): void {
        $categorias = (string) ($_GET['categoria'] ?? '');
        $rotulos = ['iluminacao' => 'Iluminação', 'sinalizacao' => 'Sinalização', 'lixo' => 'Lixo', 'buracos' => 'Buracos e vias', 'esgoto' => 'Esgoto e drenagem', 'agua' => 'Abastecimento de água', 'arborizacao' => 'Árvores e poda', 'outros' => 'Outros'];
        if (!isset($rotulos[$categorias])) $categorias = '';

        $vitorias = [];
        $somaDias = 0;
        $somaNota = 0; $qtdNota = 0;
        $apoiosTotal = 0;
        foreach ((new Denuncia)->todas() as $d) {
            if (($d['status'] ?? '') !== 'Resolvido') continue;

            $resolvidaEm = null;
            $fotoDepois = (string) ($d['foto_resolucao'] ?? '');
            foreach (($d['historico'] ?? []) as $h) {
                if (($h['status'] ?? '') === 'Resolvido') $resolvidaEm = $h['data'] ?? null;
                if ($fotoDepois === '' && !empty($h['foto'])) $fotoDepois = Uploader::urlAtual((string) $h['foto']);
            }
            $resolvidaEm = $resolvidaEm ?: ($d['criada_em'] ?? date('Y-m-d H:i:s'));
            $dias = max(0, (int) round((strtotime($resolvidaEm) - strtotime($d['criada_em'] ?? $resolvidaEm)) / 86400));

            $somaDias += $dias;
            if (!empty($d['avaliacao'])) { $somaNota += (int) $d['avaliacao']; $qtdNota++; }
            $apoiosTotal += count($d['apoios'] ?? []);

            if ($categorias !== '' && ($d['categoria'] ?? '') !== $categorias) continue;

            // Só rua e bairro (as duas primeiras partes do endereço do mapa).
            $partes = array_map('trim', explode(',', (string) ($d['endereco'] ?? '')));
            $local = implode(', ', array_slice(array_filter($partes), 0, 2));

            $vitorias[] = [
                'protocolo' => Denuncia::protocolo($d),
                'categoria' => $d['categoria'] ?? 'outros',
                'descricao' => 'Ocorrência solucionada e confirmada pelo cidadão.',
                'local' => 'Município (localização não divulgada)',
                'antes' => '', // Exibição pública exige revisão de privacidade prévia.
                'depois' => '',
                'dias' => $dias,
                'resolvida_em' => $resolvidaEm,
                'nota' => !empty($d['avaliacao']) ? (int) $d['avaliacao'] : null,
                'apoios' => count($d['apoios'] ?? []),
            ];
        }

        // As que têm antes E depois aparecem primeiro; depois, as mais recentes.
        usort($vitorias, function ($a, $b) {
            $fa = ($a['antes'] !== '' && $a['depois'] !== '') ? 1 : 0;
            $fb = ($b['antes'] !== '' && $b['depois'] !== '') ? 1 : 0;
            return [$fb, $b['resolvida_em']] <=> [$fa, $a['resolvida_em']];
        });

        $total = 0;
        foreach ((new Denuncia)->todas() as $d) if (($d['status'] ?? '') === 'Resolvido') $total++;
        $resumo = [
            'total' => $total,
            'media_dias' => $total > 0 ? round($somaDias / $total, 1) : null,
            'nota' => $qtdNota > 0 ? round($somaNota / $qtdNota, 1) : null,
            'apoios' => $apoiosTotal,
        ];
        $logado = !empty($_SESSION['user_id']);

        $this->view('paginas/vitorias', compact('vitorias', 'resumo', 'rotulos', 'categorias', 'logado'));
    }
}
