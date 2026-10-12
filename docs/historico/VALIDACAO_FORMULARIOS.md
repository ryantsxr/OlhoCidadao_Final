# Validação dos formulários — Olho Cidadão

Implementações feitas:

- Cadastro com validação de nome, e-mail, senha, confirmação e termos.
- Sanitização de campos de texto/e-mail com `trim()` no cliente e no servidor.
- Login e recuperação de senha com validação de e-mail no servidor.
- Perfil com validação de nome, e-mail, cidade e telefone.
- Máscara de telefone no formulário de perfil; o JavaScript também possui suporte reutilizável para CPF e CEP através de `data-mask="cpf"` e `data-mask="cep"`.
- Denúncia com validação do texto, limite de caracteres, endereço sanitizado e validação dos limites de latitude/longitude.
- Avaliação da denúncia limitada a 1–5 estrelas.
- Mensagem administrativa limitada a 300 caracteres e status validado no servidor.
- Senhas não são submetidas a `trim()`, para não alterar uma senha que contenha espaços intencionalmente.

## Observação sobre CPF e CEP

O projeto original não possui campos de CPF ou CEP no cadastro/perfil nem no banco de dados. Por isso, não foram criados campos novos apenas para cumprir a máscara: a implementação deixa o suporte pronto para quando esses campos existirem, sem alterar a estrutura de dados do sistema sem necessidade.


## Perfil: CPF, CEP e telefone obrigatórios
- A tela de edição de perfil agora exige telefone, CPF e CEP.
- Telefone: máscara `(00) 00000-0000` e validação de 10/11 dígitos.
- CPF: máscara `000.000.000-00` e validação dos dígitos verificadores no servidor.
- CEP: máscara `00000-000` e validação de 8 dígitos no servidor.
- Os valores são sanitizados e armazenados somente com dígitos.
- Para instalações MySQL existentes, use `sql/migracao_cpf_cep.sql`.
