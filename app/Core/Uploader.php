<?php
/**
 * Upload de imagem compartilhado entre a foto da denúncia (cidadão) e a foto
 * de comprovação de resolução (admin) — mesma validação nos dois casos
 * (tipo real do arquivo via finfo, tamanho máximo, nome aleatório), pra não
 * duplicar (e arriscar dessincronizar) a parte sensível dessa lógica.
 */
class Uploader {
    /** Apaga apenas anexos privados gerados pelo próprio upload. */
    public static function apagarUploadPrivado(string $url): void {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $parametros);
        $nome = (string)($parametros['arquivo'] ?? '');
        if (str_contains($url, '?rota=anexo&') && preg_match('/^[a-zA-Z0-9_-]+\.(?:jpg|png|webp)$/D', $nome)) {
            @unlink(__DIR__ . '/../../storage/uploads/' . $nome);
        }
    }

    /**
     * Endereço da foto no local ATUAL do site. O banco guarda o caminho completo
     * de quando a foto foi enviada (ex.: /OlhoCidadao_v102/public/?rota=anexo...);
     * se a pasta do projeto mudar de nome, a foto continuaria apontando para a
     * pasta antiga e apareceria quebrada.
     */
    public static function urlAtual(string $url): string {
        if (!defined('BASE_URL') || $url === '') return $url;
        if (preg_match('/[?&]rota=anexo&(?:amp;)?arquivo=([A-Za-z0-9_-]+\.(?:jpg|png|webp))/', $url, $m)) {
            return BASE_URL . '/?rota=anexo&arquivo=' . $m[1];
        }
        return $url;
    }

    private const TAMANHO_MAXIMO = 5 * 1024 * 1024; // 5 MB
    /**
     * Limite de resolução. Um PNG de 5 MB pode ter 30.000 × 30.000 pixels e,
     * ao ser aberto, ocupar vários GB de memória ("bomba de imagem").
     * 8.000 px por lado e 24 megapixels cobrem qualquer foto de celular.
     */
    private const LADO_MAXIMO = 8000;
    private const PIXELS_MAXIMOS = 24_000_000;
    /** A foto salva é reduzida para no máximo esse lado maior (economiza espaço e banda). */
    private const LADO_SALVO = 2560;

    private const MIME_PARA_EXTENSAO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Valida e salva a imagem enviada em $_FILES[$campo], se houver alguma.
     *
     * Retorna:
     *   - a URL autenticada (?rota=anexo&arquivo=...) em caso de sucesso;
     *   - string vazia se o campo simplesmente não foi enviado (opcional);
     * Lança \RuntimeException com uma mensagem amigável se veio algo, mas é
     * inválido (tipo não suportado, tamanho excedido, falha no upload).
     */
    public static function salvarImagemEnviada(string $campo, string $prefixoNome): string {
        if (
            !isset($_FILES[$campo]) ||
            !is_array($_FILES[$campo]) ||
            ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) {
            return '';
        }

        if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha ao enviar a imagem. Tente novamente.');
        }

        if ((int) $_FILES[$campo]['size'] > self::TAMANHO_MAXIMO) {
            throw new \RuntimeException('A imagem deve ter no máximo 5 MB.');
        }

        $tmp = $_FILES[$campo]['tmp_name'];
        if (!is_string($tmp) || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('O envio da imagem não é válido. Tente novamente.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        // getimagesize não precisa da extensão GD: verifica também se há uma
        // imagem real, reduzindo o risco de arquivos disfarçados.
        $dimensoes = @getimagesize($tmp);
        if (!isset(self::MIME_PARA_EXTENSAO[$mime]) ||
            $dimensoes === false || ($dimensoes['mime'] ?? '') !== $mime) {
            throw new \RuntimeException('Formato de imagem não suportado. Envie JPG, PNG ou WEBP.');
        }
        [$largura, $altura] = [(int) $dimensoes[0], (int) $dimensoes[1]];
        if ($largura < 1 || $altura < 1 || $largura > self::LADO_MAXIMO || $altura > self::LADO_MAXIMO
            || $largura * $altura > self::PIXELS_MAXIMOS) {
            throw new \RuntimeException('A imagem tem resolução grande demais. Envie uma foto de até 8000 × 8000 pixels.');
        }

        $uploadDir = __DIR__ . '/../../storage/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $nome = $prefixoNome . '_' . bin2hex(random_bytes(8)) . '.' . self::MIME_PARA_EXTENSAO[$mime];
        $destino = $uploadDir . $nome;

        // Os metadados (EXIF) muitas vezes trazem a localização GPS exata de onde a
        // foto foi tirada — geralmente a casa de quem denunciou. Eles SEMPRE saem:
        //  - com a extensão GD: a foto é redesenhada (some tudo, e fotos grandes são reduzidas);
        //  - sem a GD: os blocos de metadados são cortados do arquivo, sem mexer na imagem.
        if (self::gdDisponivel($mime)) {
            if (!self::regravarSemMetadados($tmp, $destino, $mime)) {
                @unlink($destino);
                throw new \RuntimeException('Não foi possível ler essa imagem. Tente outra foto (JPG, PNG ou WEBP).');
            }
        } else {
            $limpo = LimpaMetadados::limpar((string) file_get_contents($tmp), $mime);
            $tmpLimpo = $destino . '.tmp';
            if ($limpo === null || file_put_contents($tmpLimpo, $limpo, LOCK_EX) === false) {
                @unlink($tmpLimpo);
                throw new \RuntimeException('Não foi possível ler essa imagem. Tente outra foto (JPG, PNG ou WEBP).');
            }
            // Confere se a imagem continua válida e do mesmo tipo depois da limpeza.
            $conferida = @getimagesize($tmpLimpo);
            if ($conferida === false || ($conferida['mime'] ?? '') !== $mime || !rename($tmpLimpo, $destino)) {
                @unlink($tmpLimpo);
                throw new \RuntimeException('Não foi possível ler essa imagem. Tente outra foto (JPG, PNG ou WEBP).');
            }
        }
        @chmod($destino, 0640);

        return BASE_URL . '/?rota=anexo&arquivo=' . rawurlencode($nome);
    }

    /**
     * Abre a imagem com o GD e salva de novo no destino, sem os metadados.
     * Corrige a rotação das fotos de celular (que ficam só no EXIF) antes de apagá-lo.
     * Retorna false se o arquivo não for uma imagem de verdade.
     */
    /** Verifica se o PHP consegue abrir e salvar esse formato (extensão GD). */
    public static function gdDisponivel(string $mime = 'image/jpeg'): bool {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagetypes')) return false;
        return match ($mime) {
            'image/jpeg' => (bool) (imagetypes() & IMG_JPG) && function_exists('imagejpeg'),
            'image/png'  => (bool) (imagetypes() & IMG_PNG) && function_exists('imagepng'),
            'image/webp' => defined('IMG_WEBP') && (bool) (imagetypes() & IMG_WEBP) && function_exists('imagewebp'),
            default      => false,
        };
    }

    private static function regravarSemMetadados(string $origem, string $destino, string $mime): bool {
        // 24 MP em true color ocupam ~100 MB na memória; garante folga só durante o processamento.
        $limiteAtual = ini_get('memory_limit');
        if ($limiteAtual !== false && $limiteAtual !== '-1' && self::bytes($limiteAtual) < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }
        $conteudo = @file_get_contents($origem);
        $imagem = $conteudo !== false ? @imagecreatefromstring($conteudo) : false;
        if ($imagem === false) {
            return false;
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($origem);
            $giro = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($giro !== 0) {
                $girada = imagerotate($imagem, $giro, 0);
                if ($girada !== false) {
                    $imagem = $girada;
                }
            }
        }

        // Reduz fotos muito grandes (mantendo a proporção).
        $w = imagesx($imagem); $h = imagesy($imagem);
        $maior = max($w, $h);
        if ($maior > self::LADO_SALVO) {
            $fator = self::LADO_SALVO / $maior;
            $reduzida = imagescale($imagem, max(1, (int) round($w * $fator)), max(1, (int) round($h * $fator)), IMG_BICUBIC);
            if ($reduzida !== false) {
                imagedestroy($imagem);
                $imagem = $reduzida;
            }
        }

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($imagem, false);
            imagesavealpha($imagem, true); // mantém a transparência
        }

        if ($mime === 'image/jpeg') {
            $ok = imagejpeg($imagem, $destino, 88);
        } elseif ($mime === 'image/png') {
            $ok = imagepng($imagem, $destino);
        } else {
            $ok = function_exists('imagewebp') ? imagewebp($imagem, $destino, 88) : false;
        }

        imagedestroy($imagem);
        return $ok;
    }

    private static function bytes(string $valor): int {
        $valor = trim($valor);
        $n = (int) $valor;
        return match (strtolower(substr($valor, -1))) {
            'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
        };
    }
}
