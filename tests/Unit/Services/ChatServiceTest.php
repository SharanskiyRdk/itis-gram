<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ChatService;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class ChatServiceTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testLookupAndReadFlows(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->method('fetchAll')->willReturnCallback(function (string $sql, array $params = []): array {
            if (str_contains($sql, 'FROM dialogues d') && str_contains($sql, 'display_title')) {
                return [['id' => 1, 'title' => 'General']];
            }

            if (str_contains($sql, 'student_group') && str_contains($sql, 'FROM users u')) {
                return [['id' => 5, 'name' => 'Alice']];
            }

            if (str_contains($sql, 'SELECT m.id,') && str_contains($sql, 'is_read')) {
                return [['id' => 10, 'is_read' => true]];
            }

            return [['id' => 10, 'content' => 'Hello']];
        });
        $dbMock->method('fetchOne')->willReturnCallback(function (string $sql, array $params = []): ?array {
            if (str_contains($sql, 'FROM dialogues WHERE id = :id')) {
                if (($params['id'] ?? null) === 2) {
                    return ['id' => 2, 'type' => 'group', 'title' => '11-101'];
                }

                return ['id' => 1, 'type' => 'private', 'title' => 'Chat'];
            }

            if (str_contains($sql, 'FROM dialogues d') && str_contains($sql, "d.type = 'group'")) {
                return ['id' => 2];
            }

            if (str_contains($sql, 'SELECT role FROM users')) {
                return ['role' => 'admin'];
            }

            if (str_contains($sql, 'SELECT 1 FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id = :user_id')) {
                return ['1' => 1];
            }

            if (str_contains($sql, 'COUNT(*) as count FROM dialogue_users')) {
                return ['count' => 2];
            }

            if (str_contains($sql, 'SELECT 1 FROM user_blocks')) {
                return null;
            }

            if (str_contains($sql, 'SELECT m.*, u.name as user_name, u.avatar')) {
                return ['id' => 10, 'content' => 'Hello'];
            }

            return ['id' => 1, 'type' => 'private', 'title' => 'Chat'];
        });
        $dbMock->expects($this->exactly(2))->method('execute')->willReturn(true);
        $this->bindDatabaseMock($dbMock);

        $service = new ChatService();

        self::assertSame([['id' => 1, 'title' => 'General']], $service->getUserDialogues(3));
        self::assertSame(['id' => 1, 'type' => 'private', 'title' => 'Chat'], $service->getDialogue(1));
        self::assertTrue($service->canAccessDialogue(2, 3));
        self::assertSame([['id' => 10, 'content' => 'Hello']], $service->getMessages(1, 3));
        self::assertSame([['id' => 10, 'content' => 'Hello']], $service->getMessagesAfter(1, 3, 5));
        self::assertSame(['id' => 10, 'content' => 'Hello'], $service->getMessageForUser(10, 3));
        self::assertSame([10 => true], $service->getOutgoingMessageReadStates(1, 3));
        self::assertSame([['id' => 5, 'name' => 'Alice']], $service->getDialogueParticipants(1));
        self::assertSame(2, $service->getDialogueMembersCount(1));
        self::assertTrue($service->isAcademicGroupDialogue(2));
        self::assertFalse($service->isGroupTitleEditable(2));
    }

    public function testSendDeleteAndGroupMutationFlows(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->method('fetchOne')->willReturnCallback(function (string $sql, array $params = []): ?array {
            if (str_contains($sql, 'FROM dialogues WHERE id = :id')) {
                return ['id' => 1, 'type' => 'private', 'title' => 'Chat'];
            }

            if (str_contains($sql, 'SELECT 1 FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id = :user_id')) {
                return ['1' => 1];
            }

            if (str_contains($sql, 'SELECT user_id FROM messages WHERE id = :id AND is_deleted = FALSE')) {
                return ['user_id' => 3];
            }

            if (str_contains($sql, 'FROM dialogues d') && str_contains($sql, "d.type = 'group'")) {
                return ['id' => 1];
            }

            if (str_contains($sql, 'COALESCE(group_code, title)')) {
                return null;
            }

            if (str_contains($sql, 'SELECT d.id FROM dialogues d')) {
                return null;
            }

            if (str_contains($sql, 'SELECT 1 FROM user_blocks')) {
                return null;
            }

            if (str_contains($sql, 'SELECT user_id FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id <> :user_id LIMIT 1')) {
                return ['user_id' => 8];
            }

            return null;
        });
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('execute')->willReturn(true);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('commit')->willReturn(true);
        $dbMock->method('rollBack')->willReturn(true);
        $dbMock->method('lastInsertId')->willReturnOnConsecutiveCalls(55, 77, 88, 99);
        $this->bindDatabaseMock($dbMock);

        $service = new ChatService();

        self::assertSame(55, $service->sendMessage(1, 3, 'Hello'));
        self::assertTrue($service->deleteMessage(5, 3));
        self::assertFalse($service->deleteMessage(5, 2));
        self::assertSame(77, $service->createPrivateChat(1, 2));
        self::assertSame(88, $service->createGroupChat('Team', 1));
        self::assertSame(99, $service->ensureGroupDialogueMembership('11-101', 3, 3));
        self::assertTrue($service->canManageGroupDialogue(1, 3));
        self::assertTrue($service->updateGroupDialogue(1, 3, null, null));
        self::assertTrue($service->clearDialogueForUser(1, 3));
        self::assertTrue($service->clearPrivateDialogueForEveryone(1, 3));
        self::assertTrue($service->blockPrivateDialogue(1, 3));
        self::assertTrue($service->blockAndClearPrivateDialogue(1, 3));
        self::assertTrue($service->unblockPrivateDialogue(1, 3));
        self::assertFalse($service->isDialogueBlockedForUser(1, 3));
        self::assertSame([
            'blocked_by_me' => false,
            'blocked_by_other' => false,
            'partner_id' => 8,
        ], $service->getPrivateDialogueBlockState(1, 3));
    }
}
