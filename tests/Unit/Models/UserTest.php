<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testAccessorsMutatorsAndPassword(): void
    {
        $user = new User([
            'id' => 10,
            'name' => 'Alice',
            'email' => 'alice@example.test',
            'avatar' => '/a.png',
            'bio' => 'Hello',
            'is_online' => true,
            'last_seen' => '2026-01-01 10:00:00',
            'is_verified_student' => true,
            'student_group' => '11-101',
            'is_banned' => false,
            'role' => 'admin',
        ]);

        self::assertSame(10, $user->getId());
        self::assertSame('Alice', $user->getName());
        self::assertSame('alice@example.test', $user->getEmail());
        self::assertSame('/a.png', $user->getAvatar());
        self::assertSame('Hello', $user->getBio());
        self::assertTrue($user->isOnline());
        self::assertSame('2026-01-01 10:00:00', $user->getLastSeen());
        self::assertTrue($user->isVerifiedStudent());
        self::assertSame('11-101', $user->getStudentGroup());
        self::assertFalse($user->isBanned());
        self::assertSame('admin', $user->getRole());
        self::assertTrue($user->isAdmin());

        $user->setName('Bob');
        $user->setEmail('bob@example.test');
        $user->setAvatar('/b.png');
        $user->setBio('Bio');
        $user->setOnline(false);
        $user->setLastSeen('2026-01-02 10:00:00');
        $user->setIsVerifiedStudent(false);
        $user->setStudentGroup('11-202');
        $user->setIsBanned(true);
        $user->setPassword('secret');

        self::assertSame('Bob', $user->getName());
        self::assertSame('bob@example.test', $user->getEmail());
        self::assertSame('/b.png', $user->getAvatar());
        self::assertSame('Bio', $user->getBio());
        self::assertFalse($user->isOnline());
        self::assertSame('2026-01-02 10:00:00', $user->getLastSeen());
        self::assertFalse($user->isVerifiedStudent());
        self::assertSame('11-202', $user->getStudentGroup());
        self::assertTrue($user->isBanned());
        self::assertTrue($user->verifyPassword('secret'));
    }

    public function testGetDialoguesUsesDatabase(): void
    {
        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 1, 'title' => 'Chat'],
        ]);

        $ref = new \ReflectionClass(\App\Models\Model::class);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue(null, $dbMock);

        $user = new User(['id' => 5]);
        $dialogues = $user->getDialogues();

        self::assertCount(1, $dialogues);
        self::assertSame('Chat', $dialogues[0]['title']);
    }
}
