<?php
/**
 * Endpoint do Olhinho (assistente). Recebe a pergunta em JSON e devolve a
 * resposta em JSON — quem desenha o chat é o public/js/assistente.js.
 */
class AssistenteController extends Controller {
    public function perguntar(): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        // Mesmo token CSRF dos formulários, enviado no cabeçalho: impede que outro
        // site use o assistente (e os créditos da IA) em nome do visitante.
        $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(['texto' => 'Sua sessão expirou. Recarregue a página e tente de novo.', 'acoes' => [], 'humor' => 'preocupado', 'fonte' => 'erro']);
            return;
        }

        // Limite geral por IP (protege o servidor de robôs).
        $limiter = new RateLimiter('assistente', maxTentativas: 120, bloqueioSegundos: 300);
        if ($limiter->bloqueado() > 0) {
            http_response_code(429);
            echo json_encode(['texto' => 'Opa, muitas perguntas seguidas! Respira e tenta de novo em alguns minutos. 😅', 'acoes' => [], 'humor' => 'preocupado', 'fonte' => 'erro'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $limiter->registrarTentativa();

        $dados = json_decode((string) file_get_contents('php://input'), true);
        $pergunta = is_array($dados) ? (string) ($dados['pergunta'] ?? '') : '';
        $historico = is_array($dados['historico'] ?? null) ? $dados['historico'] : [];

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        echo json_encode(Assistente::responder($pergunta, $historico, $userId), JSON_UNESCAPED_UNICODE);
    }
}
