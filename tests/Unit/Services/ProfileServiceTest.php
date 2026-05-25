<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\UserRepository;
use App\Services\ChatService;
use App\Services\FileUploadService;
use App\Services\ProfileService;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class ProfileServiceTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testProfileLifecycleAndTickets(): void
    {
        $fileUpload = $this->createMock(FileUploadService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $chatService = $this->createMock(ChatService::class);

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->method('fetchAll')->willReturnCallback(function (string $sql, array $params = []): array {
            if (str_contains($sql, 'FROM support_tickets WHERE') && str_contains($sql, 'ORDER BY created_at DESC')) {
                return [
                    ['id' => 77, 'user_id' => 1, 'subject' => 'Запрос на подтверждение статуса студента ИТИС', 'message' => "Группа: 11-101\n\nПрошу подтвердить мой статус студента ИТИС."],
                ];
            }

            return [];
        });
        $dbMock->expects($this->exactly(6))->method('fetchOne')->willReturnOnConsecutiveCalls(
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test', 'avatar' => '/old.png', 'bio' => 'Bio', 'student_group' => '11-101', 'is_verified_student' => true, 'role' => 'user'],
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test', 'avatar' => '/old.png', 'bio' => 'Bio', 'student_group' => '11-101', 'is_verified_student' => true, 'role' => 'user'],
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test', 'avatar' => '/old.png', 'bio' => 'Bio', 'student_group' => '11-101', 'is_verified_student' => true, 'role' => 'user'],
            ['id' => 77],
            ['id' => 55, 'user_id' => 1, 'subject' => 'Запрос на подтверждение статуса студента ИТИС', 'message' => "Группа: 11-101\n\nПрошу подтвердить мой статус студента ИТИС."],
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test', 'avatar' => '/old.png', 'bio' => 'Bio', 'student_group' => '11-101', 'is_verified_student' => true, 'role' => 'user']
        );
        $dbMock->method('execute')->willReturn(true);
        $dbMock->expects($this->once())->method('lastInsertId')->willReturn(77);
        $this->bindDatabaseMock($dbMock);

        $fileUpload->expects($this->once())->method('uploadAvatar')->willReturn('/avatars/new.png');
        $fileUpload->expects($this->exactly(2))->method('deleteFile')->with('/old.png')->willReturn(true);
        $userRepository->expects($this->once())->method('findById')->willReturn([
            'id' => 2,
            'name' => 'Bob',
            'email' => 'bob@example.test',
            'avatar' => null,
            'bio' => null,
            'student_group' => null,
            'is_online' => false,
            'last_seen' => null,
            'role' => 'user',
        ]);
        $userRepository->expects($this->exactly(3))->method('isFriend')->willReturnOnConsecutiveCalls(false, true, false);
        $userRepository->expects($this->once())->method('addFriendship')->willReturn(true);
        $userRepository->expects($this->once())->method('removeFriendship')->willReturn(true);
        $userRepository->expects($this->once())->method('countMessages')->willReturn(3);
        $userRepository->expects($this->once())->method('countDialogues')->willReturn(2);
        $chatService->expects($this->once())->method('ensureGroupDialogueMembership')->willReturn(123);

        $service = new ProfileService($fileUpload, $userRepository, $chatService);

        self::assertTrue($service->updateProfile(1, 'Alice Updated', 'New bio'));
        self::assertSame('/avatars/new.png', $service->updateAvatar(1, ['tmp_name' => '/tmp/file', 'name' => 'file.png', 'size' => 1]));
        self::assertTrue($service->deleteAvatar(1));
        self::assertSame(77, $service->createSupportTicket(1, 'Subject', 'Message'));
        self::assertSame([
            ['id' => 77, 'user_id' => 1, 'subject' => 'Запрос на подтверждение статуса студента ИТИС', 'message' => "Группа: 11-101\n\nПрошу подтвердить мой статус студента ИТИС."],
        ], $service->getUserTickets(1));
        self::assertFalse($service->requestStudentVerification(1, '11-101'));
        self::assertTrue($service->approveStudentVerificationTicket(55));
        self::assertSame([
            'is_verified' => true,
            'student_group' => '11-101',
        ], $service->getVerificationStatus(1));
        self::assertSame(['success' => false, 'message' => 'Нельзя добавить самого себя'], $service->toggleFriendship(2, 2));
        self::assertSame(['success' => true, 'is_friend' => true, 'message' => 'Пользователь добавлен в друзья'], $service->toggleFriendship(1, 2));
        self::assertSame(['success' => true, 'is_friend' => false, 'message' => 'Пользователь удалён из друзей'], $service->toggleFriendship(1, 2));
        self::assertSame(['messages' => 3, 'dialogues' => 2], $service->getUserStats(1));
        self::assertSame([
            'user' => [
                'id' => 2,
                'name' => 'Bob',
                'email' => 'bob@example.test',
                'avatar' => null,
                'bio' => null,
                'student_group' => null,
                'is_online' => false,
                'last_seen' => null,
                'role' => 'user',
            ],
            'is_friend' => false,
            'can_toggle_friend' => true,
        ], $service->getProfileCardData(1, 2));
    }

    public function testVerificationStatusReturnsDefaultsWhenUserMissing(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $this->bindDatabaseMock($dbMock);

        $service = new ProfileService(
            $this->createMock(FileUploadService::class),
            $this->createMock(UserRepository::class),
            $this->createMock(ChatService::class)
        );

        self::assertSame([
            'is_verified' => false,
            'student_group' => null,
        ], $service->getVerificationStatus(999999));
    }
}
