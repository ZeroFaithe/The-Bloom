<?php
declare(strict_types=1);

namespace Tests\Support;

final class FirstMiddleware extends TraceMiddleware
{
    protected function name(): string
    {
        return 'first';
    }
}
