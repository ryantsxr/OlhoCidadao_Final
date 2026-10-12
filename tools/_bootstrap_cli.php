<?php
/** Carregamento comum das ferramentas de linha de comando (não acessível pelo navegador). */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/Core/CompatibilidadeTexto.php';
spl_autoload_register(static function ($class) {
    foreach (['Core', 'Models'] as $dir) {
        $arq = __DIR__ . '/../app/' . $dir . '/' . $class . '.php';
        if (is_file($arq)) { require_once $arq; return; }
    }
});
