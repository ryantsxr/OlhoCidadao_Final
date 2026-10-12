# Correção do botão Voltar — v0.52.2

No relatório de denúncia, o link de retorno agora usa uma URL interna em vez de `javascript:history.back()`, que falhava quando o PDF/relatório era aberto em uma nova aba.

As telas que abrem o relatório indicam a origem (`admin`, `admin-historico`, `denuncia-ver`). O relatório aceita somente esses três valores, voltando ao painel administrativo quando a origem não é reconhecida.

## Atualização do site já publicado

1. Faça um backup do projeto e dos dados atuais.
2. Use o pacote de **correção somente do botão Voltar** e copie os arquivos para os caminhos correspondentes no servidor, mantendo a estrutura `app/Views/`.
3. Não apague nem substitua a pasta `storage/` da instalação existente.
4. Atualize a página ignorando o cache (Ctrl+F5) e teste o botão pelo painel, histórico e detalhe da denúncia.

O pacote completo é para instalação nova, porque inclui dados zerados e apenas a conta administrativa original. Esta alteração não exige atualização de banco de dados ou CSS.
