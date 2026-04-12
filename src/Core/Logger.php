<?php

namespace App\Core;

class Logger {
    public static function error(string $message): void {
        $logDir = __DIR__ . '/../../runtime/logs';
        $logFile = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $entry = sprintf(
            "[%s] ERROR: %s%s",
            date('Y-m-d H:i:s'),
            $message,
            PHP_EOL
        );

        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}