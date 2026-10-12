-- Olho Cidadão v1.0: selecione primeiro o banco no phpMyAdmin.
-- Compatível com MySQL 8.0 e MariaDB recentes (InnoDB/utf8mb4).
-- NÃO execute sobre tabelas antigas não migradas sem antes fazer backup.
CREATE TABLE IF NOT EXISTS app_locks (
  nome VARCHAR(80) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS app_settings (
  chave VARCHAR(120) NOT NULL PRIMARY KEY,
  payload LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  cpf_hash CHAR(64) NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'cidadao',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  payload LONGTEXT NOT NULL,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_cpf_hash (cpf_hash),
  KEY idx_users_role (role,ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS denuncias (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  categoria VARCHAR(40) NOT NULL,
  status VARCHAR(45) NOT NULL,
  prioridade VARCHAR(25) NOT NULL,
  orgao VARCHAR(60) NULL,
  criada_em DATETIME NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  payload LONGTEXT NOT NULL,
  KEY idx_den_user (usuario_id),
  KEY idx_den_status_data (status,criada_em),
  KEY idx_den_categoria (categoria),
  KEY idx_den_orgao (orgao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- usuario_id não tem FK por exigência de manter denúncia anonimizada após exclusão da conta.
CREATE TABLE IF NOT EXISTS denuncia_historico (
  denuncia_id BIGINT UNSIGNED NOT NULL,
  sequencia INT UNSIGNED NOT NULL,
  status VARCHAR(60) NOT NULL,
  data DATETIME NULL,
  payload LONGTEXT NOT NULL,
  PRIMARY KEY (denuncia_id,sequencia),
  CONSTRAINT fk_hist_den FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS denuncia_apoios (
  denuncia_id BIGINT UNSIGNED NOT NULL,
  usuario_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (denuncia_id,usuario_id),
  CONSTRAINT fk_apoio_den FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS notificacoes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  denuncia_id BIGINT UNSIGNED NULL,
  lida TINYINT(1) NOT NULL DEFAULT 0,
  criada_em DATETIME NULL,
  payload LONGTEXT NOT NULL,
  KEY idx_not_usuario (usuario_id,lida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS auditoria (
  seq_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  denuncia_id BIGINT UNSIGNED NULL,
  criada_em DATETIME NULL,
  payload LONGTEXT NOT NULL,
  KEY idx_auditoria_data (criada_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
  chave VARCHAR(240) NOT NULL PRIMARY KEY,
  payload LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rate_limits (
  chave VARCHAR(240) NOT NULL PRIMARY KEY,
  payload LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
