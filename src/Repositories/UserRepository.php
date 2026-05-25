<?php

namespace App\Repositories;

use App\Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function searchUsers(string $q, int $excludeUserId = 0, int $limit = 20): array
    {
        $sql = "SELECT id, name, email, avatar, is_online 
                FROM users 
                WHERE (name ILIKE :q OR email ILIKE :q) 
                AND id != :user_id 
                AND is_deleted = FALSE
                LIMIT :limit";


        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue('q', "%$q%", \PDO::PARAM_STR);
        $stmt->bindValue('user_id', $excludeUserId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, name, email, avatar, bio, is_online, last_seen, student_group, role FROM users WHERE id = :id AND is_deleted = FALSE',
            ['id' => $id]
        );
    }

    public function getFriendIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT friend_id FROM friendships WHERE user_id = :user_id',
            ['user_id' => $userId]
        );

        return array_map(static fn(array $row) => (int)$row['friend_id'], $rows);
    }

    public function isFriend(int $userId, int $friendId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT 1 FROM friendships WHERE user_id = :user_id AND friend_id = :friend_id LIMIT 1',
            ['user_id' => $userId, 'friend_id' => $friendId]
        );

        return !empty($row);
    }

    public function addFriendship(int $userId, int $friendId): bool
    {
        if ($userId === $friendId) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'INSERT INTO friendships (user_id, friend_id, created_at) VALUES (:user_id, :friend_id, NOW()) ON CONFLICT (user_id, friend_id) DO NOTHING',
                ['user_id' => $userId, 'friend_id' => $friendId]
            );
            $this->db->execute(
                'INSERT INTO friendships (user_id, friend_id, created_at) VALUES (:user_id, :friend_id, NOW()) ON CONFLICT (user_id, friend_id) DO NOTHING',
                ['user_id' => $friendId, 'friend_id' => $userId]
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function removeFriendship(int $userId, int $friendId): bool
    {
        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'DELETE FROM friendships WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id)',
                ['user_id' => $userId, 'friend_id' => $friendId]
            );
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function countMessages(int $userId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS count FROM messages WHERE user_id = :user_id AND is_deleted = FALSE',
            ['user_id' => $userId]
        );
        return (int)($row['count'] ?? 0);
    }

    public function countDialogues(int $userId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS count FROM dialogue_users WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
        return (int)($row['count'] ?? 0);
    }
}
