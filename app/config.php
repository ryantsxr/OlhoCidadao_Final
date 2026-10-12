<?php
if (is_file(__DIR__ . '/config.local.php')) require_once __DIR__ . '/config.local.php';

// Fuso horário do sistema (datas das denúncias, prazos, histórico, logs).
// O XAMPP vem com "Europe/Berlin" no php.ini, o que adianta tudo em 5-6 horas.
// Guajará-Mirim (Rondônia) = America/Porto_Velho (UTC-4). Ajuste em config.local.php se precisar.
if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', 'America/Porto_Velho');
if (!@date_default_timezone_set(APP_TIMEZONE)) date_default_timezone_set('America/Porto_Velho');

// Edição local MySQL: importe primeiro sql/INSTALACAO_LOCAL_PRIVADA.sql no phpMyAdmin.
// Defina DB_DRIVER=json em app/config.local.php somente para voltar a uma cópia JSON antiga.
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
// Configuração de conexão com o MySQL.
// No XAMPP/phpMyAdmin padrão, o usuário é "root" e a senha costuma ser vazia.
// Ajuste os valores abaixo conforme o seu ambiente.

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'olho_cidadao');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// =========================================================================
// Configurações PRIVADAS (senha do e-mail, endereço do site)
//
// Crie o arquivo app/config.local.php com as suas credenciais — ele é lido
// aqui primeiro e NÃO deve ser enviado para o GitHub (está no .gitignore).
// Use app/config.local.exemplo.php como modelo.
// =========================================================================
// O arquivo local já foi carregado antes dos valores padrão de DB.

// =========================================================================
// Configuração de e-mail (SMTP) — usada na redefinição de senha e nos avisos
// (denúncia registrada, status alterado, boas-vindas).
//
// Deixe SMTP_HOST em branco pra manter o "modo de teste": nesse modo o link
// de redefinição aparece direto na tela, sem precisar de e-mail nenhum, e os
// avisos por e-mail não são enviados. Nada quebra se você não configurar.
//
// Com Gmail (passo a passo no README):
//   1) ative a "Verificação em duas etapas" na conta Google;
//   2) crie uma "Senha de app" em https://myaccount.google.com/apppasswords;
//   3) no config.local.php use host smtp.gmail.com, porta 587, 'tls',
//      seu e-mail em SMTP_USER e a senha de app (16 letras) em SMTP_PASS.
// Não precisa instalar o PHPMailer: o sistema já tem um cliente SMTP próprio
// (se o PHPMailer estiver instalado via Composer, ele passa a ser usado).
// =========================================================================
$_padroesEmail = [
    'APP_URL'         => '',   // ex.: 'https://meusite.com.br/OlhoCidadao/public' — obrigatório fora do localhost
    'SMTP_HOST'       => '',   // ex.: 'smtp.gmail.com' — vazio = modo de teste
    'SMTP_PORT'       => 587,
    'SMTP_SECURE'     => 'tls', // 'tls' (porta 587) ou 'ssl' (porta 465)
    'SMTP_USER'       => '',
    'SMTP_PASS'       => '',
    'SMTP_FROM_EMAIL' => '',   // vazio = usa o próprio SMTP_USER (o Gmail exige isso)
    'SMTP_FROM_NOME'  => 'Olho Cidadão',
    'SMTP_VERIFY_SSL' => true, // se der erro de certificado no XAMPP/Windows, mude para false só nos testes locais
    // Nome de quem fez o projeto, mostrado no rodapé ("Feito por ..."). Ex.: 'Cassio, Eric e Ryan — IFRO'
    'APP_EQUIPE'      => '',
];
foreach ($_padroesEmail as $_nome => $_valor) {
    if (!defined($_nome)) {
        define($_nome, $_valor);
    }
}
unset($_padroesEmail, $_nome, $_valor);

// =========================================================================
// Assistente Olhinho com IA (OPCIONAL)
//
// Sem nada configurado, o Olhinho responde com as respostas prontas de
// app/Core/Assistente.php — funciona sem internet e sem custo.
// Para ele também responder perguntas "fora do roteiro" com IA, preencha no
// app/config.local.php (veja o exemplo lá). A chave fica só no servidor,
// nunca vai para o navegador. Nome, e-mail, CPF e telefone são tirados da
// pergunta antes de enviar.
// =========================================================================
$_padroesIa = [
    'ASSISTENTE_IA_PROVEDOR'   => '',    // '' (desligado), 'anthropic' (Claude) ou 'openai' (OpenAI ou compatível)
    'ASSISTENTE_IA_CHAVE'      => '',    // a chave de API do serviço escolhido
    'ASSISTENTE_IA_MODELO'     => '',    // vazio = padrão do provedor
    'ASSISTENTE_IA_URL'        => '',    // só para serviços compatíveis com OpenAI (Groq, OpenRouter...)
    'ASSISTENTE_IA_VERIFY_SSL' => true,  // false só se der erro de certificado no XAMPP, e só no seu computador
];
foreach ($_padroesIa as $_nome => $_valor) {
    if (!defined($_nome)) {
        define($_nome, $_valor);
    }
}
unset($_padroesIa, $_nome, $_valor);

// Expiração por inatividade: 30 minutos, ajustável em app/config.local.php.
if (!defined('SESSION_IDLE_SECONDS')) define('SESSION_IDLE_SECONDS', 1800);
// Canal de contato do responsável pelo projeto; preencher antes de uso real.
if (!defined('LGPD_CONTATO')) define('LGPD_CONTATO', '');
// Habilite APENAS após testar SMTP, domínio HTTPS e entrega dos links.
if (!defined('VERIFICACAO_EMAIL_ATIVA')) define('VERIFICACAO_EMAIL_ATIVA', false);


// =========================================================================
// Segurança (v1.0.3) — todos com valor padrão seguro; ajuste no config.local.php.
// =========================================================================
// Verificação em duas etapas obrigatória para administradores:
//   'auto' = obrigatória quando o site está num servidor de verdade e
//            opcional no localhost (facilita a apresentação/testes);
//   true   = sempre obrigatória;  false = sempre opcional (não recomendado).
if (!defined('ADMIN_2FA_OBRIGATORIO')) define('ADMIN_2FA_OBRIGATORIO', 'auto');
// Redirecionar http:// para https:// e ativar HSTS:
//   'auto' = só quando APP_URL começa com https:// (assim não derruba um site sem certificado);
//   true / false = força ligado ou desligado.
if (!defined('FORCAR_HTTPS')) define('FORCAR_HTTPS', 'auto');
// IP(s) de um proxy reverso na frente do site (Cloudflare, nginx), separados por vírgula.
// Só deles o sistema aceita X-Forwarded-For/Proto. Vazio = nenhum (o padrão seguro).
if (!defined('PROXY_CONFIAVEL')) define('PROXY_CONFIAVEL', '');
// Limites contra spam/robôs (por conta, a cada 24 horas).
if (!defined('LIMITE_DENUNCIAS_DIA')) define('LIMITE_DENUNCIAS_DIA', 10);
if (!defined('LIMITE_APOIOS_DIA')) define('LIMITE_APOIOS_DIA', 60);
if (!defined('LIMITE_COMPLEMENTOS_DIA')) define('LIMITE_COMPLEMENTOS_DIA', 20);
// Por quantos dias guardar logs de erro, registros técnicos e backups (LGPD: minimização).
if (!defined('RETENCAO_LOGS_DIAS')) define('RETENCAO_LOGS_DIAS', 90);
