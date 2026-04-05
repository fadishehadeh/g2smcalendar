<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class CalendarItem
{
    public const STATUSES = [
        'Draft',
        'In Progress',
        'For Client Approval',
        'Approved',
        'Rejected',
        'Revision Requested',
        'Ready for Download',
        'Downloaded',
        'Published',
        'Cancelled',
    ];

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT ci.*, c.company_name, cal.title AS calendar_title
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             JOIN calendars cal ON cal.id = ci.calendar_id
             WHERE ci.id = :id",
            ['id' => $id]
        );
    }

    public static function comments(int $itemId, string $role): array
    {
        $sql = "SELECT ic.*, u.name, r.name AS role_name
                FROM item_comments ic
                JOIN users u ON u.id = ic.user_id
                JOIN roles r ON r.id = u.role_id
                WHERE ic.calendar_item_id = :item_id";

        if ($role === 'client') {
            $sql .= " AND ic.visibility = 'shared'";
        }

        $sql .= ' ORDER BY ic.created_at DESC';

        return Database::fetchAll($sql, ['item_id' => $itemId]);
    }

    public static function files(int $itemId): array
    {
        return Database::fetchAll(
            "SELECT f.*, u.name AS uploaded_by_name, r.name AS uploaded_by_role
             FROM item_files f
             LEFT JOIN users u ON u.id = f.uploaded_by
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE f.calendar_item_id = :item_id
             ORDER BY f.version_number DESC, f.created_at DESC, f.id DESC",
            ['item_id' => $itemId]
        );
    }

    public static function history(int $itemId): array
    {
        return Database::fetchAll(
            "SELECT ish.*, u.name
             FROM item_status_history ish
             JOIN users u ON u.id = ish.changed_by
             WHERE ish.calendar_item_id = :item_id
             ORDER BY ish.created_at DESC",
            ['item_id' => $itemId]
        );
    }

    public static function activity(int $itemId): array
    {
        return Database::fetchAll(
            "SELECT al.*, u.name, r.name AS role_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE al.entity_type = 'calendar_item' AND al.entity_id = :item_id
             ORDER BY al.created_at DESC, al.id DESC",
            ['item_id' => $itemId]
        );
    }

    public static function canAccess(array $item): bool
    {
        $user = Auth::user();

        if ($user['role_name'] === 'master_admin') {
            return true;
        }

        if ($user['role_name'] === 'employee') {
            return (bool) Database::fetch(
                'SELECT 1 FROM employee_client_assignments WHERE employee_user_id = :employee AND client_id = :client',
                ['employee' => $user['id'], 'client' => $item['client_id']]
            );
        }

        return (int) $item['client_id'] === (int) (Database::fetch(
            'SELECT id FROM clients WHERE client_user_id = :user',
            ['user' => $user['id']]
        )['id'] ?? 0);
    }
}
