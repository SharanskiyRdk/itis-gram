<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Routing\Router;
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Middleware\RequestLoggingMiddleware;
use App\Services\LoggerService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use App\Http\Request as PsrRequestHolder;
use App\Http\Response as PsrResponseHolder;

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
$router->addMiddleware(new RequestLoggingMiddleware());

// Create PSR-7 ServerRequest and store it for use in the app
$psr17 = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17, // ServerRequestFactory
    $psr17, // UriFactory
    $psr17, // UploadedFileFactory
    $psr17  // StreamFactory
);
$serverRequest = $creator->fromGlobals();
PsrRequestHolder::set($serverRequest);

// Start output buffering so we can build a PSR-7 Response after dispatch
ob_start();

$routes = require __DIR__ . '/../routes.php';
if (is_callable($routes)) {
    $routes($router);
} elseif (is_array($routes)) {
    $router->register($routes);
} else {
    throw new RuntimeException('Invalid routes configuration');
}

$psrRequest = $serverRequest;

try {
    $psrResponse = $router->handle($psrRequest);
} catch (Throwable $e) {
    $errorHandler->handleException($e);
    // handleException will exit
}

// Emit PSR-7 response
if (isset($psrResponse) && $psrResponse !== null) {
    foreach ($psrResponse->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    http_response_code($psrResponse->getStatusCode());
    echo $psrResponse->getBody();
}
