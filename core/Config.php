<?php
declare(strict_types=1);

namespace Core;

/** Reads settings from config/*.php using dot notation: Config::get('database.host'). */
class Config
{
    private static array $items = [];
    private static string $dir = '';

    public static function setPath(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            $path = self::$dir . "/$file.php";
            self::$items[$file] = is_file($path) ? require $path : [];
        }

        $value = self::$items[$file];
        foreach ($parts as $part) {
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
        $parts = explode('.', $key);
        $file = array_shift($parts);
        self::get($file); // load the file first so other keys are kept

        $target = &self::$items[$file];
        foreach ($parts as $part) {
            if (!isset($target[$part]) || !is_array($target[$part])) {
                $target[$part] = [];
            }
            $target = &$target[$part];
        }
        $target = $value;
    }
}
