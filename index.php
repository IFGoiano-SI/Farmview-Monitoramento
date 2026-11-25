<?php

require_once __DIR__ . '/Config/config.php';
require_once __DIR__ . '/Config/helpers.php';

date_default_timezone_set('America/Sao_Paulo');

$debug = config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

// Sessão: cookie inacessível ao JavaScript, SameSite e Secure quando em HTTPS.
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $https,
    'path' => '/',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/vendor/autoload.php';

set_exception_handler(function (Throwable $e) use ($debug) {
    Services\Logger::erro('Exceção não tratada', [
        'tipo' => get_class($e),
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile() . ':' . $e->getLine(),
    ]);

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo $debug
        ? '<pre>' . esc((string) $e) . '</pre>'
        : 'Ocorreu um erro inesperado. Tente novamente em instantes.';
});

require_once __DIR__ . '/router/routes.php';
require_once __DIR__ . '/core/Core.php';

(new Core())->run($routes);
