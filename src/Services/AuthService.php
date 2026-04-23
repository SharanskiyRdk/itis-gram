<?php
// App/Services/AuthService.php
namespace App\Services;

use App\Models\User;
use App\Core\Database;

class AuthService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function authenticate(string $email, string $password, string $sessionId, string $ip): ?User
    {
        $user = User::firstWhere('email', $email);

        if ($user && $user->verifyPassword($password)) {
            $this->db->updateUserSession($user->getId(), $sessionId, $ip);

            $this->db->execute(
                "UPDATE users SET is_online = TRUE, last_seen = NOW() WHERE id = :id",
                ['id' => $user->getId()]
            );

            return $user;
        }

        return null;
    }

    public function register(string $name, string $email, string $password): ?User
    {
        $existing = User::firstWhere('email', $email);
        if ($existing && $existing->getId()) {
            return null;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'is_online' => false,
            'is_deleted' => false,
            'is_verified_student' => false,
            'last_seen' => date('Y-m-d H:i:s')
        ]);

        if ($user->save()) {
            return $user;
        }

        return null;
    }

    // AuthService.php
    public function logout(int $userId, string $sessionId): void
    {
        // Очищаем сессию в БД
        $this->db->execute(
            "UPDATE users SET session_id = NULL, session_ip = NULL, is_online = FALSE, last_seen = NOW() WHERE id = :id",
            ['id' => $userId]
        );
    }

    public function restoreSession(string $sessionId, string $ip): ?User
    {
        $data = $this->db->getUserBySession($sessionId, $ip);
        if ($data) {
            return new User([
                'id' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'avatar' => $data['avatar'] ?? null,
                'bio' => $data['bio'] ?? null,
                'last_seen' => $data['last_seen'] ?? date('Y-m-d H:i:s'),
                'session_created_at' => $data['session_created_at'] ?? null
            ]);
        }
        return null;
    }
}