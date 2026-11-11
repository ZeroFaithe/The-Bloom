<?php
use Core\Env;

return [
    'host'    => Env::get('DB_HOST', '127.0.0.1'),
    'port'    => Env::get('DB_PORT', '3306'),
    'name'    => Env::get('DB_NAME', 'ipt_app'),
    'user'    => Env::get('DB_USER', 'root'),
    'pass'    => Env::get('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
