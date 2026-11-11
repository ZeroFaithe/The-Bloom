<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Log;
use Core\Middleware;
use Core\Request;
use Core\Response;

/** Logs "METHOD /path STATUS" for every request. */
class Logger implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        Log::info(sprintf('%s %s %d', $request->method, $request->path, $response->getStatus()));
        return $response;
    }
}
