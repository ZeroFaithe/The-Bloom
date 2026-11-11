<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Core\asset;
use function Core\csrf_field;
use function Core\e;
use function Core\slugify;
use function Core\url;

/** The small functions every view uses. */
final class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_e_escapes_html_so_scripts_show_as_text(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;hi&quot;)&lt;/script&gt;',
            e('<script>alert("hi")</script>')
        );
        $this->assertSame('Barista&#039;s &amp; Co', e("Barista's & Co"));
        $this->assertSame('42', e(42));
        $this->assertSame('', e(null));
    }

    public static function slugs(): array
    {
        return [
            ['Hario V60 Basics!', 'hario-v60-basics'],
            ['  French   Press  ', 'french-press'],
            ['AeroPress: Inverted (2024)', 'aeropress-inverted-2024'],
            ['Ethiopia Yirgacheffe', 'ethiopia-yirgacheffe'],
            ['../../etc/passwd', 'etc-passwd'],
            ['!!!', ''],
        ];
    }

    #[DataProvider('slugs')]
    public function test_slugify(string $title, string $expected): void
    {
        $this->assertSame($expected, slugify($title));
    }

    public function test_url_joins_base_url_and_path(): void
    {
        $this->assertSame('http://localhost/the_bloom/guides', url('/guides'));
        $this->assertSame('http://localhost/the_bloom/guides', url('guides'));
        $this->assertSame('http://localhost/the_bloom/', url());
    }

    public function test_asset_adds_a_version_number_for_real_files(): void
    {
        $this->assertMatchesRegularExpression('#^http://localhost/the_bloom/css/app\.css\?v=\d+$#', asset('css/app.css'));
    }

    public function test_asset_has_no_version_for_missing_files(): void
    {
        $this->assertSame('http://localhost/the_bloom/css/missing.css', asset('css/missing.css'));
    }

    public function test_csrf_field_contains_the_session_token(): void
    {
        $html = csrf_field();
        $this->assertMatchesRegularExpression('#^<input type="hidden" name="_token" value="[a-f0-9]{64}">$#', $html);
        $this->assertStringContainsString($_SESSION['_csrf'], $html);
    }
}
