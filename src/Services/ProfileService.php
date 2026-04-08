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
        $sql = "SELECT id, name, email, avatar, bio, is_online, last_seen, created_at 
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
        // Получаем старый аватар
        $user = $this->getUser($userId);

        // Загружаем новый аватар
        $avatarPath = $this->fileUploadService->uploadAvatar($file);

        if (!$avatarPath) {
            return null;
        }

        // Обновляем путь в БД
        $sql = "UPDATE users SET avatar = :avatar WHERE id = :id AND is_deleted = FALSE";
        $result = $this->db->execute($sql, [
            'avatar' => $avatarPath,
            'id' => $userId
        ]);

        if (!$result) {
            return null;
        }

        // Удаляем старый аватар
        if ($user && !empty($user['avatar'])) {
            $this->fileUploadService->deleteFile($user['avatar']);
        }

        return $avatarPath;
    }

    public function deleteAvatar(int $userId): bool
    {
        // Получаем пользователя
        $user = $this->getUser($userId);

        if (!$user) {
            return false;
        }

        // Удаляем файл аватара
        if (!empty($user['avatar'])) {
            $this->fileUploadService->deleteFile($user['avatar']);
        }

        // Обновляем БД
        $sql = "UPDATE users SET avatar = NULL WHERE id = :id AND is_deleted = FALSE";
        return $this->db->execute($sql, ['id' => $userId]);
    }
}