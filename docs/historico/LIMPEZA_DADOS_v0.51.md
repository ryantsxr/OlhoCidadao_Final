# Olho Cidadão v0.51 — pacote zerado

## Estado do ZIP

Este ZIP inicia com uma única conta administrativa (ID 1, e-mail
`admin@olhocidadao.com`), com a senha original dessa conta, vinda da v0.50,
preservada **em hash**. A conta de Ryan e os demais usuários foram removidos.
Nenhuma senha foi redefinida, divulgada ou substituída.

- `storage/users.json`: 1 administrador; nenhum cidadão
- `storage/denuncias.json`: `[]` (nenhuma denúncia, histórico, apoio ou foto vinculada)
- `storage/notificacoes.json`: `[]`
- `storage/auditoria.json`: `[]`
- `storage/login_attempts.json`: `{}`
- `storage/rate_limits.json`: `[]`
- `storage/backups/`: sem backups antigos (só proteção HTTP)
- `storage/logs/`: sem registros antigos (só proteção HTTP)
- `public/uploads/`: sem fotos antigas (só arquivos de proteção)
- `public/img/exemplos/`: removido, pois tinha fotos de demonstração
- `tools/gerar_dados_exemplo.php`: removido, para não recriar registros fictícios por engano
- Nenhuma chave privada, arquivo de sessão, arquivo de integridade ou cópia antiga no ZIP

O sistema continua aceitando novos cadastros e denúncias normalmente. Esta é uma limpeza
dos dados iniciais, **não** um bloqueio das funções de cadastro/denúncia. Os IDs futuros
são gerados a partir dos dados restantes: cidadão começa no ID 2; denúncia começa no ID 1.

## Instalação limpa

Extraia o ZIP numa pasta nova. O login é pela aba **Sou Admin** usando o e-mail da
conta `admin@olhocidadao.com` e a senha original dessa conta na v0.50. Para trocar a senha, entre no perfil
administrativo. Se não lembrar da senha, recupere-a antes de substituir a instalação.

## Para zerar uma instalação antiga em XAMPP / HostGator

**Atenção: operação destrutiva, sem recuperação.** Faça uma cópia privada do site
caso precise conservar dados reais por exigência legal ou operacional. Se houver
usuários reais e denúncias verdadeiras, apague apenas com a devida autorização.

1. Coloque o sistema em manutenção para evitar novos registros durante a limpeza.
2. No servidor, atualize os arquivos do código da v0.51.
3. **Substitua integralmente** estes seis arquivos pelo conteúdo do ZIP:
   `storage/users.json`, `storage/denuncias.json`, `storage/notificacoes.json`,
   `storage/auditoria.json`, `storage/login_attempts.json`, `storage/rate_limits.json`.
4. **Exclua também os arquivos antigos** dentro de `storage/backups/`,
   `storage/logs/` e `public/uploads/`, preservando os `.htaccess` existentes.
   Apague eventuais `storage/integridade.json`, `storage/integridade.key` e
   arquivos `.lock_*` **somente com o site parado**, sem requisições em execução.
   Apague `storage/.cpf.key` somente se não restar nenhum CPF cifrado a ler.
   Remova a pasta `public/img/exemplos/` e a ferramenta `tools/gerar_dados_exemplo.php`
   se ambas ainda existirem no servidor.
5. Confira se `storage/users.json` contém apenas `admin@olhocidadao.com`.
6. Teste login na aba **Sou Admin**, listagem de usuários, denúncias, auditoria,
   estatísticas, mapa e mural de vitórias (devem iniciar vazios).
7. Retire o modo manutenção e, se desejar, permita novos cadastros.

**Por que não basta enviar o novo ZIP por cima?** Upload/extração usualmente sobrescreve
arquivos com mesmo nome, mas NÃO apaga arquivos extras como backups antigos, anexos ou
chaves que existiam na instalação anterior. Eles precisam ser apagados separadamente.

**Banco MySQL:** a aplicação MVC nesta edição lê/grava os arquivos JSON do diretório
`storage/`. Se você também usou scripts do diretório `mysql-pronto/` ou importou dados
para MySQL em outra instalação, esses registros SQL **não são alterados** por este ZIP.
É necessário limpar esse banco separadamente caso contenha dados que você queira eliminar.

## Segurança

Não exponha `storage/` ao público; teste as proteções `.htaccess`. Não publique o ZIP
num repositório aberto enquanto ele contiver o hash de senha do administrador.
