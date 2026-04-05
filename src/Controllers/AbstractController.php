<?php

namespace App\Controllers;

abstract class AbstractController
{
    protected function render(string $view, array $data = []): void
    {
        $data['csrf_token'] = $this->csrfToken();
        extract($data);

        $viewFile = __DIR__ . '/../../templates/' . $view . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
            return;
        }

        echo 'View not found: ' . $view;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function csrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            $this->json(['error' => 'CSRF token mismatch'], 419);
        }
    }

    protected function requireAuth(): void
    {
        if (!$this->currentUserId()) {
            $this->redirect('/login');
        }
    }

    protected function currentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function currentUserName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    protected function currentUserEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }
}