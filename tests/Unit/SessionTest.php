<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Session;
use PHPUnit\Framework\TestCase;

/** Login state, flash messages and CSRF tokens (using $_SESSION directly, no browser). */
final class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_token_is_random_64_hex_characters_and_stays_the_same(): void
    {
        $token = Session::token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, Session::token());
    }

    public function test_each_session_gets_a_different_token(): void
    {
        $first = Session::token();
        $_SESSION = [];
        $this->assertNotSame($first, Session::token());
    }

    public function test_validate_token(): void
    {
        $token = Session::token();

        $this->assertTrue(Session::validateToken($token));
        $this->assertFalse(Session::validateToken('wrong'));
        $this->assertFalse(Session::validateToken(''));
        $this->assertFalse(Session::validateToken(null));
        $this->assertFalse(Session::validateToken(strtoupper($token)));
    }

    public function test_guest_has_no_user_or_role(): void
    {
        $this->assertNull(Session::user());
        $this->assertFalse(Session::check());
        $this->assertFalse(Session::hasRole('admin', 'editor'));
    }

    public function test_has_role(): void
    {
        $_SESSION['user'] = ['id' => 2, 'name' => 'Eddie', 'role' => 'editor'];

        $this->assertTrue(Session::check());
        $this->assertTrue(Session::hasRole('admin', 'editor'));
        $this->assertTrue(Session::hasRole('editor'));
        $this->assertFalse(Session::hasRole('admin'));
    }

    public function test_flash_message_can_be_read_once(): void
    {
        Session::flash('success', 'Guide published.');

        $this->assertSame('Guide published.', Session::pull('success'));
        $this->assertNull(Session::pull('success'));
        $this->assertSame('fallback', Session::pull('success', 'fallback'));
    }

    public function test_put_get_forget(): void
    {
        Session::put('theme', 'dark');
        $this->assertSame('dark', Session::get('theme'));

        Session::forget('theme');
        $this->assertSame('light', Session::get('theme', 'light'));
    }
}
