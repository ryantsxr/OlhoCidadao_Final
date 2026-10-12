# Alterações — v1.0.5 (entrega final)

- **Excluir denúncia (admin):** no painel, cada denúncia tem "Excluir denúncia", com caixa de confirmação obrigatória. Apaga a denúncia, o histórico, os apoios, as fotos e as notificações ligadas a ela e registra a ação na auditoria. A exclusão feita pelo sistema não aparece como adulteração no Histórico íntegro.
- **Instalação pelo GitHub:** nova ferramenta `php tools/criar_admin.php` para criar o administrador num banco novo (o arquivo com a conta de admin original continua privado).
- **Pasta `sql/` organizada:** `LEIA-ME.md` explicando cada script e o modelo lógico do Workbench em `sql/modelo_logico/`.
- README com a equipe e o quadro dos requisitos da entrega (cadastrar, atualizar, apagar, exibir).
