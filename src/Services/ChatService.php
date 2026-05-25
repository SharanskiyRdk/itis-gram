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
        $sql = 'SELECT d.*, '
            . "CASE WHEN d.type = 'private' THEN "
            . 'COALESCE((SELECT u.name FROM dialogue_users du_other INNER JOIN users u ON u.id = du_other.user_id '
            . 'WHERE du_other.dialogue_id = d.id AND du_other.user_id <> :user_id LIMIT 1), d.title) '
            . 'ELSE d.title END AS display_title, '
            . "CASE WHEN d.type = 'private' THEN "
            . '(SELECT u.avatar FROM dialogue_users du_other INNER JOIN users u ON u.id = du_other.user_id '
            . 'WHERE du_other.dialogue_id = d.id AND du_other.user_id <> :user_id LIMIT 1) '
            . 'ELSE d.avatar END AS display_avatar, '
            . '(SELECT content FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC LIMIT 1) AS last_message, '
            . '(SELECT user_id FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC LIMIT 1) AS last_message_user_id, '
            . '(SELECT created_at FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC LIMIT 1) AS last_message_time '
            . ', CASE WHEN EXISTS ('
            . 'SELECT 1 FROM dialogue_users duf '
            . 'INNER JOIN friendships f ON f.friend_id = duf.user_id '
            . 'WHERE duf.dialogue_id = d.id AND f.user_id = :user_id'
            . ') THEN 1 ELSE 0 END AS has_friend '
            . 'FROM dialogues d '
            . 'INNER JOIN dialogue_users du ON d.id = du.dialogue_id '
            . 'WHERE du.user_id = :user_id AND d.is_deleted = FALSE '
            . 'ORDER BY d.updated_at DESC';

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
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue) {
            return false;
        }

        if (($dialogue['type'] ?? '') === 'group') {
            $roleRow = $this->db->fetchOne('SELECT role FROM users WHERE id = :id AND is_deleted = FALSE', ['id' => $userId]);
            $role = $roleRow['role'] ?? 'user';
            if (in_array($role, ['admin', 'superadmin'], true)) {
                return true;
            }
        }

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
        $messages = $this->getMessagesQuery($dialogueId, $userId);

        $this->markMessagesAsRead($dialogueId, $userId);

        return $messages;
    }

    /**
     * Получить новые сообщения после указанного ID
     */
    public function getMessagesAfter(int $dialogueId, int $userId, int $afterMessageId): array
    {
        $sql = "SELECT m.*, u.name as user_name, u.avatar,
                   " . $this->messageReadStateSql() . "
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                                INNER JOIN dialogue_users du_visible ON du_visible.dialogue_id = m.dialogue_id AND du_visible.user_id = :user_id
                WHERE m.dialogue_id = :dialogue_id
                  AND m.is_deleted = FALSE
                                    AND m.created_at > COALESCE(du_visible.last_cleared_at, TO_TIMESTAMP(0))
                  AND m.id > :after_id
                ORDER BY m.created_at ASC
                LIMIT 100";

        $messages = $this->db->fetchAll($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId,
            'after_id' => $afterMessageId,
        ]);

        if (!empty($messages)) {
            $this->markMessagesAsRead($dialogueId, $userId);
        }

        return $messages;
    }

    /**
     * Отправить сообщение (возвращает ID сообщения)
     */
    public function sendMessage(int $dialogueId, int $userId, string $content, ?array $attachment = null): ?int
    {
        try {
            if (!$this->canAccessDialogue($dialogueId, $userId)) {
                return null;
            }

            $dialogue = $this->getDialogue($dialogueId);
            if (($dialogue['type'] ?? '') === 'private' && $this->isDialogueBlockedForUser($dialogueId, $userId)) {
                return null;
            }

            $filePath = null;
            $fileType = null;
            $fileSize = null;

            if ($attachment && !empty($attachment['tmp_name'])) {
                $uploadService = new FileUploadService();
                $fileType = $uploadService->detectMessageFileType($attachment);
                $filePath = $uploadService->uploadMessageFile($attachment, $fileType);

                if (!$filePath) {
                    return null;
                }

                $fileSize = (int)($attachment['size'] ?? 0);
            }


            $fields = ['dialogue_id', 'user_id', 'content', 'created_at', 'is_deleted'];
            $values = [':dialogue_id', ':user_id', ':content', 'NOW()', 'FALSE'];
            $params = [
                'dialogue_id' => $dialogueId,
                'user_id' => $userId,
                'content' => $content,
            ];

            if ($filePath !== null) {
                $fields[] = 'file_path';
                $fields[] = 'file_type';
                $fields[] = 'file_size';
                $values[] = ':file_path';
                $values[] = ':file_type';
                $values[] = ':file_size';
                $params['file_path'] = $filePath;
                $params['file_type'] = $fileType;
                $params['file_size'] = $fileSize;
            }

            $sql = 'INSERT INTO messages (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';

            $this->db->execute($sql, $params);

            $messageId = $this->db->lastInsertId();


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

        if ($this->hasBlockRelation($userId1, $userId2) || $this->hasBlockRelation($userId2, $userId1)) {
            return null;
        }


        $this->db->beginTransaction();

        try {
            $this->db->execute(
                "INSERT INTO dialogues (type, created_at, updated_at) VALUES ('private', NOW(), NOW())"
            );
            $dialogueId = $this->db->lastInsertId();


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
    public function createGroupChat(string $title, int $createdBy, ?string $groupCode = null): ?int
    {
        $this->db->beginTransaction();

        try {
            $this->db->execute(
                "INSERT INTO dialogues (type, title, group_code, created_by, created_at, updated_at) 
                 VALUES ('group', :title, :group_code, :created_by, NOW(), NOW())",
                ['title' => $title, 'group_code' => $groupCode ?? $title, 'created_by' => $createdBy]
            );

            $dialogueId = $this->db->lastInsertId();


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

    public function findGroupDialogueByTitle(string $title): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM dialogues WHERE type = 'group' AND COALESCE(group_code, title) = :group_code AND is_deleted = FALSE LIMIT 1",
            ['group_code' => $title]
        );
    }

    public function ensureGroupDialogueMembership(string $title, int $userId, ?int $createdBy = null): ?int
    {
        $dialogue = $this->findGroupDialogueByTitle($title);

        if (!$dialogue) {
            $dialogueId = $this->createGroupChat($title, $createdBy ?? $userId, $title);
            if (!$dialogueId) {
                return null;
            }
            $dialogue = ['id' => $dialogueId];
        }

        $this->db->execute(
            "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW()) ON CONFLICT (dialogue_id, user_id) DO NOTHING",
            ['dialogue_id' => (int)$dialogue['id'], 'user_id' => $userId]
        );

        return (int)$dialogue['id'];
    }

    public function canManageGroupDialogue(int $dialogueId, int $userId): bool
    {
        $sql = "SELECT d.id
                FROM dialogues d
                INNER JOIN users u ON u.id = :user_id
                WHERE d.id = :dialogue_id
                  AND d.type = 'group'
                  AND d.is_deleted = FALSE
                  AND (d.created_by = :user_id OR u.role IN ('admin', 'superadmin'))";

        return (bool)$this->db->fetchOne($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId,
        ]);
    }

    public function updateGroupDialogue(int $dialogueId, int $userId, ?string $title = null, ?string $avatar = null): bool
    {
        if (!$this->canManageGroupDialogue($dialogueId, $userId)) {
            return false;
        }

        $updates = [];
        $params = ['id' => $dialogueId];

        if ($title !== null) {
            $updates[] = 'title = :title';
            $params['title'] = $title;
        }

        if ($avatar !== null) {
            $updates[] = 'avatar = :avatar';
            $params['avatar'] = $avatar;
        }

        if (empty($updates)) {
            return true;
        }

        $updates[] = 'updated_at = NOW()';

        return $this->db->execute(
            'UPDATE dialogues SET ' . implode(', ', $updates) . ' WHERE id = :id',
            $params
        );
    }

    public function isAcademicGroupDialogue(int $dialogueId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'group') {
            return false;
        }

        $title = (string)($dialogue['title'] ?? '');
        return (bool)preg_match('/^11-\d{3}[a-z]?$/i', $title);
    }

    public function isGroupTitleEditable(int $dialogueId): bool
    {
        return !$this->isAcademicGroupDialogue($dialogueId);
    }

    /**
     * Получить участников диалога
     */
    public function getDialogueParticipants(int $dialogueId): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.avatar, u.bio, u.is_online, u.last_seen, u.student_group, u.is_verified_student, u.role
                FROM users u
                INNER JOIN dialogue_users du ON u.id = du.user_id
                WHERE du.dialogue_id = :dialogue_id AND u.is_deleted = FALSE";

        return $this->db->fetchAll($sql, ['dialogue_id' => $dialogueId]);
    }

    public function getOutgoingMessageReadStates(int $dialogueId, int $userId): array
    {
        $sql = "SELECT m.id, " . $this->messageReadStateSql() . "
            FROM messages m
            INNER JOIN dialogue_users du_visible ON du_visible.dialogue_id = m.dialogue_id AND du_visible.user_id = :current_user_id
            WHERE m.dialogue_id = :dialogue_id
              AND m.user_id = :user_id
              AND m.is_deleted = FALSE
              AND m.created_at > COALESCE(du_visible.last_cleared_at, TO_TIMESTAMP(0))
            ORDER BY m.created_at ASC";

        $rows = $this->db->fetchAll($sql, [
            'dialogue_id' => $dialogueId,
            'user_id' => $userId,
            'current_user_id' => $userId,
        ]);

        $states = [];
        foreach ($rows as $row) {
            $states[(int)$row['id']] = !empty($row['is_read']);
        }

        return $states;
    }

    /**
     * Получить одно сообщение по ID
     */
    public function getMessage(int $messageId): ?array
    {
        return $this->getMessageForUser($messageId);
    }

    public function getMessageForUser(int $messageId, ?int $userId = null): ?array
    {
        $sql = "SELECT m.*, u.name as user_name, u.avatar,
                   " . $this->messageReadStateSql() . "
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id";

        if ($userId !== null) {
            $sql .= " INNER JOIN dialogue_users du_visible ON du_visible.dialogue_id = m.dialogue_id AND du_visible.user_id = :user_id";
        }

        $sql .= " WHERE m.id = :id AND m.is_deleted = FALSE";

        $params = ['id' => $messageId];
        if ($userId !== null) {
            $params['user_id'] = $userId;
            $sql .= " AND m.created_at > COALESCE(du_visible.last_cleared_at, TO_TIMESTAMP(0))";
        }

        $sql .= " LIMIT 1";

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Базовый запрос сообщений для диалога
     */
    private function getMessagesQuery(int $dialogueId, int $userId): array
    {
        $sql = "SELECT m.*, u.name as user_name, u.avatar,
                   " . $this->messageReadStateSql() . "
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                INNER JOIN dialogue_users du_visible ON du_visible.dialogue_id = m.dialogue_id AND du_visible.user_id = :user_id
                WHERE m.dialogue_id = :dialogue_id AND m.is_deleted = FALSE
                  AND m.created_at > COALESCE(du_visible.last_cleared_at, TO_TIMESTAMP(0))
                ORDER BY m.created_at ASC
                LIMIT 100";

        return $this->db->fetchAll($sql, ['dialogue_id' => $dialogueId, 'user_id' => $userId]);
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

    public function clearDialogueForUser(int $dialogueId, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE dialogue_users SET last_cleared_at = NOW() WHERE dialogue_id = :dialogue_id AND user_id = :user_id',
            ['dialogue_id' => $dialogueId, 'user_id' => $userId]
        );
    }

    public function clearPrivateDialogueForEveryone(int $dialogueId, int $userId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private' || !$this->canAccessDialogue($dialogueId, $userId)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                'UPDATE messages SET is_deleted = TRUE WHERE dialogue_id = :dialogue_id AND is_deleted = FALSE',
                ['dialogue_id' => $dialogueId]
            );

            $this->db->execute(
                'UPDATE dialogues SET updated_at = NOW() WHERE id = :id',
                ['id' => $dialogueId]
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function blockPrivateDialogue(int $dialogueId, int $userId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private' || !$this->canAccessDialogue($dialogueId, $userId)) {
            return false;
        }

        $partnerId = $this->getPrivateDialoguePartnerId($dialogueId, $userId);
        if (!$partnerId) {
            return false;
        }

        return $this->db->execute(
            'INSERT INTO user_blocks (blocker_id, blocked_id, created_at) VALUES (:blocker_id, :blocked_id, NOW()) ON CONFLICT (blocker_id, blocked_id) DO NOTHING',
            ['blocker_id' => $userId, 'blocked_id' => $partnerId]
        );
    }

    public function blockAndClearPrivateDialogue(int $dialogueId, int $userId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private' || !$this->canAccessDialogue($dialogueId, $userId)) {
            return false;
        }

        $partnerId = $this->getPrivateDialoguePartnerId($dialogueId, $userId);
        if (!$partnerId) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                'UPDATE messages SET is_deleted = TRUE WHERE dialogue_id = :dialogue_id AND is_deleted = FALSE',
                ['dialogue_id' => $dialogueId]
            );

            $this->db->execute(
                'UPDATE dialogues SET updated_at = NOW() WHERE id = :id',
                ['id' => $dialogueId]
            );

            $this->db->execute(
                'INSERT INTO user_blocks (blocker_id, blocked_id, created_at) VALUES (:blocker_id, :blocked_id, NOW()) ON CONFLICT (blocker_id, blocked_id) DO NOTHING',
                ['blocker_id' => $userId, 'blocked_id' => $partnerId]
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function unblockPrivateDialogue(int $dialogueId, int $userId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private' || !$this->canAccessDialogue($dialogueId, $userId)) {
            return false;
        }

        $partnerId = $this->getPrivateDialoguePartnerId($dialogueId, $userId);
        if (!$partnerId) {
            return false;
        }

        return $this->db->execute(
            'DELETE FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id',
            ['blocker_id' => $userId, 'blocked_id' => $partnerId]
        );
    }

    public function isDialogueBlockedForUser(int $dialogueId, int $userId): bool
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            return false;
        }

        $partnerId = $this->getPrivateDialoguePartnerId($dialogueId, $userId);
        if (!$partnerId) {
            return false;
        }

        return $this->hasBlockRelation($userId, $partnerId) || $this->hasBlockRelation($partnerId, $userId);
    }

    public function getPrivateDialogueBlockState(int $dialogueId, int $userId): array
    {
        $dialogue = $this->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            return [
                'blocked_by_me' => false,
                'blocked_by_other' => false,
                'partner_id' => null,
            ];
        }

        $partnerId = $this->getPrivateDialoguePartnerId($dialogueId, $userId);
        if (!$partnerId) {
            return [
                'blocked_by_me' => false,
                'blocked_by_other' => false,
                'partner_id' => null,
            ];
        }

        return [
            'blocked_by_me' => $this->hasBlockRelation($userId, $partnerId),
            'blocked_by_other' => $this->hasBlockRelation($partnerId, $userId),
            'partner_id' => $partnerId,
        ];
    }

    private function getPrivateDialoguePartnerId(int $dialogueId, int $userId): ?int
    {
        $row = $this->db->fetchOne(
            'SELECT user_id FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id <> :user_id LIMIT 1',
            ['dialogue_id' => $dialogueId, 'user_id' => $userId]
        );

        return $row ? (int)$row['user_id'] : null;
    }

    private function hasBlockRelation(int $blockerId, int $blockedId): bool
    {
        return (bool)$this->db->fetchOne(
            'SELECT 1 FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id LIMIT 1',
            ['blocker_id' => $blockerId, 'blocked_id' => $blockedId]
        );
    }

    private function messageReadStateSql(): string
    {
        return "CASE WHEN EXISTS (
                    SELECT 1
                    FROM dialogue_users du_state
                    WHERE du_state.dialogue_id = m.dialogue_id
                      AND du_state.user_id <> m.user_id
                      AND du_state.last_read_at IS NOT NULL
                      AND du_state.last_read_at >= m.created_at
                ) THEN TRUE ELSE FALSE END AS is_read";
    }
}
