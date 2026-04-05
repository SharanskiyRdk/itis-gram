<?php

use App\Routing\Router;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ChatController;
use App\Controllers\SearchController;
use App\Controllers\FileController;

return function (Router $router): void {
    $router->add('GET', '/login', [AuthController::class, 'loginForm']);
    $router->add('POST', '/login', [AuthController::class, 'login']);

    $router->add('GET', '/register', [AuthController::class, 'registerForm']);
    $router->add('POST', '/register', [AuthController::class, 'register']);

    $router->add('POST', '/logout', [AuthController::class, 'logout']);

    $router->add('GET', '/', [ChatController::class, 'index']);
    $router->add('GET', '/profile', [ProfileController::class, 'show']);
    $router->add('GET', '/chat', [ChatController::class, 'show']);

    $router->add('POST', '/chat/send', [ChatController::class, 'send']);
    $router->add('POST', '/chat/create', [ChatController::class, 'create']);
    $router->add('POST', '/chat/delete', [ChatController::class, 'delete']);

    $router->add('GET', '/search/users', [SearchController::class, 'users']);
    $router->add('GET', '/search/messages', [SearchController::class, 'messages']);

    $router->add('POST', '/file/upload', [FileController::class, 'upload']);
};