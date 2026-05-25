<?php

namespace App\Models;

class Message extends Model
{
    protected static string $table = 'messages';
    protected static array $fillable = [
        'dialogue_id',
        'user_id',
        'content',
        'is_read',
        'is_deleted',
        'reply_to',
        'created_at',
    ];

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getDialogueId(): int
    {
        return (int)($this->attributes['dialogue_id'] ?? 0);
    }

    public function getUserId(): int
    {
        return (int)($this->attributes['user_id'] ?? 0);
    }

    public function getContent(): string
    {
        return $this->attributes['content'] ?? '';
    }

    public function setContent(string $content): void
    {
        $this->attributes['content'] = $content;
    }

    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'] ?? date('Y-m-d H:i:s');
    }

    public static function findAllByDialogue(int $dialogueId): array
    {
        $db = self::getDB();
        $rows = $db->fetchAll(
            "SELECT m.*, u.name as user_name, u.avatar 
             FROM messages m
             INNER JOIN users u ON m.user_id = u.id
             WHERE m.dialogue_id = :dialogue_id AND m.is_deleted = FALSE 
             ORDER BY m.created_at ASC",
            ['dialogue_id' => $dialogueId]
        );
        return $rows;
    }
}
