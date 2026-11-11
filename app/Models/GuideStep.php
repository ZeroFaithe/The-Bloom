<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class GuideStep extends Model
{
    protected static string $table = 'guide_steps';

    public static function forGuide(int $guideId): array
    {
        return Database::query(
            'SELECT * FROM guide_steps WHERE guide_id = ? ORDER BY step_number',
            [$guideId]
        )->fetchAll();
    }

    public static function deleteForGuide(int $guideId): void
    {
        Database::query('DELETE FROM guide_steps WHERE guide_id = ?', [$guideId]);
    }
}
