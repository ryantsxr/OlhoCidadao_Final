# Migração e publicação do Olho Cidadão — v1.0.3

> **Ler antes de publicar.** A troca de armazenamento é reversível apenas enquanto a cópia JSON original permanecer intacta. **Não importar dados reais em repositório público**. O banco MySQL ainda precisa ser testado com a hospedagem real.

## 1. Prepare o ambiente

- Faça backup completo do site atual, incluindo `storage/`, fotos, `.cpf.key`, `.totp.key`, `integridade.key` e `integridade.json`, preservando as chaves em local **separado, privado e seguro**. Não publique nenhuma dessas cópias.
- Atualize o código com o **ZIP de atualização sem dados**, mantendo `storage/` do site atual.
- Mantenha `DB_DRIVER=json` e teste login, denuncia, mapa, relatório, recurso e fotos.
- No cPanel HostGator, crie um **banco novo vazio** e usuário MySQL dedicado só com SELECT, INSERT, UPDATE e DELETE (veja `sql/criar_usuario_mysql.sql`). Não use a conta root.
- Aponte o domínio (DocumentRoot) para a pasta `public/`.
- No phpMyAdmin, selecione o novo banco e importe **`sql/schema.sql`**. Não utilize o schema antigo da pasta `sql/legado/`.

## 2A. Migrar com terminal/SSH (preferencial quando disponível)

Deixe `DB_DRIVER=json` durante a importação e informe credenciais MySQL em `app/config.local.php`:

```php
<?php
// DB_DRIVER continua em 'json' até acabar a migração.
define('DB_HOST', 'localhost');
define('DB_NAME', 'SEU_PREFIXO_olho');
define('DB_USER', 'SEU_PREFIXO_app');
define('DB_PASS', 'SENHA_FORTE_PRIVADA');
define('DB_CHARSET', 'utf8mb4');
```

Execute a partir da pasta raiz do projeto:

```bash
php tools/migrar_json_para_mysql_v1.php --conferir
php tools/migrar_json_para_mysql_v1.php --importar
```

- O importador **recusa banco com dados** e usa uma transação para não deixar importação parcial.
- Verifique se o número de usuários/denúncias bate com os arquivos originais.
- Nunca rode o migrador antigo (foi desativado).

## 2B. Migrar pelo phpMyAdmin sem SSH

Execute no seu computador, numa cópia privada e com PHP instalado:

```bash
php tools/gerar_importacao_sql_v1.php --arquivo=C:/backup_privado/olho_importacao.sql
```

No Linux/macOS use um caminho absoluto equivalente. **A pasta de destino precisa existir.** O SQL contém os dados pessoais, hashes e histórico em formato hexadecimal reversível — **não está criptografado**.

No phpMyAdmin do **novo banco**, depois de importar `sql/schema.sql`, importe o SQL privado gerado. Confira as contagens e **apague com segurança o arquivo de importação**; nunca o adicione ao GitHub. Leve para a hospedagem as fotos privadas em `storage/uploads/` e preserve as chaves criptográficas existentes.

## 3. Ativar MySQL após a importação

No **mesmo** `app/config.local.php` onde já ficaram as credenciais, acrescente:

```php
// Adicione esta linha APENAS depois da migração completa:
define('DB_DRIVER', 'mysql');
```

Certifique-se de que PHP tenha a extensão `pdo_mysql`. Rode no terminal, quando disponível:

```bash
php tools/testar_mysql_v1.php
php tools/verificar_instalacao.php
```

Teste **manualmente** cadastro, login original `admin@olhocidadao.com` (mesma senha), 2FA se ativado, denúncia com/sem foto, recurso, status, notificações, PDF/impressão, Kanban, relatórios, CSV, mapa e transparência. A instalação inicial limpa deve ter **1 administrador e 0 denúncias**.

## 4. Backup e recuperação em MySQL — obrigatório antes de receber dados reais

Desde a v1.0.3 o backup diário também funciona no MySQL: na primeira visita do dia o sistema exporta usuários, denúncias, notificações, auditoria, âncoras de integridade e as fotos para `storage/backups/AAAA-MM-DD/`, com SHA-256 de cada arquivo, e guarda 30 dias.

```bash
php tools/backup_diario.php                       # faz agora (agende no cron / Agendador de Tarefas)
php tools/verificar_backup.php todos              # confere
php tools/restaurar_backup.php 2026-10-08         # mostra o que seria restaurado
php tools/restaurar_backup.php 2026-10-08 --confirmar
```

Mesmo assim, **copie os backups para fora do servidor** (armazenamento externo criptografado), guarde separadas as chaves de CPF/2FA/integridade e **teste uma restauração em ambiente isolado** antes de receber dados reais.

Não restaure uma base mais antiga sobre uma instalação ativa sem analisar os dados surgidos desde o backup. Não coloque backups nem chaves na pasta pública.

## 5. Voltar temporariamente ao JSON (rollback)

1. Faça backup/snapshot **também do MySQL recém-alterado** antes de qualquer reversão.
2. Restaure a cópia **consistente** de `storage/` obtida antes da migração.
3. Volte `DB_DRIVER` para `json` (ou remova a definição do arquivo local).
4. Teste o administrador, login, denúncias e anexos.

**Importante:** registros criados no MySQL **não aparecem automaticamente** no JSON restaurado. Não faça rollback se houve atividade real sem um plano de reconciliação.

## 6. Checklist de homologação antes de mostrar à Prefeitura

- [ ] PHP `pdo_mysql`, OpenSSL, fileinfo, GD (metadados das fotos) habilitados.
- [ ] HTTPS, domínio e `APP_URL` corretos.
- [ ] SMTP real configurado; confirmação por e-mail e recuperação testadas.
- [ ] 2FA do administrador testado com códigos de recuperação guardados.
- [ ] Permissões Apache e documentos privados inacessíveis pela web.
- [ ] Rotas e telas em Android e iPhone; acessibilidade/daltonismo; PWA; mapa online.
- [ ] Banco MySQL funcionando com muitos acessos e nenhuma denúncia perdida.
- [ ] Backup MySQL+fotos+chaves e restauração testados.
- [ ] Termos LGPD e responsabilidades institucionais avaliados.
- [ ] Base de demonstração com dados **fictícios** (`php tools/dados_demonstracao.php --confirmar`), distinta da instalação inicial limpa.
- [ ] `php tools/verificar_instalacao.php https://seu-dominio` sem nenhum "ATENÇÃO" e Painel → Segurança sem itens críticos.
- [ ] Relatório acadêmico e documentação institucional revisados a partir dos arquivos originais da equipe.

> Esta versão não substitui uma homologação oficial nem uma certificação LGPD.
