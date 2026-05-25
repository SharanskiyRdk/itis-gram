<?php

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Request as PsrRequestHolder;
use App\Services\LoggerService;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController
{
    /**
     * @throws NotFoundException
     */
    protected function render(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../../templates/' . $view . '.php';

        if (!file_exists($viewFile)) {
            LoggerService::getInstance()->error("View not found: {$view}");
            throw new NotFoundException("View {$view} not found");
        }

        extract($data);
        require $viewFile;
    }

    #[NoReturn]
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    #[NoReturn]
    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    #[NoReturn]
    protected function redirectBack(string $fallback = '/'): void
    {
        $referer = (string)$this->headerParam('Referer', '');
        if ($referer !== '') {
            $parsedPath = parse_url($referer, PHP_URL_PATH);
            if (is_string($parsedPath) && $parsedPath !== '') {
                $query = parse_url($referer, PHP_URL_QUERY);
                $target = $parsedPath;
                if (is_string($query) && $query !== '') {
                    $target .= '?' . $query;
                }
                $this->redirect($target);
            }
        }

        $this->redirect($fallback);
    }

    protected function csrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    protected function verifyCsrf(): void
    {
        $token = (string)($this->bodyParam('csrf_token', '') ?? '');

        if ($token === '') {
            $token = (string)($this->headerParam('X-CSRF-TOKEN', '') ?? '');
        }

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

    protected function requireAdmin(): void
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            $this->redirect('/login');
        }

        $db = \App\Core\Database::getInstance();
        $row = $db->fetchOne('SELECT role FROM users WHERE id = :id AND is_deleted = FALSE', ['id' => $userId]);

        $role = $row['role'] ?? 'user';
        if ($role !== 'admin' && $role !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            echo '403 Forbidden';
            exit;
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

    protected function request(): ?ServerRequestInterface
    {
        return PsrRequestHolder::get();
    }

    protected function queryParam(string $key, mixed $default = null): mixed
    {
        $request = $this->request();

        if ($request) {
            $queryParams = $request->getQueryParams();
            return $queryParams[$key] ?? $default;
        }

        return $_GET[$key] ?? $default;
    }

    protected function bodyParam(string $key, mixed $default = null): mixed
    {
        $request = $this->request();

        if ($request) {
            $parsedBody = $request->getParsedBody();

            if (is_array($parsedBody)) {
                return $parsedBody[$key] ?? $default;
            }
        }

        return $_POST[$key] ?? $default;
    }

    protected function cookieParam(string $key, mixed $default = null): mixed
    {
        $request = $this->request();

        if ($request) {
            $cookies = $request->getCookieParams();
            return $cookies[$key] ?? $default;
        }

        return $_COOKIE[$key] ?? $default;
    }

    protected function serverParam(string $key, mixed $default = null): mixed
    {
        $request = $this->request();

        if ($request) {
            $serverParams = $request->getServerParams();
            return $serverParams[$key] ?? $default;
        }

        return $_SERVER[$key] ?? $default;
    }

    protected function headerParam(string $key, mixed $default = null): mixed
    {
        $request = $this->request();

        if ($request) {
            $value = $request->getHeaderLine($key);
            return $value !== '' ? $value : $default;
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $_SERVER[$serverKey] ?? $default;
    }

    protected function uploadedFileParam(string $key): ?array
    {
        $request = $this->request();

        if ($request) {
            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles[$key] ?? null;

            if ($file instanceof \Psr\Http\Message\UploadedFileInterface) {
                return [
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'tmp_name' => $file->getStream()->getMetadata('uri'),
                    'error' => $file->getError(),
                    'size' => $file->getSize(),
                ];
            }
        }

        return isset($_FILES[$key]) && is_array($_FILES[$key]) ? $_FILES[$key] : null;
    }

    protected function isAjaxRequest(): bool
    {
        $value = $this->headerParam('X-Requested-With', '');
        return is_string($value) && strtolower($value) === 'xmlhttprequest';
    }
}
