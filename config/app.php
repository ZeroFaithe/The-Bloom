<?php
use Core\Env;

return [
    'name'     => Env::get('APP_NAME', 'The Bloom'),
    'env'      => Env::get('APP_ENV', 'local'),
    'debug'    => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'base_url' => rtrim((string) Env::get('BASE_URL', 'http://localhost:8000'), '/'),
];
