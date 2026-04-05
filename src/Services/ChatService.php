<?php

namespace App\Services;

use App\Database\Database;

class ChatService
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getUserDialogues(int $userId): array
    {
        $sql = "SELECT d.*, 
                (SELECT content FROM messages WHERE dialogue_id = d.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages WHERE dialogue_id = d.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM dialogues d
                INNER JOIN dialogue_users du ON d.id = du.dialogue_id
                WHERE du.user_id = :user_id
                ORDER BY d.updated_at DESC";

        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    public function getDialogue(int $dialogueId): ?array
    {
        $sql = "SELECT * FROM dialogues WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $dialogueId]);
    }

    public function canAccessDialogue(int $dialogueId, int $userId): bool
    {
        $sql = "SELECT 1 FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id = :user_id";
        return (bool)$this->db->fetchOne($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId
        ]);
    }

    public function getMessages(int $dialogueId, int $userId): array
    {
        $sql = "SELECT m.*, u.name as user_name, u.avatar
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                WHERE m.dialogue_id = :dialogue_id AND m.is_deleted = 0
                ORDER BY m.created_at ASC
                LIMIT 100";

        $messages = $this->db->fetchAll($sql, ['dialogue_id' => $dialogueId]);

        // Отмечаем сообщения как прочитанные
        $this->markMessagesAsRead($dialogueId, $userId);

        return $messages;
    }

    public function sendMessage(int $dialogueId, int $userId, string $content): ?int
    {
        $sql = "INSERT INTO messages (dialogue_id, user_id, content, created_at) 
                VALUES (:dialogue_id, :user_id, :content, NOW())";

        $this->db->execute($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId,
            'content' => $content
        ]);

        $messageId = $this->db->lastInsertId();

        // Обновляем время последнего сообщения в диалоге
        $this->db->execute(
            "UPDATE dialogues SET updated_at = NOW() WHERE id = :id",
            ['id' => $dialogueId]
        );

        return $messageId;
    }

    public function deleteMessage(int $messageId, int $userId): bool
    {
        // Проверяем, что пользователь является автором сообщения
        $sql = "SELECT user_id FROM messages WHERE id = :id";
        $message = $this->db->fetchOne($sql, ['id' => $messageId]);

        if (!$message || $message['user_id'] != $userId) {
            return false;
        }

        return $this->db->execute(
            "UPDATE messages SET is_deleted = 1 WHERE id = :id",
            ['id' => $messageId]
        );
    }

    public function createPrivateChat(int $userId1, int $userId2): ?int
    {
        // Проверяем, существует ли уже личный чат между пользователями
        $sql = "SELECT d.id FROM dialogues d
                INNER JOIN dialogue_users du1 ON d.id = du1.dialogue_id
                INNER JOIN dialogue_users du2 ON d.id = du2.dialogue_id
                WHERE d.type = 'private' 
                AND du1.user_id = :user1 
                AND du2.user_id = :user2";

        $existing = $this->db->fetchOne($sql, ['user1' => $userId1, 'user2' => $userId2]);

        if ($existing) {
            return $existing['id'];
        }

        // Создаем диалог
        $this->db->execute(
            "INSERT INTO dialogues (type, created_at, updated_at) VALUES ('private', NOW(), NOW())"
        );
        $dialogueId = $this->db->lastInsertId();

        // Добавляем участников
        $this->db->execute(
            "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())",
            ['dialogue_id' => $dialogueId, 'user_id' => $userId1]
        );
        $this->db->execute(
            "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())",
            ['dialogue_id' => $dialogueId, 'user_id' => $userId2]
        );

        return $dialogueId;
    }

    public function createGroupChat(string $title, int $createdBy): ?int
    {
        $this->db->execute(
            "INSERT INTO dialogues (type, title, created_by, created_at, updated_at) 
             VALUES ('group', :title, :created_by, NOW(), NOW())",
            ['title' => $title, 'created_by' => $createdBy]
        );

        $dialogueId = $this->db->lastInsertId();

        // Добавляем создателя в чат
        $this->db->execute(
            "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())",
            ['dialogue_id' => $dialogueId, 'user_id' => $createdBy]
        );

        return $dialogueId;
    }

    private function markMessagesAsRead(int $dialogueId, int $userId): void
    {
        $this->db->execute(
            "UPDATE dialogue_users SET last_read_at = NOW() 
             WHERE dialogue_id = :dialogue_id AND user_id = :user_id",
            ['dialogue_id' => $dialogueId, 'user_id' => $userId]
        );
    }
}