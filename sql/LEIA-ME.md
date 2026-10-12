# Banco de dados do Olho Cidadão

| Arquivo | Para quê |
|---|---|
| `schema.sql` | **Banco usado pelo sistema.** Importe num banco vazio chamado `olho_cidadao` (`utf8mb4_unicode_ci`). Depois crie o administrador com `php tools/criar_admin.php`. |
| `schema_v1_mysql.sql` | Mesmo conteúdo do `schema.sql` (nome mantido por compatibilidade com as ferramentas). |
| `criar_usuario_mysql.sql` | Cria um usuário MySQL próprio do sistema, só com SELECT/INSERT/UPDATE/DELETE. Troque a senha antes de rodar. |
| `modelo_logico/olho_cidadao_modelo_logico.sql` | **Modelo lógico** (etapa de Banco de Dados): 12 tabelas normalizadas, chaves e relacionamentos. Abra no MySQL Workbench em *File › Import › Reverse Engineer MySQL Create Script*. Cria o banco separado `olho_cidadao_modelo`, sem mexer no banco do sistema. |
| `modelo_logico/olho_cidadao_dados_iniciais.sql` | Categorias e setores reais usados pelo sistema, para o modelo lógico. |
| `modelo_logico/modelo_logico.png` | Imagem do diagrama do modelo lógico. |
| `legado/` | Scripts antigos, só para histórico. **Não importar.** |

## Modelo lógico x banco do sistema

O modelo lógico separa cada informação em colunas e tabelas próprias (usuários, denúncias, fotos, histórico, complementos, recursos, apoios, notificações, auditoria, categorias, setores e códigos de recuperação).

No banco que o sistema usa hoje, as colunas mais consultadas (e-mail, perfil, categoria, situação, prioridade, setor, datas, coordenadas) também são colunas próprias com índices, e os demais dados de cada registro ficam num campo `payload` em JSON. Essa escolha permitiu ao sistema funcionar tanto com MySQL quanto com arquivos JSON durante o desenvolvimento.

O arquivo `INSTALACAO_LOCAL_PRIVADA.sql`, quando existir, é só da máquina de quem desenvolve (tem a conta de admin original) e **não vai para o GitHub**.
