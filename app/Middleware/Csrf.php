<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Log;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/** Rejects POST/PUT/DELETE requests that don't carry the session's CSRF token. */
class Csrf implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = $request->body['_token'] ?? $request->header('x-csrf-token');
        if (!Session::validateToken(is_string($token) ? $token : null)) {
            Log::warning("CSRF token rejected for {$request->method} {$request->path}");
            return Response::error(419);
        }
        return $next($request);
    }
}
