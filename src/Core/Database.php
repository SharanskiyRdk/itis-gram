<?php
// App/Core/Database.php
namespace App\Core;

use App\Exceptions\DatabaseException;
use PDO;
use PDOException;
use App\Services\LoggerService;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private Config $config;

    private function __construct()
    {
        $this->config = new Config(__DIR__ . '/../../.env');
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $driver = $this->config->get('DB_DRIVER', 'pgsql');
        $host = $this->config->get('DB_HOST', 'localhost');
        $port = $this->config->get('DB_PORT', '5432');
        $dbname = $this->config->get('DB_NAME', 'itisgram');
        $user = $this->config->get('DB_USER', 'postgres');
        $password = $this->config->get('DB_PASSWORD', '');

        try {
            $dsn = match($driver) {
                'pgsql' => "pgsql:host=$host;port=$port;dbname=$dbname",
                'sqlite' => "sqlite:" . __DIR__ . "/../../database/{$dbname}.sqlite",
                default => throw new \Exception("Unsupported driver: $driver")
            };

            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            LoggerService::getInstance()->error("Database connection failed: " . $e->getMessage());
            throw new DatabaseException("Database connection failed", $e->getCode(), $e);
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function execute(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            LoggerService::getInstance()->error("Query failed: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Query failed", ['sql' => $sql], $e);
        }
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

    public function fetchAll(string $sql, array $params = [], ?string $className = null): array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            if ($className) {
                return $stmt->fetchAll(PDO::FETCH_CLASS, $className);
            }

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            LoggerService::getInstance()->error("FetchAll failed: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("FetchAll failed", ['sql' => $sql], $e);
        }
    }

    public function fetchOne(string $sql, array $params = [], ?string $className = null)
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            if ($className) {
                $stmt->setFetchMode(PDO::FETCH_CLASS, $className);
                return $stmt->fetch();
            }

            return $stmt->fetch();
        } catch (PDOException $e) {
            LoggerService::getInstance()->error("FetchOne failed: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("FetchOne failed", ['sql' => $sql], $e);
        }
    }

    public function checkActiveSession(int $userId): bool
    {
        $sql = "SELECT 1 FROM users WHERE id = :id AND session_id IS NOT NULL AND is_online = TRUE";
        $result = $this->fetchOne($sql, ['id' => $userId]);
        return !empty($result);
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
}