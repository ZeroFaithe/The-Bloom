<?php
declare(strict_types=1);

namespace Core;

/** Appends one line per event to storage/logs/app.log. */
class Log
{
    private static ?string $file = null;

    /** Write to a different file (tests use this so they don't fill the real log). */
    public static function useFile(?string $file): void
    {
        self::$file = $file;
    }

    public static function write(string $level, string $message): void
    {
        // Strip line breaks so user input can't forge extra log lines.
        $message = str_replace(["\r", "\n"], ' ', $message);
        $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
        @file_put_contents(self::$file ?? BASE_PATH . '/storage/logs/app.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    public static function warning(string $message): void
    {
        self::write('warning', $message);
    }

    public static function error(string $message): void
    {
        self::write('error', $message);
    }
}
