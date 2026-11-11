<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Core\Controller;
use Core\Log;
use Core\Request;
use Core\Response;
use Core\Session;
use function Core\url;

class AuthController extends Controller
{
    // Compared against when the email is unknown, so timing doesn't reveal which emails exist.
    private const DUMMY_HASH = '$2y$10$0t/PlxibDjmVLk9w8Y4CW.2aPRsDyWTAhJUJuHuf3Dv02KyWJYneC';

    public function showLogin(Request $request): Response
    {
        if (Session::check()) {
            return $this->redirect(url($this->homeFor()));
        }
        return $this->view('auth/login', ['title' => 'Log in']);
    }

    public function login(Request $request): Response
    {
        $email = $request->text('email');
        $password = $request->input('password', '');
        $password = is_string($password) ? $password : ''; // not trimmed: spaces can be part of a password

        $user = $email !== '' ? User::findByEmail($email) : null;
        $valid = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH) && $user !== null;

        if (!$valid) {
            Log::warning("Failed login for $email");
            Session::flash('error', 'Invalid email or password.');
            Session::flash('old_email', $email);
            return $this->redirect(url('/login'));
        }

        Session::login($user);
        Log::info("Login: user {$user['id']} ({$user['role']})");
        Session::flash('success', 'Welcome back, ' . $user['name'] . '!');
        return $this->redirect(url($this->homeFor()));
    }

    /** Staff go to the dashboard; anyone else (visitor accounts) goes to the public site. */
    private function homeFor(): string
    {
        return Session::hasRole('admin', 'editor') ? '/admin' : '/';
    }

    public function logout(Request $request): Response
    {
        Session::logout();
        Session::flash('success', 'You have been logged out.');
        return $this->redirect(url('/'));
    }
}
