<?php

namespace App\Controllers;

use App\Routing\Attributes\Route;
use App\Services\ChatService;
use App\Services\FileUploadService;
use JetBrains\PhpStorm\NoReturn;

class ChatController extends AbstractController
{
    private ChatService $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    #[Route('/', 'GET')]
    public function index(): void
    {
        $this->requireAuth();

        $dialogues = $this->chatService->getUserDialogues($this->currentUserId());

        $this->render('chat/index', [
            'dialogues' => $dialogues
        ]);
    }

    #[Route('/chat', 'GET')]
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

        $this->redirect("/?id=$dialogueId");
    }

    #[Route('/chat/create', 'POST')]
    public function create(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $title = trim((string)$this->bodyParam('title', ''));
        $userId = (int)$this->bodyParam('user_id', 0);

        if ($userId > 0) {
            $dialogueId = $this->chatService->createPrivateChat(
                $this->currentUserId(),
                $userId
            );
        } else {
            if ($title === '') {
                if ($this->isAjaxRequest()) {
                    $this->json(['error' => 'Название чата обязательно'], 422);
                } else {
                    $this->render('chat/index', ['error' => 'Название чата обязательно']);
                }
                return;
            }

            if (mb_strlen($title) > 255) {
                if ($this->isAjaxRequest()) {
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
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true, 'dialogue_id' => $dialogueId]);
            } else {
                $this->redirect("/?id=$dialogueId");
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Не удалось создать чат'], 500);
            } else {
                $this->render('chat/index', ['error' => 'Не удалось создать чат']);
            }
        }
    }

    #[NoReturn]
    #[Route('/chat/send', 'POST')]
    public function send(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        $content = trim((string)$this->bodyParam('content', ''));
        $attachment = $this->uploadedFileParam('attachment');

        if (!$dialogueId || $dialogueId < 1) {
            $this->json(['error' => 'Некорректный ID диалога'], 400);
            return;
        }

        if ($content === '' && !$attachment) {
            $this->json(['error' => 'Сообщение не может быть пустым'], 422);
            return;
        }

        if ($content !== '' && mb_strlen($content) > 4000) {
            $this->json(['error' => 'Сообщение слишком длинное'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if ($dialogue && ($dialogue['type'] ?? '') === 'private' && $this->chatService->isDialogueBlockedForUser($dialogueId, $this->currentUserId())) {
            $this->json(['error' => 'Чат заблокирован'], 403);
            return;
        }

        $messageId = $this->chatService->sendMessage(
            $dialogueId,
            $this->currentUserId(),
            $content,
            $attachment
        );

        if ($messageId) {
            if ($this->isAjaxRequest()) {
                $message = $this->chatService->getMessageForUser($messageId, $this->currentUserId());
                if ($message) {
                    ob_start();
                    $this->render('chat/_message', ['message' => $message]);
                    $html = (string)ob_get_clean();
                    $this->json(['success' => true, 'message_id' => $messageId, 'message_html' => $html]);
                } else {
                    $this->json(['success' => true, 'message_id' => $messageId]);
                }
            } else {
                $this->json(['success' => true, 'message_id' => $messageId]);
            }
        } else {
            $this->json(['error' => 'Не удалось отправить сообщение'], 500);
        }
    }

    #[NoReturn]
    #[Route('/chat/delete', 'POST')]
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

    #[Route('/chat/content', 'GET')]
    public function getChatContent(): void
    {
        $this->requireAuth();

        $dialogueId = (int)$this->queryParam('id', 0);
        if (!$dialogueId || $dialogueId < 1) {
            echo '<div class="empty-chat"><p>Чат не найден</p></div>';
            return;
        }

        if (!$this->chatService->canAccessDialogue($dialogueId, $this->currentUserId())) {
            echo '<div class="empty-chat"><p>Нет доступа</p></div>';
            return;
        }

        $afterId = (int)$this->queryParam('after_id', 0);
        $messages = $afterId > 0
            ? $this->chatService->getMessagesAfter($dialogueId, $this->currentUserId(), $afterId)
            : $this->chatService->getMessages($dialogueId, $this->currentUserId());
        $dialogue = $this->chatService->getDialogue($dialogueId);
        $participants = $dialogue ? $this->chatService->getDialogueParticipants($dialogueId) : [];
        $membersCount = $dialogue ? $this->chatService->getDialogueMembersCount($dialogueId) : 0;
        $canManageGroup = $dialogue ? $this->chatService->canManageGroupDialogue($dialogueId, $this->currentUserId()) : false;
        $groupTitleLocked = $dialogue ? !$this->chatService->isGroupTitleEditable($dialogueId) : false;
        $isBlocked = $dialogue ? $this->chatService->isDialogueBlockedForUser($dialogueId, $this->currentUserId()) : false;
        $blockState = $dialogue ? $this->chatService->getPrivateDialogueBlockState($dialogueId, $this->currentUserId()) : [
            'blocked_by_me' => false,
            'blocked_by_other' => false,
            'partner_id' => null,
        ];

        if ((string)$this->queryParam('partial', '') === 'messages') {
            if (empty($messages)) {
                return;
            }

            $previousDate = (string)$this->queryParam('last_date', '');
            $this->render('chat/_messages', [
                'messages' => $messages,
                'previousDate' => $previousDate,
                'wrap' => false,
            ]);
            return;
        }

        $otherUser = null;
        if ($dialogue && $dialogue['type'] === 'private') {
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
            'otherUser' => $otherUser,
            'participants' => $participants,
            'membersCount' => $membersCount,
            'canManageGroup' => $canManageGroup,
            'groupTitleLocked' => $groupTitleLocked,
            'isBlocked' => $isBlocked,
            'blockState' => $blockState,
        ]);
    }

    #[Route('/chat/read-status', 'GET')]
    public function readStatus(): void
    {
        $this->requireAuth();

        $dialogueId = (int)$this->queryParam('dialogue_id', 0);
        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID диалога'], 422);
            return;
        }

        if (!$this->chatService->canAccessDialogue($dialogueId, $this->currentUserId())) {
            $this->json(['error' => 'Нет доступа'], 403);
            return;
        }

        $states = $this->chatService->getOutgoingMessageReadStates($dialogueId, $this->currentUserId());
        $this->json(['success' => true, 'states' => $states]);
    }

    #[NoReturn]
    #[Route('/chat/clear', 'POST')]
    public function clear(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        $scope = (string)$this->bodyParam('scope', 'me');

        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID диалога'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            $this->json(['error' => 'Действие доступно только для личного чата'], 422);
            return;
        }

        $result = $scope === 'all'
            ? $this->chatService->clearPrivateDialogueForEveryone($dialogueId, $this->currentUserId())
            : $this->chatService->clearDialogueForUser($dialogueId, $this->currentUserId());

        if ($result) {
            $this->json(['success' => true]);
            return;
        }

        $this->json(['error' => 'Не удалось очистить чат'], 500);
    }

    #[NoReturn]
    #[Route('/chat/block', 'POST')]
    public function block(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID диалога'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            $this->json(['error' => 'Действие доступно только для личного чата'], 422);
            return;
        }

        if ($this->chatService->blockPrivateDialogue($dialogueId, $this->currentUserId())) {
            $this->json(['success' => true]);
            return;
        }

        $this->json(['error' => 'Не удалось заблокировать пользователя'], 500);
    }

    #[NoReturn]
    #[Route('/chat/block-clear', 'POST')]
    public function blockAndClear(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID диалога'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            $this->json(['error' => 'Действие доступно только для личного чата'], 422);
            return;
        }

        if ($this->chatService->blockAndClearPrivateDialogue($dialogueId, $this->currentUserId())) {
            $this->json(['success' => true]);
            return;
        }

        $this->json(['error' => 'Не удалось заблокировать и очистить чат'], 500);
    }

    #[NoReturn]
    #[Route('/chat/unblock', 'POST')]
    public function unblock(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID диалога'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'private') {
            $this->json(['error' => 'Действие доступно только для личного чата'], 422);
            return;
        }

        if ($this->chatService->unblockPrivateDialogue($dialogueId, $this->currentUserId())) {
            $this->json(['success' => true]);
            return;
        }

        $this->json(['error' => 'Не удалось разблокировать пользователя'], 500);
    }

    #[NoReturn]
    #[Route('/chat/group/update', 'POST')]
    public function updateGroup(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $dialogueId = (int)$this->bodyParam('dialogue_id', 0);
        $title = trim((string)$this->bodyParam('title', ''));
        $avatarFile = $this->uploadedFileParam('avatar');

        if ($dialogueId <= 0) {
            $this->json(['error' => 'Некорректный ID группы'], 422);
            return;
        }

        if ($title !== '' && mb_strlen($title) > 255) {
            $this->json(['error' => 'Название группы слишком длинное'], 422);
            return;
        }

        if ($title === '' && !$avatarFile) {
            $this->json(['error' => 'Нет данных для обновления группы'], 422);
            return;
        }

        $dialogue = $this->chatService->getDialogue($dialogueId);
        if (!$dialogue || ($dialogue['type'] ?? '') !== 'group') {
            $this->json(['error' => 'Группа не найдена'], 404);
            return;
        }

        $groupTitleLocked = $this->chatService->isAcademicGroupDialogue($dialogueId);
        if ($groupTitleLocked) {
            $title = null;
        }

        if ($groupTitleLocked && !$avatarFile) {
            $this->json(['success' => true]);
            return;
        }

        $avatarPath = null;
        if ($avatarFile) {
            $uploadService = new FileUploadService();
            $avatarPath = $uploadService->uploadDialogueAvatar($avatarFile);
            if (!$avatarPath) {
                $this->json(['error' => 'Не удалось загрузить фото группы'], 422);
                return;
            }
        }

        $updated = $this->chatService->updateGroupDialogue(
            $dialogueId,
            $this->currentUserId(),
            $title !== '' ? $title : null,
            $avatarPath
        );

        if ($updated) {
            $this->json(['success' => true]);
            return;
        }

        $this->json(['error' => 'Нет прав на изменение группы'], 403);
    }
}
