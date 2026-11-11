<?php
declare(strict_types=1);

// Front controller: every request enters the framework here.

// With PHP's built-in server (php -S), let it serve real files in public/ such as CSS, JS and images.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    if ($file !== false && $file !== __FILE__ && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
        return false;
    }
}
define('BASE_PATH', dirname(__DIR__));

// Composer's PSR-4 autoloader (configured in composer.json).
require BASE_PATH . '/vendor/autoload.php';

use Core\Application;

$app = new Application(BASE_PATH);
require BASE_PATH . '/routes/web.php';
$app->run();
