# Olho Cidadão — instalar no MySQL local (phpMyAdmin)

Guia da v1.0.3 para rodar no **MySQL local**. Não precisa de HostGator. O `phpMyAdmin` só administra o banco; os arquivos PHP continuam no servidor Apache (por exemplo, XAMPP).

## 1. Faça uma cópia de segurança

Se você já usa outra instalação, guarde a pasta `storage/` com as chaves e fotos em local privado antes de atualizar. **Não importe o SQL inicial sobre um banco em uso.** O SQL deste pacote inicial contém exclusivamente a conta `admin@olhocidadao.com` e nenhuma denúncia.

## 2. Criar e importar no phpMyAdmin

1. Instale/abra XAMPP (ou WAMP) e inicie **Apache** e **MySQL**.
2. Abra `http://localhost/phpmyadmin` no navegador.
3. Clique em **Novo**, crie o banco `olho_cidadao` e escolha `utf8mb4_unicode_ci`.
4. Selecione o banco criado e clique na aba **Importar**.
5. Selecione `sql/INSTALACAO_LOCAL_PRIVADA.sql` e clique em **Executar**.
6. Verifique que há uma conta em `users`, nenhuma em `denuncias`, e as tabelas de histórico, notificações e auditoria criadas.

**Importante:** esse arquivo SQL contém o hash da conta administrativa original. É privado. Nunca envie para o GitHub, grupos ou sites públicos.

## 3. Configurar conexão local

Se seu ambiente é XAMPP padrão com MySQL na porta 3306, `root` sem senha, o pacote já tem os valores padrão correspondentes. Caso o seu MySQL tenha senha, outra porta ou outro usuário, copie `app/config.local.exemplo.php` para `app/config.local.php` e atualize `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`.

Exemplo de configuração com senha (valores de exemplo):

```php
<?php
define('DB_DRIVER', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'olho_cidadao');
define('DB_USER', 'olho_app');
define('DB_PASS', 'SUA_SENHA_LOCAL');
define('DB_CHARSET', 'utf8mb4');
```

Para instalação mais segura, crie um usuário MySQL exclusivo para a aplicação: abra `sql/criar_usuario_mysql.sql`, **troque a senha**, rode no phpMyAdmin (aba SQL) e use `olho_app` + essa senha no `config.local.php`. Esse usuário só lê e grava linhas — não consegue apagar tabelas nem mexer em outros bancos. O usuário `root` sem senha é apenas um padrão de alguns ambientes locais, **não serve para disponibilizar o sistema na internet**.

### Extensões do PHP

No `C:\xampp\php\php.ini`, confira que estas linhas estão **sem** `;` na frente (e reinicie o Apache):

```
extension=gd
extension=pdo_mysql
extension=openssl
extension=fileinfo
extension=mbstring
```

Sem a **GD** as fotos continuam funcionando e perdem a localização GPS, mas não são reduzidas — com ela o resultado é melhor.

## 4. Abrir o site

1. Copie a pasta `OlhoCidadao_MVC` para `C:\xampp\htdocs\` (ou a pasta pública da sua instalação PHP).
2. Abra `http://localhost/OlhoCidadao_MVC/` (o `.htaccess` leva para `public/`).
3. Entre com o e-mail **`admin@olhocidadao.com`** e com **a senha original que você já utilizava**. Não foi criada uma nova senha.
4. Teste painel, Kanban, relatórios, cadastro de cidadão de teste, uma denúncia, status e notificações. Faça testes apenas numa cópia descartável se desejar manter a instalação inicial zerada.

## 5. Verificação e diagnóstico

Com PHP CLI do XAMPP configurado no PATH, na raiz do projeto execute:

```powershell
php tools/testar_mysql_v1.php
php tools/verificar_instalacao.php
```

Se aparecer **`could not find driver`**, habilite `pdo_mysql` no `php.ini` usado pelo Apache/CLI e reinicie o Apache. Se aparecer **`Connection refused`**, inicie o MySQL ou confira a porta em `DB_PORT`. Se aparecer **`Access denied`**, corrija usuário/senha. Se aparecer **`Unknown database`**, crie `olho_cidadao` e importe o SQL.

## 6. Migração de uma instalação JSON com dados reais (alternativa)

O SQL inicial desta edição é **para uma instalação zerada**. Se você tiver usuários e denúncias reais em outro computador, NÃO use a semente para substituí-los. Restaure a cópia privada `storage/` para uma área temporária, faça backup e use `tools/migrar_json_para_mysql_v1.php` ou `tools/gerar_importacao_sql_v1.php` em um banco MySQL novo e vazio, conforme o guia original do projeto. Depois teste a migração antes de ativá-la.

## Observações de segurança e limites

- Arquivos PDF, fotos e a chave privada de CPF/2FA ficam protegidos em `storage/`, **não dentro de tabelas públicas**.
- O modo JSON permanece disponível para recuperação de dados antigos, mas esta edição usa **`DB_DRIVER=mysql` por padrão**.
- A v1.0.3 foi testada em MariaDB 10.11 com PHP 8.3, tanto no servidor embutido do PHP quanto no Apache (igual ao XAMPP), inclusive com o usuário restrito `olho_app`.
- Para apresentar, dá para criar dados fictícios com `php tools/dados_demonstracao.php --confirmar` (só num banco vazio).
- Nunca publique o SQL inicial, backups, arquivos JSON com dados, `app/config.local.php` ou chaves privadas em repositórios GitHub.
- Uso real pela Prefeitura ainda requer homologação institucional, HTTPS, SMTP e backups copiados para fora do servidor (veja `docs/SEGURANCA.md`).
