<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Model;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class TestModel extends Model
{
    protected static string $table = 'test_models';
    protected static array $fillable = ['name', 'flag', 'created_at', 'is_deleted'];
}

class ModelTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testAllFindAndWhereUseDatabase(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'One', 'flag' => true],
        ]);

        $this->bindDatabaseMock($dbMock);

        $all = TestModel::all();
        self::assertCount(1, $all);
        self::assertSame('One', $all[0]->name);

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchOne')->willReturn(['id' => 2, 'name' => 'Two']);
        $this->bindDatabaseMock($dbMock);

        $found = TestModel::find(2);
        self::assertInstanceOf(TestModel::class, $found);
        self::assertSame(2, $found->id);

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 3, 'name' => 'Three'],
        ]);
        $this->bindDatabaseMock($dbMock);

        $where = TestModel::where('name', 'Three');
        self::assertCount(1, $where);
        self::assertSame(3, $where[0]->id);
    }

    public function testFirstWhereReturnsNullWhenMissing(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchOne')->willReturn(null);

        $this->bindDatabaseMock($dbMock);

        self::assertNull(TestModel::firstWhere('name', 'missing'));
    }

    public function testSaveInsertUpdateAndDelete(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->exactly(3))->method('execute')->willReturn(true);
        $dbMock->expects($this->once())->method('lastInsertId')->willReturn(42);

        $this->bindDatabaseMock($dbMock);

        $insert = new TestModel(['name' => 'New', 'flag' => true]);
        self::assertTrue($insert->save());
        self::assertSame(42, $insert->id);

        $update = new TestModel(['id' => 7, 'name' => 'Old', 'flag' => false]);
        self::assertTrue($update->save());
        self::assertSame(7, $update->id);

        self::assertTrue($update->delete());
    }

    public function testMagicAccessorsAndToArray(): void
    {
        $model = new TestModel(['name' => 'Alpha', 'flag' => true]);
        self::assertSame('Alpha', $model->name);
        self::assertSame(true, $model->flag);
        $model->name = 'Beta';
        self::assertSame('Beta', $model->toArray()['name']);
    }
}
