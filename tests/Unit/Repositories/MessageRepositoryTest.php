<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\MessageRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class MessageRepositoryTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testSearchMessagesUsesPreparedStatement(): void
    {
        $statement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['bindValue', 'execute', 'fetchAll'])
            ->getMock();

        $statement->expects($this->exactly(3))->method('bindValue')->willReturn(true);
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $statement->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 1, 'content' => 'match'],
        ]);

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('getConnection')->willReturn($pdo);
        $this->bindDatabaseMock($dbMock);

        $repo = new MessageRepository();
        $rows = $repo->searchMessages(3, 'match');

        self::assertCount(1, $rows);
        self::assertSame('match', $rows[0]['content']);
    }
}
