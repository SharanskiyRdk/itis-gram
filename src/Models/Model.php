<?php

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static ?Database $db = null;
    protected static string $table = '';
    protected static array $fillable = [];
    protected array $attributes = [];
    protected array $original = [];

    protected static function getDB(): Database
    {
        if (static::$db === null) {
            static::$db = Database::getInstance();
        }
        return static::$db;
    }

    public static function all(): array
    {
        $db = self::getDB();
        $rows = $db->fetchAll("SELECT * FROM " . static::$table . " WHERE is_deleted = FALSE ORDER BY id DESC");
        return array_map(fn($row) => new static($row), $rows);
    }

    public static function find(int $id): ?static
    {
        $db = self::getDB();
        $row = $db->fetchOne("SELECT * FROM " . static::$table . " WHERE id = ? AND is_deleted = FALSE", [$id]);
        if (!$row) {
            return null;
        }

        $model = new static();
        $model->attributes = $row;
        $model->original = $row;

        return $model;
    }

    public static function where(string $column, $value): array
    {
        $db = self::getDB();
        $rows = $db->fetchAll(
            'SELECT * FROM ' . static::$table . ' WHERE ' . $column . ' = ? AND is_deleted = FALSE',
            [$value]
        );
        return array_map(fn($row) => new static($row), $rows);
    }

    public static function firstWhere(string $column, $value): ?static
    {
        $db = self::getDB();
        $row = $db->fetchOne(
            'SELECT * FROM ' . static::$table . ' WHERE ' . $column . ' = ? AND is_deleted = FALSE LIMIT 1',
            [$value]
        );

        if ($row) {
            $model = new static();
            $model->attributes = $row;
            $model->original = $row;
            return $model;
        }
        return null;
    }

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): void
    {
        if (isset($attributes['id'])) {
            $this->attributes['id'] = $attributes['id'];
        }

        foreach (static::$fillable as $field) {
            if (isset($attributes[$field])) {
                $this->attributes[$field] = $attributes[$field];
            }
        }
        $this->original = $this->attributes;
    }

    public function save(): bool
    {
        if (isset($this->attributes['id'])) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert(): bool
    {
        $fields = array_keys($this->attributes);
        $placeholders = array_map(fn($f) => ":$f", $fields);

        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";

        $db = self::getDB();

        $params = [];
        foreach ($this->attributes as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? 1 : 0;
            } else {
                $params[$key] = $value;
            }
        }

        $result = $db->execute($sql, $params);

        if ($result) {
            $this->attributes['id'] = $db->lastInsertId();
            $this->original = $this->attributes;
        }

        return $result;
    }

    protected function update(): bool
    {
        $fields = array_filter(array_keys($this->attributes), fn($field) => $field !== 'id');
        $sets = array_map(fn($f) => "$f = :$f", $fields);

        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE id = :id";

        $db = self::getDB();

        $params = [];
        foreach ($this->attributes as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? 1 : 0;
            } else {
                $params[$key] = $value;
            }
        }

        return $db->execute($sql, $params);
    }

    public function delete(): bool
    {
        $db = self::getDB();
        return $db->execute("UPDATE " . static::$table . " SET is_deleted = TRUE WHERE id = ?", [$this->id]);
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        if (in_array($name, static::$fillable)) {
            $this->attributes[$name] = $value;
        }
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
