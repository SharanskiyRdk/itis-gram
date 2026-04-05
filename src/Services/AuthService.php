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

    public function authenticate(string $email, string $password): ?array
    {
        $sql = "SELECT id, name, email, password FROM users WHERE email = :email AND is_deleted = 0";
        $user = $this->db->fetchOne($sql, ['email' => $email]);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);

            // Обновляем время последней активности
            $this->db->execute(
                "UPDATE users SET last_seen = NOW(), is_online = 1 WHERE id = :id",
                ['id' => $user['id']]
            );

            return $user;
        }

        return null;
    }

    public function register(string $name, string $email, string $password): ?int
    {
        // Проверяем, существует ли пользователь
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );

        if ($existing) {
            return null;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password, created_at, last_seen) 
                VALUES (:name, :email, :password, NOW(), NOW())";

        $this->db->execute($sql, [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        return $this->db->lastInsertId();
    }
}