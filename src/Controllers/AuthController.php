<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;

class AuthController extends AbstractController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function loginForm(): void
    {
        if ($this->currentUserId()) {
            $this->redirect('/');
        }

        $this->render('auth/login');
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = $this->validateLogin($email, $password);

        if (!empty($errors)) {
            $this->render('auth/login', [
                'error' => implode('; ', $errors),
                'email' => $email,
            ]);
            return;
        }

        $user = $this->authService->authenticate($email, $password);

        if (!$user) {
            $this->render('auth/login', [
                'error' => 'Неверный email или пароль',
                'email' => $email,
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        $this->redirect('/');
    }

    public function registerForm(): void
    {
        if ($this->currentUserId()) {
            $this->redirect('/');
        }

        $this->render('auth/register');
    }

    public function register(): void
    {
        $this->verifyCsrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = $this->validateRegistration($name, $email, $password);

        if (!empty($errors)) {
            $this->render('auth/register', [
                'error' => implode('; ', $errors),
                'name' => $name,
                'email' => $email,
            ]);
            return;
        }

        $userId = $this->authService->register($name, $email, $password);

        if (!$userId) {
            $this->render('auth/register', [
                'error' => 'Пользователь с таким email уже существует',
                'name' => $name,
                'email' => $email,
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        $this->redirect('/');
    }

    public function logout(): void
    {
        $this->verifyCsrf();

        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->redirect('/login');
    }

    private function validateLogin(string $email, string $password): array
    {
        $errors = [];

        if ($email === '') {
            $errors[] = 'Email обязателен';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email';
        }

        if ($password === '') {
            $errors[] = 'Пароль обязателен';
        } elseif (mb_strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не короче 6 символов';
        }

        return $errors;
    }

    private function validateRegistration(string $name, string $email, string $password): array
    {
        $errors = [];

        if ($name === '') {
            $errors[] = 'Имя обязательно';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Имя должно быть от 2 до 100 символов';
        }

        if ($email === '') {
            $errors[] = 'Email обязателен';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email';
        }

        if ($password === '') {
            $errors[] = 'Пароль обязателен';
        } elseif (mb_strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не короче 6 символов';
        }

        return $errors;
    }
}