<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Dialogue;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class DialogueTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testAccessorsAndParticipantQueries(): void
    {
        $dialogue = new Dialogue([
            'id' => 5,
            'type' => 'group',
            'title' => 'Team',
            'avatar' => '/g.png',
            'group_code' => '11-101',
            'created_by' => 2,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 11:00:00',
            'is_deleted' => null,
        ]);

        self::assertSame(5, $dialogue->getId());
        self::assertSame('group', $dialogue->getType());
        self::assertSame('Team', $dialogue->getTitle());
        self::assertSame('/g.png', $dialogue->getAvatar());
        self::assertSame('11-101', $dialogue->getGroupCode());
        self::assertSame(2, $dialogue->getCreatedBy());
        self::assertSame('2026-01-01 10:00:00', $dialogue->getCreatedAt());
        self::assertSame('2026-01-01 11:00:00', $dialogue->getUpdatedAt());
        self::assertNull($dialogue->getDeletedAt());

        $dialogue->setTitle('New title');
        $dialogue->setAvatar('/new.png');
        $dialogue->setGroupCode('11-102');

        self::assertSame('New title', $dialogue->getTitle());
        self::assertSame('/new.png', $dialogue->getAvatar());
        self::assertSame('11-102', $dialogue->getGroupCode());

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('execute')->willReturn(true);
        $dbMock->expects($this->once())->method('fetchOne')->willReturn(['1' => 1]);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 2, 'name' => 'User'],
        ]);
        $this->bindDatabaseMock($dbMock);

        self::assertTrue($dialogue->addParticipant(9));
        self::assertTrue($dialogue->addParticipantIfMissing(9));
        self::assertTrue($dialogue->isParticipant(9));
        self::assertSame([['id' => 2, 'name' => 'User']], $dialogue->getParticipants());
    }
}
