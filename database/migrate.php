<?php
declare(strict_types=1);

/**
 * Run from the project folder:  php database/migrate.php
 * Creates the database if needed, applies new .sql files in order,
 * and creates the first admin user if the users table is empty.
 */
if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line.');
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Core\Config;
use Core\Database;
use Core\Env;

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config.php');

$c = Config::get('database');
$server = new PDO(
    "mysql:host={$c['host']};port={$c['port']};charset={$c['charset']}",
    $c['user'],
    $c['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$dbName = preg_replace('/[^A-Za-z0-9_]/', '', $c['name']);
$server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

Database::query('CREATE TABLE IF NOT EXISTS migrations (
    name       VARCHAR(190) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$applied = array_column(Database::query('SELECT name FROM migrations')->fetchAll(), 'name');
$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }
    Database::connection()->exec((string) file_get_contents($file));
    Database::query('INSERT INTO migrations (name) VALUES (?)', [$name]);
    echo "Applied $name\n";
    $count++;
}
echo $count === 0 ? "Nothing to migrate.\n" : "Done: $count migration(s) applied.\n";

// First admin account
$users = (int) Database::query('SELECT COUNT(*) AS total FROM users')->fetch()['total'];
if ($users === 0) {
    $email = (string) Env::get('ADMIN_EMAIL', 'admin@thebloom.local');
    $password = (string) Env::get('ADMIN_PASSWORD', '');
    $generated = $password === '';
    if ($generated) {
        $password = bin2hex(random_bytes(6));
    }
    Database::query(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)',
        ['Admin', $email, password_hash($password, PASSWORD_DEFAULT), 'admin']
    );
    echo "Created admin user: $email" . ($generated ? " / password: $password (change it!)" : '') . "\n";
}
