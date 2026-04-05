<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Client
{
    public static function accessible(): array
    {
        $user = Auth::user();

        if ($user['role_name'] === 'master_admin') {
            return Database::fetchAll(
                'SELECT c.*, u.name AS client_user_name FROM clients c LEFT JOIN users u ON u.id = c.client_user_id ORDER BY c.company_name'
            );
        }

        if ($user['role_name'] === 'employee') {
            return Database::fetchAll(
                'SELECT c.*, u.name AS client_user_name
                 FROM clients c
                 LEFT JOIN users u ON u.id = c.client_user_id
                 JOIN employee_client_assignments eca ON eca.client_id = c.id
                 WHERE eca.employee_user_id = :employee
                 ORDER BY c.company_name',
                ['employee' => $user['id']]
            );
        }

        return Database::fetchAll(
            'SELECT c.*, u.name AS client_user_name FROM clients c LEFT JOIN users u ON u.id = c.client_user_id WHERE c.client_user_id = :user',
            ['user' => $user['id']]
        );
    }
}
