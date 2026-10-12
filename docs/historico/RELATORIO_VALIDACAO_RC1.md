# Olho Cidadão — registro de validação do candidato v1.0.0-rc1

## Concluído neste pacote

- Revisão de pontos de persistência e inclusão de um driver configurável `json` / `mysql`.
- Modelos originais mantidos com interface de aplicação compatível; no modo MySQL há tabelas indexadas e atributos opcionais preservados em `payload`.
- Esquema SQL novo: usuários, denúncias, histórico, apoios, notificações, auditoria, rate limits e controle de concorrência.
- Migração CLI para banco vazio com transação, e exportador de SQL privado para phpMyAdmin.
- Ferramentas antigas de migração desativadas para evitar executar schema incompatível.
- Verificações de integridade de JSON e fluxo de acesso à conta administrativa.
- Suíte de regressão JSON que usa cópia temporária e não altera a base de dados incluída.
- Guia de implantação e rollback; README atualizado para GitHub.

## Testes executados

- **23/23** testes de regressão JSON isolados: cadastro, e-mail duplicado, denúncias, protocolo, apoios, rejeição, recurso, confirmação, notificações, auditoria e integridade.
- **9/9** rotas HTTP locais responderam com sucesso ou redirecionamento de autenticação esperado.
- **87 arquivos PHP** passaram na análise de sintaxe; **25 arquivos JavaScript** passaram em `node --check`.
- Importador CLI `--conferir` confirmou **1 administrador, 0 denúncias** no pacote inicial.
- Exportador SQL privado produziu arquivo de importação com cabeçalho/transação e cadastro administrativo, em teste local; o arquivo de teste foi apagado.

## Ainda não é possível marcar como homologado

- O PHP do ambiente de testes **não dispõe de `pdo_mysql`** e não havia servidor MySQL acessível. Portanto **não foi executado** `--importar`, nem foram validadas transações/consultas contra MySQL real.
- Sem credenciais da HostGator não foi possível medir desempenho, testar PHP/extensões/SMTP reais ou backup e restauração completos.
- Não foi concluída a avaliação visual em iPhone e Android físicos, leitores de tela ou teste de carga com muitos usuários.
- Não foram disponibilizados os arquivos da documentação acadêmica original para atualizá-los; atualizar somente depois de recebê-los.

## Condições para classificar v1.0 como estável

1. Importar em MySQL/MariaDB real e executar teste de leitura e fluxos completos (criação, alteração, exclusão, auditoria) sem divergências.
2. Realizar teste concorrente de denúncias/cadastros sob carga e otimizar rotinas que ainda leem coleções inteiras.
3. Testar backup externo e restauração de MySQL, anexos e chaves privadas.
4. Validar HTTPS, SMTP, autorização, privacidade do mapa, uploads com GD e navegação mobile na hospedagem.
5. Sincronizar documentação oficial da banca e preparar demo separada com dados fictícios.

Não declarar a v1.0 final até esses itens serem aprovados.
