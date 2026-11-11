<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/** Only admins may pass (use after 'auth'). */
class Admin implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Session::hasRole('admin')) {
            return Response::error(403);
        }
        return $next($request);
    }
}
