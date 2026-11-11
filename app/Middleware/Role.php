<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;
use function Core\url;

/**
 * Lets a request through only if the user is logged in with an allowed role.
 *   'auth'       -> admins and editors (the default)
 *   'role:admin' -> admins only
 * Guests are sent to the login page; logged-in users with the wrong role get 403.
 */
class Role implements Middleware
{
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles !== [] ? $roles : ['admin', 'editor'];
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!Session::check()) {
            Session::flash('error', 'Please log in to continue.');
            return Response::redirect(url('/login'));
        }
        if (!Session::hasRole(...$this->roles)) {
            return Response::error(403);
        }
        return $next($request);
    }
}
