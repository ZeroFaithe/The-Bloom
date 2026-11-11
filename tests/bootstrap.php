<?php
declare(strict_types=1);

/*
 * Runs once before the tests. It loads the framework the same way public/index.php does,
 * but it does NOT read .env and does NOT touch the database or the real log file.
 */

use Core\Config;
use Core\Log;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Config::load(BASE_PATH . '/config.php');
Config::set('app.base_url', 'http://localhost/the_bloom');
Config::set('app.debug', false);
Config::set('services.youtube.api_key', ''); // tests that need a key set their own fake one

// Log lines from tests go to a temporary file instead of storage/logs/app.log.
Log::useFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'the_bloom_tests.log');

$_SESSION = [];
