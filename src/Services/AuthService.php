<?php

namespace App\Services;

use App\Database\Database;

class AuthService
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function authenticate(string $email, string $password, string $sessionId, string $ip): ?array
    {
        $sql = "SELECT id, name, email, password FROM users WHERE email = :email AND is_deleted = FALSE";
        $user = $this->db->fetchOne($sql, ['email' => $email]);

        if ($user && password_verify($password, $user['password'])) {
            // Проверяем, не зашел ли уже кто-то с этого аккаунта
            if ($this->db->checkActiveSession($user['id'])) {
                return ['error' => 'session_conflict'];
            }

            unset($user['password']);

            $this->db->updateUserSession($user['id'], $sessionId, $ip);

            $this->db->execute(
                "UPDATE users SET last_seen = NOW(), is_online = TRUE WHERE id = :id",
                ['id' => $user['id']]
            );

            return $user;
        }

        return null;
    }

    public function restoreSession(string $sessionId, string $ip): ?array
    {
        return $this->db->getUserBySession($sessionId, $ip);
    }

    public function register(string $name, string $email, string $password): ?int
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email AND is_deleted = FALSE",
            ['email' => $email]
        );

        if ($existing) {
            return null;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password, created_at, last_seen, is_deleted, is_online) 
                VALUES (:name, :email, :password, NOW(), NOW(), FALSE, FALSE)";

        $this->db->execute($sql, [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        return $this->db->lastInsertId();
    }

    public function logout(int $userId, string $sessionId): void
    {
        $this->db->clearUserSession($userId);
    }
}