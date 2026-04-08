<?php

use App\Routing\Router;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ChatController;
use App\Controllers\SearchController;
use App\Controllers\FileController;

return function (Router $router): void {
    // Auth routes
    $router->add('GET', '/login', [AuthController::class, 'loginForm']);
    $router->add('POST', '/login', [AuthController::class, 'login']);
    $router->add('GET', '/register', [AuthController::class, 'registerForm']);
    $router->add('POST', '/register', [AuthController::class, 'register']);
    $router->add('POST', '/logout', [AuthController::class, 'logout']);

    // Chat routes
    $router->add('GET', '/', [ChatController::class, 'index']);
    $router->add('GET', '/chat', [ChatController::class, 'show']);
    $router->add('POST', '/chat/send', [ChatController::class, 'send']);
    $router->add('POST', '/chat/create', [ChatController::class, 'create']);
    $router->add('POST', '/chat/delete', [ChatController::class, 'delete']);

    // Profile routes
    $router->add('GET', '/profile', [ProfileController::class, 'show']);
    $router->add('POST', '/profile/update', [ProfileController::class, 'update']);
    $router->add('POST', '/profile/avatar', [ProfileController::class, 'avatar']);
    $router->add('POST', '/profile/avatar/delete', [ProfileController::class, 'deleteAvatar']);

    // Search routes
    $router->add('GET', '/search/users', [SearchController::class, 'users']);
    $router->add('GET', '/search/messages', [SearchController::class, 'messages']);

    // File routes
    $router->add('POST', '/file/upload', [FileController::class, 'upload']);
    $router->add('GET', '/file/download', [FileController::class, 'download']);
    $router->add('POST', '/file/delete', [FileController::class, 'delete']);
};