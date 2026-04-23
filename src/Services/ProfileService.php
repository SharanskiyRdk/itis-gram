<?php
namespace App\Services;

use App\Models\User;
use App\Models\SupportTicket;
use App\Core\Database;

class ProfileService
{
    private Database $db;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->fileUploadService = new FileUploadService();
    }

    public function getUser(int $userId): ?User
    {
        return User::find($userId);
    }

    public function updateProfile(int $userId, string $name, ?string $bio): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $user->setName($name);
        $user->bio = $bio;

        return $user->save();
    }

    public function updateAvatar(int $userId, array $file): ?string
    {
        $user = User::find($userId);
        if (!$user) return null;

        $oldAvatar = $user->getAvatar();
        $avatarPath = $this->fileUploadService->uploadAvatar($file);

        if (!$avatarPath) return null;

        $user->setAvatar($avatarPath);
        if ($user->save()) {
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
        if (!$user) return false;

        $avatar = $user->getAvatar();
        if ($avatar) {
            $this->fileUploadService->deleteFile($avatar);
        }

        $user->setAvatar(null);
        return $user->save();
    }

    public function createSupportTicket(int $userId, string $subject, string $message): ?int
    {
        $ticket = new SupportTicket();
        $ticket->attributes['user_id'] = $userId;
        $ticket->attributes['subject'] = $subject;
        $ticket->attributes['message'] = $message;
        $ticket->attributes['status'] = 'open';
        $ticket->attributes['created_at'] = date('Y-m-d H:i:s');
        $ticket->attributes['updated_at'] = date('Y-m-d H:i:s');

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
        return (bool)$this->createSupportTicket($userId, $subject, $message);
    }

    public function getVerificationStatus(int $userId): array
    {
        $user = User::find($userId);
        if ($user) {
            return [
                'is_verified' => $user->isVerifiedStudent(),
                'student_group' => $user->studentGroup ?? null
            ];
        }

        return [
            'is_verified' => false,
            'student_group' => null
        ];
    }
}