# 👁️ Olho Cidadão — v1.0.5

**Tecnologia a serviço da cidadania.** Sistema web em PHP (MVC) para o morador registrar e acompanhar problemas da cidade — poste apagado, buraco, lixo, esgoto — com protocolo, mapa, fotos, histórico e painel para a administração.

> Projeto acadêmico do curso técnico em Informática do **IFRO – Campus Guajará-Mirim**. Não é um serviço oficial da prefeitura.

## Equipe

- Enzo Ryan
- Marcus Vinicius
- Artur Gabriel
- Dercley Maciel
- Eric Valiante
- Gabriel Henrique

## Requisitos da entrega final

| Exigência | Onde está no sistema |
|---|---|
| **Cadastrar** | Cadastro de usuário (`?rota=cadastro`) e registro de denúncia em 3 passos (`?rota=denuncia-passo1`) |
| **Atualizar** | Cidadão: editar perfil e denúncia, complementar. Admin: situação, prioridade, setor, perfil e suspensão de usuários |
| **Apagar** | Cidadão: excluir a própria conta (Meu perfil). Admin: excluir denúncia (painel › "Excluir denúncia"), com confirmação e auditoria |
| **Buscar no banco e exibir** | Painel do cidadão e do admin, Minhas denúncias, mapa, estatísticas, Kanban, consulta por protocolo |
| **Arquivo SQL do banco** | Pasta [`sql/`](sql/) — veja [sql/LEIA-ME.md](sql/LEIA-ME.md) |

## O que o sistema faz

**Para o cidadão**
- Cadastro, login, recuperação de senha e (opcional) confirmação de e-mail.
- Denúncia em 3 passos: categoria → descrição, foto e local no mapa → protocolo.
- Acompanhar status, complementar, apoiar a denúncia de outro morador, recorrer de uma rejeição e confirmar quando foi resolvido.
- Denúncia anônima para os outros moradores, consulta pública por protocolo e relatório em PDF.
- Mascote **Olhinho** (3D) com assistente de dúvidas, ditado por voz, Mural de vitórias (antes/depois) e painel de transparência.
- Acessibilidade: tema claro/escuro, alto contraste, modo para daltonismo, fonte para leitura, VLibras e navegação por teclado.
- App instalável no celular (PWA).

**Para a administração**
- Painel com filtros, Kanban, encaminhamento por setor com prazo, prioridade, ações em massa e foto de comprovação.
- Estatísticas, mapa de calor, relatório geral, telão, auditoria e gestão de usuários.
- **Histórico íntegro** (selos encadeados, ideia de blockchain) e **Painel de segurança**.

## Tecnologias

PHP 8.1+ · MySQL/MariaDB (PDO) · HTML, CSS e JavaScript · Leaflet + OpenStreetMap · Chart.js · three.js · Font Awesome.
Todas as bibliotecas e fontes ficam em `public/vendor/` — **o sistema funciona sem internet** (só os ladrilhos do mapa e o VLibras precisam dela).

## Instalação rápida (XAMPP)

1. Copie a pasta para `C:\xampp\htdocs\OlhoCidadao` e inicie **Apache** e **MySQL**.
2. No `php.ini` do XAMPP confirme que estas linhas estão **sem** `;` na frente e reinicie o Apache:
   `extension=gd` · `extension=pdo_mysql` · `extension=openssl` · `extension=fileinfo` · `extension=mbstring`
3. No phpMyAdmin (ou no Workbench) crie o banco `olho_cidadao` (`utf8mb4_unicode_ci`) e importe `sql/schema.sql`.
4. Copie `app/config.local.exemplo.php` para `app/config.local.php` e preencha o usuário e a senha do MySQL (recomendado: crie um usuário próprio com `sql/criar_usuario_mysql.sql`, trocando a senha).
5. Crie o administrador no terminal, dentro da pasta do projeto: `php tools/criar_admin.php "Seu Nome" seu@email.com` (a senha é pedida na hora).
6. Abra `http://localhost/OlhoCidadao/` e entre com esse e-mail. (Opcional, só em banco de teste: `php tools/dados_demonstracao.php --confirmar` cria moradores e denúncias de exemplo.)
7. Confira tudo com `php tools/verificar_instalacao.php`.

Passo a passo detalhado: [docs/INSTALAR_MYSQL_LOCAL.md](docs/INSTALAR_MYSQL_LOCAL.md) · Hospedagem: [docs/GUIA_MIGRACAO_HOSPEDAGEM.md](docs/GUIA_MIGRACAO_HOSPEDAGEM.md)

## Segurança

Resumo do que protege o sistema (detalhes em [docs/SEGURANCA.md](docs/SEGURANCA.md)):

- Senhas com **Argon2id**, política de senha e bloqueio de senhas comuns.
- Login com limite por IP **e** por conta, resposta igual exista ou não o e-mail, sessão que expira por inatividade.
- **Verificação em duas etapas** (app autenticador + QR code + códigos de recuperação), **obrigatória para administradores** quando o sistema está num servidor.
- **CSP com nonce**: o navegador só executa scripts do próprio site; proteção contra CSRF e XSS em todos os formulários.
- Fotos redesenhadas sem GPS/EXIF, com limite de tamanho e de resolução, guardadas fora da pasta pública.
- CPF criptografado, histórico com selos, **backup diário (MySQL + fotos) com restauração**, limpeza automática de registros antigos (LGPD).
- Anti-spam: limites diários por conta para denúncias, apoios e complementos.
- Avisos por e-mail de novo acesso de admin, troca de senha, troca de e-mail e 2FA.
- Painel de segurança para o admin conferir tudo em um só lugar.

## Ferramentas (terminal)

| Comando | Para quê |
|---|---|
| `php tools/criar_admin.php "Nome" email` | Cria a conta de administrador numa instalação nova |
| `php tools/verificar_instalacao.php [URL]` | Confere PHP, extensões, pastas, chaves, banco, backup — e, com a URL, se arquivos internos estão bloqueados na web |
| `php tools/backup_diario.php` | Faz o backup agora (dá para agendar no Agendador de Tarefas do Windows) |
| `php tools/verificar_backup.php todos` | Confere os backups por SHA-256 |
| `php tools/restaurar_backup.php AAAA-MM-DD --confirmar` | Restaura um backup (antes guarda uma cópia do estado atual) |
| `php tools/dados_demonstracao.php --confirmar` | Cria dados de exemplo para apresentar (só use num banco de teste) |
| `php tools/testar_mysql_v1.php` · `php tools/testar_email.php` | Testam a conexão MySQL e o envio de e-mail |
| `php tests/smoke_json.php` | 57 testes automáticos numa cópia temporária |

## Configuração (`app/config.local.php`)

Tudo tem um valor padrão seguro. Para colocar no ar, preencha: `APP_URL` (com `https://`), SMTP (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`), `LGPD_CONTATO` (aparece nos Termos e em `/.well-known/security.txt`) e o usuário do banco. Fuso horário: `APP_TIMEZONE` (padrão America/Porto_Velho). Opções de segurança: `ADMIN_2FA_OBRIGATORIO`, `FORCAR_HTTPS`, `PROXY_CONFIAVEL`, `LIMITE_DENUNCIAS_DIA`, `LIMITE_APOIOS_DIA`, `LIMITE_COMPLEMENTOS_DIA`, `RETENCAO_LOGS_DIAS`, `SESSION_IDLE_SECONDS`.

## Nunca publique

`app/config.local.php`, `sql/INSTALACAO_LOCAL_PRIVADA.sql`, `storage/` (dados, fotos, backups, logs) e as chaves `storage/.cpf.key`, `storage/.totp.key`, `storage/integridade.key`. Guarde uma cópia das chaves fora do servidor: sem elas os CPFs e a 2FA não podem ser recuperados. O `.gitignore` já cobre esses arquivos, mas não remove o que já foi enviado ao Git.

## Documentação

- [docs/SEGURANCA.md](docs/SEGURANCA.md) — todas as proteções, explicadas
- [docs/DIAGRAMAS.md](docs/DIAGRAMAS.md) — casos de uso, MVC, banco de dados e fluxo da denúncia
- [docs/ROTEIRO_APRESENTACAO.md](docs/ROTEIRO_APRESENTACAO.md) — roteiro de 5 minutos para a banca
- [docs/ALTERACOES_v1.0.5.md](docs/ALTERACOES_v1.0.5.md), [v1.0.4](docs/ALTERACOES_v1.0.4.md) e [v1.0.3](docs/ALTERACOES_v1.0.3.md) — o que mudou
- `docs/historico/` — anotações das versões anteriores
