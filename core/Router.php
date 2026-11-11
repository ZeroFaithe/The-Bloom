<?php
declare(strict_types=1);

namespace Core;

use Closure;

/** Maps URLs (like /guides/{slug}) to controller actions and runs middleware. */
class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];
    private array $groupStack = [];

    public function get(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** Middleware for every route. Accepts an alias ('csrf') or a class name. */
    public function use(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Share a prefix and middleware between several routes:
     *   $router->group(['prefix' => '/admin', 'middleware' => ['auth']], function (Router $r) { ... });
     */
    public function group(array $options, Closure $callback): void
    {
        $this->groupStack[] = [
            'prefix'     => $options['prefix'] ?? '',
            'middleware' => $options['middleware'] ?? [],
        ];
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, array|Closure $handler, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
        }

        $path = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        // turn {slug} into a named regex group
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        // HEAD asks for a GET page without its body; the web server drops the body for us.
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            $request->params = array_map('rawurldecode', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            $core = fn (Request $req): Response => $this->callHandler($route['handler'], $req);

            return $this->runMiddleware(
                array_merge($this->globalMiddleware, $route['middleware']),
                $request,
                $core
            );
        }

        return $this->errorPage($pathMatched ? 405 : 404);
    }

    private function runMiddleware(array $stack, Request $request, callable $core): Response
    {
        // Build the "onion": the last middleware sits closest to the controller.
        $next = $core;
        foreach (array_reverse($stack) as $name) {
            [$class, $args] = $this->resolveMiddleware($name);
            $next = function (Request $req) use ($class, $args, $next): Response {
                return (new $class(...$args))->handle($req, $next);
            };
        }
        return $next($request);
    }

    /**
     * Turns a short name into a class plus its values (see 'middleware' in config.php):
     *   'auth'              -> [Role::class, []]
     *   'role:admin,editor' -> [Role::class, ['admin', 'editor']]
     */
    private function resolveMiddleware(string $name): array
    {
        [$alias, $values] = array_pad(explode(':', $name, 2), 2, '');
        $args = $values === '' ? [] : array_map('trim', explode(',', $values));
        return [Config::get('middleware.' . $alias, $alias), $args];
    }

    private function callHandler(array|Closure $handler, Request $request): Response
    {
        if ($handler instanceof Closure) {
            $result = $handler($request);
        } else {
            [$class, $method] = $handler;
            $result = (new $class())->$method($request);
        }
        return $result instanceof Response ? $result : new Response((string) $result);
    }

    public function errorPage(int $code): Response
    {
        return Response::error($code);
    }
}
