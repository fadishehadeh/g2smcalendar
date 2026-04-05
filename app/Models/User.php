<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function employees(): array
    {
        return Database::fetchAll(
            "SELECT users.id, users.name, users.email
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE roles.name = 'employee' AND users.status = 'active'
             ORDER BY users.name"
        );
    }

    public static function clients(): array
    {
        return Database::fetchAll(
            "SELECT users.id, users.name, users.email
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE roles.name = 'client' AND users.status = 'active'
             ORDER BY users.name"
        );
    }
}
