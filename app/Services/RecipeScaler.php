<?php
declare(strict_types=1);

namespace App\Services;

/** Coffee math: brew ratio, number formatting and step timings. */
final class RecipeScaler
{
    /** Grams of water per gram of coffee (250 g / 15 g = 16.67). */
    public static function ratio(float $coffeeG, float $waterG): float
    {
        return $coffeeG > 0 ? $waterG / $coffeeG : 0.0;
    }

    /** "1:16.7" */
    public static function formatRatio(float $coffeeG, float $waterG): string
    {
        return '1:' . self::number(self::ratio($coffeeG, $waterG));
    }

    /** Scale an amount when the coffee dose changes, keeping the same ratio. */
    public static function scale(float $amount, float $fromCoffeeG, float $toCoffeeG): float
    {
        return $fromCoffeeG > 0 ? $amount * ($toCoffeeG / $fromCoffeeG) : $amount;
    }

    /** 15.0 -> "15", 16.67 -> "16.7" */
    public static function number(float|int|string|null $value): string
    {
        $text = number_format(round((float) $value, 1), 1, '.', '');
        return str_ends_with($text, '.0') ? substr($text, 0, -2) : $text;
    }

    /** 135 -> "2:15" */
    public static function clock(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    /**
     * Adds 'starts_at' and 'ends_at' (seconds from the first pour) to each step.
     * Returns ['steps' => [...], 'total_seconds' => int].
     */
    public static function timeline(array $steps): array
    {
        $clock = 0;
        foreach ($steps as $i => $step) {
            $duration = $step['duration_seconds'] !== null ? (int) $step['duration_seconds'] : null;
            $steps[$i]['starts_at'] = $clock;
            $steps[$i]['ends_at'] = $duration !== null ? $clock + $duration : null;
            $clock += $duration ?? 0;
        }
        return ['steps' => $steps, 'total_seconds' => $clock];
    }
}
