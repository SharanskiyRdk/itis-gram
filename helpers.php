<?php
// helpers.php

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        $token = csrf_token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('currentUserName')) {
    function currentUserName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }
}

if (!function_exists('currentUserEmail')) {
    function currentUserEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }
}

if (!function_exists('isAuthenticated')) {
    function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        die();
    }
}

if (!function_exists('toast')) {
    function toast(string $message, string $type = 'success'): string
    {
        return json_encode(['toast' => ['message' => $message, 'type' => $type]]);
    }
}

if (!function_exists('getSessionLifetime')) {
    function getSessionLifetime(): int
    {
        return (int)($_ENV['SESSION_LIFETIME'] ?? 3600);
    }
}