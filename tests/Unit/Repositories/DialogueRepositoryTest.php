<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\DialogueRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class DialogueRepositoryTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testFindExistingPrivateDialogueReturnsRow(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchOne')->willReturn(['id' => 11]);
        $this->bindDatabaseMock($dbMock);

        $repo = new DialogueRepository();
        self::assertSame(['id' => 11], $repo->findExistingPrivateDialogue(1, 2));
    }

    public function testCreatePrivateDialogueInsertsParticipants(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('beginTransaction');
        $dbMock->expects($this->exactly(3))->method('execute')->willReturn(true);
        $dbMock->expects($this->once())->method('lastInsertId')->willReturn(99);
        $dbMock->expects($this->once())->method('commit');
        $this->bindDatabaseMock($dbMock);

        $repo = new DialogueRepository();
        self::assertSame(99, $repo->createPrivateDialogue(1, 2));
    }

    public function testCreatePrivateDialogueRollsBackOnFailure(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('beginTransaction');
        $dbMock->expects($this->once())->method('execute')->willThrowException(new \RuntimeException('fail'));
        $dbMock->expects($this->once())->method('rollBack');
        $this->bindDatabaseMock($dbMock);

        $repo = new DialogueRepository();
        self::assertNull($repo->createPrivateDialogue(1, 2));
    }
}
