<?php

use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ChatController;
use App\Controllers\SearchController;
use App\Controllers\FileController;
use App\Routing\Router;

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
    $router->add('POST', '/chat/clear', [ChatController::class, 'clear']);
    $router->add('POST', '/chat/block', [ChatController::class, 'block']);
    $router->add('POST', '/chat/block-clear', [ChatController::class, 'blockAndClear']);
    $router->add('POST', '/chat/unblock', [ChatController::class, 'unblock']);
    $router->add('POST', '/chat/group/update', [ChatController::class, 'updateGroup']);
    $router->add('GET', '/chat/content', [ChatController::class, 'getChatContent']);
    $router->add('GET', '/chat/read-status', [ChatController::class, 'readStatus']);

    // Profile routes
    $router->add('GET', '/profile', [ProfileController::class, 'show']);
    $router->add('POST', '/profile/update', [ProfileController::class, 'update']);
    $router->add('POST', '/profile/avatar', [ProfileController::class, 'avatar']);
    $router->add('POST', '/profile/avatar/delete', [ProfileController::class, 'deleteAvatar']);
    $router->add('POST', '/profile/support', [ProfileController::class, 'support']);
    $router->add('POST', '/profile/verify-request', [ProfileController::class, 'verifyRequest']);
    $router->add('GET', '/profile/card', [ProfileController::class, 'card']);
    $router->add('POST', '/profile/friend-toggle', [ProfileController::class, 'friendToggle']);

    // Search routes
    $router->add('GET', '/search/users', [SearchController::class, 'users']);
    $router->add('GET', '/search/messages', [SearchController::class, 'messages']);
    $router->add('POST', '/search/users/chat', [SearchController::class, 'createChat']);

    // File routes
    $router->add('POST', '/file/upload', [FileController::class, 'upload']);
    $router->add('GET', '/file/download', [FileController::class, 'download']);
    $router->add('POST', '/file/delete', [FileController::class, 'delete']);

    // Admin routes
    $router->add('GET', '/admin', [\App\Controllers\AdminController::class, 'index']);
    $router->add('GET', '/admin/tickets', [\App\Controllers\AdminController::class, 'tickets']);
    $router->add('POST', '/admin/tickets/resolve', [\App\Controllers\AdminController::class, 'resolveTicket']);
    $router->add('GET', '/admin/users', [\App\Controllers\AdminController::class, 'users']);
    $router->add('POST', '/admin/users/role', [\App\Controllers\AdminController::class, 'changeUserRole']);
};