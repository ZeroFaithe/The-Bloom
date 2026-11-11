<?php
declare(strict_types=1);

namespace Core;

/** Base class for all controllers. */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): Response
    {
        return new Response(View::render($view, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }
}
