# Olho Cidadão — notas da versão 0.52 (histórico; leia AJUSTE_FOTOS_v0.52.1.md para o comportamento atual)

Base: v0.51 ZERADO ADMIN ORIGINAL, com login exclusivo `admin@olhocidadao.com` (mesmo hash de senha anterior), sem denúncias, usuários cidadãos ou anexos fictícios.

**Novas funções:** 4 categorias (buracos, esgoto, água, arborização); status Rejeitada com motivo de 10+ caracteres; complementação do próprio autor; 4 fotos no envio inicial; paginação de denúncias do cidadão; transparência pública agregada; rota de anexos privados; generalização do mapa e restrição de dados individuais; backup de anexos; escrita atômica; ferramentas CLI de diagnóstico e verificação de backup.

**Atenção:** Requer GD para uploads de fotos (além de Fileinfo/OpenSSL), porque imagens precisam ser regravadas para remover metadados. O modo JSON permanece ativo. A criptografia CPF continua igual à versão 0.51. Configuração de SMTP e HTTPS real dependem do servidor. MFA/2FA e confirmação de e-mail ainda são pendentes.

Ver `CHECKLIST_FINAL_v0.52.md` para itens restantes, dados sensíveis e instalação segura.

**Acabamento extra:** filtros do relatório/CSV incluem 8 categorias e Rejeitada, Kanban/telão/cartões atualizados, consulta pública não divulga mensagens internas do histórico; mapa do dashboard generaliza coordenadas e anexos órfãos em falhas de upload são removidos.
