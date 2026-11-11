<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Guide extends Model
{
    protected static string $table = 'guides';

    public const DIFFICULTIES = ['beginner', 'intermediate', 'advanced'];
    public const STATUSES = ['draft', 'published'];

    /** Guides with method and author names, newest changes first. */
    public static function listForAdmin(?int $limit = null): array
    {
        $sql = 'SELECT g.id, g.title, g.slug, g.difficulty, g.status, g.updated_at,
                       m.name AS method_name, u.name AS author_name
                FROM guides g
                JOIN methods m ON m.id = g.method_id
                JOIN users u ON u.id = g.author_id
                ORDER BY g.updated_at DESC, g.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return Database::query($sql)->fetchAll();
    }

    /** Published guides for the public list, with optional filters. */
    public static function searchPublished(string $keyword = '', ?int $methodId = null, ?string $difficulty = null): array
    {
        $sql = 'SELECT g.id, g.title, g.slug, g.summary, g.difficulty, g.coffee_g, g.water_g, g.temperature_c,
                       m.name AS method_name, m.slug AS method_slug
                FROM guides g
                JOIN methods m ON m.id = g.method_id
                WHERE g.status = ?';
        $params = ['published'];

        if ($methodId !== null) {
            $sql .= ' AND g.method_id = ?';
            $params[] = $methodId;
        }
        if ($difficulty !== null) {
            $sql .= ' AND g.difficulty = ?';
            $params[] = $difficulty;
        }
        if ($keyword !== '') {
            // Escape LIKE wildcards so "%" or "_" in a search are treated as plain text.
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $sql .= ' AND (g.title LIKE ? OR g.summary LIKE ? OR m.name LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $sql .= ' ORDER BY g.updated_at DESC, g.id DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    /** One guide (any status) with its method and author names. */
    public static function findWithMethodBySlug(string $slug): ?array
    {
        $sql = 'SELECT g.*, m.name AS method_name, m.slug AS method_slug, u.name AS author_name
                FROM guides g
                JOIN methods m ON m.id = g.method_id
                JOIN users u ON u.id = g.author_id
                WHERE g.slug = ?
                LIMIT 1';
        return Database::query($sql, [$slug])->fetch() ?: null;
    }

    /** A guide's steps (the guide_steps table), in order. */
    public static function steps(int $guideId): array
    {
        return Database::query(
            'SELECT * FROM guide_steps WHERE guide_id = ? ORDER BY step_number',
            [$guideId]
        )->fetchAll();
    }

    public static function stepCount(int $guideId): int
    {
        return (int) Database::query('SELECT COUNT(*) FROM guide_steps WHERE guide_id = ?', [$guideId])->fetchColumn();
    }

    /**
     * Save a guide and its steps together: either everything is saved or nothing is.
     * Pass $id = null to create; returns the guide id.
     */
    public static function saveWithSteps(?int $id, array $data, array $steps, int $authorId): int
    {
        return Database::transaction(function () use ($id, $data, $steps, $authorId): int {
            if ($id === null) {
                $id = (int) static::create($data + ['author_id' => $authorId]);
            } else {
                static::update($id, $data);
                Database::query('DELETE FROM guide_steps WHERE guide_id = ?', [$id]);
            }

            foreach (array_values($steps) as $index => $step) {
                Database::query(
                    'INSERT INTO guide_steps (guide_id, step_number, instruction, duration_seconds, pour_g)
                     VALUES (?, ?, ?, ?, ?)',
                    [$id, $index + 1, $step['instruction'], $step['duration_seconds'], $step['pour_g']]
                );
            }
            return $id;
        });
    }
}
