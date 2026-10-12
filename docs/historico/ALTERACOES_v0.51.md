# Olho Cidadão v0.51 — segurança e privacidade

## Implantação sem perder dados

1. **Guarde uma cópia privada da v0.50**, especialmente `storage/*.json`, uploads e as chaves da integridade se já existirem. Não compartilhe ZIPs com dados reais.
2. Copie o projeto novo para a pasta de instalação. **Preserve seus arquivos atuais** `storage/*.json`, `storage/integridade.key` e `storage/integridade.json`, uploads e `app/config.local.php`. Não substitua os dados reais pelos exemplos que acompanham este arquivo compactado.
3. Assegure que o PHP possa **gravar em `storage/`, `storage/backups/` e `storage/logs/`**, e que a extensão **OpenSSL** esteja ativa. `mbstring` é recomendada; a aplicação inclui fallback limitado via `iconv` para hospedagens sem `mbstring`.
4. **Antes de tornar o site público**, use `php tools/migrar_cpfs.php`. Esse comando converte o CPF em cada registro de `storage/users.json` em `cpf_cifrado` e `cpf_hash` e cria `storage/.cpf.key`. Se não executar, a primeira requisição ao sistema realiza a migração automaticamente. O ZIP de atualização ainda contém registros antigos até a primeira migração: **não publique/compartilhe o ZIP como se fosse anonimizado**.
5. **Faça backup separado e protegido de `storage/.cpf.key`**. NUNCA coloque a chave em Git ou em pastas públicas. A chave não faz parte dos backups diários dos JSON; perder a chave torna impossível decifrar CPFs existentes. Se já existe uma chave válida na instalação, PRESERVE-A; uma nova chave não consegue ler os CPFs antigos.
6. Teste cadastro, login, atualizar CPF, verificar CPF já cadastrado, exportação de dados, recuperação de senha e acesso à tela de integridade.
7. Configure SMTP antes de publicar: para impedir recuperação indevida de contas, links de redefinição em modo teste agora só aparecem no `localhost`.
8. Use HTTPS e faça com que o **document root** aponte para `public/` sempre que possível. No Apache, verifique que `storage/` não pode ser acessada externamente. Em Nginx ou em outras hospedagens, as regras `.htaccess` NÃO se aplicam, portanto mantenha a pasta `storage/` fora do document root.

## Bloco 1: implementado

- `BackupDiario`: gera uma cópia de `storage/*.json` por dia, com hashes SHA-256 de cada arquivo em `manifesto.json`; limpa pastas de backup com mais de 30 dias. Os backups não são cifrados individualmente (ainda contêm nome/e-mail e demais informações); restrinja acesso e permissões. **Não incluem arquivos de chave** e não devem ser enviados para a web.
- `tools/backup_diario.php`: pode ser executado no cron/Agendador de Tarefas para fazer backup inclusive quando não há visitantes. Sem agendamento o backup é feito no primeiro acesso do dia, **não** em dias sem tráfego.
- Logs de erros PHP em `storage/logs/php-errors.log` (se gravável), erros ocultos para visitantes em produção, com detalhes registrados no servidor. Não use logs publicamente.
- Cabeçalhos: `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, CSP restrita a `frame-ancestors`, `base-uri`, `object-src`, `Cache-Control`, e HSTS nas respostas HTTPS em produção.
- Senhas novas entre 8 e 72 bytes; uma lista de senhas comuns e repetições previsíveis é bloqueada, inclusive em cadastro, alteração de perfil e redefinição. Contas legadas continuam entrando com senha antiga; ao redefinir, a senha nova precisa atender à política.
- Sessões autenticadas expiram após 30 minutos de inatividade. Personalize com `SESSION_IDLE_SECONDS` em `app/config.local.php`.

## Bloco 2: implementado

- `PrivacidadeCpf`: cifra com AES-256-GCM e gera HMAC-SHA256 distinto para verificar duplicidade sem revelar o CPF. Migração preserva IDs, senhas, denúncias e acesso ao perfil; o CPF decifrado só é montado em memória, não volta para `users.json`.
- Termos atualizados explicando coleta, finalidades, riscos, compartilhamentos, acesso, correção, exportação e exclusão.
- Canal de contato editável: `LGPD_CONTATO` em `app/config.local.php`.

## Limitações e próximos blocos

- Esta atualização **não implementa ainda** as novas categorias, Rejeitada, edição complementar, múltiplas fotos, paginação, painel de transparência, confirmação de e-mail, 2FA ou seus botões de Configurações.
- A cifra do CPF **não** cifra todo o `users.json`, nem fotos, denúncias, e-mails ou backups; cada um exige controle de acesso e política de retenção apropriada.
- A conformidade integral com a LGPD exige também avaliação jurídica, governança de acesso, resposta a incidentes e política real de retenção: o texto informativo do projeto não equivale, sozinho, à conformidade.

## Pacote v0.51 zerado — conta administrativa original

Na edição `ZERADO_ADMIN_ORIGINAL`, `storage/users.json` já contém **somente** `admin@olhocidadao.com` com o hash de senha original da v0.50. Não contém CPF ou outros dados dos usuários excluídos, e as denúncias começam vazias. Siga o arquivo `LIMPEZA_DADOS_v0.51.md` em vez das instruções acima de preservação de dados da edição de atualização. Atenção: um servidor antigo pode ter backups, anexos e bases MySQL adicionais que o upload deste ZIP não apaga automaticamente.
