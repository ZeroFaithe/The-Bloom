<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;
use function Core\url;

/** Only logged-in staff (admins and editors) may pass. */
class Auth implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Session::check()) {
            Session::flash('error', 'Please log in to continue.');
            return Response::redirect(url('/login'));
        }
        if (!Session::hasRole('admin', 'editor')) {
            return Response::error(403);
        }
        return $next($request);
    }
}
