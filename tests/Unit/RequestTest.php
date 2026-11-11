<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Request;
use PHPUnit\Framework\TestCase;

/** Turning what the browser sent into a Request object. */
final class RequestTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
    }

    private function capture(string $method, string $uri, string $base = ''): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        return Request::capture($base);
    }

    public function test_strips_the_xampp_folder_from_the_path(): void
    {
        $this->assertSame('/guides', $this->capture('GET', '/the_bloom/guides', '/the_bloom')->path);
        $this->assertSame('/', $this->capture('GET', '/the_bloom', '/the_bloom')->path);
        $this->assertSame('/', $this->capture('GET', '/the_bloom/', '/the_bloom')->path);
    }

    public function test_does_not_strip_a_folder_that_only_starts_the_same(): void
    {
        $this->assertSame('/the_bloomers', $this->capture('GET', '/the_bloomers', '/the_bloom')->path);
    }

    public function test_query_string_is_not_part_of_the_path(): void
    {
        $this->assertSame('/guides', $this->capture('GET', '/guides/?q=v60')->path);
    }

    public function test_with_no_folder_the_path_is_unchanged(): void
    {
        $this->assertSame('/admin/guides', $this->capture('GET', '/admin/guides')->path);
    }

    public function test_forms_can_fake_put_and_delete(): void
    {
        $_POST = ['_method' => 'delete'];
        $this->assertSame('DELETE', $this->capture('POST', '/admin/guides/3')->method);
    }

    public function test_method_field_is_ignored_on_get(): void
    {
        $_POST = ['_method' => 'DELETE'];
        $this->assertSame('GET', $this->capture('GET', '/guides')->method);
    }

    public function test_input_prefers_the_form_body_over_the_query_string(): void
    {
        $_GET = ['q' => 'from-url', 'page' => '2'];
        $_POST = ['q' => 'from-form'];
        $request = $this->capture('POST', '/search');

        $this->assertSame('from-form', $request->input('q'));
        $this->assertSame('2', $request->input('page'));
        $this->assertSame('none', $request->input('missing', 'none'));
    }

    public function test_headers_are_case_insensitive(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'abc';
        $request = $this->capture('POST', '/x');

        $this->assertSame('abc', $request->header('X-CSRF-Token'));
        $this->assertSame('abc', $request->header('x-csrf-token'));
    }

    public function test_text_always_gives_a_trimmed_string(): void
    {
        $_POST = ['email' => '  alex@example.com ', 'tampered' => ['x'], 'number' => 5];
        $request = $this->capture('POST', '/login');

        $this->assertSame('alex@example.com', $request->text('email'));
        $this->assertSame('', $request->text('tampered'), 'an array becomes an empty string');
        $this->assertSame('', $request->text('missing'));
    }
}
