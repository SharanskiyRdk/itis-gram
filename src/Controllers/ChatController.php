<?php

namespace App\Controllers;

use App\Services\ChatService;
use App\Services\ProfileService;
use JetBrains\PhpStorm\NoReturn;

class ChatController extends AbstractController
{
    private ChatService $chatService;
    private ProfileService $profileService;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->profileService = new ProfileService();
    }

    public function index(): void
    {
        $this->requireAuth();

        $dialogues = $this->chatService->getUserDialogues($this->currentUserId());

        $_SESSION['avatar'] = $this->profileService->getUser($this->currentUserId())['avatar'] ?? null;

        $this->render('chat/index', [
            'dialogues' => $dialogues,
            'user' => [
                'name' => $this->currentUserName(),
                'email' => $this->currentUserEmail(),
                'avatar' => $_SESSION['avatar'] ?? null
            ]
        ]);
    }

    public function show(): void
    {
        $this->requireAuth();

        $dialogueId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$dialogueId || $dialogueId < 1) {
            $this->redirect('/');
            return;
        }

        if (!$this->chatService->canAccessDialogue($dialogueId, $this->currentUserId())) {
            $this->redirect('/');
            return;
        }

        $messages = $this->chatService->getMessages($dialogueId, $this->currentUserId());
        $dialogue = $this->chatService->getDialogue($dialogueId);

        // Получаем участников для личного чата
        if ($dialogue && $dialogue['type'] === 'private') {
            $participants = $this->chatService->getDialogueParticipants($dialogueId);
            $dialogue['participants'] = $participants;
        }

        // Получаем количество участников для группового чата
        if ($dialogue && $dialogue['type'] === 'group') {
            $membersCount = $this->chatService->getDialogueMembersCount($dialogueId);
            $dialogue['members_count'] = $membersCount;
        }

        $this->render('chat/show', [
            'dialogue_id' => $dialogueId,
            'messages' => $messages,
            'dialogue' => $dialogue
        ]);
    }

    public function create(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $title = trim((string)($_POST['title'] ?? ''));
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            $dialogueId = $this->chatService->createPrivateChat(
                $this->currentUserId(),
                $userId
            );
        } else {
            if ($title === '') {
                if ($this->isAjax()) {
                    $this->json(['error' => 'Название чата обязательно'], 422);
                } else {
                    $this->render('chat/index', ['error' => 'Название чата обязательно']);
                }
                return;
            }

            if (mb_strlen($title) > 255) {
                if ($this->isAjax()) {
                    $this->json(['error' => 'Название чата слишком длинное'], 422);
                } else {
                    $this->render('chat/index', ['error' => 'Название чата слишком длинное']);
                }
                return;
            }

            $dialogueId = $this->chatService->createGroupChat(
                $title,
                $this->currentUserId()
            );
        }

        if ($dialogueId) {
            if ($this->isAjax()) {
                $this->json(['success' => true, 'dialogue_id' => $dialogueId]);
            } else {
                $this->redirect("/chat?id=$dialogueId");
            }
        } else {
            if ($this->isAjax()) {
                $this->json(['error' => 'Не удалось создать чат'], 500);
            } else {
                $this->render('chat/index', ['error' => 'Не удалось создать чат']);
            }
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }


    #[NoReturn]
    public function send(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = filter_input(INPUT_POST, 'dialogue_id', FILTER_VALIDATE_INT);
        $content = trim((string)($_POST['content'] ?? ''));

        if (!$dialogueId || $dialogueId < 1) {
            $this->json(['error' => 'Некорректный ID диалога'], 400);
            return;
        }

        if ($content === '') {
            $this->json(['error' => 'Сообщение не может быть пустым'], 422);
            return;
        }

        if (mb_strlen($content) > 4000) {
            $this->json(['error' => 'Сообщение слишком длинное'], 422);
            return;
        }

        $messageId = $this->chatService->sendMessage(
            $dialogueId,
            $this->currentUserId(),
            $content
        );

        if ($messageId) {
            $this->json(['success' => true, 'message_id' => $messageId]);
        } else {
            $this->json(['error' => 'Не удалось отправить сообщение'], 500);
        }
    }

    #[NoReturn]
    public function delete(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        if (!$messageId || $messageId < 1) {
            $this->json(['error' => 'Некорректный ID сообщения'], 400);
            return;
        }

        $result = $this->chatService->deleteMessage($messageId, $this->currentUserId());

        if ($result) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось удалить сообщение'], 500);
        }
    }

    public function getChatContent(): void
    {
        $this->requireAuth();

        $dialogueId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$dialogueId || $dialogueId < 1) {
            echo '<div class="empty-chat"><p>Чат не найден</p></div>';
            return;
        }

        if (!$this->chatService->canAccessDialogue($dialogueId, $this->currentUserId())) {
            echo '<div class="empty-chat"><p>Нет доступа</p></div>';
            return;
        }

        $messages = $this->chatService->getMessages($dialogueId, $this->currentUserId());
        $dialogue = $this->chatService->getDialogue($dialogueId);

        // Получаем участников для личного чата
        $otherUser = null;
        if ($dialogue && $dialogue['type'] === 'private') {
            $participants = $this->chatService->getDialogueParticipants($dialogueId);
            foreach ($participants as $p) {
                if ($p['id'] != $this->currentUserId()) {
                    $otherUser = $p;
                    break;
                }
            }
        }

        $this->render('chat/_chat_content', [
            'dialogue_id' => $dialogueId,
            'messages' => $messages,
            'dialogue' => $dialogue,
            'otherUser' => $otherUser
        ]);
    }
}