-- =============================================================================
--  OLHO CIDADÃO — MODELO LÓGICO DO BANCO DE DADOS (MySQL 8 / MariaDB 10.4+)
--  Projeto Integrado — Etapa Banco de Dados — Técnico em Informática | IFRO
--
--  Como abrir no MySQL Workbench:
--    File > Import > Reverse Engineer MySQL Create Script...  (selecione este arquivo,
--    marque "Place imported objects on a diagram") e depois File > Save Model As (.mwb).
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS `olho_cidadao_modelo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `olho_cidadao_modelo`;

-- -----------------------------------------------------------------------------
-- usuarios: cidadãos e administradores
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`                 VARCHAR(120)  NOT NULL,
  `email`                VARCHAR(190)  NOT NULL,
  `senha_hash`           VARCHAR(255)  NOT NULL COMMENT 'Argon2id (ou bcrypt)',
  `cpf_cifrado`          VARCHAR(255)  NULL     COMMENT 'CPF criptografado com AES-256-GCM',
  `cpf_hash`             CHAR(64)      NULL     COMMENT 'HMAC do CPF, evita CPF duplicado',
  `telefone`             VARCHAR(20)   NULL,
  `cidade`               VARCHAR(100)  NULL,
  `cep`                  CHAR(9)       NULL,
  `foto`                 VARCHAR(255)  NULL,
  `perfil`               ENUM('cidadao','admin') NOT NULL DEFAULT 'cidadao',
  `ativo`                TINYINT(1)    NOT NULL DEFAULT 1,
  `email_verificado`     TINYINT(1)    NOT NULL DEFAULT 0,
  `totp_ativo`           TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Verificação em duas etapas ligada',
  `totp_segredo_cifrado` VARCHAR(255)  NULL     COMMENT 'Segredo do app autenticador (cifrado)',
  `criado_em`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_usuarios_email` (`email`),
  UNIQUE INDEX `uq_usuarios_cpf_hash` (`cpf_hash`),
  INDEX `idx_usuarios_perfil` (`perfil`, `ativo`)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- codigos_recuperacao: códigos de uso único da verificação em duas etapas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `codigos_recuperacao` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `codigo_hash` VARCHAR(255) NOT NULL COMMENT 'Guardado só o hash do código',
  `usado_em`    DATETIME     NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_codigos_usuario_idx` (`usuario_id`),
  CONSTRAINT `fk_codigos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- categorias: tipo do problema (iluminação, buracos, lixo...)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(30)      NOT NULL,
  `nome` VARCHAR(60)      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_categorias_slug` (`slug`)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- orgaos: setores da prefeitura que recebem as denúncias, com prazo de resposta
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orgaos` (
  `id`          TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(30)       NOT NULL,
  `nome`        VARCHAR(120)      NOT NULL,
  `prazo_dias`  SMALLINT UNSIGNED NOT NULL COMMENT 'Prazo padrão de resposta',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_orgaos_slug` (`slug`)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- denuncias: ocorrência registrada pelo cidadão
--   protocolo exibido = 'OC-' + ano de criada_em + id com 6 dígitos (ex.: OC-2026-000021)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `denuncias` (
  `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`            INT UNSIGNED     NULL COMMENT 'Fica NULL se a conta for excluída (LGPD)',
  `categoria_id`          TINYINT UNSIGNED NOT NULL,
  `orgao_id`              TINYINT UNSIGNED NULL COMMENT 'Setor para onde foi encaminhada',
  `denuncia_anterior_id`  INT UNSIGNED     NULL COMMENT 'Problema que voltou a acontecer',
  `descricao`             TEXT             NOT NULL,
  `endereco`              VARCHAR(255)     NULL,
  `latitude`              DECIMAL(10,7)    NULL,
  `longitude`             DECIMAL(10,7)    NULL,
  `anonima`               TINYINT(1)       NOT NULL DEFAULT 0,
  `status`                ENUM('Pendente','Em análise','Aguardando confirmação','Resolvido','Rejeitada','Cancelada') NOT NULL DEFAULT 'Pendente',
  `prioridade`            ENUM('Baixa','Normal','Alta','Urgente') NOT NULL DEFAULT 'Normal',
  `motivo_rejeicao`       VARCHAR(300)     NULL,
  `prazo_resposta`        DATE             NULL,
  `encaminhada_em`        DATETIME         NULL,
  `avaliacao`             TINYINT UNSIGNED NULL COMMENT 'Nota de 1 a 5 dada pelo cidadão',
  `criada_em`             DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_denuncias_usuario_idx` (`usuario_id`),
  INDEX `fk_denuncias_categoria_idx` (`categoria_id`),
  INDEX `fk_denuncias_orgao_idx` (`orgao_id`),
  INDEX `fk_denuncias_anterior_idx` (`denuncia_anterior_id`),
  INDEX `idx_denuncias_status_data` (`status`, `criada_em`),
  CONSTRAINT `fk_denuncias_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_denuncias_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_denuncias_orgao`
    FOREIGN KEY (`orgao_id`) REFERENCES `orgaos` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_denuncias_anterior`
    FOREIGN KEY (`denuncia_anterior_id`) REFERENCES `denuncias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- fotos: foto enviada pelo cidadão ou foto de comprovação da solução
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fotos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `denuncia_id` INT UNSIGNED NOT NULL,
  `arquivo`     VARCHAR(255) NOT NULL COMMENT 'Nome aleatório, guardado fora da pasta pública',
  `tipo`        ENUM('denuncia','resolucao') NOT NULL DEFAULT 'denuncia',
  `enviada_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_fotos_denuncia_idx` (`denuncia_id`),
  CONSTRAINT `fk_fotos_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- historico_denuncia: linha do tempo, cada entrada selada (HMAC encadeado)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historico_denuncia` (
  `denuncia_id` INT UNSIGNED      NOT NULL,
  `sequencia`   SMALLINT UNSIGNED NOT NULL,
  `status`      VARCHAR(40)       NOT NULL,
  `mensagem`    VARCHAR(500)      NULL,
  `data`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `selo`        CHAR(64)          NOT NULL COMMENT 'HMAC-SHA256 desta entrada + selo anterior',
  PRIMARY KEY (`denuncia_id`, `sequencia`),
  CONSTRAINT `fk_historico_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- complementos: informações que o cidadão acrescenta depois (máx. 10 por denúncia)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complementos` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `denuncia_id` INT UNSIGNED  NOT NULL,
  `texto`       VARCHAR(1000) NOT NULL,
  `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_complementos_denuncia_idx` (`denuncia_id`),
  CONSTRAINT `fk_complementos_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- recursos: pedido de revisão de uma denúncia rejeitada
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recursos` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `denuncia_id`   INT UNSIGNED  NOT NULL,
  `justificativa` VARCHAR(1000) NOT NULL,
  `criado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_recursos_denuncia_idx` (`denuncia_id`),
  CONSTRAINT `fk_recursos_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- apoios: morador que apoia a denúncia de outro (relação N:N)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `apoios` (
  `denuncia_id` INT UNSIGNED NOT NULL,
  `usuario_id`  INT UNSIGNED NOT NULL,
  PRIMARY KEY (`denuncia_id`, `usuario_id`),
  INDEX `fk_apoios_usuario_idx` (`usuario_id`),
  CONSTRAINT `fk_apoios_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_apoios_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- notificacoes: avisos ao cidadão sobre mudanças nas denúncias
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `denuncia_id` INT UNSIGNED NULL,
  `mensagem`    VARCHAR(300) NOT NULL,
  `lida`        TINYINT(1)   NOT NULL DEFAULT 0,
  `criada_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_notificacoes_usuario_idx` (`usuario_id`, `lida`),
  INDEX `fk_notificacoes_denuncia_idx` (`denuncia_id`),
  CONSTRAINT `fk_notificacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacoes_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- auditoria: ações feitas pelos administradores
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED NULL COMMENT 'NULL se o administrador foi excluído',
  `denuncia_id` INT UNSIGNED NULL,
  `acao`        VARCHAR(300) NOT NULL,
  `criada_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_auditoria_admin_idx` (`admin_id`),
  INDEX `fk_auditoria_denuncia_idx` (`denuncia_id`),
  INDEX `idx_auditoria_data` (`criada_em`),
  CONSTRAINT `fk_auditoria_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_auditoria_denuncia`
    FOREIGN KEY (`denuncia_id`) REFERENCES `denuncias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;
