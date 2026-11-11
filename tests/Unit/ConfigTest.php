<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Config;
use PHPUnit\Framework\TestCase;

/** Reading settings from config.php with dot notation. */
final class ConfigTest extends TestCase
{
    public function test_reads_nested_values(): void
    {
        $this->assertSame('https://www.googleapis.com/youtube/v3', Config::get('services.youtube.base_url'));
        $this->assertSame(8, Config::get('services.youtube.timeout'));
    }

    public function test_returns_the_default_for_missing_keys_and_files(): void
    {
        $this->assertSame('x', Config::get('services.youtube.nope', 'x'));
        $this->assertSame('x', Config::get('no_such_section.key', 'x'));
        $this->assertNull(Config::get('app.name.too.deep'));
    }

    public function test_middleware_aliases_point_to_real_classes(): void
    {
        foreach (['auth', 'role', 'csrf', 'logger'] as $alias) {
            $this->assertTrue(class_exists(Config::get("middleware.$alias")), $alias);
        }
    }

    public function test_reads_a_whole_section(): void
    {
        $this->assertSame(['host', 'port', 'name', 'user', 'pass', 'charset'], array_keys(Config::get('database')));
    }

    public function test_set_changes_one_value_and_keeps_the_rest(): void
    {
        Config::set('services.youtube.timeout', 3);

        $this->assertSame(3, Config::get('services.youtube.timeout'));
        $this->assertSame(7, Config::get('services.youtube.cache_days'));

        Config::set('services.youtube.timeout', 8);
    }
}
