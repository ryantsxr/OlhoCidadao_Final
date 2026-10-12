-- Adiciona CPF e CEP ao cadastro de usuários.
-- Execute apenas se a tabela users já existir e ainda não tiver essas colunas.
ALTER TABLE users
  ADD COLUMN cpf VARCHAR(11) NOT NULL DEFAULT '' AFTER telefone,
  ADD COLUMN cep VARCHAR(8) NOT NULL DEFAULT '' AFTER cpf;
