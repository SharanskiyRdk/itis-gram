<?php

namespace App\Controllers;

use App\Services\ChatService;

class ChatController extends AbstractController
{
    private ChatService $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    public function index(): void
    {
        $this->requireAuth();

        $dialogues = $this->chatService->getUserDialogues($this->currentUserId());

        $this->render('chat/index', ['dialogues' => $dialogues]);
    }

    public function show(): void
    {
        $this->requireAuth();

        $dialogueId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$dialogueId || $dialogueId < 1) {
            $this->redirect('/');
            return;
        }

        // Проверяем, имеет ли пользователь доступ к диалогу
        if (!$this->chatService->canAccessDialogue($dialogueId, $this->currentUserId())) {
            $this->redirect('/');
            return;
        }

        $messages = $this->chatService->getMessages($dialogueId, $this->currentUserId());
        $dialogue = $this->chatService->getDialogue($dialogueId);

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
            // Создаем личный чат
            $dialogueId = $this->chatService->createPrivateChat(
                $this->currentUserId(),
                $userId
            );
        } else {
            // Создаем групповой чат
            if ($title === '') {
                $this->json(['error' => 'Название чата обязательно'], 422);
                return;
            }

            if (mb_strlen($title) > 255) {
                $this->json(['error' => 'Название чата слишком длинное'], 422);
                return;
            }

            $dialogueId = $this->chatService->createGroupChat(
                $title,
                $this->currentUserId()
            );
        }

        if ($dialogueId) {
            $this->redirect("/chat?id=$dialogueId");
        } else {
            $this->render('chat/index', ['error' => 'Не удалось создать чат']);
        }
    }

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
}