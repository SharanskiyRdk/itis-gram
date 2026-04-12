<?php

namespace App\Services;

use App\Database\Database;

class ProfileService
{
    private Database $db;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->db = new Database();
        $this->fileUploadService = new FileUploadService();
    }

    public function getUser(int $userId): ?array
    {
        $sql = "SELECT id, name, email, avatar, bio, is_online, last_seen, created_at,
                       is_verified_student, student_group, is_banned
                FROM users 
                WHERE id = ? AND is_deleted = FALSE";
        return $this->db->fetchOne($sql, [$userId]);
    }

    public function updateProfile(int $userId, string $name, ?string $bio): bool
    {
        $sql = "UPDATE users SET name = :name, bio = :bio WHERE id = :id AND is_deleted = FALSE";
        return $this->db->execute($sql, [
            'name' => $name,
            'bio' => $bio,
            'id' => $userId
        ]);
    }

    public function updateAvatar(int $userId, array $file): ?string
    {
        $user = $this->getUser($userId);
        $avatarPath = $this->fileUploadService->uploadAvatar($file);
        if (!$avatarPath) return null;

        $sql = "UPDATE users SET avatar = :avatar WHERE id = :id AND is_deleted = FALSE";
        $result = $this->db->execute($sql, ['avatar' => $avatarPath, 'id' => $userId]);

        if (!$result) return null;
        if ($user && !empty($user['avatar'])) {
            $this->fileUploadService->deleteFile($user['avatar']);
        }
        return $avatarPath;
    }

    public function deleteAvatar(int $userId): bool
    {
        $user = $this->getUser($userId);
        if (!$user) return false;
        if (!empty($user['avatar'])) {
            $this->fileUploadService->deleteFile($user['avatar']);
        }
        return $this->db->execute("UPDATE users SET avatar = NULL WHERE id = :id", ['id' => $userId]);
    }
    public function createSupportTicket(int $userId, string $subject, string $message): ?int
    {
        $sql = "INSERT INTO support_tickets (user_id, subject, message, created_at, updated_at) 
                VALUES (:user_id, :subject, :message, NOW(), NOW())";
        $this->db->execute($sql, [
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message
        ]);
        return $this->db->lastInsertId();
    }

    public function getUserTickets(int $userId): array
    {
        $sql = "SELECT * FROM support_tickets WHERE user_id = :user_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    public function requestStudentVerification(int $userId, string $studentGroup): bool
    {
        // Создаём обращение на верификацию
        $subject = "Запрос на подтверждение статуса студента ИТИС";
        $message = "Группа: " . $studentGroup . "\n\nПрошу подтвердить мой статус студента ИТИС.";
        $this->createSupportTicket($userId, $subject, $message);
        return true;
    }

    public function getVerificationStatus(int $userId): array
    {
        $sql = "SELECT is_verified_student, student_group FROM users WHERE id = :id";
        $result = $this->db->fetchOne($sql, ['id' => $userId]);

        if ($result) {
            return [
                'is_verified' => (bool)$result['is_verified_student'],
                'student_group' => $result['student_group']
            ];
        }

        return [
            'is_verified' => false,
            'student_group' => null
        ];
    }
}