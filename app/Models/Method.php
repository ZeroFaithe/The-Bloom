<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Method extends Model
{
    protected static string $table = 'methods';

    public static function allOrdered(): array
    {
        return Database::query('SELECT * FROM methods ORDER BY name')->fetchAll();
    }

    public static function allWithGuideCounts(): array
    {
        return Database::query(
            'SELECT m.*, COUNT(g.id) AS guide_count
             FROM methods m
             LEFT JOIN guides g ON g.method_id = m.id
             GROUP BY m.id
             ORDER BY m.name'
        )->fetchAll();
    }
}
