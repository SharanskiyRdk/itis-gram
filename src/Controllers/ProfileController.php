<?php

namespace App\Controllers;

use App\Routing\Attributes\Route;
use App\Services\ProfileService;
use JetBrains\PhpStorm\NoReturn;

class ProfileController extends AbstractController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    #[Route('/profile', 'GET')]
    public function show(): void
    {
        $this->requireAuth();

        $user = $this->profileService->getUser($this->currentUserId());
        $isAdmin = $user ? $user->isAdmin() : false;

        $tickets = $this->profileService->getUserTickets($this->currentUserId());

        $verificationStatus = $this->profileService->getVerificationStatus($this->currentUserId());

        if (!$verificationStatus) {
            $verificationStatus = [
                'is_verified' => false,
                'student_group' => null
            ];
        }

        $stats = $this->profileService->getUserStats($this->currentUserId());

        $adminStats = null;
        if ($isAdmin) {
            $db = \App\Core\Database::getInstance();
            $adminStats = [
                'users' => (int)($db->fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_deleted = FALSE')['c'] ?? 0),
                'tickets_open' => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'open'")['c'] ?? 0),
                'groups' => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM dialogues WHERE type = 'group' AND is_deleted = FALSE")['c'] ?? 0),
            ];
        }

        $this->render('profile/show', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'tickets' => $tickets,
            'verificationStatus' => $verificationStatus,
            'stats' => $stats,
            'studentGroup' => $user ? $user->getStudentGroup() : null,
            'adminStats' => $adminStats,
        ]);
    }

    public function edit(): void
    {
        $this->requireAuth();
        $user = $this->profileService->getUser($this->currentUserId());
        $this->render('profile/edit', ['user' => $user]);
    }

    #[NoReturn]
    #[Route('/profile/update', 'POST')]
    public function update(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $name = trim((string)$this->bodyParam('name', ''));
        $bio = trim((string)$this->bodyParam('bio', ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $this->json(['error' => 'Некорректное имя'], 422);
            return;
        }

        if (mb_strlen($bio) > 500) {
            $this->json(['error' => 'Слишком длинное описание'], 422);
            return;
        }

        $result = $this->profileService->updateProfile($this->currentUserId(), $name, $bio);

        if ($result) {
            $_SESSION['user_name'] = $name;
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось обновить профиль'], 500);
        }
    }

    #[NoReturn]
    #[Route('/profile/avatar', 'POST')]
    public function avatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $avatarFile = $this->uploadedFileParam('avatar');

        if (!$avatarFile) {
            $this->json(['error' => 'Файл не передан'], 400);
            return;
        }

        try {
            $avatarPath = $this->profileService->updateAvatar($this->currentUserId(), $avatarFile);
        } catch (\Throwable $e) {
            \App\Services\LoggerService::getInstance()->error('Avatar upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->json(['error' => 'Не удалось загрузить аватар'], 500);
            return;
        }

        if ($avatarPath) {
            $_SESSION['avatar'] = $avatarPath;
            $this->json(['success' => true, 'avatar_url' => $avatarPath]);
        } else {
            $this->json(['error' => 'Не удалось загрузить аватар'], 422);
        }
    }

    #[NoReturn]
    #[Route('/profile/avatar/delete', 'POST')]
    public function deleteAvatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $result = $this->profileService->deleteAvatar($this->currentUserId());
        if ($result) {
            $_SESSION['avatar'] = null;
        }
        $this->json(['success' => $result]);
    }

    #[NoReturn]
    #[Route('/profile/support', 'POST')]
    public function support(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $subject = trim((string)$this->bodyParam('subject', ''));
        $message = trim((string)$this->bodyParam('message', ''));

        if ($subject === '' || mb_strlen($subject) > 255) {
            $this->json(['error' => 'Некорректная тема'], 422);
            return;
        }

        if ($message === '' || mb_strlen($message) > 2000) {
            $this->json(['error' => 'Некорректное сообщение'], 422);
            return;
        }

        $db = \App\Core\Database::getInstance();
        $exists = $db->fetchOne('SELECT id FROM support_tickets WHERE user_id = :uid AND subject = :subject LIMIT 1', [
            'uid' => $this->currentUserId(),
            'subject' => $subject
        ]);

        if ($exists) {
            $this->json(['error' => 'Вы уже отправляли обращение с такой темой'], 409);
            return;
        }

        $ticketId = $this->profileService->createSupportTicket($this->currentUserId(), $subject, $message);

        if ($ticketId) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось отправить обращение'], 500);
        }
    }

    #[NoReturn]
    #[Route('/profile/verify-request', 'POST')]
    public function verifyRequest(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $studentGroup = trim((string)$this->bodyParam('student_group', ''));

        if ($studentGroup === '' || mb_strlen($studentGroup) > 50) {
            $this->json(['error' => 'Некорректный номер группы'], 422);
            return;
        }

        $subject = 'Запрос на подтверждение статуса студента ИТИС';
        $db = \App\Core\Database::getInstance();
        $exists = $db->fetchOne('SELECT id FROM support_tickets WHERE user_id = :uid AND subject = :subject LIMIT 1', [
            'uid' => $this->currentUserId(),
            'subject' => $subject
        ]);

        if ($exists) {
            $this->json(['error' => 'Вы уже отправляли запрос на подтверждение'], 409);
            return;
        }

        $result = $this->profileService->requestStudentVerification($this->currentUserId(), $studentGroup);

        if ($result) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось отправить запрос'], 500);
        }
    }

    #[NoReturn]
    #[Route('/profile/card', 'GET')]
    public function card(): void
    {
        $this->requireAuth();

        $userId = (int)$this->queryParam('user_id', 0);
        if ($userId <= 0) {
            $this->json(['error' => 'Некорректный пользователь'], 422);
            return;
        }

        $card = $this->profileService->getProfileCardData($this->currentUserId(), $userId);
        if (!$card) {
            $this->json(['error' => 'Пользователь не найден'], 404);
            return;
        }

        $this->json(['success' => true, 'card' => $card]);
    }

    #[NoReturn]
    #[Route('/profile/friend-toggle', 'POST')]
    public function friendToggle(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $userId = (int)$this->bodyParam('user_id', 0);
        if ($userId <= 0) {
            $this->json(['error' => 'Некорректный пользователь'], 422);
            return;
        }

        $result = $this->profileService->toggleFriendship($this->currentUserId(), $userId);
        if (!empty($result['success'])) {
            $this->json($result);
            return;
        }

        $payload = $result;
        $payload['error'] = $result['message'] ?? 'Не удалось обновить друзей';
        $this->json($payload, 500);
    }
}
