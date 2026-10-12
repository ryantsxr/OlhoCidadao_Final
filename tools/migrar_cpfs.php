<?php
/** Opcional: rodar ANTES de colocar no ar: php tools/migrar_cpfs.php */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
spl_autoload_register(static function ($class) {
    foreach (['Core', 'Models'] as $dir) {
        $arq = __DIR__ . '/../app/' . $dir . '/' . $class . '.php';
        if (is_file($arq)) { require_once $arq; return; }
    }
});
try {
    echo 'Contas migradas: ' . PrivacidadeCpf::migrarExistentes() . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
