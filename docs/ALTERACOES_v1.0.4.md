# Olho Cidadão — alterações da v1.0.4

Feita sobre a versão enviada em 11/10/2026 (que já trazia as melhorias de 2FA abaixo).

## 2FA (vindas da versão enviada, revisadas e testadas)
- **Gerar novos 8 códigos de recuperação** em Configurações (pede senha + código do app ou de recuperação; os antigos deixam de valer na hora). Mostra quantos códigos restam.
- Sem a chave `storage/.totp.key`, o login mostra uma mensagem clara em vez de erro 500, e os **códigos de recuperação continuam funcionando**.
- A chave da 2FA nunca é recriada por cima de segredos existentes; corrigido um erro 500 no MySQL quando a chave era criada dentro de outra transação.

## Novas nesta versão
- **Fuso horário certo**: o XAMPP vem com `Europe/Berlin`, o que adiantava datas de denúncias, prazos e logs em 5–6 h. Agora o padrão é `America/Porto_Velho` (Rondônia), ajustável em `APP_TIMEZONE`. Datas já gravadas antes continuam como estavam.
- **Proteção das pastas privadas que se conserta sozinha**: se o `.htaccess` de `storage/`, `storage/backups`, `storage/logs` ou `storage/uploads` sumir (ex.: cópia sem arquivos ocultos), ele é recriado na próxima visita e o Painel de segurança avisa.
- **Painel de segurança** avisa quando há admin com 2FA ativa mas a chave `.totp.key` não está na pasta.
- 4 testes automáticos novos para os códigos de recuperação (57 no total).
