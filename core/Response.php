<?php
declare(strict_types=1);

namespace Core;

/** What we send back to the browser. */
class Response
{
    /** Status codes and their names. 419 is not an official code, so Apache needs the name spelled out. */
    private const REASONS = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        419 => 'Page Expired',
        422 => 'Unprocessable Content',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {}

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    /** $url should be a full URL — build it with url('/path'). */
    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    /** Render app/Views/errors/<code>.php (falls back to plain text). */
    public static function error(int $code): self
    {
        $friendly = [404 => 'Page Not Found', 500 => 'Server Error']; // nicer page titles
        $title = $friendly[$code] ?? self::REASONS[$code] ?? 'Error';

        try {
            $html = View::render("errors/$code", ['title' => $title, 'code' => $code]);
        } catch (\Throwable) {
            $html = "<h1>$code</h1><p>$title</p>";
        }
        return new self($html, $code);
    }

    public static function reason(int $code): string
    {
        return self::REASONS[$code] ?? 'Status';
    }

    public function status(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        // Full status line ("HTTP/1.1 419 Page Expired"); a bare http_response_code(419) becomes 500 on Apache.
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        header(sprintf('%s %d %s', $protocol, $this->status, self::REASONS[$this->status] ?? 'Status'), true, $this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->content;
    }
}
