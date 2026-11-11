<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Response;
use PHPUnit\Framework\TestCase;

/** What the framework sends back to the browser. */
final class ResponseTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_defaults_to_a_200_html_page(): void
    {
        $response = new Response('<p>Hi</p>');

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('<p>Hi</p>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function test_json(): void
    {
        $response = Response::json(['title' => 'Café'], 422);

        $this->assertSame(422, $response->getStatus());
        $this->assertSame(['title' => 'Café'], json_decode($response->getContent(), true));
        $this->assertStringContainsString('Café', $response->getContent(), 'accents are kept as they are');
    }

    public function test_redirect(): void
    {
        $response = Response::redirect('http://localhost/the_bloom/admin');

        $this->assertSame(302, $response->getStatus());
        $this->assertSame('http://localhost/the_bloom/admin', $response->getHeader('Location'));
        $this->assertSame('', $response->getContent());
    }

    public function test_error_pages_render_with_the_right_status(): void
    {
        foreach ([403 => 'Forbidden', 404 => 'Page Not Found', 419 => 'Page Expired', 500 => 'Server Error'] as $code => $title) {
            $response = Response::error($code);
            $this->assertSame($code, $response->getStatus());
            $this->assertStringContainsString($title, $response->getContent(), (string) $code);
        }
    }

    public function test_every_error_code_has_a_name_for_the_status_line(): void
    {
        // Apache turns a status code it doesn't know (like 419) into 500 unless a name is sent with it.
        foreach ([403, 404, 405, 419, 422, 500, 502, 503] as $code) {
            $this->assertNotSame('Status', Response::reason($code), (string) $code);
        }
        $this->assertSame('Page Expired', Response::reason(419));
    }
}
