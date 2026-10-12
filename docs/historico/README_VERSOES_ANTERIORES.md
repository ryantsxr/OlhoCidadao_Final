# Correção da versão v0.52.1 — fotos no formulário

A etapa 2 da denúncia voltou a ter **uma única foto opcional**, como na v0.51. O envio não exige GD; as imagens continuam privadas. Sem a extensão GD, não é possível remover automaticamente metadados EXIF da imagem enviada. Leia `AJUSTE_FOTOS_v0.52.1.md`.

---

# PACOTE INICIAL LIMPO — v0.51

**Antes de instalar sobre um site existente:** leia `LIMPEZA_DADOS_v0.51.md`.
**Conta mantida:** somente o admin principal (ID 1), com senha hash existente preservada.
**Dados:** zero cidadãos, zero denúncias, zero notificações, zero registros de auditoria e zero anexos.

---

# Olho Cidadão — MVC

**Versão: v0.51**

Projeto reorganizado no padrão **MVC (Model, View, Controller)** em PHP.

## Novidades da v0.51 — blocos 1 e 2

Segurança, backup diário automático, logs privados, senhas mínimas de 8 caracteres,
sessão com expiração por inatividade, CPF cifrado e termos de privacidade atualizados.
**Leia `ALTERACOES_v0.51.md` antes de publicar ou substituir dados de uma instalação.**

## Novidades da v0.50

- **Olhinho, o mascote 3D** (no lugar do olho, no painel da esquerda dos 3 passos da denúncia):
  boneco de moletom verde com um olho só, feito com Three.js (arquivo local em
  `public/js/vendor/`, não precisa de internet). Ele respira, pisca, segue o mouse com o olho,
  acena e **reage ao formulário**: olha para a descrição enquanto a pessoa digita, comemora
  quando ela anexa a foto ou marca o local, avisa quando já existe denúncia parecida e
  comemora no passo 3. Parado por um tempo, ele dá dicas no balão. Sem WebGL, aparece a
  imagem dele (`img/mascote.png`). Com "reduzir animações" ligado no sistema, ele fica parado.
  Os textos das dicas ficam no começo de `app/Views/denuncia/_lado-esquerdo.php`.
- **Assistente Olhinho** (clicar no mascote, no botão "Tirar dúvida" ou no botão redondo dos
  painéis do cidadão): responde dúvidas com respostas prontas (como denunciar, status, prazos,
  anônimo, cancelar, editar, reabrir, senha, privacidade, emergência…), **consulta protocolos**
  ("como está o OC-2026-000012?"), resume "minhas denúncias" e, se a pessoa descreve um
  problema, sugere a categoria. Funciona sem internet e sem custo. **IA opcional**: com uma
  chave de API no `config.local.php` (Claude ou OpenAI/compatível), ele também responde perguntas
  fora do roteiro; a chave fica só no servidor e e-mail/CPF/telefone são tirados da pergunta
  antes de enviar. Respostas prontas: `app/Core/Assistente.php` (é só editar a lista).
- **Denúncia por voz**: botão "Falar" ao lado da descrição (passo 2 e editar), usando o
  reconhecimento de voz do navegador (Chrome/Edge; no Chrome o áudio é convertido por um serviço
  do Google, o sistema só recebe o texto). Só funciona em `localhost` ou `https`.
- **Mural de vitórias** (`?rota=vitorias`, público): os problemas resolvidos com o **antes e
  depois** lado a lado (barrinha de arrastar), dias até resolver, nota e botão de compartilhar
  (WhatsApp/Instagram no celular, copiar link no PC). Link no login, no painel e na consulta.
- **Histórico à prova de alteração** ("blockchain" simplificado): cada linha nova do histórico
  recebe um selo HMAC-SHA256 encadeado ao anterior, e uma âncora por denúncia fica em
  `storage/integridade.json`. A tela **Histórico íntegro** do admin confere tudo e aponta se
  alguém mudou ou apagou algo direto no arquivo; a consulta pública mostra o selo "Histórico
  íntegro". Denúncias antigas ganham o selo na próxima gravação do sistema.
- **Dados de exemplo com fotos**: `php tools/gerar_dados_exemplo.php` agora coloca fotos
  ilustrativas de antes e depois (marcadas como "imagem ilustrativa") nas denúncias resolvidas
  e aguardando confirmação, para o mural ter o que mostrar na apresentação.

## Ajuste da v0.49.6

- Painel do cidadão no celular: os botões de tour (?) e de notificações (sino) ficavam empilhados no
  meio da tela. Agora ficam na mesma linha do botão "Fazer denúncia", todos da mesma altura.

## Ajuste da v0.49.5

- Painel da esquerda dos 3 passos da denúncia deixou de ficar vazio: além do logo, mostra a lista
  dos passos (o atual destacado e os concluídos com ✓), dicas do passo e um aviso de privacidade.
  O painel fica fixo enquanto a tela rola. No celular volta a ser só o logo, para não alongar a tela.

## Ajuste da v0.49.4

- Passo 2 da denúncia mais compacto (a tela estava comprida demais): descrição e foto lado a lado,
  regras numa faixa fina no topo, mapa mais baixo ao lado do botão "Usar minha localização" e o
  botão Enviar na mesma linha da opção de denúncia anônima. No celular continua em uma coluna.

## Ajuste da v0.49.3

- Removidos os botões de Google, Apple e Facebook (e o "ou continue com") das telas de login e
  cadastro: eram só enfeite, sem função, e login social não é viável no projeto.

## Ajuste da v0.49.2

- Nas telas de login, cadastro, recuperar e redefinir senha, erros e confirmações agora aparecem
  dentro do formulário (logo abaixo do título) e ficam na tela, em vez de um aviso que some no
  canto. Corrigido também o botão de fechar dos avisos, que o estilo do login esticava e deixava
  o texto espremido.

## Correção da v0.49.1

- O mapa escuro usava o servidor da CARTO, que passou a exigir chave de API (aparecia "API KEY
  REQUIRED"). Agora o estilo escuro é o mapa comum do OpenStreetMap com as cores invertidas por
  CSS — não depende de chave nenhuma.

## Novidades da v0.49 (o restante da lista de melhorias)

**Base visual (todas as telas):** fonte Sora nos títulos; vidro translúcido nos cartões (tema
escuro); cartões que inclinam em 3D; textura sutil de ruído; rolagem, seleção de texto e dicas
(tooltips) na cor da marca; escala fixa de cantos e espaços; verde reservado à ação principal;
transição suave entre páginas; reações ao toque (onda nos botões, pulso ao apoiar); tela de
abertura quando o app está instalado; contador de notificações no ícone da aba; puxar para
atualizar no celular; menu lateral recolhível; rodapé com nome, versão e links (preencha
`APP_EQUIPE` em `app/config.php` para aparecer "Feito por ...").

**Acessibilidade:** status com ícone além da cor; nas Configurações, "Cores para daltonismo"
(paleta Okabe-Ito) e "Fonte de leitura fácil" (Atkinson Hyperlegible, indicada para dislexia).

**Painel do cidadão:** saudação pelo horário com sol/lua; cartão "Seu impacto na cidade";
mini gráficos nos cartões de número; etapas (Recebida → Em análise → Resolvida) em cada
denúncia; mini mapa "Perto de você" com GPS; notificações numa gaveta lateral agrupadas por
Hoje/Ontem/Esta semana; tour no primeiro acesso (botão "?" refaz); dica do dia e linha do tempo
de atividade.

**Formulário:** barra de progresso com %; pino fixo no centro do mapa (arraste o mapa); rótulos
que sobem e ícone de validação no login/cadastro; transição deslizante entre passos; revisão
final antes de enviar; contador de caracteres que muda de cor; atalhos de categoria do painel já
abrem o passo 1 com a categoria escolhida.

**Mapa público:** estilos escuro, ruas e satélite; pulso nas denúncias novas (3 dias) ou
urgentes; botão "onde estou" com radar; no celular, detalhes sobem de baixo ao tocar num ponto;
passar o mouse na lista destaca o ponto; deslizar um item da lista para a direita abre a
denúncia no botão de apoiar.

**Detalhe e relatório:** linha do tempo com datas, foto de antes e depois lado a lado, ações em
botões redondos (apoiar, mapa, compartilhar, copiar protocolo, editar, relatório) e relatório
com cara de documento oficial (faixa, QR do protocolo, assinaturas e aviso de autenticidade).

**Admin:** busca rápida com Ctrl+K (ou "/"); barra flutuante de ações em massa na lista;
comparação com o mês anterior (▲/▼) e calendário de atividade nas estatísticas; modo telão
(`?rota=admin-telao`) com números grandes que se atualizam a cada 30 s.

**Telas restantes:** Perfil, Consultar protocolo, Sobre e Termos no estilo de cartões; páginas
404/500 com o olho que segue o mouse; estados vazios com ilustração e textos mais leves
("O olho não achou nada por aqui").

## Novidades da v0.48

- **Olho que segue o mouse** no logo das telas de login, cadastro e recuperar senha (pisca de
  vez em quando e "olha" para o campo que recebe o foco). Usa `img/logo_olho.png` e
  `img/logo_white_olho.png` (o logo sem a pupila; a pupila é desenhada por cima).
- **Olho piscando como carregamento**: aparece ao enviar formulários e trocar de página, se
  demorar mais que um instante; o botão mostra "Enviando...".
- **Cartões com luz que segue o mouse** e borda em degradê no hover.
- **Foto da denúncia com arrastar e soltar**, prévia, trocar e remover (confere formato e
  tamanho antes de enviar).
- **Protocolo em formato de ingresso** no passo 3, com QR code (abre a consulta do protocolo),
  botão de copiar e confete na primeira denúncia do cidadão. O QR é gerado no navegador por
  `js/vendor/qrcode.js` (MIT), sem depender de internet.
- **Barra fixa embaixo no celular**: Início, Minhas, + Denunciar, Mapa e Perfil (cidadão);
  Painel, Números, Quadro, Mapa e Usuários (admin).
- **Mapa público**: filtro de período (7, 30, 90 dias ou tudo) e "Ver a evolução no tempo",
  com o mapa de calor se espalhando dia a dia.
- **Quadro kanban do admin** (`?rota=admin-kanban`): colunas por status, arrastar o cartão muda
  o status e avisa o cidadão; no celular, use o botão "⋯" do cartão. "Resolvido" continua
  sendo só pelo cidadão.

## Novidades da v0.47

- **Mapa público**: balão novo com foto (ou ícone grande da categoria quando não há foto),
  status em pílula e botão de detalhes; lista lateral das denúncias sincronizada com os
  filtros (clicar leva até o ponto); no celular a lista abre como gaveta pelo botão "Lista";
  estados vazios com botão "Mostrar tudo".
- **Avisos no canto da tela** (sucesso, erro, aviso, info) com ícone, botão de fechar e barra
  de tempo, em todas as telas — inclusive no lugar dos alertas do navegador no passo 2.
- **Animações leves**: cartões entrando com fade, números das estatísticas contando até o
  valor e botões levantando ao passar o mouse. Desligam sozinhas se o sistema pedir
  "reduzir movimento".
- **Tema claro revisado em todas as telas**: login, cadastro, recuperar senha, Sobre, Termos,
  404/500 e passos 2 e 3 ganharam versão clara; o logo troca para a versão de texto escuro;
  o mapa, a consulta e as páginas públicas agora respeitam o tema escolhido.
- Foco visível ao navegar pelo teclado e ajustes de layout no celular (cartões, login, fotos).
- Arquivos novos: `public/css/acabamento.css` e `public/js/acabamento.js` (carregados pelo `_pwa.php`).

## Novidades da v0.46

- **Pacote zerado (v0.51)**: a antiga ferramenta de criação de dados de demonstração foi
  removida. Não há denúncias de exemplo nem usuários fictícios neste pacote.
- **Mapa público com filtros e agrupamento**: filtros por categoria e status, contador
  "X de Y", pontos próximos agrupados (dá para desligar) e mapa de calor respeitando os filtros.
- **Aviso de denúncia parecida**: ao marcar o local no passo 2, o sistema procura denúncias
  ativas da mesma categoria a até 100 m e oferece apoiar a existente.
- **Órgão responsável e prazo**: o admin encaminha a denúncia para uma secretaria
  (lista em `Denuncia::ORGAOS`, fácil de editar). O cidadão vê "Encaminhada para a Secretaria
  de Obras, prazo de 15 dias" com barra de progresso, na tela da denúncia, em "Minhas
  denúncias", na consulta por protocolo, no relatório e no CSV.
- Esses campos novos (órgão, prazo) ainda não estão nos Models de `mysql-pronto/`.

## Estrutura

- `app/Controllers/` — regras de controle e fluxo das páginas
- `app/Models/` — acesso e manipulação dos dados
- `app/Views/` — HTML das telas
- `app/Core/` — classes base e roteador
- `public/` — ponto de entrada, CSS, JavaScript, imagens e uploads
- `storage/` — arquivos JSON usados como armazenamento (por enquanto)
- `sql/`, `tools/`, `app/config.php`, `app/Core/Database.php` — preparados para migrar para
  MySQL quando for a hora; não estão em uso ainda (veja "Migrar para MySQL" abaixo)

## Como executar no XAMPP/Apache

1. Mantenha a pasta `OlhoCidadao_MVC` dentro de `htdocs/OlhoCidadao`.
2. Confirme que o Apache está ligado.
3. Acesse:

   `http://localhost/OlhoCidadao/OlhoCidadao_MVC/`

O `.htaccess` da raiz encaminha tudo para a pasta `public/`, e `storage/`, `app/`, `sql/`, `tools/` e `mysql-pronto/` têm um `.htaccess` próprio que bloqueia o acesso pelo navegador.

## Migrar para MySQL (quando quiser)

O projeto já vem com o necessário pronto, só não está ligado ainda:

1. Importe `sql/schema.sql` no phpMyAdmin (aba SQL).
2. Confira as credenciais em `app/config.php`.
3. Rode `php tools/migrar_json_para_mysql.php` uma vez para copiar os dados dos JSON para o banco.
4. Troque os três arquivos em `app/Models/` (User, Denuncia, LoginAttempt) pela versão que usa
   `Database::getConnection()` em vez de ler/escrever os arquivos JSON.
5. O `public/index.php` já carrega o `app/config.php` — não precisa mexer nele.

## Importante

Não é necessário acessar diretamente `public/index.php`. O projeto pode ser acessado pela pasta raiz.

O sistema também pode ser executado com o servidor embutido do PHP:

```bash
php -S localhost:8000 -t public
```

Nesse caso, acesse `http://localhost:8000/`.

## Antes de vender / colocar no ar de verdade

- Na edição limpa da v0.51, `storage/users.json` já mantém exclusivamente uma conta de admin,
  e todos os demais arquivos de dados estão zerados. **Não apague `users.json`: isso impediria
  o login administrativo.** Se for atualizar uma instalação que já existe no servidor, confira
  o guia `LIMPEZA_DADOS_v0.51.md` para remover arquivos antigos que não são eliminados
  automaticamente ao enviar o ZIP.
- Confirme que o servidor final está apontando pra dentro da pasta `public/` (ou, se não
  estiver, que o `.htaccess` da raiz está mesmo ativo — teste abrindo
  `SEUSITE.com/app/Models/User.php` no navegador: tem que dar "Forbidden", nunca mostrar
  código ou baixar o arquivo).
- Apague o arquivo `robots.txt` (ou edite ele) quando quiser que o Google comece a indexar o
  site — hoje ele está configurado pra bloquear todos os buscadores de propósito.
- O site já esconde os erros do PHP automaticamente fora do `localhost` — não precisa mexer
  em nada pra isso.
- Ative HTTPS no domínio final (a maioria dos provedores de hospedagem oferece isso grátis
  via Let's Encrypt) — o cookie de sessão já está pronto pra usar isso automaticamente assim
  que detectar HTTPS.
- Configure o envio de e-mail de verdade (veja "Configurar envio de e-mail (SMTP)" abaixo) —
  sem isso, o link de redefinição de senha só aparece na tela (modo de teste), nunca chega
  na caixa de entrada do cidadão.

## Configurar envio de e-mail (SMTP)

Hoje o "Esqueci minha senha" funciona em **modo de teste**: como não há SMTP configurado, o
link de redefinição aparece direto na tela em vez de ser mandado por e-mail. Pra ligar o
envio de verdade:

1. Instale o PHPMailer (uma vez só, precisa de internet e do Composer instalado):

   ```bash
   composer require phpmailer/phpmailer
   ```

2. Abra `app/config.php` e preencha as constantes `SMTP_*` com as credenciais do seu
   provedor (Gmail, Mailgun, Resend, SendGrid, ou o SMTP da própria hospedagem). O arquivo já
   tem exemplos comentados.

3. Pronto — a partir daí, `Mailer::enviar()` (usado em `AuthController::enviarLinkReset()`)
   passa a mandar o e-mail de verdade por SMTP autenticado. Se o `vendor/` não existir ainda
   ou o `SMTP_HOST` estiver vazio, o sistema continua funcionando no modo de teste de antes —
   nada quebra se você não fizer isso agora.

## O que mudou na v0.45

- Nas listas de denúncias (cidadão e admin), foto, mapa e relatório PDF viraram botões redondos só com ícone (o nome aparece ao passar o mouse).

## O que mudou na v0.44 (cadastro)

- Tela de cadastro redesenhada: campos com ícone, botão de mostrar/ocultar senha, medidor de força da senha, aviso ao vivo de "senhas conferem", termos em linha própria, passos "Como funciona" no lado esquerdo e atalhos no rodapé (mesmo padrão do login). Os nomes dos campos e a validação não mudaram.

## O que mudou na v0.43 (estatísticas e listas)

- Admin > Estatísticas: gráficos de verdade com Chart.js (rosca de situação e de prioridade, barras por categoria, linha dos últimos 6 meses), cartões coloridos e o mapa de calor. As cores seguem o tema claro/escuro.
- "Minhas denúncias" e a lista do admin: cartão com borda na cor do status, ícone colorido por categoria e links de foto, mapa e relatório PDF em formato de botão.

## O que mudou na v0.42 (login)

- Tela de login mais compacta (cabe em telas menores sem rolar): lado esquerdo com 3 destaques (seguro/anônimo, protocolo, mapa) e lado direito com atalhos em cartões (Mapa público, Consultar protocolo, Sobre).

## O que mudou na v0.41 (painel admin)

- Formulário de status de cada denúncia em 2 colunas: status e prioridade lado a lado, e a foto de comprovação em um cartão ao lado da mensagem (mostra o nome da imagem escolhida). Em telas menores o formulário ocupa a linha inteira.

## O que mudou na v0.40 (configurações e passos)

- Configurações: o painel ocupa a largura da tela e ganhou uma pré-visualização ao lado; tema, fonte e contraste agora aparecem na hora (continua precisando salvar para valer).
- Passos da denúncia: botão "Voltar" no passo 1 (para o painel) e no passo 2 (para o passo 1).

## O que mudou na v0.39 (painel do cidadão)

- Painel redesenhado: cartões de estatística com cor própria, categorias clicáveis (já abrem o passo 1 com a categoria escolhida), lista de denúncias recentes com ícone por categoria, protocolo, data, miniatura da foto e link para os detalhes (mostra as 5 últimas + "Ver todas") e estado vazio com botão.
- Estilos novos em `public/css/dashboard.css`, escopados em `.dash` para não afetar outras páginas.

## O que mudou na v0.38 (PWA + mapa de calor)

- **PWA**: `public/manifest.json`, `public/sw.js`, `public/offline.html` e ícones em `public/img/`. O parcial `app/Views/_pwa.php` entra no `<head>` de todas as páginas. No celular, o navegador oferece "Instalar app" / "Adicionar à tela inicial". Só funciona em **HTTPS ou localhost**. Para forçar atualização do cache, aumente `VERSAO` no `sw.js`.
- **Mapa de calor**: botão "Pontos / Mapa de calor" no mapa público e um mapa de calor novo em Admin > Estatísticas (biblioteca leaflet.heat). Apoios aumentam o peso do ponto.

## O que mudou na v0.37 (e-mail pelo Gmail)

- **Recuperação de senha por e-mail:** o link agora é completo (com o endereço do site), em e-mail bonito (HTML + texto).
  Antes o link ia como `/?rota=...`, que não abre dentro de um e-mail.
- **Avisos por e-mail:** denúncia registrada (com o protocolo), status alterado pela prefeitura, boas-vindas no cadastro
  e "sua senha foi alterada" (aviso de segurança). Só saem se o SMTP estiver configurado e nunca travam a página.
- **Sem Composer:** `app/Core/Mailer.php` tem um cliente SMTP próprio (Gmail, porta 587/TLS ou 465/SSL).
  Se o PHPMailer estiver instalado, ele continua sendo usado.
- **Senha fora do código:** as credenciais ficam em `app/config.local.php` (privado, no `.gitignore`).

### Como ligar o Gmail (5 minutos)

1. Na conta Google que vai **enviar** os e-mails, ative a **Verificação em duas etapas**.
2. Abra https://myaccount.google.com/apppasswords e crie uma **Senha de app** (16 letras). A senha normal do Gmail não funciona.
3. Copie `app/config.local.exemplo.php` para `app/config.local.php` e preencha `SMTP_USER` (o Gmail) e `SMTP_PASS` (a senha de app, com ou sem espaços).
4. Teste no terminal: `php tools/testar_email.php seuemail@gmail.com`. Se falhar, o motivo aparece no log do PHP (linha `[Mailer]`).
5. Hospedando na internet? Preencha também `APP_URL` (ex.: `https://meusite.com.br/OlhoCidadao/public`).
   Só no localhost (ou IP da rede local) o endereço é descoberto sozinho, por segurança.

Observações: o Gmail limita cerca de 500 e-mails por dia em conta comum (sobra para a apresentação).
No XAMPP/Windows, se der erro de certificado, use `define('SMTP_VERIFY_SSL', false);` **só no seu computador**.

## O que mudou na v0.36 (celular)

- **`public/css/responsivo.css`**: CSS global carregado por todas as páginas, depois dos CSS de cada tela.
- **Rolagem lateral corrigida:** busca do painel admin (`admin` e `admin-usuarios`), tabelas do relatório geral
  (agora rolam dentro da própria caixa) e campos do perfil.
- **Login/cadastro/recuperação de senha:** o corpo da página não tem mais altura fixa de 100vh,
  então o formulário comprido do cadastro não fica cortado (classe `pagina-auth` no `<body>`).
- **iPhone:** campos com fonte de 16px (evita o zoom automático ao tocar) e alvos de toque de pelo menos 44px.
- **Altura real da tela:** menu gaveta e mapa público usam `dvh` (a barra do navegador do celular não corta mais).
- Gráficos do painel não estouram em telas de 320px; `theme-color` define a cor da barra do navegador no celular.
- Testado em 320, 360, 390, 414 e 768px: nenhuma das 26 páginas tem rolagem lateral.

## O que mudou na v0.35 (acessibilidade)

- **VLibras:** widget oficial do Governo Federal em todas as páginas (botão de acessibilidade no canto da tela;
  o avatar traduz o texto selecionado/da página para Libras). Arquivo: `app/Views/_vlibras.php`, incluído antes do `</body>` de cada view.
- Precisa de internet no navegador do usuário (carrega de `vlibras.gov.br`). Não é preciso rodar a `translate-api`.

## O que mudou na v0.34 (funções novas)

**Para o cidadão**
- **Consulta por protocolo** (`?rota=consulta`, sem login): qualquer pessoa digita `OC-2026-000012` e vê status,
  local e andamento — sem nome nem dados pessoais. Tem limite de consultas por IP. Link na tela de login.
- **Editar denúncia pendente**: botão "Editar" em *Minhas denúncias* (descrição e endereço). A edição entra no histórico.
  Depois que a prefeitura começa a analisar, não dá mais para editar.
- **Seus dados (LGPD)** no Perfil: baixar tudo em JSON e **excluir a conta** (pede a senha; as denúncias ficam
  registradas, mas sem ligação com a pessoa).

**Para o administrador**
- **Prioridade** (Baixa / Normal / Alta / Urgente) em cada denúncia, com selo colorido, ordenação
  "Mais urgentes primeiro" e registro na auditoria.
- **Estatísticas** (`?rota=admin-estatisticas`): taxa de resolução, tempo médio, avaliação média, atrasadas,
  reincidências, gráficos por categoria / status / mês / prioridade e as denúncias mais apoiadas.
- **Usuários** (`?rota=admin-usuarios`): busca, tornar/remover administrador, **suspender/reativar** contas.
  Conta suspensa perde o acesso na hora (mesmo com sessão aberta). Ninguém altera a própria conta. Tudo vai para a auditoria.

**Correções**
- As mensagens de sucesso/erro (cancelar, confirmar, reabrir, apoiar) **não apareciam** em *Minhas denúncias* e na
  tela da denúncia — agora aparecem. O Perfil também passou a avisar "Perfil atualizado".

**Arquivos novos:** `public/css/novidades.css`, `app/Views/admin/{estatisticas,usuarios,_menu}.php`,
`app/Views/denuncia/{consulta,editar}.php`, `sql/migracao_v034.sql`.
Nada precisa ser migrado no modo JSON: contas e denúncias antigas continuam funcionando
(sem o campo, a conta conta como ativa e a prioridade como "Normal").

## O que mudou na v0.33 (segurança e estabilidade)

- **Mapa público:** o texto das denúncias (endereço, status) agora é escapado antes de entrar no popup (evita XSS).
- **Proteção de pastas:** `.htaccess` na raiz e em `storage/`, `app/`, `sql/`, `tools/`, `mysql-pronto/`.
  Teste abrindo `.../storage/users.json` no navegador: tem que dar "Forbidden" (ou 404), nunca mostrar o conteúdo.
- **Trava dos arquivos JSON** (`app/Core/ArquivoLock.php`): evita perder dados quando duas pessoas gravam ao mesmo tempo.
- **Upload único** (`app/Core/Uploader.php`): valida o tipo real, o tamanho e **remove os metadados (EXIF/GPS)** das fotos
  regravando a imagem com o GD. Se o GD não estiver ativo no PHP, a foto é salva como veio.
- **Mensagens de erro** nos passos 1 e 2 da denúncia (antes o erro era guardado, mas não aparecia na tela).
- **Perfil:** a foto só é salva depois que todos os campos passam na validação.
- **Login:** o bloqueio por tentativas vale por "e-mail + IP" e há um limite geral por IP.
- **Sair:** o link de logout leva um token, então outro site não consegue deslogar o usuário.
- **Erros do PHP:** `127.0.0.1` também conta como ambiente local.

> Aviso: o `sql/schema.sql` e os Models em `mysql-pronto/` ainda são os antigos (sem apoios, protocolo, reabertura, avaliação etc.).
> Antes de migrar para o MySQL, eles precisam ser atualizados para ficar iguais aos de `app/Models/`.
