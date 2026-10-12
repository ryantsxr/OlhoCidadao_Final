# Segurança do Olho Cidadão (v1.0.3)

Este documento lista cada proteção do sistema, o ataque que ela impede e onde está no código. O admin acompanha boa parte disso em **Painel → Segurança**.

## 1. Contas e login

| Proteção | Contra o quê | Onde |
|---|---|---|
| Senhas com **Argon2id** (bcrypt se o PHP não tiver Argon2); hashes antigos convertidos sozinhos no próximo login | Vazamento do banco revelar senhas | `app/Core/PoliticaSenha.php` |
| Senha de 8 a 72 caracteres, bloqueio de senhas comuns e repetidas | Senhas fáceis de adivinhar | `PoliticaSenha::erro()` |
| 5 erros (e-mail + IP) → bloqueio de 5 min; 20 erros por IP; 30 erros por conta em 30 min (qualquer IP) — esse último só barra IPs que já erraram, então ninguém consegue travar a conta do dono | Força bruta, inclusive distribuída, sem virar arma para bloquear contas | `AuthController::authenticate`, `LoginAttempt`, `RateLimiter` |
| Mesma mensagem e **mesmo tempo de resposta** exista ou não a conta; "esqueci a senha" não revela e-mails | Descobrir quem tem cadastro | `PoliticaSenha::verificar()` |
| "Computador local" decidido pelo endereço real da conexão (não pelo cabeçalho Host, que pode ser forjado) | Fingir ser localhost para escapar da 2FA obrigatória ou ver erros técnicos | `app/Core/Ambiente.php` |
| Sessão nova a cada login (`session_regenerate_id`), cookie `HttpOnly` + `SameSite=Lax` + `Secure` e prefixo `__Host-` em HTTPS | Roubo e fixação de sessão | `public/index.php` |
| Expira após 30 min sem uso; todas as sessões caem ao trocar senha, suspender a conta ou mudar o papel | Computador compartilhado, sessão roubada | `public/index.php`, `Controller::auth()` (`auth_epoch`) |
| **2FA** (TOTP com QR code, 8 códigos de recuperação de uso único, código não pode ser reutilizado) | Senha do admin vazada | `Totp.php`, `SegurancaConta.php` |
| 2FA **obrigatória para admin** em servidor (`ADMIN_2FA_OBRIGATORIO`): sem ela, o admin só acessa a tela de ativação | Admin sem segunda proteção | `Controller::auth()` |
| Senha atual exigida para trocar senha, e-mail, excluir conta e mexer na 2FA | Quem pegou uma sessão aberta | `PerfilController`, `ConfiguracaoController` |
| **Avisos por e-mail**: novo acesso ao painel admin, senha trocada/redefinida, e-mail alterado (aviso vai para o e-mail antigo), 2FA ligada/desligada | Invasão passar despercebida | `SegurancaConta::alertar()` |

## 2. Navegador

| Proteção | Contra o quê |
|---|---|
| **Content-Security-Policy com nonce**: só rodam scripts do próprio site, os com o nonce da página e os do VLibras. Nenhum `onclick`/`onerror` no HTML | XSS — mesmo se um texto malicioso escapar, ele não executa |
| `htmlspecialchars` em toda saída e `json_encode` com `JSON_HEX_*` dentro de scripts | XSS |
| Token **CSRF** em todo formulário POST e no link de sair | Site de terceiros agir em nome do usuário |
| `X-Frame-Options`, `frame-ancestors 'self'` | Clickjacking |
| `X-Content-Type-Options: nosniff`, `Referrer-Policy: origin`, `Permissions-Policy`, `Cross-Origin-Opener-Policy` | Vazamento de dados entre sites |
| HTTPS forçado e **HSTS** quando `APP_URL` começa com `https://` (com proxy/Cloudflare na frente, informe o IP dele em `PROXY_CONFIAVEL`) | Interceptação na rede |
| Páginas logadas com `Cache-Control: no-store`; o service worker nunca guarda páginas privadas | Dados ficarem no cache de um PC compartilhado |
| Bibliotecas servidas pelo próprio site (`public/vendor/`) | CDN invadido injetar código; sistema parar sem internet |

## 3. Fotos e arquivos

- Tipo real conferido (`finfo` + `getimagesize`), máximo 5 MB, **máximo 8000 × 8000 px / 24 megapixels** (bloqueia "bomba de imagem").
- Toda foto perde o EXIF/GPS (que costuma ter a localização da casa de quem fotografou). Com a extensão **GD** ela é redesenhada; sem a GD, os blocos de metadados e qualquer coisa colada depois da imagem são cortados do arquivo em puro PHP (`app/Core/LimpaMetadados.php`), mantendo só a orientação da foto.
- Fotos reduzidas para no máximo 2560 px, nome aleatório, guardadas em `storage/uploads` (fora da pasta pública) e entregues só ao dono e à administração.
- No mapa público as coordenadas são arredondadas (≈ 1 km) e o endereço exato não aparece para terceiros.

## 4. Servidor e dados

- `.htaccess` bloqueia `storage/`, `app/`, `sql/`, `tools/`, arquivos `.json/.sql/.key/.md/.log` e arquivos ocultos. Ideal: domínio apontando direto para `public/`.
- `php tools/verificar_instalacao.php https://seusite` testa pela internet se algum arquivo interno está exposto.
- Erros do PHP vão para `storage/logs` e não aparecem na tela fora do localhost; `X-Powered-By` removido.
- Banco: consultas preparadas (PDO, sem emulação) e **usuário próprio só com SELECT/INSERT/UPDATE/DELETE** (`sql/criar_usuario_mysql.sql`).
- **CPF criptografado** (AES-256-GCM) com índice HMAC para evitar duplicidade.
- **Histórico íntegro**: cada mudança recebe um selo HMAC encadeado; o admin vê qualquer alteração feita direto no banco.
- **Backup diário** (MySQL e fotos) conferido por SHA-256, mantido 30 dias, com restauração que guarda antes o estado atual. Se falhar, espera 1 hora para tentar de novo e aparece como crítico no Painel de segurança.
- **Anti-spam** por conta e por dia: 10 denúncias, 60 apoios, 20 complementos/recursos (configuráveis).

## 5. Privacidade (LGPD)

- O cidadão baixa todos os seus dados (JSON) e pode excluir a conta; as denúncias continuam, mas sem ligação com a pessoa.
- Denúncia anônima para os outros moradores; o assistente com IA remove e-mail, CPF e telefone antes de enviar a pergunta.
- Limpeza automática diária: registros de tentativas (que guardam IP) somem após 24 h, logs após `RETENCAO_LOGS_DIAS` (90), backups após 30 dias.
- `/.well-known/security.txt` publica o contato de segurança quando `LGPD_CONTATO` está preenchido.

## 6. Antes de colocar na internet

1. HTTPS com certificado (Let's Encrypt é gratuito) e `APP_URL` com `https://`.
2. Usuário MySQL próprio com senha forte.
3. SMTP configurado (recuperação de senha e avisos).
4. `LGPD_CONTATO` preenchido.
5. Domínio apontando para `public/`.
6. Cópia das chaves `storage/*.key` guardada fora do servidor.
7. `php tools/verificar_instalacao.php https://seusite` sem nenhum "ATENÇÃO".
8. Agendar `php tools/backup_diario.php` e copiar os backups para fora do servidor.

## Limites honestos

- Quem tiver acesso ao servidor (arquivos + chaves) consegue ler os dados — por isso as chaves devem ficar protegidas e com cópia fora dele.
- O widget VLibras é do Governo Federal e roda fora do nosso controle (está liberado só para os domínios dele na CSP).
- Proteção contra ataques de volume (DDoS) depende da hospedagem (ex.: Cloudflare).
- Um teste de invasão feito por outra pessoa sempre encontra coisas que quem escreveu o código não vê.
