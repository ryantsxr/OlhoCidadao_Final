<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/Core/CompatibilidadeTexto.php';

// Fora do localhost (ou seja, assim que o site for pra um servidor de verdade),
// esconde os erros do PHP na tela — evita vazar caminho de arquivo, versão do
// PHP, etc. para qualquer visitante. No localhost continua mostrando, pra você
// conseguir depurar.
require_once __DIR__ . '/../app/Core/Ambiente.php';
$emProducao = !Ambiente::local();
ini_set('display_errors', $emProducao ? '0' : '1');
error_reporting(E_ALL);
// Erros ficam em arquivo privado; nunca mostrar traces ao visitante em produção.
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0700, true);
ini_set('log_errors', '1');
if (is_dir($logDir) && is_writable($logDir)) ini_set('error_log', $logDir . '/php-errors.log');
// HTTPS obrigatório quando configurado (FORCAR_HTTPS / APP_URL com https://).
if (Ambiente::forcarHttps() && !Ambiente::https()) {
    $host = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    $apphost = (string) parse_url((string) APP_URL, PHP_URL_HOST);
    if ($apphost !== '') $host = $apphost; // nunca redireciona para um host vindo só do cabeçalho
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// Política de Segurança de Conteúdo (CSP): o navegador só executa scripts do
// próprio site, os que têm o "nonce" desta página e os do VLibras (Governo Federal).
// Mesmo que algum dia um texto malicioso escape do htmlspecialchars, ele não roda.
define('CSP_NONCE', rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='));
$vlibras = "https://vlibras.gov.br https://*.vlibras.gov.br https://cdn.jsdelivr.net/gh/spbgovbr-vlibras/";
$csp = [
    "default-src 'self'",
    "script-src 'self' 'nonce-" . CSP_NONCE . "' $vlibras",
    "style-src 'self' 'unsafe-inline' $vlibras",
    "img-src 'self' data: blob: https://tile.openstreetmap.org https://*.tile.openstreetmap.org https://server.arcgisonline.com $vlibras",
    "font-src 'self' data: $vlibras",
    "connect-src 'self' https://nominatim.openstreetmap.org $vlibras https://us.i.posthog.com",
    "media-src 'self' blob: $vlibras",
    "frame-src $vlibras",
    "worker-src 'self' blob:",
    "manifest-src 'self'",
    "form-action 'self'",
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "object-src 'none'",
];
if (Ambiente::https()) $csp[] = 'upgrade-insecure-requests';
header('Content-Security-Policy: ' . implode('; ', $csp));
unset($vlibras, $csp);
header('X-Content-Type-Options: nosniff');
// Envia apenas o domínio de origem aos fornecedores de mapas; não expõe paths, parâmetros nem tokens.
// O OpenStreetMap exige Referer válido para carregar os ladrilhos (HTTP 403 sem ele).
header('Referrer-Policy: origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(self), microphone=(self), geolocation=(self), payment=(), usb=(), interest-cohort=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cache-Control: no-store, private');
header_remove('X-Powered-By');
if ($emProducao && Ambiente::https()) {
    header('Strict-Transport-Security: max-age=31536000');
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('expose_php', '0');

// Cookie de sessão mais seguro: não acessível via JavaScript (httponly),
// só trafega em HTTPS quando o site já estiver em HTTPS (secure), e não é
// enviado em requisições de sites de terceiros (samesite).
$https = Ambiente::https();
// Em HTTPS o cookie ganha o prefixo __Host-: o navegador recusa se ele vier
// sem "secure", com outro domínio ou outro caminho (anti-fixação de sessão).
session_name($https ? '__Host-OLHOSESSID' : 'OLHOSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $https,
    'samesite' => 'Lax',
]);
session_start();
// Apenas sessões autenticadas expiram após inatividade. Mantém login antigo funcional.
if (!empty($_SESSION['user_id'])) {
    $inatividade = defined('SESSION_IDLE_SECONDS') ? (int)SESSION_IDLE_SECONDS : 1800;
    $inatividade = max(60, $inatividade);
    if (isset($_SESSION['ultima_atividade']) && time() - (int)$_SESSION['ultima_atividade'] > $inatividade) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['error'] = 'Sua sessão expirou por inatividade. Entre novamente.';
    } else {
        $_SESSION['ultima_atividade'] = time();
    }
}
spl_autoload_register(function($class){
    foreach (['Core','Controllers','Models'] as $dir) {
        $file=__DIR__.'/../app/'.$dir.'/'.$class.'.php';
        if (file_exists($file)) require $file;
    }
});
$router=new Router();
$router->get('/?rota=anexo',[AnexoController::class,'imagem']);
$router->get('/?rota=login',[AuthController::class,'login']);
$router->post('/?rota=login',[AuthController::class,'authenticate']);
$router->get('/?rota=cadastro',[AuthController::class,'cadastro']);
$router->post('/?rota=cadastro',[AuthController::class,'cadastrar']);
$router->get('/?rota=segundo-fator',[AuthController::class,'segundoFator']);
$router->post('/?rota=segundo-fator',[AuthController::class,'validarSegundoFator']);
$router->get('/?rota=verificar-email',[AuthController::class,'verificarEmail']);
$router->get('/?rota=reenviar-verificacao',[AuthController::class,'reenviarVerificacao']);
$router->post('/?rota=reenviar-verificacao',[AuthController::class,'solicitarVerificacao']);
$router->post('/?rota=seguranca-2fa-iniciar',[ConfiguracaoController::class,'iniciar2fa']);
$router->post('/?rota=seguranca-2fa-ativar',[ConfiguracaoController::class,'ativar2fa']);
$router->post('/?rota=seguranca-2fa-desativar',[ConfiguracaoController::class,'desativar2fa']);
$router->post('/?rota=seguranca-2fa-regenerar',[ConfiguracaoController::class,'regenerarCodigos2fa']);
$router->get('/?rota=logout',[AuthController::class,'logout']);
$router->get('/?rota=mapa',[DenunciaController::class,'mapaPublico']);
$router->get('/?rota=esqueci-senha',[AuthController::class,'esqueciSenha']);
$router->post('/?rota=esqueci-senha',[AuthController::class,'enviarLinkReset']);
$router->get('/?rota=redefinir-senha',[AuthController::class,'redefinirSenha']);
$router->post('/?rota=redefinir-senha',[AuthController::class,'salvarNovaSenha']);
$router->get('/?rota=dashboard',[DashboardController::class,'index']);
$router->post('/?rota=notificacoes-marcar-lidas',[DashboardController::class,'marcarNotificacoesLidas']);
$router->get('/?rota=denuncia-passo1',[DenunciaController::class,'passo1']);
$router->post('/?rota=denuncia-passo1',[DenunciaController::class,'salvarPasso1']);
$router->get('/?rota=denuncia-passo2',[DenunciaController::class,'passo2']);
$router->post('/?rota=denuncia-passo2',[DenunciaController::class,'salvarPasso2']);
$router->get('/?rota=denuncia-passo3',[DenunciaController::class,'passo3']);
$router->get('/?rota=denuncias-proximas',[DenunciaController::class,'proximas']);
$router->post('/?rota=admin-encaminhar',[AdminController::class,'encaminhar']);
$router->post('/?rota=admin-excluir-denuncia',[AdminController::class,'excluirDenuncia']);
$router->get('/?rota=admin-kanban',[AdminController::class,'kanban']);
$router->get('/?rota=admin-telao',[AdminController::class,'telao']);
$router->get('/?rota=admin-busca',[AdminController::class,'buscaRapida']);
$router->post('/?rota=admin-massa',[AdminController::class,'acoesEmMassa']);
$router->post('/?rota=admin-kanban-mover',[AdminController::class,'kanbanMover']);
$router->get('/?rota=minhas-denuncias',[DenunciaController::class,'minhas']);
$router->get('/?rota=denuncia-relatorio',[DenunciaController::class,'relatorio']);
$router->post('/?rota=denuncia-complementar',[DenunciaController::class,'complementar']);
$router->post('/?rota=denuncia-recorrer',[DenunciaController::class,'recorrerRejeicao']);
$router->post('/?rota=denuncia-cancelar',[DenunciaController::class,'cancelar']);
$router->post('/?rota=denuncia-confirmar',[DenunciaController::class,'confirmar']);
$router->post('/?rota=denuncia-reabrir',[DenunciaController::class,'reabrir']);
$router->get('/?rota=denuncia-ver',[DenunciaController::class,'ver']);
$router->post('/?rota=denuncia-apoiar',[DenunciaController::class,'apoiar']);
$router->get('/?rota=perfil',[PerfilController::class,'index']);
$router->post('/?rota=perfil',[PerfilController::class,'update']);
$router->get('/?rota=configuracoes',[ConfiguracaoController::class,'index']);
$router->post('/?rota=configuracoes',[ConfiguracaoController::class,'save']);
$router->get('/?rota=admin',[AdminController::class,'index']);
$router->get('/?rota=admin-historico',[AdminController::class,'historico']);
$router->get('/?rota=admin-auditoria',[AdminController::class,'auditoria']);
$router->post('/?rota=admin-status',[AdminController::class,'atualizarStatus']);
$router->get('/?rota=admin-exportar-csv',[AdminController::class,'exportarCsv']);
$router->get('/?rota=admin-relatorio',[AdminController::class,'relatorioGeral']);
$router->get('/?rota=consulta',[DenunciaController::class,'consultaProtocolo']);
$router->get('/?rota=denuncia-editar',[DenunciaController::class,'editar']);
$router->post('/?rota=denuncia-editar',[DenunciaController::class,'salvarEdicao']);
$router->post('/?rota=perfil-exportar',[PerfilController::class,'exportarDados']);
$router->post('/?rota=perfil-excluir',[PerfilController::class,'excluirConta']);
$router->get('/?rota=admin-estatisticas',[AdminController::class,'estatisticas']);
$router->get('/?rota=admin-usuarios',[AdminController::class,'usuarios']);
$router->post('/?rota=admin-usuario-alterar',[AdminController::class,'alterarUsuario']);
$router->get('/?rota=sobre',[PaginaController::class,'sobre']);
$router->get('/?rota=security-txt',[PaginaController::class,'securityTxt']);
$router->get('/?rota=termos',[PaginaController::class,'termos']);
$router->post('/?rota=assistente',[AssistenteController::class,'perguntar']);
$router->get('/?rota=transparencia',[PaginaController::class,'transparencia']);
$router->get('/?rota=vitorias',[PaginaController::class,'vitorias']);
$router->get('/?rota=admin-integridade',[AdminController::class,'integridade']);
$router->get('/?rota=admin-seguranca',[AdminController::class,'seguranca']);
// A rota vem pela query string para funcionar tanto no XAMPP/Apache
// quanto no servidor embutido do PHP e em subpastas do projeto.
$rota = $_GET['rota'] ?? 'login';
$routeKey = '/?rota=' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$rota);

// Base pública do projeto (ex.: /OlhoCidadao/public). Mantemos o "/public" aqui
// de propósito: assim os links de CSS/JS/imagens apontam pra um caminho real
// no disco e funcionam mesmo se o .htaccess da raiz não estiver ativo (ex.:
// AllowOverride None no Apache/XAMPP) — sem depender de reescrita de URL.
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$publicDir = rtrim(dirname($script), '/');
define('BASE_URL', $publicDir ?: '');

try {
    // Migra CPF antes de qualquer cópia diária, para não criar backups em texto puro.
    PrivacidadeCpf::migrarExistentes();
    try {
        BackupDiario::executar();
    } catch (\Throwable $backupErro) {
        error_log('[BACKUP] ' . (string)$backupErro);
        // Falha de backup não derruba o atendimento. Aparece no Painel de segurança do admin.
    }
    try {
        Manutencao::diaria();
    } catch (\Throwable $manutencaoErro) {
        error_log('[MANUTENCAO] ' . (string)$manutencaoErro);
    }
    // HEAD é um GET sem corpo (usado por monitores de site e alguns navegadores).
    $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $router->dispatch($metodo === 'HEAD' ? 'GET' : $metodo, $routeKey);
} catch (\Throwable $e) {
    error_log('[APLICACAO] ' . (string)$e);
    http_response_code(500);
    // No localhost mostra o detalhe do erro pra ajudar a depurar; em produção, esconde.
    $detalhe = $emProducao ? '' : ($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
    require __DIR__ . '/../app/Views/erro/500.php';
}
