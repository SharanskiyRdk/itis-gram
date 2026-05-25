<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = [
        'name', 'email', 'password', 'avatar', 'bio',
        'is_online', 'last_seen', 'is_deleted',
        'is_verified_student', 'student_group', 'is_banned', 'role',
        'session_id', 'session_ip', 'session_created_at'
    ];


    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->attributes['email'] ?? '';
    }

    public function getPassword(): string
    {
        return $this->attributes['password'] ?? '';
    }

    public function getAvatar(): ?string
    {
        return $this->attributes['avatar'] ?? null;
    }

    public function getBio(): ?string
    {
        return $this->attributes['bio'] ?? null;
    }

    public function isOnline(): bool
    {
        return (bool)($this->attributes['is_online'] ?? false);
    }

    public function getLastSeen(): string
    {
        return $this->attributes['last_seen'] ?? date('Y-m-d H:i:s');
    }

    public function isVerifiedStudent(): bool
    {
        return (bool)($this->attributes['is_verified_student'] ?? false);
    }

    public function getStudentGroup(): ?string
    {
        return $this->attributes['student_group'] ?? null;
    }

    public function isBanned(): bool
    {
        return (bool)($this->attributes['is_banned'] ?? false);
    }

    public function getRole(): string
    {
        return (string)($this->attributes['role'] ?? 'user');
    }

    public function isAdmin(): bool
    {
        $role = $this->getRole();
        return in_array($role, ['admin', 'superadmin'], true);
    }


    public function setName(string $name): void
    {
        $this->attributes['name'] = $name;
    }

    public function setEmail(string $email): void
    {
        $this->attributes['email'] = $email;
    }

    public function setPassword(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setAvatar(?string $avatar): void
    {
        $this->attributes['avatar'] = $avatar;
    }

    public function setBio(?string $bio): void
    {
        $this->attributes['bio'] = $bio;
    }

    public function setOnline(bool $online): void
    {
        $this->attributes['is_online'] = $online;
    }

    public function setLastSeen(string $lastSeen): void
    {
        $this->attributes['last_seen'] = $lastSeen;
    }

    public function updateLastSeen(): void
    {
        $this->attributes['last_seen'] = date('Y-m-d H:i:s');
    }

    public function setIsVerifiedStudent(bool $verified): void
    {
        $this->attributes['is_verified_student'] = $verified;
    }

    public function setStudentGroup(?string $group): void
    {
        $this->attributes['student_group'] = $group;
    }

    public function setIsBanned(bool $banned): void
    {
        $this->attributes['is_banned'] = $banned;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getPassword());
    }

    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'] ?? date('Y-m-d H:i:s');
    }


    public function getDialogues(): array
    {
        $db = self::getDB();
        $sql = 'SELECT d.*, '
            . '(SELECT content FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC LIMIT 1) AS last_message, '
            . '(SELECT created_at FROM messages WHERE dialogue_id = d.id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC LIMIT 1) AS last_message_time '
            . 'FROM dialogues d '
            . 'INNER JOIN dialogue_users du ON d.id = du.dialogue_id '
            . 'WHERE du.user_id = :user_id '
            . 'ORDER BY d.updated_at DESC';

        return $db->fetchAll($sql, ['user_id' => $this->getId()]);
    }
}
