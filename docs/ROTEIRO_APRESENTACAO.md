# Roteiro de apresentação (≈ 5 minutos)

## Antes da banca (no dia anterior)

1. Use um **banco só para a apresentação**: crie `olho_cidadao_demo`, importe `sql/INSTALACAO_LOCAL_PRIVADA.sql`, aponte `DB_NAME` para ele no `config.local.php` e rode
   `php tools/dados_demonstracao.php --confirmar` (cria 5 moradores e 18 denúncias fictícias; senha dos moradores: `Demo#Cidade2026`).
2. Rode `php tools/verificar_instalacao.php` e abra **Painel → Segurança** para ver tudo verde (no localhost os avisos de HTTPS/SMTP são normais).
3. Deixe abertas duas janelas: uma anônima com o cidadão (`ana.demo@exemplo.com`) e outra com o admin.
4. Tenha o celular com o app autenticador, se for mostrar a 2FA.
5. Sem internet, tudo funciona, menos os ladrilhos do mapa e o VLibras — se puder, garanta internet.

## Na apresentação

| Tempo | O que mostrar | O que falar |
|---|---|---|
| 0:00 | Tela de login → painel do cidadão | O problema: o morador não tem como registrar e acompanhar reclamações da cidade. |
| 0:30 | **Fazer denúncia**: categoria → descrição (ditado por voz), foto, ponto no mapa → protocolo | O Olhinho guia cada passo; o protocolo permite acompanhar sem login. |
| 1:30 | **Admin**: a denúncia aparece no painel/Kanban → encaminhar ao setor (prazo automático) → marcar resolvida com foto | Encaminhamento por setor, prioridade e prazo. |
| 2:30 | **Cidadão** confirma e avalia → aparece no **Mural de vitórias** | O morador valida que foi resolvido de verdade. |
| 3:00 | **Transparência** e **Estatísticas** (gráficos, mapa de calor) | Dados abertos para a população. |
| 3:30 | **Histórico íntegro** e **Segurança** | Selos encadeados (ideia de blockchain), 2FA, CSP, Argon2id, fotos sem GPS, backup e LGPD. |
| 4:30 | Acessibilidade: tema, alto contraste, daltonismo, VLibras | Inclusão. |
| 5:00 | Encerramento | Próximos passos: homologação com a prefeitura, HTTPS e hospedagem. |

## Perguntas prováveis da banca

- **"E se alguém tentar invadir?"** → Limite de tentativas por IP e por conta, 2FA obrigatória para admin no servidor, CSP com nonce contra XSS, CSRF em todo formulário, fotos redesenhadas, arquivos internos bloqueados (`docs/SEGURANCA.md`).
- **"E se o banco for apagado?"** → Backup diário do MySQL e das fotos, conferido por SHA-256, com restauração por comando.
- **"E se alguém mudar uma denúncia direto no banco?"** → A tela Histórico íntegro mostra, porque cada mudança tem um selo HMAC que depende do anterior.
- **"E a LGPD?"** → CPF criptografado, exportar e excluir conta, denúncia anônima, localização pública aproximada e limpeza automática de registros antigos.
- **"Por que MVC?"** → Separação entre regras (Models), telas (Views) e fluxo (Controllers); ver `docs/DIAGRAMAS.md`.
