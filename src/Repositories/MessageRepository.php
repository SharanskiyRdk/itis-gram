<?php

namespace App\Repositories;

use App\Core\Database;

class MessageRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function searchMessages(int $userId, string $q, int $limit = 50): array
    {
        $sql = "SELECT m.id, m.content, m.created_at, m.dialogue_id, 
                       u.name as user_name, u.id as user_id
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                INNER JOIN dialogue_users du ON m.dialogue_id = du.dialogue_id
                WHERE du.user_id = :user_id 
                AND m.content ILIKE :q 
                AND m.is_deleted = FALSE
                ORDER BY m.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('q', "%$q%", \PDO::PARAM_STR);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
