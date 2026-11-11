<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return static::findBy('email', $email);
    }
}
