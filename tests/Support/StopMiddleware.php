<?php
declare(strict_types=1);

namespace Tests\Support;

use Core\Middleware;
use Core\Request;
use Core\Response;

/** Test middleware that never lets the request reach the controller. */
final class StopMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        TraceMiddleware::$calls[] = 'stop';
        return new Response('stopped', 401);
    }
}
