<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

/** Saved YouTube video details, so public pages never call the API. */
class VideoCache extends Model
{
    protected static string $table = 'video_cache';
    protected static string $primaryKey = 'video_id';

    /** Was this video fetched within the last $days days? */
    public static function isFresh(string $videoId, int $days): bool
    {
        return Database::query(
            'SELECT 1 FROM video_cache WHERE video_id = ? AND fetched_at >= (NOW() - INTERVAL ? DAY) LIMIT 1',
            [$videoId, $days]
        )->fetch() !== false;
    }

    /** Insert the video, or refresh it if it is already saved. */
    public static function store(array $video): void
    {
        Database::query(
            'INSERT INTO video_cache (video_id, title, channel_title, thumbnail_url, fetched_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                 title = VALUES(title),
                 channel_title = VALUES(channel_title),
                 thumbnail_url = VALUES(thumbnail_url),
                 fetched_at = NOW()',
            [$video['video_id'], $video['title'], $video['channel_title'], $video['thumbnail_url']]
        );
    }
}
