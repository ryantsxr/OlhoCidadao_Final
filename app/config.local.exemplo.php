<?php
/** Copie para config.local.php e ajuste para o seu MySQL local.
 *  NÃO envie config.local.php nem senhas ao GitHub.
 *  phpMyAdmin é o gerenciador; a aplicação se conecta ao SERVIDOR MySQL.
 */
define('DB_DRIVER', 'mysql');
define('DB_HOST', '127.0.0.1'); // XAMPP normalmente: 127.0.0.1
define('DB_NAME', 'olho_cidadao');
define('DB_PORT', 3306); // Se seu MySQL usa outra porta, ajuste aqui.
define('DB_PASS', '');    // Preencher sua senha local, se houver.
define('DB_CHARSET', 'utf8mb4');
define('DB_USER', 'root'); // Melhor: 'olho_app' criado com sql/criar_usuario_mysql.sql
// --- Para colocar na internet (veja docs/SEGURANCA.md) ---
// define('APP_URL', 'https://meusite.com.br/OlhoCidadao/public'); // https:// liga o redirecionamento e o HSTS
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_USER', 'seuemail@gmail.com');
// define('SMTP_PASS', 'senha de app de 16 letras');
// define('LGPD_CONTATO', 'privacidade@meusite.com.br');
// define('VERIFICACAO_EMAIL_ATIVA', true);       // só depois de testar o SMTP
// --- Segurança (já vêm com valores seguros) ---
// define('ADMIN_2FA_OBRIGATORIO', 'auto');     // 'auto' = obrigatória fora do localhost; true = sempre
// define('FORCAR_HTTPS', 'auto');
// define('LIMITE_DENUNCIAS_DIA', 10);
// define('LIMITE_APOIOS_DIA', 60);
// define('LIMITE_COMPLEMENTOS_DIA', 20);
// define('RETENCAO_LOGS_DIAS', 90);
// define('SESSION_IDLE_SECONDS', 1800);
