<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SupportTicket;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseMockTrait;

class SupportTicketTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
    }

    public function testAccessorsResolveAndWhere(): void
    {
        $ticket = new SupportTicket([
            'id' => 7,
            'user_id' => 3,
            'subject' => 'Help',
            'message' => 'Need help',
            'status' => 'open',
            'admin_response' => null,
            'created_at' => '2026-01-01 00:00:00',
        ]);

        self::assertSame(7, $ticket->getId());
        self::assertSame(3, $ticket->getUserId());
        self::assertSame('Help', $ticket->getSubject());
        self::assertSame('Need help', $ticket->getMessage());
        self::assertSame('open', $ticket->getStatus());
        self::assertNull($ticket->getAdminResponse());
        self::assertSame('2026-01-01 00:00:00', $ticket->getCreatedAt());

        $ticket->resolve('Done');
        self::assertSame('resolved', $ticket->getStatus());
        self::assertSame('Done', $ticket->getAdminResponse());

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchAll')->willReturn([
            ['id' => 9, 'subject' => 'A'],
        ]);
        $this->bindDatabaseMock($dbMock);

        $rows = SupportTicket::where('user_id', 3);
        self::assertCount(1, $rows);
        self::assertSame(9, $rows[0]['id']);
    }
}
