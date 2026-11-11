<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\Admin;
use App\Middleware\Auth;
use App\Middleware\Csrf;
use App\Middleware\Logger;
use Core\Request;
use Core\Response;
use Core\Session;
use PHPUnit\Framework\TestCase;

/** The security guards in front of the controllers. */
final class MiddlewareTest extends TestCase
{
    private bool $reachedController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->reachedController = false;
    }

    private function next(): callable
    {
        return function (Request $request): Response {
            $this->reachedController = true;
            return new Response('ok');
        };
    }

    private function request(string $method, array $body = [], array $headers = []): Request
    {
        return new Request($method, '/admin/guides', [], $body, $headers);
    }

    private function loginAs(string $role): void
    {
        $_SESSION['user'] = ['id' => 1, 'name' => 'Test', 'role' => $role];
    }

    // ---- CSRF ----

    public function test_csrf_lets_get_requests_through(): void
    {
        $response = (new Csrf())->handle($this->request('GET'), $this->next());

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($this->reachedController);
    }

    public function test_csrf_blocks_a_post_without_a_token(): void
    {
        $response = (new Csrf())->handle($this->request('POST'), $this->next());

        $this->assertSame(419, $response->getStatus());
        $this->assertFalse($this->reachedController);
    }

    public function test_csrf_blocks_a_wrong_token(): void
    {
        Session::token();
        $response = (new Csrf())->handle($this->request('POST', ['_token' => str_repeat('a', 64)]), $this->next());

        $this->assertSame(419, $response->getStatus());
    }

    public function test_csrf_blocks_a_token_sent_as_an_array(): void
    {
        $response = (new Csrf())->handle($this->request('POST', ['_token' => [Session::token()]]), $this->next());

        $this->assertSame(419, $response->getStatus());
    }

    public function test_csrf_blocks_delete_and_put_too(): void
    {
        $this->assertSame(419, (new Csrf())->handle($this->request('DELETE'), $this->next())->getStatus());
        $this->assertSame(419, (new Csrf())->handle($this->request('PUT'), $this->next())->getStatus());
    }

    public function test_csrf_accepts_the_form_token(): void
    {
        $response = (new Csrf())->handle($this->request('POST', ['_token' => Session::token()]), $this->next());

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($this->reachedController);
    }

    public function test_csrf_accepts_the_header_token_used_by_javascript(): void
    {
        $request = $this->request('POST', [], ['x-csrf-token' => Session::token()]);

        $this->assertSame(200, (new Csrf())->handle($request, $this->next())->getStatus());
    }

    // ---- Auth (staff only) ----

    public function test_auth_sends_guests_to_the_login_page(): void
    {
        $response = (new Auth())->handle($this->request('GET'), $this->next());

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('http://localhost/the_bloom/login', $response->getHeader('Location'));
        $this->assertSame('Please log in to continue.', $_SESSION['_flash']['error']);
        $this->assertFalse($this->reachedController);
    }

    public function test_auth_blocks_visitors(): void
    {
        $this->loginAs('visitor');

        $this->assertSame(403, (new Auth())->handle($this->request('GET'), $this->next())->getStatus());
        $this->assertFalse($this->reachedController);
    }

    public function test_auth_lets_editors_and_admins_in(): void
    {
        foreach (['editor', 'admin'] as $role) {
            $this->loginAs($role);
            $this->assertSame(200, (new Auth())->handle($this->request('GET'), $this->next())->getStatus(), $role);
        }
    }

    // ---- Admin (admins only) ----

    public function test_admin_blocks_editors(): void
    {
        $this->loginAs('editor');

        $this->assertSame(403, (new Admin())->handle($this->request('GET'), $this->next())->getStatus());
        $this->assertFalse($this->reachedController);
    }

    public function test_admin_blocks_guests(): void
    {
        $this->assertSame(403, (new Admin())->handle($this->request('GET'), $this->next())->getStatus());
    }

    public function test_admin_lets_admins_in(): void
    {
        $this->loginAs('admin');

        $this->assertSame(200, (new Admin())->handle($this->request('GET'), $this->next())->getStatus());
    }

    // ---- Logger ----

    public function test_logger_writes_method_path_and_status(): void
    {
        $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'the_bloom_tests.log';
        @unlink($logFile);

        (new Logger())->handle($this->request('GET'), $this->next());

        $this->assertStringContainsString('INFO: GET /admin/guides 200', (string) file_get_contents($logFile));
    }
}
