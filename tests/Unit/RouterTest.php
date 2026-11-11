<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Request;
use Core\Response;
use Core\Router;
use PHPUnit\Framework\TestCase;
use Tests\Support\FirstMiddleware;
use Tests\Support\SecondMiddleware;
use Tests\Support\StopMiddleware;
use Tests\Support\TraceMiddleware;

/** How URLs reach controllers, and how middleware wraps them. */
final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        TraceMiddleware::$calls = [];
        $_SESSION = [];
    }

    private function request(string $method, string $path): Request
    {
        return new Request($method, $path, [], [], []);
    }

    public function test_matches_a_plain_path(): void
    {
        $router = new Router();
        $router->get('/guides', fn () => 'all guides');

        $response = $router->dispatch($this->request('GET', '/guides'));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('all guides', $response->getContent());
    }

    public function test_reads_parameters_from_the_url(): void
    {
        $router = new Router();
        $router->get('/guides/{slug}', fn (Request $r) => 'guide ' . $r->param('slug'));

        $response = $router->dispatch($this->request('GET', '/guides/hario-v60'));

        $this->assertSame('guide hario-v60', $response->getContent());
    }

    public function test_url_encoded_parameters_are_decoded(): void
    {
        $router = new Router();
        $router->get('/guides/{slug}', fn (Request $r) => $r->param('slug'));

        $this->assertSame('café', $router->dispatch($this->request('GET', '/guides/caf%C3%A9'))->getContent());
    }

    public function test_a_parameter_does_not_swallow_slashes(): void
    {
        $router = new Router();
        $router->get('/guides/{slug}', fn () => 'guide');

        $this->assertSame(404, $router->dispatch($this->request('GET', '/guides/a/b'))->getStatus());
    }

    public function test_unknown_path_is_404(): void
    {
        $router = new Router();
        $router->get('/guides', fn () => 'ok');

        $response = $router->dispatch($this->request('GET', '/nope'));

        $this->assertSame(404, $response->getStatus());
        $this->assertNotSame('', $response->getContent(), 'the 404 page is rendered');
    }

    public function test_known_path_with_wrong_method_is_405(): void
    {
        $router = new Router();
        $router->post('/logout', fn () => 'bye');

        $this->assertSame(405, $router->dispatch($this->request('GET', '/logout'))->getStatus());
    }

    public function test_head_requests_use_the_get_route(): void
    {
        $router = new Router();
        $router->get('/guides', fn () => 'all guides');

        $this->assertSame(200, $router->dispatch($this->request('HEAD', '/guides'))->getStatus());
    }

    public function test_same_path_can_have_get_and_post(): void
    {
        $router = new Router();
        $router->get('/login', fn () => 'form');
        $router->post('/login', fn () => 'submit');

        $this->assertSame('form', $router->dispatch($this->request('GET', '/login'))->getContent());
        $this->assertSame('submit', $router->dispatch($this->request('POST', '/login'))->getContent());
    }

    public function test_groups_add_a_prefix_and_nest(): void
    {
        $router = new Router();
        $router->group(['prefix' => '/admin'], function (Router $r) {
            $r->get('/', fn () => 'dashboard');
            $r->group(['prefix' => '/methods'], function (Router $r) {
                $r->get('/{id}/edit', fn (Request $req) => 'edit ' . $req->param('id'));
            });
        });

        $this->assertSame('dashboard', $router->dispatch($this->request('GET', '/admin'))->getContent());
        $this->assertSame('edit 7', $router->dispatch($this->request('GET', '/admin/methods/7/edit'))->getContent());
        $this->assertSame(404, $router->dispatch($this->request('GET', '/methods/7/edit'))->getStatus());
    }

    public function test_middleware_runs_in_order_around_the_controller(): void
    {
        $router = new Router();
        $router->use(FirstMiddleware::class);
        $router->group(['middleware' => [SecondMiddleware::class]], function (Router $r) {
            $r->get('/x', function () {
                TraceMiddleware::$calls[] = 'controller';
                return 'done';
            });
        });

        $router->dispatch($this->request('GET', '/x'));

        // Like layers of an onion: global first, then group, then the controller, then back out.
        $this->assertSame(
            ['first:before', 'second:before', 'controller', 'second:after', 'first:after'],
            TraceMiddleware::$calls
        );
    }

    public function test_middleware_can_stop_the_request(): void
    {
        $router = new Router();
        $router->get('/secret', function () {
            TraceMiddleware::$calls[] = 'controller';
            return 'secret';
        }, [StopMiddleware::class]);

        $response = $router->dispatch($this->request('GET', '/secret'));

        $this->assertSame(401, $response->getStatus());
        $this->assertSame(['stop'], TraceMiddleware::$calls, 'the controller never ran');
    }

    public function test_middleware_aliases_come_from_config(): void
    {
        // 'auth' is a short name in config.php; a guest gets sent to the login page.
        $router = new Router();
        $router->get('/admin', fn () => 'dashboard', ['auth']);

        $response = $router->dispatch($this->request('GET', '/admin'));

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('http://localhost/the_bloom/login', $response->getHeader('Location'));
    }

    public function test_middleware_values_after_a_colon_are_passed_on(): void
    {
        // 'role:admin' builds new Role('admin'): an editor is turned away, an admin gets in.
        $router = new Router();
        $router->get('/admin/methods', fn () => 'methods', ['role:admin']);

        $_SESSION['user'] = ['id' => 2, 'name' => 'Eddie', 'role' => 'editor'];
        $this->assertSame(403, $router->dispatch($this->request('GET', '/admin/methods'))->getStatus());

        $_SESSION['user'] = ['id' => 1, 'name' => 'Ana', 'role' => 'admin'];
        $this->assertSame('methods', $router->dispatch($this->request('GET', '/admin/methods'))->getContent());
    }

    public function test_controller_class_and_method_handler(): void
    {
        $router = new Router();
        $router->get('/ping', [PingController::class, 'ping']);

        $response = $router->dispatch($this->request('GET', '/ping'));

        $this->assertSame('{"pong":true}', str_replace([" ", "\n"], '', $response->getContent()));
        $this->assertSame('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
    }
}

/** Tiny controller used only by the test above. */
final class PingController
{
    public function ping(Request $request): Response
    {
        return Response::json(['pong' => true]);
    }
}
