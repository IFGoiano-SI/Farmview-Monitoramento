<?php

require_once __DIR__ . '/../../Config/config.php';
require_once __DIR__ . '/../../Config/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../../' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}
