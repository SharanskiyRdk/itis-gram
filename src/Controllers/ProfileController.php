<?php

namespace App\Controllers;

use App\Core\Database;
use App\Services\ProfileService;
use JetBrains\PhpStorm\NoReturn;

class ProfileController extends AbstractController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function show(): void
    {
        $this->requireAuth();

        $user = $this->profileService->getUser($this->currentUserId());

        $tickets = $this->profileService->getUserTickets($this->currentUserId());

        $verificationStatus = $this->profileService->getVerificationStatus($this->currentUserId());

        if (!$verificationStatus) {
            $verificationStatus = [
                'is_verified' => false,
                'student_group' => null
            ];
        }

        // Статистика
        $stats = $this->getUserStats($this->currentUserId());

        if (!$stats) {
            $stats = [
                'messages' => 0,
                'dialogues' => 0
            ];
        }

        $this->render('profile/show', [
            'user' => $user,
            'tickets' => $tickets,
            'verificationStatus' => $verificationStatus,
            'stats' => $stats
        ]);
    }

    private function getUserStats(int $userId): array
    {
        $db = Database::getInstance();

        $messagesCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM messages WHERE user_id = :user_id AND is_deleted = FALSE",
            ['user_id' => $userId]
        );

        $dialoguesCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM dialogue_users WHERE user_id = :user_id",
            ['user_id' => $userId]
        );

        return [
            'messages' => $messagesCount['count'] ?? 0,
            'dialogues' => $dialoguesCount['count'] ?? 0
        ];
    }

    public function edit(): void
    {
        $this->requireAuth();
        $user = $this->profileService->getUser($this->currentUserId());
        $this->render('profile/edit', ['user' => $user]);
    }

    #[NoReturn]
    public function update(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $name = trim((string)($_POST['name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));

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
    public function avatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            $this->json(['error' => 'Файл не передан'], 400);
            return;
        }

        $avatarPath = $this->profileService->updateAvatar($this->currentUserId(), $_FILES['avatar']);

        if ($avatarPath) {
            $this->json(['success' => true, 'avatar_url' => $avatarPath]);
        } else {
            $this->json(['error' => 'Не удалось загрузить аватар'], 422);
        }
    }

    #[NoReturn]
    public function deleteAvatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $result = $this->profileService->deleteAvatar($this->currentUserId());
        $this->json(['success' => $result]);
    }

    #[NoReturn]
    public function support(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($subject === '' || mb_strlen($subject) > 255) {
            $this->json(['error' => 'Некорректная тема'], 422);
            return;
        }

        if ($message === '' || mb_strlen($message) > 2000) {
            $this->json(['error' => 'Некорректное сообщение'], 422);
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
    public function verifyRequest(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $studentGroup = trim((string)($_POST['student_group'] ?? ''));

        if ($studentGroup === '' || mb_strlen($studentGroup) > 50) {
            $this->json(['error' => 'Некорректный номер группы'], 422);
            return;
        }

        $result = $this->profileService->requestStudentVerification($this->currentUserId(), $studentGroup);

        if ($result) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось отправить запрос'], 500);
        }
    }
}