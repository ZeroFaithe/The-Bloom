<?php
declare(strict_types=1);

namespace Tests\Support;

final class SecondMiddleware extends TraceMiddleware
{
    protected function name(): string
    {
        return 'second';
    }
}
