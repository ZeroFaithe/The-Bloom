<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RecipeScaler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The coffee math behind every guide page and the ratio calculator. */
final class RecipeScalerTest extends TestCase
{
    public function test_ratio_is_grams_of_water_per_gram_of_coffee(): void
    {
        $this->assertEqualsWithDelta(16.667, RecipeScaler::ratio(15, 250), 0.001);
        $this->assertSame(16.0, RecipeScaler::ratio(20, 320));
    }

    public function test_ratio_with_no_coffee_is_zero_instead_of_dividing_by_zero(): void
    {
        $this->assertSame(0.0, RecipeScaler::ratio(0, 250));
    }

    public function test_format_ratio(): void
    {
        $this->assertSame('1:16.7', RecipeScaler::formatRatio(15, 250));
        $this->assertSame('1:16', RecipeScaler::formatRatio(20, 320));
    }

    public function test_scale_keeps_the_same_ratio(): void
    {
        // Doubling the coffee doubles the water.
        $this->assertSame(500.0, RecipeScaler::scale(250, 15, 30));
        // Halving it halves the water.
        $this->assertSame(160.0, RecipeScaler::scale(320, 20, 10));
    }

    public function test_scale_with_no_starting_coffee_returns_the_amount_unchanged(): void
    {
        $this->assertSame(250.0, RecipeScaler::scale(250, 0, 30));
    }

    public static function numbers(): array
    {
        return [
            'whole number drops .0' => [15.0, '15'],
            'rounds to one decimal' => [16.67, '16.7'],
            'rounds down'           => [2.04, '2'],
            'numeric string'        => ['18.25', '18.3'],
            'null becomes zero'     => [null, '0'],
            'integer'               => [300, '300'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_number_formatting(float|int|string|null $input, string $expected): void
    {
        $this->assertSame($expected, RecipeScaler::number($input));
    }

    public static function clocks(): array
    {
        return [
            [0, '0:00'],
            [5, '0:05'],
            [45, '0:45'],
            [135, '2:15'],
            [600, '10:00'],
        ];
    }

    #[DataProvider('clocks')]
    public function test_clock(int $seconds, string $expected): void
    {
        $this->assertSame($expected, RecipeScaler::clock($seconds));
    }

    public function test_timeline_adds_start_and_end_times(): void
    {
        $result = RecipeScaler::timeline([
            ['instruction' => 'Bloom', 'duration_seconds' => 45],
            ['instruction' => 'Pour', 'duration_seconds' => '30'],
            ['instruction' => 'Swirl', 'duration_seconds' => null],
            ['instruction' => 'Drawdown', 'duration_seconds' => 60],
        ]);

        $steps = $result['steps'];
        $this->assertSame([0, 45], [$steps[0]['starts_at'], $steps[0]['ends_at']]);
        $this->assertSame([45, 75], [$steps[1]['starts_at'], $steps[1]['ends_at']]);
        // A step with no time starts at the current clock and has no end.
        $this->assertSame([75, null], [$steps[2]['starts_at'], $steps[2]['ends_at']]);
        $this->assertSame([75, 135], [$steps[3]['starts_at'], $steps[3]['ends_at']]);
        $this->assertSame(135, $result['total_seconds']);
        $this->assertSame('Bloom', $steps[0]['instruction'], 'original step data is kept');
    }

    public function test_timeline_of_no_steps(): void
    {
        $this->assertSame(['steps' => [], 'total_seconds' => 0], RecipeScaler::timeline([]));
    }
}
