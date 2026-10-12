<?php
/**
 * Painel de segurança do administrador: confere a configuração e a saúde do
 * sistema e devolve uma lista de itens com nível 'ok', 'atencao' ou 'critico'.
 * Nada aqui altera dados — é só leitura.
 */
final class PainelSeguranca {
    /** @return array{itens: array<int,array{nivel:string,titulo:string,detalhe:string,icone:string}>, resumo: array, tentativas: array} */
    public static function verificar(): array {
        $itens = [];
        $local = Ambiente::local();
        $add = static function (string $nivel, string $icone, string $titulo, string $detalhe) use (&$itens): void {
            $itens[] = compact('nivel', 'icone', 'titulo', 'detalhe');
        };

        // 1. HTTPS
        if (Ambiente::https()) {
            $add('ok', 'fa-lock', 'Conexão segura (HTTPS)', 'O site está sendo acessado por HTTPS' . (Ambiente::forcarHttps() ? ' e o http:// é redirecionado automaticamente.' : '.'));
        } elseif ($local) {
            $add('ok', 'fa-laptop-code', 'Rodando no computador local', 'HTTPS só é necessário quando o site for para um servidor. Lá, preencha APP_URL com https://.');
        } else {
            $add('critico', 'fa-lock-open', 'Site sem HTTPS', 'Senhas e dados trafegam sem criptografia. Instale um certificado (Let\'s Encrypt é gratuito) e defina APP_URL com https://.');
        }

        // 2. 2FA dos administradores
        $admins = (new User)->admins();
        $sem2fa = array_values(array_filter($admins, static fn($u) => !SegurancaConta::doisFatoresAtivo($u)));
        if (!$sem2fa) {
            $add('ok', 'fa-mobile-screen', 'Verificação em duas etapas', 'Todos os ' . count($admins) . ' administrador(es) usam 2FA.');
        } else {
            $nomes = implode(', ', array_map(static fn($u) => explode(' ', trim((string)($u['nome'] ?? 'Admin')))[0], $sem2fa));
            $add(Ambiente::admin2faObrigatorio() ? 'atencao' : ($local ? 'atencao' : 'critico'), 'fa-mobile-screen',
                count($sem2fa) . ' administrador(es) sem 2FA',
                'Sem 2FA: ' . $nomes . '. ' . (Ambiente::admin2faObrigatorio()
                    ? 'Eles serão obrigados a ativar no próximo acesso.'
                    : 'Ative em Configurações. Num servidor real a 2FA passa a ser obrigatória automaticamente.'));
        }

        // 3. Backup
        $pastas = array_values(array_filter(glob(BackupDiario::raiz() . '/*', GLOB_ONLYDIR) ?: [],
            static fn($p) => preg_match('/^\d{4}-\d{2}-\d{2}$/', basename($p))));
        rsort($pastas);
        $falha = BackupDiario::ultimaFalha();
        if ($falha !== null) {
            $add('critico', 'fa-box-archive', 'O último backup falhou', $falha . ' — veja storage/logs/php-errors.log e rode php tools/backup_diario.php.');
        } elseif (!$pastas) {
            $add('critico', 'fa-box-archive', 'Nenhum backup encontrado', 'Rode php tools/backup_diario.php ou confira as permissões da pasta storage/backups.');
        } else {
            $ultimo = $pastas[0];
            $problemas = BackupDiario::verificar($ultimo);
            $dias = (int) floor((strtotime(date('Y-m-d')) - strtotime(basename($ultimo))) / 86400);
            if ($problemas) {
                $add('critico', 'fa-box-archive', 'Backup de ' . date('d/m/Y', strtotime(basename($ultimo))) . ' com problema', implode(' ', $problemas));
            } elseif ($dias > 1) {
                $add('atencao', 'fa-box-archive', 'Último backup há ' . $dias . ' dias', 'O backup é feito na primeira visita do dia. Agende tools/backup_diario.php para não depender de visitas.');
            } else {
                $add('ok', 'fa-box-archive', 'Backup em dia', 'Último backup: ' . date('d/m/Y', strtotime(basename($ultimo))) . ', conferido por SHA-256. ' . count($pastas) . ' cópia(s) guardada(s) (' . BackupDiario::DIAS_RETENCAO . ' dias).');
            }
        }

        // 4. Integridade do histórico
        $todas = (new Denuncia)->todas();
        $integ = Integridade::verificar($todas);
        if ($integ['problemas']) {
            $add('critico', 'fa-link-slash', count($integ['problemas']) . ' denúncia(s) com sinal de alteração', 'Veja os detalhes em "Histórico íntegro".');
        } else {
            $add('ok', 'fa-link', 'Histórico das denúncias íntegro', (int) $integ['ok'] . ' denúncia(s) com a corrente de selos conferida.');
        }

        // 5. Tentativas de login
        $tent = (new LoginAttempt)->resumo();
        if ($tent['bloqueados_agora'] > 0 || $tent['falhas'] >= 50) {
            $add('atencao', 'fa-user-lock', $tent['falhas'] . ' senha(s) errada(s) nas últimas 24 h', $tent['bloqueados_agora'] . ' acesso(s) bloqueado(s) agora. Pode ser alguém tentando adivinhar senhas — veja a lista abaixo.');
        } else {
            $add('ok', 'fa-user-lock', 'Tentativas de login normais', $tent['falhas'] . ' senha(s) errada(s) nas últimas 24 h.');
        }

        // 6. Fotos sem metadados (GD)
        if (Uploader::gdDisponivel('image/jpeg') && Uploader::gdDisponivel('image/png')) {
            $add('ok', 'fa-image', 'Fotos sem localização escondida', 'A extensão GD está ativa: toda foto é redesenhada e perde o GPS/EXIF.' . (Uploader::gdDisponivel('image/webp') ? '' : ' (WEBP indisponível: só JPG e PNG serão aceitos.)'));
        } else {
            $add('atencao', 'fa-image', 'Extensão GD desligada', 'As fotos continuam sem GPS/EXIF (os metadados são cortados do arquivo), mas não são reduzidas nem redesenhadas. Para o melhor resultado, no XAMPP tire o ";" de ";extension=gd" no php.ini e reinicie o Apache.');
        }

        // 7. Banco de dados
        if (MySqlStore::ativo()) {
            if (DB_USER === 'root' || DB_PASS === '') {
                $add($local ? 'atencao' : 'critico', 'fa-database', 'Banco usando "root"' . (DB_PASS === '' ? ' sem senha' : ''),
                    'Crie um usuário só para o sistema com sql/criar_usuario_mysql.sql e coloque-o no config.local.php.');
            } else {
                $add('ok', 'fa-database', 'Banco com usuário próprio', 'Conectado como "' . DB_USER . '", com senha.');
            }
        } else {
            $add('atencao', 'fa-database', 'Modo JSON (sem MySQL)', 'Indicado só para testes. Use o MySQL em uso real.');
        }

        // 7b. CPFs que não podem ser lidos (chave trocada)
        $ilegiveis = count(array_filter((new User)->todos(), static fn($u) => !empty($u['cpf_ilegivel'])));
        if ($ilegiveis > 0) {
            $add('critico', 'fa-id-card', $ilegiveis . ' CPF(s) não podem ser lidos',
                'A chave storage/.cpf.key desta pasta não é a mesma usada quando os CPFs foram salvos (acontece ao trocar a pasta do sistema). Copie o arquivo .cpf.key da instalação antiga para storage/ e recarregue. Os dados continuam guardados, nada foi perdido.');
        }

        // 8. Chaves de criptografia
        $storage = realpath(__DIR__ . '/../../storage') ?: '';
        $chaves = ['.cpf.key' => 'CPF', '.totp.key' => '2FA', 'integridade.key' => 'selos'];
        $faltando = [];
        foreach ($chaves as $arq => $uso) if (!is_file($storage . '/' . $arq)) $faltando[] = $arq;
        $add('ok', 'fa-key', 'Chaves de criptografia', ($faltando
            ? 'Ainda não criadas: ' . implode(', ', $faltando) . ' (são geradas no primeiro uso). '
            : 'Todas presentes. ') . 'Guarde uma cópia FORA do servidor: sem elas, CPFs e 2FA não podem ser recuperados.');

        // 8b. Proteção das pastas privadas (recria na hora se tiver sumido)
        $storageDir = realpath(__DIR__ . '/../../storage') ?: (__DIR__ . '/../../storage');
        $recriados = Manutencao::protegerPastas($storageDir);
        if ($recriados) {
            $add('atencao', 'fa-lock', 'Proteção de pastas restaurada', 'O arquivo .htaccess estava faltando em: ' . implode(', ', $recriados) . '. Ele foi recriado agora. Ao copiar o sistema, inclua os arquivos ocultos.');
        }
        // 2FA: segredo salvo mas chave .totp.key ausente
        $totpKey = $storageDir . '/.totp.key';
        if (!is_file($totpKey) && array_filter($admins, static fn($u) => SegurancaConta::doisFatoresAtivo($u))) {
            $add('critico', 'fa-key', 'Chave da 2FA (.totp.key) não encontrada',
                'Há administrador com 2FA ativa, mas storage/.totp.key não está nesta pasta. Copie a chave da instalação original; sem ela só os códigos de recuperação funcionam.');
        }

        // 9. Pasta pública
        $publico = realpath(__DIR__ . '/../../public') ?: '';
        $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
        if ($docRoot !== '' && $docRoot === $publico) {
            $add('ok', 'fa-folder-closed', 'Só a pasta public/ fica na web', 'storage/, app/ e sql/ estão fora do alcance do navegador.');
        } else {
            $add($local ? 'ok' : 'atencao', 'fa-folder-closed', 'Arquivos internos protegidos pelo .htaccess',
                'Funciona no XAMPP. Numa hospedagem, o ideal é apontar o domínio direto para a pasta public/ (veja o README).');
        }

        // 10. E-mail
        if (Mailer::smtpConfigurado()) {
            $add('ok', 'fa-envelope', 'Avisos de segurança por e-mail', 'Ativos: novo acesso de admin, troca de senha, e-mail e 2FA geram aviso ao dono da conta.');
        } else {
            $add($local ? 'ok' : 'atencao', 'fa-envelope', 'E-mail (SMTP) não configurado', 'Sem SMTP não saem avisos de segurança nem links de recuperação de senha. Configure em app/config.local.php.');
        }

        // 11. Erros na tela
        if (!$local && ini_get('display_errors') && ini_get('display_errors') !== '0' && strtolower((string) ini_get('display_errors')) !== 'off') {
            $add('critico', 'fa-bug', 'Erros do PHP aparecem na tela', 'Isso revela caminhos e detalhes internos. Desligue display_errors.');
        } else {
            $add('ok', 'fa-bug', 'Erros técnicos escondidos do público', 'Erros vão para storage/logs (guardados por ' . (int) RETENCAO_LOGS_DIAS . ' dias).');
        }

        // 12. Versão do PHP
        $add(version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'critico', 'fa-code', 'PHP ' . PHP_VERSION,
            version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Versão com suporte. Mantenha o XAMPP/servidor sempre atualizado.' : 'Versão sem atualizações de segurança. Atualize para PHP 8.2 ou mais novo.');

        $resumo = ['ok' => 0, 'atencao' => 0, 'critico' => 0];
        foreach ($itens as $i) $resumo[$i['nivel']]++;
        // Críticos primeiro, depois atenção, depois ok.
        $ordem = ['critico' => 0, 'atencao' => 1, 'ok' => 2];
        usort($itens, static fn($a, $b) => $ordem[$a['nivel']] <=> $ordem[$b['nivel']]);
        return ['itens' => $itens, 'resumo' => $resumo, 'tentativas' => $tent];
    }

    /** Mostra só o começo do e-mail (ex.: ad***@olhocidadao.com). */
    public static function mascararEmail(string $email): string {
        [$nome, $dominio] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($nome, 0, 2) . str_repeat('*', max(3, mb_strlen($nome) - 2)) . ($dominio !== '' ? '@' . $dominio : '');
    }
}
