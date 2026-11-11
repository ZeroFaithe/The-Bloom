<?php
declare(strict_types=1);

namespace Tests\Support;

use Core\Middleware;
use Core\Request;
use Core\Response;

/** Test middleware that writes "before"/"after" lines so tests can check the order. */
abstract class TraceMiddleware implements Middleware
{
    public static array $calls = [];

    abstract protected function name(): string;

    public function handle(Request $request, callable $next): Response
    {
        self::$calls[] = $this->name() . ':before';
        $response = $next($request);
        self::$calls[] = $this->name() . ':after';
        return $response;
    }
}
