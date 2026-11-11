<?php
declare(strict_types=1);

namespace App\Services;

use Core\Config;
use Core\Log;

/**
 * Talks to the YouTube Data API v3 with an API key (public video data only, no user accounts).
 *   search.list -> find videos for the editor's video picker (costs quota, staff only)
 *   videos.list -> title, channel and thumbnail for the video a guide uses (saved in video_cache)
 * Every method returns null on failure; lastError() says why and errorMessage() explains it.
 */
class YouTubeService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private ?string $lastError = null;

    private const MESSAGES = [
        'not_configured' => 'YouTube search is not set up yet. Add YOUTUBE_API_KEY to .env.',
        'quota'          => "Today's YouTube limit has been reached. Paste a video link instead, or try again tomorrow.",
        'setup'          => 'YouTube rejected the request. Check that the API key is correct and that YouTube Data API v3 is enabled for it.',
        'network'        => 'Could not reach YouTube. Check the internet connection and try again.',
        'not_found'      => 'YouTube could not find that video. It may be private or deleted.',
        'invalid_id'     => 'That is not a valid YouTube video ID.',
        'error'          => 'YouTube did not respond as expected. Try again, or paste a video link instead.',
    ];

    public function __construct()
    {
        $config = Config::get('services.youtube', []);
        $this->apiKey = (string) ($config['api_key'] ?? '');
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->timeout = (int) ($config['timeout'] ?? 8);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function errorMessage(): string
    {
        return self::MESSAGES[$this->lastError ?? 'error'] ?? self::MESSAGES['error'];
    }

    /** search.list: embeddable videos matching a search phrase. */
    public function search(string $query, int $max = 8): ?array
    {
        $data = $this->get('search', [
            'part'            => 'snippet',
            'type'            => 'video',
            'videoEmbeddable' => 'true',
            'safeSearch'      => 'strict',
            'maxResults'      => max(1, min(25, $max)),
            'q'               => $query,
        ]);
        if ($data === null) {
            return null;
        }

        $videos = [];
        foreach ($data['items'] ?? [] as $item) {
            $id = (string) ($item['id']['videoId'] ?? '');
            if (self::isValidId($id)) {
                $videos[] = $this->fromSnippet($id, $item['snippet'] ?? []);
            }
        }
        return $videos;
    }

    /** videos.list: details for one video. */
    public function details(string $videoId): ?array
    {
        if (!self::isValidId($videoId)) {
            $this->lastError = 'invalid_id';
            return null;
        }
        $data = $this->get('videos', ['part' => 'snippet', 'id' => $videoId]);
        if ($data === null) {
            return null;
        }
        $item = $data['items'][0] ?? null;
        if (!is_array($item)) {
            $this->lastError = 'not_found';
            return null;
        }
        return $this->fromSnippet($videoId, $item['snippet'] ?? []);
    }

    /** YouTube video IDs are exactly 11 characters: letters, digits, "-" and "_". */
    public static function isValidId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{11}\z/', $id) === 1; // \z, not $: $ would also allow a newline at the end
    }

    /**
     * Accepts a bare ID or a common YouTube link and returns the video ID, or null.
     * youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, youtube.com/embed/ID
     */
    public static function extractId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (self::isValidId($value)) {
            return $value;
        }

        $parts = parse_url($value);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $host = strtolower((string) preg_replace('/^(www\.|m\.)/', '', $parts['host']));
        $path = $parts['path'] ?? '';
        $id = '';

        if ($host === 'youtu.be') {
            $id = explode('/', ltrim($path, '/'))[0];
        } elseif (in_array($host, ['youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (isset($query['v']) && is_string($query['v'])) {
                $id = $query['v'];
            } elseif (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[1];
            }
        }
        return self::isValidId($id) ? $id : null;
    }

    private function fromSnippet(string $id, array $snippet): array
    {
        $thumbs = $snippet['thumbnails'] ?? [];
        $thumb = $thumbs['medium']['url'] ?? $thumbs['high']['url'] ?? $thumbs['default']['url'] ?? null;

        return [
            'video_id'      => $id,
            // search.list returns titles with HTML entities (&#39;); decode so our views escape them only once.
            'title'         => mb_substr(self::decode($snippet['title'] ?? ''), 0, 255),
            'channel_title' => mb_substr(self::decode($snippet['channelTitle'] ?? ''), 0, 150),
            'thumbnail_url' => is_string($thumb) && str_starts_with($thumb, 'https://') ? $thumb : null,
        ];
    }

    private static function decode(mixed $text): string
    {
        return html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** GET request to the API. Never logs the URL, because it contains the API key. */
    private function get(string $endpoint, array $params): ?array
    {
        $this->lastError = null;

        if (!$this->isConfigured()) {
            $this->lastError = 'not_configured';
            return null;
        }
        if (!function_exists('curl_init')) {
            $this->lastError = 'network';
            Log::error('YouTube API: the PHP curl extension is not enabled (php.ini: extension=curl).');
            return null;
        }

        $url = $this->baseUrl . '/' . $endpoint . '?' . http_build_query($params + ['key' => $this->apiKey]);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        unset($curl);

        if ($body === false) {
            $this->lastError = 'network';
            Log::error("YouTube API $endpoint request failed: $curlError");
            return null;
        }

        $data = json_decode((string) $body, true);
        if ($status === 200 && is_array($data)) {
            return $data;
        }

        $reason = is_array($data)
            ? (string) ($data['error']['errors'][0]['reason'] ?? $data['error']['status'] ?? 'unknown')
            : 'invalid response';
        $this->lastError = match (true) {
            in_array($reason, ['quotaExceeded', 'dailyLimitExceeded', 'rateLimitExceeded', 'userRateLimitExceeded'], true) => 'quota',
            $status === 400, $status === 401, $status === 403 => 'setup',
            default => 'error',
        };
        Log::error("YouTube API $endpoint failed: HTTP $status ($reason)");
        return null;
    }
}
