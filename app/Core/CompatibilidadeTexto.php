<?php
/** Fallback para hospedagens sem mbstring, usando iconv (se disponível). */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $texto, ?string $encoding = null): int {
        return function_exists('iconv_strlen') ? (int) iconv_strlen($texto, $encoding ?? 'UTF-8') : strlen($texto);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $texto, int $inicio, ?int $tamanho = null, ?string $encoding = null): string {
        if (function_exists('iconv_substr')) {
            $retorno = iconv_substr($texto, $inicio, $tamanho, $encoding ?? 'UTF-8');
            return $retorno === false ? '' : $retorno;
        }
        return $tamanho === null ? substr($texto, $inicio) : substr($texto, $inicio, $tamanho);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $texto, ?string $encoding = null): string {
        return strtolower($texto);
    }
}
if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $texto, int $inicio, int $largura, string $marcador = '', ?string $encoding = null): string {
        $trecho = mb_substr($texto, $inicio);
        if (mb_strlen($trecho) <= $largura) return $trecho;
        return mb_substr($trecho, 0, max(0, $largura - mb_strlen($marcador))) . $marcador;
    }
}
