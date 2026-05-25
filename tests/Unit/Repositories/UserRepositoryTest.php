<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class UserRepositoryTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testSearchFindByIdAndFriends(): void
    {
        $statement = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['bindValue', 'execute', 'fetchAll'])
            ->getMock();

        $statement->expects($this->exactly(3))->method('bindValue')->willReturn(true);
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $statement->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'Alice'],
        ]);

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('getConnection')->willReturn($pdo);
        $dbMock->expects($this->exactly(2))->method('fetchOne')->willReturnOnConsecutiveCalls(
            ['id' => 2, 'name' => 'Bob'],
            ['1' => 1]
        );
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['friend_id' => 3],
            ['friend_id' => 4],
        ]);
        $this->bindDatabaseMock($dbMock);

        $repo = new UserRepository();

        self::assertCount(1, $repo->searchUsers('Ali', 5));
        self::assertSame(['id' => 2, 'name' => 'Bob'], $repo->findById(2));
        self::assertSame([3, 4], $repo->getFriendIds(2));
        self::assertTrue($repo->isFriend(2, 3));
    }

    public function testAddRemoveAndCountOperations(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->exactly(2))->method('beginTransaction');
        $dbMock->expects($this->exactly(3))->method('execute')->willReturn(true);
        $dbMock->expects($this->exactly(2))->method('commit');
        $dbMock->expects($this->never())->method('rollBack');
        $dbMock->expects($this->exactly(2))->method('fetchOne')->willReturnOnConsecutiveCalls(
            ['count' => 9],
            ['count' => 4]
        );
        $this->bindDatabaseMock($dbMock);

        $repo = new UserRepository();

        self::assertTrue($repo->addFriendship(1, 2));
        self::assertTrue($repo->removeFriendship(1, 2));
        self::assertSame(9, $repo->countMessages(1));
        self::assertSame(4, $repo->countDialogues(1));
    }
}
