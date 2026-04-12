<?php

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Formatter\LineFormatter;
use App\Core\Config;

class LoggerService
{
    private Logger $logger;
    private static ?LoggerService $instance = null;
    private bool $debugMode;

    private function __construct()
    {
        $this->debugMode = $this->isDebugMode();
        $this->logger = new Logger('itisgram');

        $format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($format, 'Y-m-d H:i:s', true, true);

        // Лог в файл - всегда
        $logFile = __DIR__ . '/../../runtime/logs/app.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $fileHandler = new StreamHandler($logFile, Logger::DEBUG);
        $fileHandler->setFormatter($formatter);
        $this->logger->pushHandler($fileHandler);

        // В режиме debug - вывод в браузерную консоль
        if ($this->debugMode) {
            $consoleHandler = new BrowserConsoleHandler();
            $consoleHandler->setFormatter($formatter);
            $this->logger->pushHandler($consoleHandler);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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

    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->enrichContext($context));
    }

    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $this->enrichContext($context));
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $this->enrichContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->enrichContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->enrichContext($context));
    }

    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $this->enrichContext($context));
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->enrichContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->debugMode) {
            $this->logger->debug($message, $this->enrichContext($context));
        }
    }

    public function logException(\Throwable $e, array $extraContext = []): void
    {
        $context = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->debugMode ? $e->getTraceAsString() : null,
            ...$extraContext
        ];

        $this->error($e->getMessage(), $context);
    }

    private function enrichContext(array $context): array
    {
        $enriched = $context;

        if (isset($_SERVER['REQUEST_URI'])) {
            $enriched['uri'] = $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $enriched['method'] = $_SERVER['REQUEST_METHOD'];
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $enriched['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($_SESSION['user_id'])) {
            $enriched['user_id'] = $_SESSION['user_id'];
        }

        return $enriched;
    }
}