<?php
/**
 * Remove metadados de fotos SEM precisar da extensão GD (puro PHP).
 *
 * Usado quando o PHP não tem a GD ligada (comum no XAMPP). Apaga tudo que
 * pode identificar a pessoa — localização GPS, modelo do celular, data,
 * comentários, XMP, IPTC — mantendo a imagem idêntica, pixel por pixel.
 *  - JPG: remove os blocos APP1–APP15 e COM; guarda só a orientação
 *         (para a foto de celular não aparecer deitada).
 *  - PNG: remove os blocos eXIf, tEXt, zTXt, iTXt e tIME.
 *  - WEBP: remove os blocos EXIF e XMP e desliga os avisos deles no VP8X.
 * Retorna o conteúdo limpo, ou null se o arquivo não tiver a estrutura esperada.
 */
final class LimpaMetadados {
    public static function limpar(string $dados, string $mime): ?string {
        return match ($mime) {
            'image/jpeg' => self::jpeg($dados),
            'image/png'  => self::png($dados),
            'image/webp' => self::webp($dados),
            default      => null,
        };
    }

    private static function jpeg(string $d): ?string {
        $n = strlen($d);
        if ($n < 4 || substr($d, 0, 2) !== "\xFF\xD8") return null;
        $saida = "\xFF\xD8";
        $orientacao = 1;
        $i = 2;
        while ($i < $n) {
            if ($d[$i] !== "\xFF") return null;
            $marcador = ord($d[$i + 1] ?? "\0");
            if ($marcador === 0xFF) { $i++; continue; }                // preenchimento
            if ($marcador === 0xD9) { $saida .= "\xFF\xD9"; break; }   // fim sem dados (raro)
            if (($marcador >= 0xD0 && $marcador <= 0xD7) || $marcador === 0x01) { $saida .= substr($d, $i, 2); $i += 2; continue; }
            if ($i + 4 > $n) return null;
            $tamanho = unpack('n', substr($d, $i + 2, 2))[1];
            if ($tamanho < 2 || $i + 2 + $tamanho > $n) return null;
            $segmento = substr($d, $i, 2 + $tamanho);
            if ($marcador === 0xDA) {
                // Início dos dados da imagem: copiado sem mudanças até o fim da imagem
                // principal (FFD9). O que vier depois (outras imagens embutidas por
                // alguns celulares, que podem ter EXIF próprio) é descartado.
                if ($orientacao !== 1) $saida .= self::app1Orientacao($orientacao);
                $fim = strpos($d, "\xFF\xD9", $i + 2);
                if ($fim === false) return null;
                return $saida . substr($d, $i, $fim + 2 - $i);
            }
            if ($marcador === 0xE1) {
                $orientacao = self::orientacaoExif(substr($segmento, 4)) ?? $orientacao;
                $i += 2 + $tamanho;
                continue;                                             // EXIF/XMP fora
            }
            $manter = $marcador === 0xEE                                                   // Adobe (cores)
                || ($marcador === 0xE2 && substr($segmento, 4, 12) === "ICC_PROFILE\0");    // perfil de cor
            if (($marcador >= 0xE2 && $marcador <= 0xEF && !$manter) || $marcador === 0xFE) {
                $i += 2 + $tamanho;                                   // outros APPn e comentários fora
                continue;
            }
            $saida .= $segmento;                                      // JFIF, perfil de cor, tabelas, etc.
            $i += 2 + $tamanho;
        }
        return strlen($saida) > 4 ? $saida : null;
    }

    /** Lê a tag Orientation (0x0112) de um bloco EXIF, se houver. */
    private static function orientacaoExif(string $app1): ?int {
        if (substr($app1, 0, 6) !== "Exif\0\0") return null;
        $t = substr($app1, 6);
        if (strlen($t) < 8) return null;
        $le = substr($t, 0, 2) === 'II';
        $u16 = static fn(int $p) => strlen($t) >= $p + 2 ? unpack($le ? 'v' : 'n', substr($t, $p, 2))[1] : 0;
        $u32 = static fn(int $p) => strlen($t) >= $p + 4 ? unpack($le ? 'V' : 'N', substr($t, $p, 4))[1] : 0;
        $ifd = $u32(4);
        $qtd = $u16($ifd);
        for ($k = 0; $k < min($qtd, 200); $k++) {
            $e = $ifd + 2 + $k * 12;
            if ($u16($e) === 0x0112) {
                $v = $u16($e + 8);
                return ($v >= 1 && $v <= 8) ? $v : null;
            }
        }
        return null;
    }

    /** Bloco EXIF mínimo, só com a orientação (nada de GPS, data ou aparelho). */
    private static function app1Orientacao(int $o): string {
        $tiff = "MM\x00\x2A" . pack('N', 8) . pack('n', 1)
            . pack('n', 0x0112) . pack('n', 3) . pack('N', 1) . pack('n', $o) . "\x00\x00"
            . pack('N', 0);
        $corpo = "Exif\0\0" . $tiff;
        return "\xFF\xE1" . pack('n', strlen($corpo) + 2) . $corpo;
    }

    private static function png(string $d): ?string {
        if (substr($d, 0, 8) !== "\x89PNG\r\n\x1a\n") return null;
        $remover = ['eXIf', 'tEXt', 'zTXt', 'iTXt', 'tIME'];
        $saida = substr($d, 0, 8);
        $i = 8; $n = strlen($d);
        while ($i + 12 <= $n) {
            $tam = unpack('N', substr($d, $i, 4))[1];
            $tipo = substr($d, $i + 4, 4);
            if ($i + 12 + $tam > $n) return null;
            if (!in_array($tipo, $remover, true)) $saida .= substr($d, $i, 12 + $tam);
            $i += 12 + $tam;
            if ($tipo === 'IEND') return $saida;
        }
        return null;
    }

    private static function webp(string $d): ?string {
        if (strlen($d) < 12 || substr($d, 0, 4) !== 'RIFF' || substr($d, 8, 4) !== 'WEBP') return null;
        $corpo = '';
        $i = 12; $n = strlen($d);
        while ($i + 8 <= $n) {
            $tipo = substr($d, $i, 4);
            $tam = unpack('V', substr($d, $i + 4, 4))[1];
            $total = 8 + $tam + ($tam % 2);
            if ($i + 8 + $tam > $n) return null;
            $bloco = substr($d, $i, min($total, $n - $i));
            if ($tipo === 'VP8X' && strlen($bloco) >= 9) {
                // Desliga os avisos de EXIF (bit 3) e XMP (bit 2).
                $bloco[8] = chr(ord($bloco[8]) & ~0x0C);
            }
            if ($tipo !== 'EXIF' && $tipo !== 'XMP ') $corpo .= $bloco;
            $i += $total;
        }
        return 'RIFF' . pack('V', 4 + strlen($corpo)) . 'WEBP' . $corpo;
    }
}
