<?php
session_start();
$_SESSION['url_rasp'] = '192.168.0.127:5000';
require_once __DIR__ . '/Config/helpers.php';


spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';

    $class = str_replace('\\', '/', $class);

    $file = $baseDir . $class . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/router/routes.php';
require_once __DIR__ . '/core/Core.php';

$core = new Core();
$core->run($routes);