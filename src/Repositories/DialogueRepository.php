<?php

namespace App\Repositories;

use App\Core\Database;

class DialogueRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findExistingPrivateDialogue(int $user1, int $user2): ?array
    {
        $sql = "SELECT d.id FROM dialogues d
                INNER JOIN dialogue_users du1 ON d.id = du1.dialogue_id
                INNER JOIN dialogue_users du2 ON d.id = du2.dialogue_id
                WHERE d.type = 'private' 
                AND du1.user_id = :user1 
                AND du2.user_id = :user2";

        $result = $this->db->fetchOne($sql, ['user1' => $user1, 'user2' => $user2]);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    public function createPrivateDialogue(int $user1, int $user2): ?int
    {
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO dialogues (type, created_at, updated_at) VALUES ('private', NOW(), NOW())"
            );
            $dialogueId = $this->db->lastInsertId();

            $this->db->execute(
                'INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())',
                ['dialogue_id' => $dialogueId, 'user_id' => $user1]
            );
            $this->db->execute(
                'INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())',
                ['dialogue_id' => $dialogueId, 'user_id' => $user2]
            );

            $this->db->commit();
            return (int)$dialogueId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }
}
