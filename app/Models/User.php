<?php
class User {
    private string $file;
    public function __construct() {
        $this->file = __DIR__ . '/../../storage/users.json';
    }
    private function all(): array {
        $users = LeituraJson::array($this->file);
        foreach ($users as &$u) {
            if (!empty($u['foto']) && is_string($u['foto'])) $u['foto'] = Uploader::urlAtual($u['foto']);
        }
        unset($u);
        return $users;
    }
    private function saveAll(array $users): void {
        GravacaoSegura::json($this->file, $users);
    }
    public function findByEmail(string $email): ?array {
        foreach ($this->all() as $u) if (strtolower($u['email']) === strtolower($email)) return PrivacidadeCpf::paraAplicacao($u);
        return null;
    }
    public function find(int $id): ?array {
        foreach ($this->all() as $u) if ((int)$u['id'] === $id) return PrivacidadeCpf::paraAplicacao($u);
        return null;
    }
    /** Evita reutilizar IDs de cidadãos excluídos que ainda constam em denúncias/apoios. */
    private function proximoIdDisponivel(array $users): int {
        $maior = 0;
        foreach ($users as $u) $maior = max($maior, (int)($u['id'] ?? 0));
        foreach ((new Denuncia)->todas() as $d) {
            $maior = max($maior, (int)($d['usuario_id'] ?? 0));
            foreach (($d['apoios'] ?? []) as $uid) $maior = max($maior, (int)$uid);
        }
        return MySqlStore::ativo() ? max($maior + 1, MySqlStore::proximoId('users')) : $maior + 1;
    }

    public function create(array $data): array {
        return ArquivoLock::executar('users', function () use ($data) {
            $users = $this->all();
            foreach ($users as $existente) {
                if (strcasecmp((string)($existente['email'] ?? ''), trim((string)$data['email'])) === 0) {
                    throw new DomainException('Este e-mail já está cadastrado.');
                }
            }
            $user = [
                'id' => $this->proximoIdDisponivel($users),
                'nome' => trim($data['nome']),
                'email' => trim($data['email']),
                'senha' => PoliticaSenha::hash($data['senha']),
                'cidade' => '',
                'telefone' => '',
                'cpf_cifrado' => '',
                'cpf_hash' => '',
                'cep' => '',
                'foto' => '',
                'config' => [],
                'role' => $data['role'] ?? 'cidadao',
                'ativo' => true,
                'email_verificado' => !SegurancaConta::verificacaoHabilitada(),
                'auth_epoch' => 0,
                'criado_em' => date('Y-m-d H:i:s')
            ];
            if (!empty($data['cpf'])) PrivacidadeCpf::prepararRegistro($user, (string)$data['cpf']);
            $users[] = $user; $this->saveAll($users); return PrivacidadeCpf::paraAplicacao($user);
        });
    }
    public function update(int $id, array $data): ?array {
        return ArquivoLock::executar('users', function () use ($id, $data) {
            $users = $this->all();
            // A checagem precisa estar dentro do lock: duas abas não podem salvar
            // o mesmo e-mail/CPF simultaneamente depois de passar na validação da tela.
            if (isset($data['email'])) {
                foreach ($users as $outro) {
                    if ((int)($outro['id'] ?? 0) !== $id && strcasecmp((string)($outro['email'] ?? ''), (string)$data['email']) === 0) {
                        throw new DomainException('Esse e-mail já está sendo usado por outra conta.');
                    }
                }
            }
            if (isset($data['cpf']) && $data['cpf'] !== '') {
                $indiceCpf = PrivacidadeCpf::indice((string)$data['cpf']);
                foreach ($users as $outro) {
                    if ((int)($outro['id'] ?? 0) === $id) continue;
                    if ((!empty($outro['cpf_hash']) && hash_equals((string)$outro['cpf_hash'], $indiceCpf))
                        || (!empty($outro['cpf']) && PrivacidadeCpf::digitos((string)$outro['cpf']) === PrivacidadeCpf::digitos((string)$data['cpf']))) {
                        throw new DomainException('Esse CPF já está cadastrado em outra conta.');
                    }
                }
            }
            foreach ($users as &$u) {
                if ((int)$u['id'] !== $id) continue;
                if (isset($data['email']) && strcasecmp((string)($u['email']??''), (string)$data['email']) !== 0 && SegurancaConta::verificacaoHabilitada() && ($u['role']??'cidadao') !== 'admin') {
                    $u['email_verificado'] = false;
                    unset($u['email_token_hash'], $u['email_token_expira']);
                }
                foreach (['nome','email','cidade','telefone','cep','foto','config'] as $field)
                    if (array_key_exists($field,$data)) $u[$field] = $data[$field];
                if (array_key_exists('cpf', $data)) PrivacidadeCpf::prepararRegistro($u, (string)$data['cpf']);
                if (!empty($data['senha'])) {
                    $u['senha'] = PoliticaSenha::hash($data['senha']);
                    $u['auth_epoch'] = (int)($u['auth_epoch']??0) + 1;
                }
                $this->saveAll($users); return PrivacidadeCpf::paraAplicacao($u);
            }
            return null;
        });
    }

    /** Troca só o formato do hash (ex.: bcrypt → Argon2id) sem derrubar a sessão. */
    public function atualizarHashSenha(int $id, string $hashNovo): void {
        ArquivoLock::executar('users', function () use ($id, $hashNovo) {
            $users = $this->all();
            foreach ($users as &$u) {
                if ((int)$u['id'] !== $id) continue;
                $u['senha'] = $hashNovo;
                $this->saveAll($users);
                return;
            }
        });
    }

    /** Todos os usuários (usado só na tela de gestão de usuários do admin). */
    public function todos(): array {
        return array_map([PrivacidadeCpf::class, 'paraAplicacao'], $this->all());
    }

    /** Conta suspensa não consegue entrar. Contas antigas, sem o campo, contam como ativas. */
    public static function estaAtivo(?array $u): bool {
        return $u !== null && ($u['ativo'] ?? true) !== false;
    }

    /** Muda o papel ('cidadao' ou 'admin') ou liga/desliga a conta. Retorna false se o usuário não existir. */
    public function alterarAcesso(int $id, ?string $role = null, ?bool $ativo = null): bool {
        return ArquivoLock::executar('users', function () use ($id, $role, $ativo) {
            $users = $this->all();
            foreach ($users as &$u) {
                if ((int)$u['id'] !== $id) continue;
                if ($role !== null && in_array($role, ['cidadao', 'admin'], true) && $u['role'] !== $role) {
                    $u['role'] = $role;
                    $u['auth_epoch'] = (int)($u['auth_epoch']??0)+1;
                }
                if ($ativo !== null && ($u['ativo']??true) !== $ativo) {
                    $u['ativo'] = $ativo;
                    $u['auth_epoch'] = (int)($u['auth_epoch']??0)+1;
                }
                $this->saveAll($users);
                return true;
            }
            return false;
        });
    }

    /** Apaga a conta de vez (exclusão a pedido do titular). As denúncias continuam, mas sem identificar a pessoa. */
    public function excluir(int $id): void {
        ArquivoLock::executar('users', function () use ($id) {
            $users = array_values(array_filter($this->all(), fn($u) => (int)$u['id'] !== $id));
            $this->saveAll($users);
        });
    }

    /** Usada para notificar a administração quando um cidadão reabre uma denúncia. */
    public function admins(): array {
        return array_map([PrivacidadeCpf::class, 'paraAplicacao'],
            array_values(array_filter($this->all(), fn($u) => ($u['role'] ?? 'cidadao') === 'admin')));
    }

    /** Verifica se outra conta (id diferente de $excetoId) já usa esse e-mail. */
    public function emailEmUsoPorOutro(string $email, int $excetoId): bool {
        foreach ($this->all() as $u) {
            if ((int)$u['id'] !== $excetoId && strtolower($u['email']) === strtolower($email)) {
                return true;
            }
        }
        return false;
    }

    /** Verifica se outra conta (id diferente de $excetoId) já usa esse CPF (comparando só os dígitos). */
    public function cpfEmUsoPorOutro(string $cpf, int $excetoId): bool {
        $cpf = PrivacidadeCpf::digitos($cpf);
        if ($cpf === '') return false;
        $indice = PrivacidadeCpf::indice($cpf);
        foreach ($this->all() as $u) {
            if ((int)$u['id'] === $excetoId) continue;
            if (!empty($u['cpf_hash']) && hash_equals((string)$u['cpf_hash'], $indice)) return true;
            // Compatibilidade defensiva durante migração de versões antigas.
            if (!empty($u['cpf']) && PrivacidadeCpf::digitos((string)$u['cpf']) === $cpf) return true;
        }
        return false;
    }

    /** Gera e grava um token de redefinição de senha, válido por 1 hora. */
    public function gerarTokenReset(int $id): ?string {
        return ArquivoLock::executar('users', function () use ($id) {
            $users = $this->all();
            foreach ($users as &$u) {
                if ((int)$u['id'] !== $id) continue;
                $token = bin2hex(random_bytes(32));
                $u['reset_token'] = hash('sha256', $token);
                $u['reset_expira'] = time() + 3600;
                $this->saveAll($users);
                return $token;
            }
            return null;
        });
    }

    /** Retorna o usuário dono do token, se ele existir e ainda não tiver expirado. */
    public function findByTokenReset(string $token): ?array {
        $hash = hash('sha256', $token);
        foreach ($this->all() as $u) {
            if (!empty($u['reset_token']) && hash_equals((string)$u['reset_token'], $hash)) {
                if (($u['reset_expira'] ?? 0) < time()) return null;
                return $u;
            }
        }
        return null;
    }

    /** Define a nova senha do usuário e invalida o token de redefinição. */
    public function redefinirSenha(int $id, string $novaSenha): void {
        ArquivoLock::executar('users', function () use ($id, $novaSenha) {
            $users = $this->all();
            foreach ($users as &$u) {
                if ((int)$u['id'] !== $id) continue;
                $u['senha'] = PoliticaSenha::hash($novaSenha);
                $u['auth_epoch'] = (int)($u['auth_epoch']??0)+1;
                unset($u['reset_token'], $u['reset_expira']);
                $this->saveAll($users);
                return;
            }
        });
    }
}
