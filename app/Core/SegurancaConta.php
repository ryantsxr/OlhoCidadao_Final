<?php
/** Alterações críticas de autenticação: lock transacional de usuários. */
final class SegurancaConta {
    private static function modificar(int $id, callable $acao): bool {
        return ArquivoLock::executar('users', static function () use ($id, $acao): bool {
            $file=__DIR__.'/../../storage/users.json';
            $users=LeituraJson::array($file);
            foreach ($users as &$u) {
                if ((int)($u['id']??0) !== $id) continue;
                $alterou=$acao($u);
                if ($alterou) GravacaoSegura::json($file, $users);
                return $alterou;
            }
            return false;
        });
    }
    public static function verificacaoHabilitada(): bool {
        return defined('VERIFICACAO_EMAIL_ATIVA') && VERIFICACAO_EMAIL_ATIVA
            && Mailer::smtpConfigurado() && trim((string)APP_URL) !== ''
            && str_starts_with((string)APP_URL, 'https://');
    }
    public static function emailVerificado(array $u): bool {
        return ($u['role']??'cidadao') === 'admin' || ($u['email_verificado']??true) === true;
    }
    public static function emitirTokenEmail(int $id): ?string {
        $token=bin2hex(random_bytes(32));
        $salvou=self::modificar($id, static function (&$u) use ($token): bool {
            if (($u['role']??'cidadao')==='admin' || ($u['email_verificado']??true)===true) return false;
            $u['email_token_hash']=hash('sha256', $token);
            $u['email_token_expira']=time()+86400;
            return true;
        });
        return $salvou ? $token : null;
    }
    public static function confirmarEmail(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        return ArquivoLock::executar('users', static function () use ($token): bool {
            $file=__DIR__.'/../../storage/users.json';
            $users=LeituraJson::array($file);
            foreach ($users as &$u) {
                if (!hash_equals((string)($u['email_token_hash']??''), hash('sha256',$token))) continue;
                if ((int)($u['email_token_expira']??0) < time()) return false;
                $u['email_verificado']=true;
                unset($u['email_token_hash'], $u['email_token_expira']);
                GravacaoSegura::json($file,$users);
                return true;
            }
            return false;
        });
    }
    public static function enviarVerificacao(array $u): bool {
        if (!self::verificacaoHabilitada()) return false;
        $token=self::emitirTokenEmail((int)$u['id']);
        if (!$token) return false;
        $url=Mailer::url('/?rota=verificar-email&token='.$token);
        return Mailer::enviar((string)$u['email'], (string)$u['nome'], 'Confirme seu e-mail - Olho Cidadão',
            "Confirme o seu cadastro acessando o link (válido por 24 horas):\n{$url}\nSe não foi você, ignore.",
            Mailer::modelo('Confirmar e-mail', 'Olá!', ['Para utilizar sua conta, confirme o seu endereço de e-mail. O link expira em 24 horas.'], 'Confirmar meu e-mail', $url));
    }
    public static function doisFatoresAtivo(array $u): bool {
        return ($u['totp_ativo']??false)===true;
    }
    /** Gera oito códigos aleatórios de uso único. Só os hashes persistem. */
    private static function novosCodigosRecuperacao(): array {
        $codigos = [];
        for ($i = 0; $i < 8; $i++) $codigos[] = strtoupper(bin2hex(random_bytes(5)));
        return $codigos;
    }
    /** Total restante; lê a persistência privada sem expor hashes às views. */
    public static function contarCodigosRecuperacao(int $id): int {
        $usuarios = LeituraJson::array(__DIR__ . '/../../storage/users.json');
        foreach ($usuarios as $u) {
            if ((int)($u['id'] ?? 0) !== $id) continue;
            return self::doisFatoresAtivo($u) && is_array($u['totp_backups'] ?? null)
                ? count($u['totp_backups']) : 0;
        }
        return 0;
    }
    /** Somente o dono da conta, após validar senha e TOTP, ativa a proteção. */
    public static function ativar2fa(int $id, string $segredo): array {
        $codigos=self::novosCodigosRecuperacao();
        $cifrado=Totp::cifrar($segredo);
        $hashes=array_map(static fn($c)=>hash('sha256',$c), $codigos);
        $ok=self::modificar($id, static function (&$u) use ($cifrado,$hashes): bool {
            if (($u['role']??'cidadao') !== 'admin' || self::doisFatoresAtivo($u)) return false;
            $u['totp_segredo']=$cifrado;
            $u['totp_ativo']=true;
            $u['totp_backups']=$hashes;
            $u['totp_ultimo_passo']=-1;
            $u['auth_epoch']=(int)($u['auth_epoch']??0)+1;
            return true;
        });
        return $ok ? $codigos : [];
    }
    /** Validação e consumo sob o MESMO lock de users, inclusive no MySQL. */
    private static function consumirSegundoFator(array &$u, string $codigo): bool {
        if (!self::doisFatoresAtivo($u)) return false;
        if (preg_match('/^\d{6}$/D', $codigo)) {
            $segredo = Totp::decifrar((string)($u['totp_segredo'] ?? ''));
            $passo = Totp::passoValido($segredo, $codigo, (int)($u['totp_ultimo_passo'] ?? -1));
            if ($passo === null) return false;
            $u['totp_ultimo_passo'] = $passo;
            return true;
        }
        if (!preg_match('/^[A-F0-9]{10}$/D', $codigo)) return false;
        $hashDigitado = hash('sha256', $codigo);
        foreach (($u['totp_backups'] ?? []) as $i => $hash) {
            if (!hash_equals((string)$hash, $hashDigitado)) continue;
            unset($u['totp_backups'][$i]);
            $u['totp_backups'] = array_values($u['totp_backups']);
            return true;
        }
        return false;
    }
    public static function verificar2fa(int $id, string $codigo): bool {
        $codigo = strtoupper(trim(str_replace([' ', '-'], '', $codigo)));
        return self::modificar($id, static function (&$u) use ($codigo): bool {
            return self::consumirSegundoFator($u, $codigo);
        });
    }
    /**
     * Regenera sem limite de conjuntos ao longo do tempo. Uma única transação:
     * valida o fator, invalida TODOS os backups antigos e grava somente hashes.
     * Senha, CSRF e rate limit são verificados pelo controller ANTES de chamar.
     * Retorna [] sem alterar os códigos em caso de fator inválido.
     */
    public static function regenerarCodigos2fa(int $id, string $codigo): array {
        $codigo = strtoupper(trim(str_replace([' ', '-'], '', $codigo)));
        if (!preg_match('/^(?:\d{6}|[A-F0-9]{10})$/D', $codigo)) return [];
        $codigos = self::novosCodigosRecuperacao();
        $hashes = array_map(static fn($c) => hash('sha256', $c), $codigos);
        $ok = self::modificar($id, static function (&$u) use ($codigo, $hashes): bool {
            if (($u['role'] ?? '') !== 'admin' || !self::consumirSegundoFator($u, $codigo)) return false;
            $u['totp_backups'] = $hashes;
            // Revoga as outras sessões; o controlador mantém a sessão atual.
            $u['auth_epoch'] = (int)($u['auth_epoch'] ?? 0) + 1;
            return true;
        });
        return $ok ? $codigos : [];
    }
    public static function desativar2fa(int $id): bool {
        return self::modificar($id, static function (&$u): bool {
            if (!self::doisFatoresAtivo($u)) return false;
            unset($u['totp_segredo'],$u['totp_ativo'],$u['totp_backups'],$u['totp_ultimo_passo']);
            $u['auth_epoch']=(int)($u['auth_epoch']??0)+1;
            return true;
        });
    }

    /**
     * Aviso de segurança por e-mail para o dono da conta (novo acesso de admin,
     * senha trocada, 2FA ligada/desligada, e-mail alterado). Só envia com SMTP
     * configurado e nunca interrompe a ação. Também fica no log do servidor.
     */
    public static function alertar(array $u, string $evento, array $paragrafosExtras = []): void {
        $quando = date('d/m/Y \à\s H:i');
        $onde = Ambiente::navegador() . ' — IP ' . Ambiente::ip();
        error_log('[SEGURANCA] ' . $evento . ' | conta #' . (int)($u['id'] ?? 0) . ' | ' . $onde);
        Mailer::avisar($u, 'Aviso de segurança: ' . $evento, $evento, array_merge([
            "Registramos esta ação na sua conta do Olho Cidadão em {$quando}, a partir de: {$onde}.",
        ], $paragrafosExtras, [
            'Se foi você, pode ignorar este e-mail. Se NÃO foi você, troque sua senha imediatamente e avise a administração.',
        ]), 'Abrir o Olho Cidadão', '/?rota=login');
    }
}
