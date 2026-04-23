<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Routing\Router;
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Services\LoggerService;

$errorHandler = new ErrorHandler();
$errorHandler->register();

if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $config = new Config(__DIR__ . '/../.env');
} catch (Throwable $e) {
    LoggerService::getInstance()->critical($e->getMessage());
    echo 'Ошибка загрузки конфигурации';
    session_abort();
    exit;
}

$router = new Router();

$routes = require __DIR__ . '/../routes.php';
$routes($router);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

try {
    $router->dispatch($uri, $method);
} catch (Throwable $e) {
    $errorHandler->handleException($e);
}