<?php
// Простая утилита для выполнения migration.sql через PDO
// Usage: php bin/migrate.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;

chdir(__DIR__ . '/..');

try {
    $config = new Config(__DIR__ . '/..' . '/.env');
} catch (Throwable $e) {
    echo "Ошибка загрузки конфигурации: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$driver = $config->get('DB_DRIVER', 'pgsql');
$host = $config->get('DB_HOST');
$port = $config->get('DB_PORT');
$dbname = $config->get('DB_NAME');
$user = $config->get('DB_USER');
$password = $config->get('DB_PASSWORD');

$path = __DIR__ . '/../migration.sql';
if (!file_exists($path)) {
    echo "migration.sql not found at $path" . PHP_EOL;
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    echo "Не удалось прочитать migration.sql" . PHP_EOL;
    exit(1);
}

try {
    if ($driver === 'sqlite') {
        $dsn = "sqlite:" . __DIR__ . "/../database/{$dbname}.sqlite";
        $pdo = new PDO($dsn);
    } else {
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $host, $port, $dbname);
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
} catch (PDOException $e) {
    echo "Не удалось подключиться к БД: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Простейшее разделение по `;` — подходит для большинства простых миграций
$parts = array_filter(array_map('trim', explode(';', $sql)));

echo "Запуск миграций (" . count($parts) . " команд)..." . PHP_EOL;
$ok = 0;
foreach ($parts as $idx => $part) {
    if ($part === '') continue;
    try {
        $pdo->exec($part);
        $ok++;
        echo sprintf("[%d/%d] OK\n", $idx + 1, count($parts));
    } catch (PDOException $e) {
        echo sprintf("[%d/%d] ERROR: %s\n", $idx + 1, count($parts), $e->getMessage());
    }
}

echo "Миграции выполнены: $ok/" . count($parts) . " успешных команд." . PHP_EOL;

exit(0);
