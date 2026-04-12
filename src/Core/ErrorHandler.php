<?php

namespace App\Core;

use App\Services\LoggerService;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthException;
use App\Exceptions\ForbiddenException;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

class ErrorHandler
{
    private LoggerService $logger;
    private bool $debugMode;

    public function __construct()
    {
        $this->logger = LoggerService::getInstance();
        $this->debugMode = $this->isDebugMode();
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $this->logger->error("PHP Error: {$message}", [
            'level' => $level,
            'file' => $file,
            'line' => $line
        ]);

        if ($this->debugMode) {
            $this->renderErrorPage(500, "PHP Error: {$message}", [
                'file' => $file,
                'line' => $line
            ]);
        }

        return true;
    }

    public function handleException(Throwable $e): void
    {
        $this->logger->logException($e);

        $statusCode = $this->getStatusCode($e);

        if ($statusCode >= 500) {
            $this->logger->critical("Critical error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        if ($this->isAjaxRequest()) {
            $this->renderJsonError($e, $statusCode);
            return;
        }

        $this->renderErrorPage($statusCode, $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->critical("Fatal error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            if (!$this->isAjaxRequest()) {
                $this->renderErrorPage(500, "Fatal error occurred", [
                    'message' => $this->debugMode ? $error['message'] : null
                ]);
            }
        }
    }

    private function getStatusCode(Throwable $e): int
    {
        if ($e instanceof NotFoundException) {
            return 404;
        }

        if ($e instanceof AuthException) {
            return 401;
        }

        if ($e instanceof ForbiddenException) {
            return 403;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AppException) {
            return $e->getCode() ?: 500;
        }

        return 500;
    }

    #[NoReturn]
    private function renderErrorPage(int $statusCode, string $message, array $context = []): void
    {
        http_response_code($statusCode);

        $errorTemplate = __DIR__ . "/../../templates/errors/{$statusCode}.php";

        if (file_exists($errorTemplate)) {
            $debugMode = $this->debugMode;
            $errorMessage = $message;
            $errorContext = $context;
            require $errorTemplate;
        } else {
            $this->renderDefaultErrorPage($statusCode, $message, $context);
        }

        exit;
    }

    private function renderDefaultErrorPage(int $statusCode, string $message, array $context): void
    {
        $titles = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page Not Found',
            419 => 'Session Expired',
            422 => 'Validation Error',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];

        $title = $titles[$statusCode] ?? 'Error';
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= $statusCode ?> - <?= $title ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    text-align: center;
                    max-width: 500px;
                }
                .error-code {
                    font-size: 120px;
                    font-weight: 800;
                    color: rgba(255,255,255,0.3);
                    margin-bottom: 20px;
                }
                .error-title {
                    font-size: 28px;
                    color: white;
                    margin-bottom: 16px;
                }
                .error-message {
                    color: rgba(255,255,255,0.8);
                    margin-bottom: 32px;
                    line-height: 1.6;
                }
                .error-debug {
                    background: rgba(0,0,0,0.3);
                    border-radius: 12px;
                    padding: 16px;
                    text-align: left;
                    font-family: monospace;
                    font-size: 12px;
                    color: #ffcc00;
                    margin-top: 20px;
                    overflow-x: auto;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: white;
                    color: #667eea;
                    text-decoration: none;
                    border-radius: 12px;
                    font-weight: 600;
                    transition: transform 0.2s;
                }
                .btn:hover {
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
        <div class="error-container">
            <div class="error-code"><?= $statusCode ?></div>
            <div class="error-title"><?= htmlspecialchars($title) ?></div>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
            <a href="/" class="btn">Вернуться на главную</a>

            <?php if ($this->debugMode && !empty($context)): ?>
                <div class="error-debug">
                    <strong>Debug Information:</strong><br><br>
                    <?php foreach ($context as $key => $value): ?>
                        <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars((string)$value) ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        </body>
        </html>
        <?php
    }

    #[NoReturn]
    private function renderJsonError(Throwable $e, int $statusCode): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);

        $response = [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $statusCode
        ];

        if ($e instanceof ValidationException) {
            $response['errors'] = $e->getErrors();
        }

        if ($this->debugMode) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function isDebugMode(): bool
    {
        try {
            $config = new Config(__DIR__ . '/../../.env');
            return (bool)$config->get('APP_DEBUG', false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}