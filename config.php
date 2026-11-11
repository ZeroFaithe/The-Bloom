<?php
/*
 * All settings in one place. Read them with dot notation, for example:
 *   Config::get('app.base_url')   Config::get('services.youtube.timeout')
 * Secret or machine-specific values come from .env through Env::get().
 */

use App\Middleware\Csrf;
use App\Middleware\Logger;
use App\Middleware\Role;
use Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'The Bloom'),
        'env'      => Env::get('APP_ENV', 'local'),
        'debug'    => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
        'base_url' => rtrim((string) Env::get('BASE_URL', 'http://localhost:8000'), '/'),
    ],

    'database' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => Env::get('DB_PORT', '3306'),
        'name'    => Env::get('DB_NAME', 'ipt_app'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    // Short names routes can use. A value after ":" is passed to the middleware:
    //   'auth'       -> any staff member (admin or editor)
    //   'role:admin' -> admins only
    'middleware' => [
        'auth'   => Role::class,
        'role'   => Role::class,
        'csrf'   => Csrf::class,
        'logger' => Logger::class,
    ],

    'services' => [
        'youtube' => [
            'api_key'    => Env::get('YOUTUBE_API_KEY', ''),
            'base_url'   => 'https://www.googleapis.com/youtube/v3',
            'timeout'    => 8,   // seconds to wait for YouTube before giving up
            'cache_days' => 7,   // refresh a video's saved title/thumbnail after this many days
        ],
    ],
];
