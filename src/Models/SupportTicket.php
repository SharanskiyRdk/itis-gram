<?php

namespace App\Models;

use App\Models\Model;

class SupportTicket extends Model
{
    protected static string $table = 'support_tickets';
    protected static array $fillable = [
        'user_id', 'subject', 'message', 'status',
        'admin_response', 'created_at', 'updated_at'
    ];

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getUserId(): int
    {
        return (int)($this->attributes['user_id'] ?? 0);
    }

    public function getSubject(): string
    {
        return $this->attributes['subject'] ?? '';
    }

    public function getMessage(): string
    {
        return $this->attributes['message'] ?? '';
    }

    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'open';
    }

    public function getAdminResponse(): ?string
    {
        return $this->attributes['admin_response'] ?? null;
    }

    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'] ?? date('Y-m-d H:i:s');
    }

    public function setSubject(string $subject): void
    {
        $this->attributes['subject'] = $subject;
    }

    public function setMessage(string $message): void
    {
        $this->attributes['message'] = $message;
    }

    public function setStatus(string $status): void
    {
        $this->attributes['status'] = $status;
    }

    public function setAdminResponse(?string $response): void
    {
        $this->attributes['admin_response'] = $response;
    }

    public function resolve(string $response): void
    {
        $this->setStatus('resolved');
        $this->setAdminResponse($response);
    }

    public static function where(string $column, $value): array
    {
        $db = self::getDB();
        $rows = $db->fetchAll(
            "SELECT * FROM " . static::$table . " WHERE $column = ? ORDER BY created_at DESC",
            [$value]
        );
        return $rows;
    }
}
