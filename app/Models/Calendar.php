<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Calendar
{
    public static function list(array $filters = []): array
    {
        $user = Auth::user();
        $params = [];
        $where = [];

        if ($user['role_name'] === 'employee') {
            $where[] = 'cal.client_id IN (SELECT client_id FROM employee_client_assignments WHERE employee_user_id = :employee)';
            $params['employee'] = $user['id'];
        } elseif ($user['role_name'] === 'client') {
            $where[] = 'cal.client_id IN (SELECT id FROM clients WHERE client_user_id = :client_user)';
            $params['client_user'] = $user['id'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = 'cal.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['month'])) {
            $where[] = 'cal.month = :month';
            $params['month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'cal.year = :year';
            $params['year'] = $filters['year'];
        }

        $sql = "SELECT cal.*, c.company_name, u.name AS employee_name
                FROM calendars cal
                JOIN clients c ON c.id = cal.client_id
                JOIN users u ON u.id = cal.assigned_employee_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY cal.year DESC, cal.month DESC, c.company_name';

        return Database::fetchAll($sql, $params);
    }

    public static function items(int $calendarId, array $filters = []): array
    {
        $params = ['calendar_id' => $calendarId];
        $where = ['ci.calendar_id = :calendar_id'];

        foreach (['status', 'platform', 'campaign'] as $filter) {
            if (!empty($filters[$filter])) {
                $where[] = "ci.{$filter} = :{$filter}";
                $params[$filter] = $filters[$filter];
            }
        }

        if (!empty($filters['search'])) {
            $where[] = '(ci.title LIKE :search OR ci.caption_en LIKE :search OR ci.campaign LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        return Database::fetchAll(
            "SELECT ci.*, c.company_name, u.name AS employee_name
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             JOIN users u ON u.id = ci.assigned_employee_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ci.scheduled_date, ci.scheduled_time, ci.id",
            $params
        );
    }
}
