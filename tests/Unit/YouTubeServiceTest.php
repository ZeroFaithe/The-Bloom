<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\YouTubeService;
use Core\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The YouTube Data API wrapper. Calls go to a fake API (tests/Support/fake-youtube.php)
 * running on this computer, so the tests are free and don't need a real key.
 */
final class YouTubeServiceTest extends TestCase
{
    private const FAKE_KEY = 'test-key-123';

    /** @var resource|null */
    private static $server = null;
    private static string $fakeUrl = '';

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('curl_init')) {
            return;
        }
        // Find a free port, then start "php -S 127.0.0.1:<port> fake-youtube.php".
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);

        $log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'the_bloom_fake_youtube.log';
        self::$server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/../Support/fake-youtube.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
            $pipes
        ) ?: null;

        for ($i = 0; $i < 50 && self::$server; $i++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($socket) {
                fclose($socket);
                self::$fakeUrl = "http://127.0.0.1:$port";
                return;
            }
            usleep(100_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        Config::set('services.youtube.api_key', '');
        Config::set('services.youtube.base_url', 'https://www.googleapis.com/youtube/v3');
    }

    private function fakeService(string $key = self::FAKE_KEY): YouTubeService
    {
        if (self::$fakeUrl === '') {
            $this->markTestSkipped('The fake YouTube server could not start (is the PHP curl extension enabled?).');
        }
        Config::set('services.youtube.api_key', $key);
        Config::set('services.youtube.base_url', self::$fakeUrl);
        return new YouTubeService();
    }

    protected function tearDown(): void
    {
        Config::set('services.youtube.api_key', '');
    }

    // ---- reading video IDs from what an editor pastes ----

    public static function links(): array
    {
        return [
            'bare id'          => ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch link'       => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch with extra' => ['https://youtube.com/watch?list=PL1&v=dQw4w9WgXcQ&t=42s', 'dQw4w9WgXcQ'],
            'mobile link'      => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'short link'       => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            'shorts'           => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'embed'            => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'spaces trimmed'   => ['  dQw4w9WgXcQ  ', 'dQw4w9WgXcQ'],
            'empty'            => ['', null],
            'too short'        => ['dQw4w9WgXc', null],
            'too long'         => ['dQw4w9WgXcQQ', null],
            'bad characters'   => ['dQw4w9WgX<Q', null],
            'other website'    => ['https://vimeo.com/watch?v=dQw4w9WgXcQ', null],
            'look-alike host'  => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', null],
            'javascript link'  => ['javascript:alert(1)', null],
            'channel page'     => ['https://www.youtube.com/@somebarista', null],
        ];
    }

    #[DataProvider('links')]
    public function test_extract_id(string $pasted, ?string $expected): void
    {
        $this->assertSame($expected, YouTubeService::extractId($pasted));
    }

    public function test_is_valid_id(): void
    {
        $this->assertTrue(YouTubeService::isValidId('a-b_C123456'));
        $this->assertFalse(YouTubeService::isValidId("dQw4w9WgXcQ\n"));
        $this->assertFalse(YouTubeService::isValidId('" onerror=x'));
    }

    // ---- without an API key ----

    public function test_without_a_key_nothing_is_sent(): void
    {
        $youtube = new YouTubeService();

        $this->assertFalse($youtube->isConfigured());
        $this->assertNull($youtube->search('v60'));
        $this->assertSame('not_configured', $youtube->lastError());
        $this->assertStringContainsString('YOUTUBE_API_KEY', $youtube->errorMessage());
    }

    public function test_details_rejects_a_bad_id_before_calling_youtube(): void
    {
        $youtube = new YouTubeService();

        $this->assertNull($youtube->details('nope'));
        $this->assertSame('invalid_id', $youtube->lastError());
    }

    // ---- against the fake API ----

    public function test_search_returns_clean_results(): void
    {
        $results = $this->fakeService()->search('v60');

        $this->assertCount(2, $results, 'the result with a bad ID is skipped');
        $this->assertSame([
            'video_id'      => 'AAAAAAAAAAA',
            'title'         => "Barista's V60 \"guide\"",  // HTML entities decoded once
            'channel_title' => 'Bloom & Co',
            'thumbnail_url' => 'https://i.ytimg.com/vi/x/mqdefault.jpg',
        ], $results[0]);
        $this->assertNull($results[1]['thumbnail_url'], 'non-https thumbnails are dropped');
    }

    public function test_details_of_a_real_video(): void
    {
        $youtube = $this->fakeService();
        $video = $youtube->details('AAAAAAAAAAA');

        $this->assertSame('French Press Basics', $video['title']);
        $this->assertNull($youtube->lastError());
    }

    public function test_details_of_a_missing_video(): void
    {
        $youtube = $this->fakeService();

        $this->assertNull($youtube->details('ZZZZZZZZZZZ'));
        $this->assertSame('not_found', $youtube->lastError());
    }

    public static function failures(): array
    {
        return [
            'daily quota used up' => ['quota', 'quota'],
            'wrong API key'       => ['badkey', 'setup'],
            'YouTube error page'  => ['broken', 'error'],
        ];
    }

    #[DataProvider('failures')]
    public function test_errors_are_named(string $query, string $expectedError): void
    {
        $youtube = $this->fakeService();

        $this->assertNull($youtube->search($query));
        $this->assertSame($expectedError, $youtube->lastError());
        $this->assertNotSame('', $youtube->errorMessage());
    }

    public function test_unreachable_server_is_a_network_error(): void
    {
        $this->fakeService();
        Config::set('services.youtube.base_url', 'http://127.0.0.1:1');
        $youtube = new YouTubeService();

        $this->assertNull($youtube->search('v60'));
        $this->assertSame('network', $youtube->lastError());
        Config::set('services.youtube.base_url', self::$fakeUrl);
    }

    public function test_the_api_key_is_never_written_to_the_log(): void
    {
        $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'the_bloom_tests.log';
        @unlink($logFile);
        $youtube = $this->fakeService();

        $youtube->search('quota');
        $youtube->search('badkey');

        $log = (string) @file_get_contents($logFile);
        $this->assertStringContainsString('YouTube API search failed', $log);
        $this->assertStringNotContainsString(self::FAKE_KEY, $log);
    }
}
