<?php

namespace App\Services;

use App\Models\User;
use App\Models\SupportTicket;
use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\ChatService;

class ProfileService
{
    private Database $db;
    private FileUploadService $fileUploadService;
    private UserRepository $users;
    private ChatService $chatService;

    public function __construct(?FileUploadService $fileUploadService = null, ?UserRepository $users = null, ?ChatService $chatService = null)
    {
        $this->db = Database::getInstance();
        $this->fileUploadService = $fileUploadService ?? new FileUploadService();
        $this->users = $users ?? new UserRepository();
        $this->chatService = $chatService ?? new ChatService();
    }

    public function getUser(int $userId): ?User
    {
        return User::find($userId);
    }

    public function updateProfile(int $userId, string $name, ?string $bio): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $user->setName($name);
        $user->bio = $bio;

        return $user->save();
    }

    public function updateAvatar(int $userId, array $file): ?string
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $oldAvatar = $user->getAvatar();
        $avatarPath = $this->fileUploadService->uploadAvatar($file);

        if (!$avatarPath) {
            return null;
        }

        $updated = $this->db->execute(
            'UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id',
            ['avatar' => $avatarPath, 'id' => $userId]
        );

        if ($updated) {
            if ($oldAvatar) {
                $this->fileUploadService->deleteFile($oldAvatar);
            }
            return $avatarPath;
        }

        return null;
    }

    public function deleteAvatar(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $avatar = $user->getAvatar();
        if ($avatar) {
            $this->fileUploadService->deleteFile($avatar);
        }

        $updated = $this->db->execute(
            'UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = :id',
            ['id' => $userId]
        );

        return (bool)$updated;
    }

    public function createSupportTicket(int $userId, string $subject, string $message): ?int
    {
        $ticket = new SupportTicket([
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($ticket->save()) {
            return $ticket->getId();
        }

        return null;
    }

    public function getUserTickets(int $userId): array
    {
        return SupportTicket::where('user_id', $userId);
    }

    public function requestStudentVerification(int $userId, string $studentGroup): bool
    {
        $subject = "Запрос на подтверждение статуса студента ИТИС";
        $message = "Группа: " . $studentGroup . "\n\nПрошу подтвердить мой статус студента ИТИС.";

        $existing = $this->db->fetchOne(
            'SELECT id FROM support_tickets WHERE user_id = :user_id AND subject = :subject LIMIT 1',
            [
                'user_id' => $userId,
                'subject' => $subject,
            ]
        );

        if ($existing) {
            return false;
        }

        return (bool)$this->createSupportTicket($userId, $subject, $message);
    }

    public function approveStudentVerificationTicket(int $ticketId): bool
    {
        $ticket = $this->db->fetchOne(
            'SELECT id, user_id, subject, message FROM support_tickets WHERE id = :id LIMIT 1',
            ['id' => $ticketId]
        );

        if (!$ticket) {
            return false;
        }

        $studentGroup = $this->extractStudentGroupFromMessage((string)($ticket['message'] ?? ''));
        if ($studentGroup === null) {
            return false;
        }

        try {
            $updatedUser = $this->db->execute(
                'UPDATE users SET student_group = :student_group, is_verified_student = TRUE, updated_at = NOW() WHERE id = :id',
                [
                    'student_group' => $studentGroup,
                    'id' => (int)$ticket['user_id'],
                ]
            );

            if (!$updatedUser) {
                return false;
            }

            return (bool)$this->chatService->ensureGroupDialogueMembership($studentGroup, (int)$ticket['user_id'], (int)$ticket['user_id']);
        } catch (\Throwable $e) {
            \App\Services\LoggerService::getInstance()->error('Failed to approve student verification ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function extractStudentGroupFromMessage(string $message): ?string
    {
        if (preg_match('/Группа:\s*([A-Za-z0-9\-]+)/u', $message, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function getVerificationStatus(int $userId): array
    {
        $user = User::find($userId);
        if ($user) {
            return [
                'is_verified' => $user->isVerifiedStudent(),
                'student_group' => $user->getStudentGroup()
            ];
        }

        return [
            'is_verified' => false,
            'student_group' => null
        ];
    }

    public function getProfileCardData(int $viewerId, int $userId): ?array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return null;
        }

        $friend = $this->users->isFriend($viewerId, $userId);

        return [
            'user' => [
                'id' => (int)$user['id'],
                'name' => (string)$user['name'],
                'email' => (string)$user['email'],
                'avatar' => $user['avatar'] ?? null,
                'bio' => $user['bio'] ?? null,
                'student_group' => $user['student_group'] ?? null,
                'is_online' => (bool)($user['is_online'] ?? false),
                'last_seen' => $user['last_seen'] ?? null,
                'role' => $user['role'] ?? 'user',
            ],
            'is_friend' => $friend,
            'can_toggle_friend' => $viewerId !== $userId,
        ];
    }

    public function toggleFriendship(int $viewerId, int $userId): array
    {
        if ($viewerId === $userId) {
            return ['success' => false, 'message' => 'Нельзя добавить самого себя'];
        }

        $isFriend = $this->users->isFriend($viewerId, $userId);

        if ($isFriend) {
            $success = $this->users->removeFriendship($viewerId, $userId);
            return [
                'success' => $success,
                'is_friend' => false,
                'message' => $success ? 'Пользователь удалён из друзей' : 'Не удалось удалить из друзей',
            ];
        }

        $success = $this->users->addFriendship($viewerId, $userId);

        return [
            'success' => $success,
            'is_friend' => $success,
            'message' => $success ? 'Пользователь добавлен в друзья' : 'Не удалось добавить в друзья',
        ];
    }

    public function getUserStats(int $userId): array
    {
        return [
            'messages' => $this->users->countMessages($userId),
            'dialogues' => $this->users->countDialogues($userId)
        ];
    }
}
