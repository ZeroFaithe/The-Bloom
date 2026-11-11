<?php
declare(strict_types=1);

namespace Core;

/** Reads settings from config.php using dot notation: Config::get('database.host'). */
class Config
{
    private static array $items = [];

    /** Load the settings file (call after Env::load so .env values are available). */
    public static function load(string $file): void
    {
        self::$items = is_file($file) ? require $file : [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    /** Change a setting while the app runs (used by tests): Config::set('app.base_url', '...'). */
    public static function set(string $key, mixed $value): void
    {
        $target = &self::$items;
        foreach (explode('.', $key) as $part) {
            if (!isset($target[$part]) || !is_array($target[$part])) {
                $target[$part] = [];
            }
            $target = &$target[$part];
        }
        $target = $value;
    }
}
