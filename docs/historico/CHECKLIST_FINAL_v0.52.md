# Olho Cidadão — checklist de acabamento e publicação (v0.52.1)

> Base: v0.51 zerada. Mantido **somente** `admin@olhocidadao.com`, com o hash original da senha. Nenhuma conta de cidadão, denúncia ou fotografia de teste acompanha este pacote.

## 1. Implementado neste pacote

- [x] Mantida a identidade visual existente, sem refazer o layout completo.
- [x] Categoria: Buracos e vias (novidade provisória).
- [x] Categoria: Esgoto e drenagem (novidade provisória).
- [x] Categoria: Abastecimento de água (novidade provisória).
- [x] Categoria: Árvores e poda (novidade provisória).
- [x] Preservadas Iluminação, Sinalização, Lixo e Outros.
- [x] Validação das novas categorias no backend e no formulário.
- [x] Fluxo de **Rejeitada** com motivo obrigatório de pelo menos 10 caracteres, histórico e filtros no painel.
- [x] Cidadão pode complementar denúncia ativa (de 10 a 1.000 caracteres, até 10 complementos), sem apagar relato original.
- [x] Complemento fica na linha do tempo e exige autenticação, propriedade e token CSRF.
- [x] **1 foto opcional por denúncia** no envio inicial (até 5 MB). Campo de fotos adicionais removido na v0.52.1 para restaurar o formulário original.
- [x] Imagens novas armazenadas em `storage/uploads/` e servidas por rota que exige login e autorização (autor/admin).
- [x] Sem GD, fotos continuam sendo salvas **privadamente**, mas preservam o EXIF; com GD, o EXIF é removido automaticamente. Recomenda-se GD antes de uso público.
- [x] Mapa público continua visualmente semelhante, com coordenadas generalizadas para 2 casas decimais e sem fotos, endereço ou relato individual.
- [x] Consulta por protocolo deixa de exibir endereço exato.
- [x] Página de detalhes oculta foto/endereço/descrição exata dos outros usuários autenticados; autor e admin continuam vendo.
- [x] Mural público passa a mostrar resumos genéricos, sem imagem ou descrição fornecida pelo autor — até haver moderação.
- [x] Página pública de transparência agregada (`?rota=transparencia`), sem dados pessoais, acessível no mapa.
- [x] Paginação em "Minhas denúncias" (8 registros/página, preservando filtro).
- [x] Relatórios, CSV, Kanban, telão e indicadores compatíveis com todas as categorias e com o novo status.
- [x] Mini mapa do cidadão sem localização precisa de denúncias de terceiros.
- [x] Consulta pública de protocolo sem mensagens internas do histórico.
- [x] Backups diários passam a incluir arquivos de imagem, hashes SHA-256 e retenção local de 30 dias.
- [x] Ferramenta CLI para verificar integridade do backup.
- [x] Ferramenta CLI de verificação pré-publicação.
- [x] Gravação atômica de usuários, notificações, auditoria e denúncias (com locks de escrita).
- [x] Service worker atualizado para nova versão do cache e ignora anexos privados.
- [x] Testes isolados: regras de denúncia, login admin/cidadão, CSRF, rejeição, complementos, privacidade, transparência e backup (não foram incluídos dados fictícios no ZIP).

## 2. O que já existia na v0.51 e foi preservado

- [x] Sessão com inatividade, cookies HTTPOnly/SameSite e autenticação por papel.
- [x] Proteção CSRF nas operações de escrita, limite de tentativas de login.
- [x] Política de senha de 8 caracteres e rejeição de senhas muito comuns.
- [x] CPF cifrado e índice HMAC para checar duplicidade.
- [x] Histórico de status, fotos de resolução, apoio, confirmação do cidadão, relatório e auditoria.
- [x] Integridade HMAC do histórico, backups, logs, headers HTTP e PWA básico.
- [x] Administrador original exclusivo no banco JSON limpo.

## 3. Pendências reais antes do uso com a população

### Segurança e privacidade (alta prioridade)
- [ ] Ativar **HTTPS** no domínio real e forçar HTTP → HTTPS no servidor.
- [ ] Confirmar que **a raiz pública do host aponta para `public/`** ou que a proteção `.htaccess` funciona (nega `storage`, `app`, chaves, backups, logs, SQL, arquivos `.tmp`). Testar via URL no próprio domínio.
- [ ] Configurar SMTP e URL absoluta em `app/config.local.php` (não publique senhas).
- [ ] Implementar autenticação de dois fatores TOTP/2FA para o admin, com códigos de recuperação. **Não está implementado**.
- [ ] Verificação de e-mail para cidadãos, sem bloquear login existente na migração. **Não está implementado**.
- [ ] Planejar processo de moderação de fotos, endereço e texto antes de torná-los públicos.
- [ ] Nomear responsável pelo tratamento de dados e canal de privacidade (`LGPD_CONTATO`) e validar textos/consentimento e política de retenção com a instituição.
- [ ] Revisar proteção de CPF, e-mail, telefone e nomes em exportações, logs e relatórios.
- [ ] Revisar política de exclusão: denúncias e backups podem reter dados após exclusão da conta. Necessário definir retenção, anonimização e descarte seguro.
- [ ] Agendar varredura de segurança, testes de upload real e verificação de autorização para todos os papéis.

### Operação, qualidade, infraestrutura
- [ ] Executar o verificador na hospedagem: `php tools/verificar_instalacao.php`. Atender avisos.
- [ ] Configurar rotina **cron** diária para `php tools/backup_diario.php`, pois backup por acesso não garante cópia em dias sem acessos.
- [ ] Fazer cópia **externa criptografada** de JSON, uploads e **chaves privadas** (`storage/.cpf.key` e `storage/integridade.key`) em local separado; chaves precisam de proteção e cópia isolada.
- [ ] Exercitar recuperação integral num ambiente de testes; verificar integridade por `php tools/verificar_backup.php AAAA-MM-DD`.
- [ ] Confirmar extensões PHP **OpenSSL e fileinfo** e, preferencialmente, **GD**, limites `upload_max_filesize`, `post_max_size`, `max_file_uploads` e permissões de pastas.
- [ ] Testar em navegadores Android/iPhone, celulares pequenos, internet lenta e conexões intermitentes; mapa depende de terceiros.
- [ ] Testar acessibilidade por teclado, contraste, leitor de tela, tamanho de fonte e formulários.
- [ ] Revisar eventual PWA offline, expiração de caches, versões de scripts e dependências CDN.
- [ ] Criar monitoramento real de disponibilidade, espaço em disco, avisos por e-mail, exceções e restauração.
- [ ] Testes de carga e concorrência antes de tráfego elevado. JSON continua como armazenamento principal.
- [ ] Migração integral para MySQL com transações, índices, backup e rollback se houver previsão de grande volume. Os arquivos `mysql-pronto/` são material alternativo legado, **não** tornam esta versão MySQL por padrão.

### Regras de negócio e experiência
- [ ] Validar as 4 categorias novas e seus prazos/órgãos com a administração municipal (prazos no código são orientativos).
- [ ] Definir um SLA formal e fluxo de escalonamento quando o órgão não responder.
- [ ] Definir regra de retorno e recurso depois de uma rejeição (nesta versão rejeição é terminal).
- [ ] Moderar denúncias duplicadas, ofensivas, maliciosas ou com dados pessoais, com trilha de decisões.
- [ ] Revisar quem vê detalhes de ocorrências de terceiros; nesta versão esses detalhes foram restringidos.
- [ ] Adicionar 2FA/e-mail verificado/configurações administrativas com fluxo de recuperação seguro.
- [ ] Implementar cache de leitura/consultas ou banco relacional quando a carga exigir.
- [ ] Testar notificações por e-mail e eventual integração WhatsApp com consentimento.
- [ ] Definir como cidadão e prefeitura poderão responder, acompanhar recurso e complementar com documentos além de fotos.
- [ ] Criar testes automatizados com CI/CD, homologação, registro de versão e rollback de produção.
- [ ] Definir identidade institucional, equipe de suporte, horário de atendimento e canal de contato públicos.

## 4. Instalação segura — HostGator/XAMPP

1. Tire backup **externo** da instalação anterior; não sobrescreva `users.json` nem `denuncias.json` de um site já em uso sem decidir conscientemente zerar tudo.
2. Publique **somente esta versão** no diretório correto. Se hospedar na pasta raiz do projeto, confira regras Apache; o recomendado é expor somente `public/`.
3. Verifique se o único usuário inicial é `admin@olhocidadao.com`; sua **senha original da v0.50** foi preservada no hash, não foi alterada.
4. Mantenha `app/config.local.php`, `storage/.cpf.key` e `storage/integridade.key` fora de repositórios e inacessíveis publicamente. No primeiro acesso, novas chaves podem ser geradas; faça backup separado imediatamente.
5. Configure URL final, SMTP e contato de privacidade; ative HTTPS.
6. Confirme que `storage/` pode ser gravado pelo PHP e, para remover metadados, habilite GD (upload privado funciona sem ela).
7. Rode verificação de instalação e faça cadastro/denúncia/teste de foto **somente na homologação**, sem contaminar a base zerada de produção.
8. Programe backup externo diário e experimente restaurar os dados; não dependa apenas do backup local.
9. Teste login, cadastro, redefinição, registro, rejeição, foto, complementação, protocolo, mapa, transparência, exportação e encerramento pelo cidadão.
10. Valide privacidade, termos, suporte e autorização institucional antes de abrir o sistema ao público.

## 5. Observações honestas

- Nenhuma atualização substitui **teste na hospedagem real** nem auditoria formal de segurança/LGPD.
- Na v0.52.1, há apenas uma foto opcional. **Sem GD, a imagem mantém o EXIF original**, mas fica privada e só abre para autor/admin. O fluxo de upload sem GD foi testado em ambiente isolado.
- **2FA e confirmação de e-mail ainda não foram implementados.**
- **MySQL não está ativado**: esta versão continua rodando em JSON. O histórico de decisões e denúncias não foi convertido para banco relacional.
- **Conteúdos detalhados públicos foram restringidos por padrão** para evitar exposição indevida; liberar fotos/reports publicamente exige revisão institucional.
- A validação foi feita sobre cópias de testes; os registros fictícios **não** fazem parte do arquivo de entrega.
