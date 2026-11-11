<?php
declare(strict_types=1);

namespace Core;

/** Login state, one-time flash messages and the CSRF token. */
class Session
{
    private static ?array $oldInput = null;
    private static ?array $errorBag = null;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Save a message for the next page load only. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Read a flash message and remove it. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    // ---- form helpers: remember input and errors after a redirect ----

    public static function flashInput(array $input): void
    {
        $_SESSION['_flash']['_old'] = $input;
    }

    /** Value the user typed last time (read once per request, then cleared). */
    public static function old(string $key, mixed $default = ''): mixed
    {
        if (self::$oldInput === null) {
            self::$oldInput = $_SESSION['_flash']['_old'] ?? [];
            unset($_SESSION['_flash']['_old']);
        }
        return self::$oldInput[$key] ?? $default;
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['_flash']['_errors'] = $errors;
    }

    /** field => message, from the last failed form. */
    public static function errors(): array
    {
        if (self::$errorBag === null) {
            self::$errorBag = $_SESSION['_flash']['_errors'] ?? [];
            unset($_SESSION['_flash']['_errors']);
        }
        return self::$errorBag;
    }

    // ---- authentication ----

    public static function login(array $user): void
    {
        session_regenerate_id(true); // stops session fixation
        $_SESSION['user'] = [
            'id'   => (int) $user['id'],
            'name' => (string) $user['name'],
            'role' => (string) $user['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    // ---- CSRF ----

    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validateToken(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }
}
