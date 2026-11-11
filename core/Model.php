<?php
declare(strict_types=1);

namespace Core;

/**
 * Tiny base model. Extend it and set $table:
 *   class Post extends Model { protected static string $table = 'posts'; }
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function all(): array
    {
        return Database::query('SELECT * FROM `' . static::$table . '`')->fetchAll();
    }

    public static function find(int|string $id): ?array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1';
        return Database::query($sql, [$id])->fetch() ?: null;
    }

    /** First row where $column equals $value, e.g. Guide::findBy('slug', 'v60-basics'). */
    public static function findBy(string $column, mixed $value): ?array
    {
        self::assertColumn($column);
        $sql = 'SELECT * FROM `' . static::$table . "` WHERE `$column` = ? LIMIT 1";
        return Database::query($sql, [$value])->fetch() ?: null;
    }

    /** All rows where $column equals $value. */
    public static function where(string $column, mixed $value): array
    {
        self::assertColumn($column);
        $sql = 'SELECT * FROM `' . static::$table . "` WHERE `$column` = ?";
        return Database::query($sql, [$value])->fetchAll();
    }

    /** Number of rows, optionally where $column equals $value. */
    public static function count(?string $column = null, mixed $value = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM `' . static::$table . '`';
        $params = [];
        if ($column !== null) {
            self::assertColumn($column);
            $sql .= " WHERE `$column` = ?";
            $params[] = $value;
        }
        return (int) Database::query($sql, $params)->fetch()['total'];
    }

    /** Does a row with this value exist? Pass $exceptId to ignore the row being edited. */
    public static function existsWhere(string $column, mixed $value, int|string|null $exceptId = null): bool
    {
        self::assertColumn($column);
        $sql = 'SELECT 1 FROM `' . static::$table . "` WHERE `$column` = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND `' . static::$primaryKey . '` <> ?';
            $params[] = $exceptId;
        }
        return Database::query($sql . ' LIMIT 1', $params)->fetch() !== false;
    }

    private static function assertColumn(string $column): void
    {
        if (!preg_match('/^\w+\z/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: $column");
        }
    }

    public static function create(array $data): string
    {
        $cols = implode('`, `', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        Database::query('INSERT INTO `' . static::$table . "` (`$cols`) VALUES ($marks)", array_values($data));
        return Database::lastInsertId();
    }

    public static function update(int|string $id, array $data): void
    {
        $set = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($data)));
        Database::query(
            'UPDATE `' . static::$table . "` SET $set WHERE `" . static::$primaryKey . '` = ?',
            [...array_values($data), $id]
        );
    }

    public static function delete(int|string $id): void
    {
        Database::query('DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?', [$id]);
    }
}
