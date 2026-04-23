<?php

namespace App\Services;

use App\Models\Dialogue;
use App\Models\Message;
use App\Models\User;
use App\Core\Database;

class ChatService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Получить диалоги пользователя
     */
    public function getUserDialogues(int $userId): array
    {
        $sql = "SELECT d.*, 
                (SELECT content FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM dialogues d
                INNER JOIN dialogue_users du ON d.id = du.dialogue_id
                WHERE du.user_id = :user_id AND d.is_deleted = FALSE
                ORDER BY d.updated_at DESC";

        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    /**
     * Получить диалог по ID (возвращает массив для совместимости)
     */
    public function getDialogue(int $dialogueId): ?array
    {
        $sql = "SELECT * FROM dialogues WHERE id = :id AND is_deleted = FALSE";
        return $this->db->fetchOne($sql, ['id' => $dialogueId]);
    }

    /**
     * Проверить доступ пользователя к диалогу
     */
    public function canAccessDialogue(int $dialogueId, int $userId): bool
    {
        $sql = "SELECT 1 FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id = :user_id";
        return (bool)$this->db->fetchOne($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId
        ]);
    }

    /**
     * Получить сообщения диалога
     */
    public function getMessages(int $dialogueId, int $userId): array
    {
        $sql = "SELECT m.*, u.name as user_name, u.avatar
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                WHERE m.dialogue_id = :dialogue_id AND m.is_deleted = FALSE
                ORDER BY m.created_at ASC
                LIMIT 100";

        $messages = $this->db->fetchAll($sql, ['dialogue_id' => $dialogueId]);

        // Отмечаем сообщения как прочитанные
        $this->markMessagesAsRead($dialogueId, $userId);

        return $messages;
    }

    /**
     * Отправить сообщение (возвращает ID сообщения)
     */
    public function sendMessage(int $dialogueId, int $userId, string $content): ?int
    {
        try {
            // Проверяем доступ к диалогу
            if (!$this->canAccessDialogue($dialogueId, $userId)) {
                return null;
            }

            // Вставляем сообщение
            $sql = "INSERT INTO messages (dialogue_id, user_id, content, created_at, is_deleted) 
                    VALUES (:dialogue_id, :user_id, :content, NOW(), FALSE)";

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

        } catch (\Exception $e) {
            \App\Services\LoggerService::getInstance()->error("Failed to send message", [
                'dialogue_id' => $dialogueId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Удалить сообщение
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        // Проверяем, что пользователь является автором сообщения
        $sql = "SELECT user_id FROM messages WHERE id = :id AND is_deleted = FALSE";
        $message = $this->db->fetchOne($sql, ['id' => $messageId]);

        if (!$message || $message['user_id'] != $userId) {
            return false;
        }

        return $this->db->execute(
            "UPDATE messages SET is_deleted = TRUE WHERE id = :id",
            ['id' => $messageId]
        );
    }

    /**
     * Создать личный чат
     */
    public function createPrivateChat(int $userId1, int $userId2): ?int
    {
        // Проверяем, существует ли уже личный чат между пользователями
        $sql = "SELECT d.id FROM dialogues d
                INNER JOIN dialogue_users du1 ON d.id = du1.dialogue_id
                INNER JOIN dialogue_users du2 ON d.id = du2.dialogue_id
                WHERE d.type = 'private' 
                AND du1.user_id = :user1 
                AND du2.user_id = :user2
                AND d.is_deleted = FALSE";

        $existing = $this->db->fetchOne($sql, [
            'user1' => $userId1,
            'user2' => $userId2
        ]);

        if ($existing) {
            return (int)$existing['id'];
        }

        // Создаем диалог
        $this->db->beginTransaction();

        try {
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

            $this->db->commit();
            return $dialogueId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            \App\Services\LoggerService::getInstance()->error("Failed to create private chat", [
                'user1' => $userId1,
                'user2' => $userId2,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Создать групповой чат
     */
    public function createGroupChat(string $title, int $createdBy): ?int
    {
        $this->db->beginTransaction();

        try {
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

            $this->db->commit();
            return $dialogueId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            \App\Services\LoggerService::getInstance()->error("Failed to create group chat", [
                'title' => $title,
                'created_by' => $createdBy,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить участников диалога
     */
    public function getDialogueParticipants(int $dialogueId): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.avatar, u.is_online, u.last_seen
                FROM users u
                INNER JOIN dialogue_users du ON u.id = du.user_id
                WHERE du.dialogue_id = :dialogue_id AND u.is_deleted = FALSE";

        return $this->db->fetchAll($sql, ['dialogue_id' => $dialogueId]);
    }

    /**
     * Получить количество участников диалога
     */
    public function getDialogueMembersCount(int $dialogueId): int
    {
        $sql = "SELECT COUNT(*) as count FROM dialogue_users WHERE dialogue_id = :dialogue_id";
        $result = $this->db->fetchOne($sql, ['dialogue_id' => $dialogueId]);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Отметить сообщения как прочитанные
     */
    private function markMessagesAsRead(int $dialogueId, int $userId): void
    {
        $this->db->execute(
            "UPDATE dialogue_users SET last_read_at = NOW() 
             WHERE dialogue_id = :dialogue_id AND user_id = :user_id",
            ['dialogue_id' => $dialogueId, 'user_id' => $userId]
        );
    }
}