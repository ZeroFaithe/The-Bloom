<?php
declare(strict_types=1);

namespace Core;

/** Wraps everything the browser sent us. */
class Request
{
    public array $params = []; // route parameters like {slug}

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
    ) {}

    /** $basePath is the folder part of BASE_URL, e.g. "/the-bloom" on XAMPP. */
    public static function capture(string $basePath = ''): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $base = '/' . trim($basePath, '/');
        if ($base !== '/' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        // Allow HTML forms to fake PUT/PATCH/DELETE with a hidden _method field.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        return new self($method, $path, $_GET, $_POST, self::readHeaders());
    }

    private static function readHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        return $headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** Like input(), but always a trimmed string: a tampered field such as email[]=x becomes ''. */
    public function text(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) ? trim($value) : $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
