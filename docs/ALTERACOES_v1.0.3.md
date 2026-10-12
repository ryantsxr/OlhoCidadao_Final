# Olho Cidadão — alterações da v1.0.3

Versão focada em segurança, confiabilidade e acabamento, sobre a v1.0.2.

## Segurança
- **CSP com nonce**: o navegador só executa scripts do próprio site; removidos todos os `onclick`/`onerror` do HTML.
- **Bibliotecas locais** em `public/vendor/` (Leaflet, MarkerCluster, Leaflet.heat, Chart.js, Font Awesome e as fontes): sem dependência de CDN e funcionando sem internet.
- **2FA obrigatória para administradores** fora do localhost (`ADMIN_2FA_OBRIGATORIO`), com **QR code** para o app autenticador.
- Senhas em **Argon2id**, convertidas automaticamente no próximo login.
- Login com **tempo de resposta igual** exista ou não a conta e **limite por conta** (além do limite por IP), sem permitir que alguém trave a conta do dono.
- "Computador local" agora depende do endereço real da conexão, não do cabeçalho Host (que pode ser forjado); suporte a proxy confiável (`PROXY_CONFIAVEL`).
- **Avisos de segurança por e-mail**: novo acesso de admin, senha trocada/redefinida, e-mail alterado (aviso no e-mail antigo), 2FA ligada/desligada.
- **Anti-spam** por conta: 10 denúncias, 60 apoios e 20 complementos/recursos por dia (configurável).
- Fotos: **limite de resolução** (bloqueia "bomba de imagem"), redução para 2560 px com a GD e, **sem a GD**, remoção do GPS/EXIF em puro PHP — a foto nunca é salva com a localização.
- HTTPS forçado + HSTS quando `APP_URL` é `https://`; cookie `__Host-` em HTTPS; `Cross-Origin-Opener-Policy`; sem `X-Powered-By`.
- `.htaccess` também bloqueia `.key`, `.md`, arquivos ocultos e outros arquivos internos.
- `/.well-known/security.txt` com o contato de segurança.
- **Painel de segurança** para o admin (HTTPS, 2FA, backup, integridade, tentativas de login, GD, banco, chaves, e-mail, PHP).
- `sql/criar_usuario_mysql.sql`: usuário do banco só com SELECT/INSERT/UPDATE/DELETE.
- Chave de integridade criada com permissão 600 e sem condição de corrida.

## Dados e LGPD
- **Backup diário também no MySQL** (antes só no modo JSON), com fotos e âncoras de integridade, e **restauração** (`tools/restaurar_backup.php`), que antes salva o estado atual.
- **Faxina diária**: registros de tentativas (com IP) apagados após 24 h, logs após 90 dias, temporários esquecidos.

## Correções
- App instalável (PWA) voltou a funcionar: o manifesto agora é `manifest.webmanifest` (o `.htaccess` bloqueava `.json`).
- Requisições `HEAD` deixaram de dar 404.
- O VLibras não gera mais erro no console quando está sem internet.
- `.gitignore` ignorava `public/js/vendor` (three.js e QR code) — corrigido para `/vendor/`.
- Administrador forçado a ativar a 2FA cai direto na seção certa da tela.

## Organização
- Raiz do projeto só com o essencial; guias em `docs/` e anotações antigas em `docs/historico/`.
- Removidos `mysql-pronto/` e o migrador antigo desativado; SQLs antigos movidos para `sql/legado/`.
- Novos documentos: `docs/SEGURANCA.md`, `docs/DIAGRAMAS.md`, `docs/ROTEIRO_APRESENTACAO.md`.
- `tools/verificar_instalacao.php` mais completo (inclusive testa pela internet se arquivos internos vazam) e `tools/dados_demonstracao.php` para a apresentação.
