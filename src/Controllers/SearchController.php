<?php

namespace App\Controllers;

use App\Core\Database;

class SearchController extends AbstractController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function users(): void
    {
        $this->requireAuth();

        $q = trim((string)($_GET['q'] ?? ''));

        $users = [];

        if ($q !== '' && mb_strlen($q) <= 100) {
            $sql = "SELECT id, name, email, avatar, is_online 
                    FROM users 
                    WHERE (name ILIKE :q OR email ILIKE :q) 
                    AND id != :user_id 
                    AND is_deleted = FALSE
                    LIMIT 20";

            $users = $this->db->fetchAll($sql, [
                'q' => "%$q%",
                'user_id' => $this->currentUserId()
            ]);
        }

        // Если AJAX запрос
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json(['users' => $users]);
            return;
        }

        $this->render('search/users', ['q' => $q, 'users' => $users]);
    }

    public function createChat(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0 || $userId === $this->currentUserId()) {
            $this->json(['error' => 'Некорректный пользователь'], 400);
            return;
        }

        // Проверяем существование пользователя
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = :id AND is_deleted = FALSE",
            ['id' => $userId]
        );

        if (!$user) {
            $this->json(['error' => 'Пользователь не найден'], 404);
            return;
        }

        // Проверяем существующий диалог
        $sql = "SELECT d.id FROM dialogues d
                INNER JOIN dialogue_users du1 ON d.id = du1.dialogue_id
                INNER JOIN dialogue_users du2 ON d.id = du2.dialogue_id
                WHERE d.type = 'private' 
                AND du1.user_id = :user1 
                AND du2.user_id = :user2";

        $existing = $this->db->fetchOne($sql, [
            'user1' => $this->currentUserId(),
            'user2' => $userId
        ]);

        if ($existing) {
            $this->json(['success' => true, 'dialogue_id' => $existing['id']]);
            return;
        }

        // Создаем новый диалог
        $this->db->beginTransaction();

        try {
            $this->db->execute(
                "INSERT INTO dialogues (type, created_at, updated_at) VALUES ('private', NOW(), NOW())"
            );
            $dialogueId = $this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())",
                ['dialogue_id' => $dialogueId, 'user_id' => $this->currentUserId()]
            );
            $this->db->execute(
                "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW())",
                ['dialogue_id' => $dialogueId, 'user_id' => $userId]
            );

            $this->db->commit();

            $this->json(['success' => true, 'dialogue_id' => $dialogueId]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['error' => 'Не удалось создать чат'], 500);
        }
    }

    public function messages(): void
    {
        $this->requireAuth();

        $q = trim((string)($_GET['q'] ?? ''));

        if ($q === '' || mb_strlen($q) > 100) {
            $this->json(['messages' => []]);
            return;
        }

        $sql = "SELECT m.id, m.content, m.created_at, m.dialogue_id, 
                       u.name as user_name, u.id as user_id
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                INNER JOIN dialogue_users du ON m.dialogue_id = du.dialogue_id
                WHERE du.user_id = :user_id 
                AND m.content ILIKE :q 
                AND m.is_deleted = FALSE
                ORDER BY m.created_at DESC
                LIMIT 50";

        $messages = $this->db->fetchAll($sql, [
            'user_id' => $this->currentUserId(),
            'q' => "%$q%"
        ]);

        $this->json(['messages' => $messages]);
    }
}