<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\DialogueRepository;
use App\Repositories\MessageRepository;
use App\Routing\Attributes\Route;

class SearchController extends AbstractController
{
    private UserRepository $users;
    private DialogueRepository $dialogues;
    private MessageRepository $messages;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->dialogues = new DialogueRepository();
        $this->messages = new MessageRepository();
    }

    #[Route('/search/users', 'GET')]
    public function users(): void
    {
        $this->requireAuth();

        $q = trim((string)$this->queryParam('q', ''));

        $users = [];

        if ($q !== '' && mb_strlen($q) <= 100) {
            $users = $this->users->searchUsers($q, $this->currentUserId());
        }


        if ($this->isAjaxRequest()) {
            $this->json(['users' => $users]);
            return;
        }

        $this->render('search/users', ['q' => $q, 'users' => $users]);
    }

    #[Route('/search/users/chat', 'POST')]
    public function createChat(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $userId = (int)$this->bodyParam('user_id', 0);

        if ($userId <= 0 || $userId === $this->currentUserId()) {
            $this->json(['error' => 'Некорректный пользователь'], 400);
            return;
        }


        $user = $this->users->findById($userId);

        if (!$user) {
            $this->json(['error' => 'Пользователь не найден'], 404);
            return;
        }


        $sql = "SELECT d.id FROM dialogues d
                INNER JOIN dialogue_users du1 ON d.id = du1.dialogue_id
                INNER JOIN dialogue_users du2 ON d.id = du2.dialogue_id
                WHERE d.type = 'private' 
                AND du1.user_id = :user1 
                AND du2.user_id = :user2";

        $existing = $this->dialogues->findExistingPrivateDialogue($this->currentUserId(), $userId);

        if ($existing) {
            $this->json(['success' => true, 'dialogue_id' => $existing['id']]);
            return;
        }


        $dialogueId = $this->dialogues->createPrivateDialogue($this->currentUserId(), $userId);

        if ($dialogueId) {
            $this->json(['success' => true, 'dialogue_id' => $dialogueId]);
        } else {
            $this->json(['error' => 'Не удалось создать чат'], 500);
        }
    }

    #[Route('/search/messages', 'GET')]
    public function messages(): void
    {
        $this->requireAuth();

        $q = trim((string)$this->queryParam('q', ''));

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

        $messages = $this->messages->searchMessages($this->currentUserId(), $q);

        $this->json(['messages' => $messages]);
    }
}
