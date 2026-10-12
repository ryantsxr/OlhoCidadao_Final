# Olho Cidadão v0.52.1 — correção de envio de fotos

## Correções realizadas

- Tela **Descreva e localize** voltou a mostrar apenas **uma foto opcional**, com seleção/arraste, prévia, troca e remoção (como na v0.51).
- Removido o campo visual e o processamento das **3 fotos adicionais**. As demais funcionalidades da v0.52 permanecem.
- O envio de foto não requer mais a extensão GD: usa validação de formato real (fileinfo + getimagesize, sem GD) e salva no diretório **privado** `storage/uploads`.
- Quando GD está habilitado, a foto é regravada para retirar EXIF/metadados (como na v0.52). **Sem GD, os metadados da imagem original não são removidos**, mas a foto não deve ser publicada diretamente: o acesso continua condicionado à autenticação e autorização pela rota `?rota=anexo`.
- A rota de anexos passou a permitir também que o administrador acesse comprovantes e fotos das denúncias autorizadas.
- **Nenhum cadastro ou denúncia de teste** foi incluído. Somente `admin@olhocidadao.com`, com o hash da senha original preservado.

## Para atualizar uma instalação ativa

**Faça um backup completo antes de substituir arquivos.** Para esta correção, atualize no servidor:

1. `app/Views/denuncia/passo2.php`
2. `app/Controllers/DenunciaController.php`
3. `app/Core/Uploader.php`
4. `app/Core/Controller.php`

Evite substituir `storage/users.json`, `storage/denuncias.json`, outros arquivos de dados ou `storage/.cpf.key` de um sistema já em uso. Se quiser instalar o ZIP completo como instalação nova, use o guia de instalação e limpeza do projeto.

**Recomendação de privacidade:** habilite GD em produção para remover metadados EXIF e proteger usuários que enviam fotos com informações de localização embutidas.
