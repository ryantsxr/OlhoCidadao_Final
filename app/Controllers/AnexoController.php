<?php
/** Arquivos servidos SOMENTE depois de autenticação. */
class AnexoController extends Controller {
    public function imagem(): void {
        $this->auth();
        $arquivo = (string) ($_GET['arquivo'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_-]{1,100}\.(jpg|png|webp)$/D', $arquivo)) {
            http_response_code(404); return;
        }
        $pasta = realpath(__DIR__ . '/../../storage/uploads');
        $caminho = $pasta ? $pasta . DIRECTORY_SEPARATOR . $arquivo : '';
        if (!$pasta || !is_file($caminho)) { http_response_code(404); return; }
        // Uma imagem só é liberada se estiver referenciada no cadastro do usuário
        // ou em alguma denúncia (fotos privadas dos relatos e comprovantes).
        $autorizada = false;
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $user = (new User)->find($uid);
        if (str_contains((string)($user['foto'] ?? ''), 'arquivo=' . rawurlencode($arquivo))) $autorizada = true;
        if (!$autorizada) {
            foreach ((new Denuncia)->todas() as $d) {
                if ((int)($d['usuario_id'] ?? 0) !== $uid && ($user['role'] ?? '') !== 'admin') continue;
                foreach (['foto', 'foto_resolucao'] as $campo) {
                    if (str_contains((string)($d[$campo] ?? ''), 'arquivo=' . rawurlencode($arquivo))) {
                        $autorizada = true; break 2;
                    }
                }
                foreach ($d['fotos'] ?? [] as $foto) {
                    if (str_contains((string)$foto, 'arquivo=' . rawurlencode($arquivo))) {
                        $autorizada = true; break 2;
                    }
                }
            }
        }
        if (!$autorizada) { http_response_code(403); return; }
        $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
        header('Content-Type: ' . ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext]);
        header('Content-Length: ' . filesize($caminho));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($caminho);
    }
}
