-- Olho Cidadão — usuário próprio do MySQL (em vez de "root" sem senha).
-- 1) TROQUE a senha abaixo por uma forte (ex.: 20 caracteres aleatórios).
-- 2) Rode este arquivo no phpMyAdmin (aba SQL) ou no MySQL Workbench.
-- 3) No app/config.local.php coloque:
--      define('DB_USER', 'olho_app');
--      define('DB_PASS', 'a mesma senha forte');
-- Assim, mesmo que alguém ache uma falha no site, ele só enxerga este banco
-- e não consegue apagar tabelas nem mexer nos outros bancos do MySQL.

CREATE USER IF NOT EXISTS 'olho_app'@'localhost' IDENTIFIED BY 'TROQUE_ESTA_SENHA_forte_2026!';
CREATE USER IF NOT EXISTS 'olho_app'@'127.0.0.1' IDENTIFIED BY 'TROQUE_ESTA_SENHA_forte_2026!';

-- Só o necessário para o sistema funcionar: ler, inserir, alterar e apagar linhas.
-- (Sem DROP, ALTER, CREATE USER, FILE, etc.)
GRANT SELECT, INSERT, UPDATE, DELETE ON olho_cidadao.* TO 'olho_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON olho_cidadao.* TO 'olho_app'@'127.0.0.1';
FLUSH PRIVILEGES;
