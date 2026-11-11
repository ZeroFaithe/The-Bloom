<?php
declare(strict_types=1);

namespace Core;

/** Every middleware filters a request, then either stops it or passes it on with $next. */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
