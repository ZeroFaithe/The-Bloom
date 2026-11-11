<?php
declare(strict_types=1);

namespace Core;

use Throwable;

/** Boots the framework: loads settings, then turns a Request into a Response. */
class Application
{
    public Router $router;
    public Request $request;

    public function __construct(public readonly string $basePath)
    {
        Env::load($basePath . '/.env');
        Config::setPath($basePath . '/config');
        Session::start();

        // On XAMPP the site may live at http://localhost/the-bloom, so strip that folder from the URL.
        $folder = (string) parse_url((string) Config::get('app.base_url', ''), PHP_URL_PATH);

        $this->router  = new Router();
        $this->request = Request::capture($folder);
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch($this->request);
        } catch (Throwable $e) {
            $response = $this->handleError($e);
        }
        $response->send();
    }

    private function handleError(Throwable $e): Response
    {
        Log::error(get_class($e) . ': ' . $e->getMessage());

        if (Config::get('app.debug')) {
            $html = '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
            return new Response($html, 500);
        }
        return Response::error(500);
    }
}
