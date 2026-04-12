<?php

namespace App\Database;

use App\Core\Logger;
use App\Exceptions\ConfigException;
use App\Exceptions\FileNotFoundException;
use PDO;
use PDOException;
use App\Core\Config;

class Database
{
    private PDO $connection;
    private static ?Database $instance = null;
    private Config $config;

    /**
     * @throws ConfigException
     * @throws FileNotFoundException
     */
    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? self::loadConfig();

        $host = $this->config->get('DB_HOST', 'localhost');
        $port = $this->config->get('DB_PORT', '5432');
        $dbname = $this->config->get('DB_NAME', 'itisgram');
        $user = $this->config->get('DB_USER', 'postgres');
        $password = $this->config->get('DB_PASSWORD', '');

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if ($this->config->get('APP_DEBUG', false)) {
                error_log("Database connected successfully");
            }
        } catch (PDOException $e) {
            Logger::error("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * @throws ConfigException
     * @throws FileNotFoundException
     */
    private static function loadConfig(): Config
    {
        $envPath = __DIR__ . '/../../.env';

        if (!file_exists($envPath)) {
            throw new ConfigException("Env file not found");
        }

        return new Config($envPath);
    }

    public static function getInstance(?Config $config = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function lastInsertId(): int
    {
        return (int)$this->connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function updateUserSession(int $userId, string $sessionId, string $ip): bool
    {
        $sql = "UPDATE users SET session_id = :session_id, session_ip = :ip, session_created_at = NOW() 
            WHERE id = :id";
        return $this->execute($sql, [
            'session_id' => $sessionId,
            'ip' => $ip,
            'id' => $userId
        ]);
    }

    public function getUserBySession(string $sessionId, string $ip): ?array
    {
        $sql = "SELECT id, name, email, avatar, bio, last_seen, session_created_at 
            FROM users 
            WHERE session_id = :session_id AND session_ip = :ip 
            AND session_created_at > NOW() - INTERVAL '1 hour'
            AND is_deleted = FALSE";

        return $this->fetchOne($sql, ['session_id' => $sessionId, 'ip' => $ip]);
    }

    public function clearUserSession(int $userId): bool
    {
        return $this->execute(
            "UPDATE users SET session_id = NULL, session_ip = NULL, is_online = FALSE WHERE id = :id",
            ['id' => $userId]
        );
    }

    public function checkActiveSession(int $userId): bool
    {
        $sql = "SELECT 1 FROM users WHERE id = :id AND session_id IS NOT NULL AND is_online = TRUE";
        return (bool)$this->fetchOne($sql, ['id' => $userId]);
    }
}