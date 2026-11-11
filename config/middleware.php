<?php
use App\Middleware\Admin;
use App\Middleware\Auth;
use App\Middleware\Csrf;
use App\Middleware\Logger;

// Short names that routes can use: ['auth', 'admin']
return [
    'auth'   => Auth::class,
    'admin'  => Admin::class,
    'csrf'   => Csrf::class,
    'logger' => Logger::class,
];
