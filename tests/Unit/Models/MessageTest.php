<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Message;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class MessageTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testAccessorsAndDialogueLookup(): void
    {
        $message = new Message([
            'id' => 8,
            'dialogue_id' => 5,
            'user_id' => 3,
            'content' => 'hello',
            'is_read' => true,
            'is_deleted' => false,
            'reply_to' => 2,
            'created_at' => '2026-01-01 12:00:00',
        ]);

        self::assertSame(8, $message->getId());
        self::assertSame(5, $message->getDialogueId());
        self::assertSame(3, $message->getUserId());
        self::assertSame('hello', $message->getContent());
        self::assertSame('2026-01-01 12:00:00', $message->getCreatedAt());

        $message->setContent('updated');
        self::assertSame('updated', $message->getContent());

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 1, 'content' => 'hello'],
        ]);
        $this->bindDatabaseMock($dbMock);

        self::assertSame([
            ['id' => 1, 'content' => 'hello'],
        ], Message::findAllByDialogue(1));
    }
}
