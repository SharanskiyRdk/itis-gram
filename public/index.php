<?php

require_once __DIR__ . '/../helpers.php';

use App\Routing\Router;
use App\Core\Config;
use App\Core\Logger;

if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $config = new Config(__DIR__ . '/../.env');
} catch (Throwable $e) {
    Logger::error($e->getMessage(), 'EXCEPTION');
    echo 'Ошибка загрузки конфигурации';
    session_abort();
}

$router = new Router();

$routes = require __DIR__ . '/../routes.php';
$routes($router);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$router->dispatch($uri, $method);