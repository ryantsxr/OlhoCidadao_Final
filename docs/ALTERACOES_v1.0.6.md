# Alterações da v1.0.6 — sistema inteiro em inglês e espanhol

**Problema:** ao trocar o idioma em Configurações, só alguns textos mudavam (os que tinham `data-i18n`). O resto das telas continuava em português.

**O que mudou**

- `public/js/i18n.js` (novo): percorre a página inteira e traduz textos, placeholders, títulos (`title`), `aria-label`, `alt`, botões e o título da aba. Também traduz na hora o que aparece depois (avisos/toasts, modais, tour do painel, falas do mascote, respostas do Olhinho, mapa) e os rótulos dos gráficos (Chart.js). Voltar para português restaura os textos originais.
- `public/js/i18n-dicionario.js` (novo): cerca de 1.000 frases do sistema em português, inglês e espanhol. Números, datas e protocolos dentro das frases são preservados (`"Prazo de {0} dias"`). Para traduzir um texto novo, basta acrescentar uma linha `["português", "inglês", "espanhol"]`.
- Frases montadas com nomes e dados ("Por Ana Souza", "Encaminhada para …", "A denúncia OC-… está …", datas por extenso) são traduzidas por regras no próprio `i18n.js`.
- Carregado em todas as páginas via `_pwa.php` (e direto em `segundo-fator`, `reenviar-verificacao` e `transparencia`). `configuracoes.js` chama `ocI18n.aplicar()` ao trocar o idioma, então a prévia muda na hora.
- Olhinho (`app/Core/Assistente.php`): entende perguntas em inglês e espanhol (palavras-chave extras) e a resposta aparece no idioma escolhido.
- Para não traduzir um trecho, marque o elemento com `data-no-i18n`.

**O que continua no idioma original:** o que os usuários digitam (descrições, nomes, endereços, mensagens) e nomes próprios/técnicos (CPF, Leaflet, OpenStreetMap, nomes de arquivos).

**Testes:** `php tests/smoke_json.php` (57 aprovados) e varredura automática das telas públicas, do cidadão e do admin em inglês e espanhol, sem textos do sistema sobrando em português.
