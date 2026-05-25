<?php

namespace App\Models;

class Dialogue extends Model
{
    protected static string $table = 'dialogues';
    protected static array $fillable = ['type', 'title', 'group_code', 'avatar', 'created_by', 'created_at', 'updated_at', 'is_deleted'];

    public mixed $updatedAt;

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getType(): string
    {
        return $this->attributes['type'] ?? 'private';
    }

    public function getTitle(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    public function getAvatar(): ?string
    {
        return $this->attributes['avatar'] ?? null;
    }

    public function getGroupCode(): ?string
    {
        return $this->attributes['group_code'] ?? null;
    }

    public function getCreatedBy(): ?int
    {
        return $this->attributes['created_by'] ?? null;
    }

    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'] ?? date('Y-m-d H:i:s');
    }

    public function getUpdatedAt(): string
    {
        return $this->attributes['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function getDeletedAt(): ?string
    {
        return $this->attributes['is_deleted'] ?? null;
    }

    public function setTitle(?string $title): void
    {
        $this->attributes['title'] = $title;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->attributes['avatar'] = $avatar;
    }

    public function setGroupCode(?string $groupCode): void
    {
        $this->attributes['group_code'] = $groupCode;
    }

    public function addParticipant(int $userId): bool
    {
        $db = self::getDB();
        return $db->execute(
            "INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:dialogue_id, :user_id, NOW()) ON CONFLICT (dialogue_id, user_id) DO NOTHING",
            ['dialogue_id' => $this->getId(), 'user_id' => $userId]
        );
    }

    public function addParticipantIfMissing(int $userId): bool
    {
        return $this->addParticipant($userId);
    }

    public function isParticipant(int $userId): bool
    {
        $db = self::getDB();
        $result = $db->fetchOne(
            "SELECT 1 FROM dialogue_users WHERE dialogue_id = :dialogue_id AND user_id = :user_id",
            ['dialogue_id' => $this->getId(), 'user_id' => $userId]
        );
        return !empty($result);
    }

    public function getParticipants(): array
    {
        $db = self::getDB();
        $rows = $db->fetchAll(
            "SELECT u.id, u.name, u.email, u.avatar, u.is_online, u.last_seen
         FROM users u
         INNER JOIN dialogue_users du ON u.id = du.user_id
         WHERE du.dialogue_id = :dialogue_id AND u.is_deleted = FALSE",
            ['dialogue_id' => $this->getId()]
        );
        return $rows;
    }
}
