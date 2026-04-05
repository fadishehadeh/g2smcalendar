<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class WorkspaceService
{
    private static ?bool $hasSoftDeleteColumns = null;

    public function unreadNotificationsCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return (int) (Database::fetch(
            'SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND is_read = 0',
            ['user_id' => $user['id']]
        )['total'] ?? 0);
    }

    public function dashboardStats(): array
    {
        [$clientScope, $clientParams] = $this->scope('ci.client_id');
        [$calendarScope, $calendarParams] = $this->scope('cal.client_id');

        $user = Auth::user();
        $clients = match ($user['role_name']) {
            'master_admin' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM clients')['total'] ?? 0),
            'employee' => (int) (Database::fetch(
                'SELECT COUNT(DISTINCT client_id) AS total FROM employee_client_assignments WHERE employee_user_id = :user_id',
                ['user_id' => $user['id']]
            )['total'] ?? 0),
            default => 1,
        };

        $employees = (int) (Database::fetch(
            "SELECT COUNT(*) AS total
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'employee' AND u.status = 'active'"
        )['total'] ?? 0);

        $calendars = (int) (Database::fetch(
            "SELECT COUNT(*) AS total FROM calendars cal WHERE {$calendarScope}",
            $calendarParams
        )['total'] ?? 0);

        $downloads = (int) (Database::fetch(
            "SELECT COUNT(*) AS total
             FROM download_logs dl
             JOIN calendar_items ci ON ci.id = dl.calendar_item_id
             WHERE {$clientScope}" . $this->softDeleteClause('ci'),
            $clientParams
        )['total'] ?? 0);

        $totalPosts = (int) (Database::fetch(
            "SELECT COUNT(*) AS total FROM calendar_items ci WHERE {$clientScope}" . $this->softDeleteClause('ci'),
            $clientParams
        )['total'] ?? 0);

        $statusCounts = Database::fetchAll(
            "SELECT ci.status, COUNT(*) AS total
             FROM calendar_items ci
             WHERE {$clientScope}" . $this->softDeleteClause('ci') . "
             GROUP BY ci.status",
            $clientParams
        );

        $map = [];
        foreach ($statusCounts as $row) {
            $map[$row['status']] = (int) $row['total'];
        }

        return [
            'clients' => $clients,
            'employees' => $employees,
            'calendars' => $calendars,
            'pending_approvals' => $map['For Client Approval'] ?? 0,
            'approved' => $map['Approved'] ?? 0,
            'rejected' => $map['Rejected'] ?? 0,
            'downloads' => $downloads,
            'total_posts' => $totalPosts,
        ];
    }

    public function recentActivity(int $limit = 8): array
    {
        [$scope, $params] = $this->scope('ci.client_id');

        $rows = Database::fetchAll(
            "SELECT al.*, u.name, ci.title AS item_title, ci.status, c.company_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN calendar_items ci ON al.entity_type = 'calendar_item' AND ci.id = al.entity_id
             LEFT JOIN clients c ON c.id = ci.client_id
             WHERE ci.id IS NULL OR {$scope}
             ORDER BY al.created_at DESC
             LIMIT {$limit}",
            $params
        );

        foreach ($rows as &$row) {
            $row['detail_url'] = !empty($row['entity_type']) && $row['entity_type'] === 'calendar_item' && !empty($row['entity_id'])
                ? 'index.php?route=calendar.item&item_id=' . (int) $row['entity_id']
                : 'index.php?route=activity';
        }

        return $rows;
    }

    public function notifications(int $limit = 8): array
    {
        $user = Auth::user();

        $rows = Database::fetchAll(
            'SELECT n.*, ci.title AS item_title, c.company_name
             FROM notifications n
             LEFT JOIN calendar_items ci ON ci.id = n.calendar_item_id
             LEFT JOIN clients c ON c.id = ci.client_id
             WHERE n.user_id = :user_id
             ORDER BY n.created_at DESC
             LIMIT ' . $limit,
            ['user_id' => $user['id']]
        );

        foreach ($rows as &$row) {
            $row['detail_url'] = !empty($row['calendar_item_id'])
                ? 'index.php?route=calendar.item&item_id=' . (int) $row['calendar_item_id']
                : 'index.php?route=notifications';
        }

        return $rows;
    }

    public function allPosts(array $filters = []): array
    {
        [$scope, $params] = $this->scope('ci.client_id');
        $where = [$scope];
        $view = ($filters['view'] ?? 'active') === 'trash' ? 'trash' : 'active';

        if ($this->hasSoftDeleteColumns()) {
            $where[] = $view === 'trash' ? 'ci.deleted_at IS NOT NULL' : 'ci.deleted_at IS NULL';
        }

        if (!empty($filters['search'])) {
            $where[] = '(ci.title LIKE :search OR ci.campaign LIKE :search OR c.company_name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        return Database::fetchAll(
            "SELECT ci.*, c.company_name,
                    (
                        SELECT f.id
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_file_id,
                    (
                        SELECT f.mime_type
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_mime_type
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ci.scheduled_date DESC, ci.id DESC
             LIMIT 100",
            $params
        );
    }

    public function approvals(): array
    {
        [$scope, $params] = $this->scope('ci.client_id');
        $params['status'] = 'For Client Approval';

        return Database::fetchAll(
            "SELECT ci.*, c.company_name,
                    (
                        SELECT f.id
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_file_id,
                    (
                        SELECT f.mime_type
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_mime_type
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             WHERE {$scope} AND ci.status = :status" . $this->softDeleteClause('ci') . "
             ORDER BY ci.scheduled_date ASC, ci.id DESC",
            $params
        );
    }

    public function clientCards(array $filters = []): array
    {
        [$scope, $params] = $this->scope('c.id');
        $where = [$scope];

        if (!empty($filters['search'])) {
            $where[] = '(c.company_name LIKE :search OR c.contact_name LIKE :search OR c.contact_email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        return Database::fetchAll(
            "SELECT c.*,
                    COUNT(DISTINCT cal.id) AS calendars_count,
                    COUNT(DISTINCT eca.employee_user_id) AS employees_count
             FROM clients c
             LEFT JOIN calendars cal ON cal.client_id = c.id
             LEFT JOIN employee_client_assignments eca ON eca.client_id = c.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY c.id
             ORDER BY c.company_name",
            $params
        );
    }

    public function employeeCards(array $filters = []): array
    {
        $params = [];
        $where = ["r.name = 'employee'"];

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search OR u.email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $rows = Database::fetchAll(
            "SELECT u.*, r.name AS role_name,
                    COUNT(DISTINCT eca.client_id) AS clients_count,
                    COUNT(DISTINCT CASE WHEN ci.status IN ('Draft', 'In Progress', 'For Client Approval') THEN ci.id END) AS active_tasks
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employee_client_assignments eca ON eca.employee_user_id = u.id
             LEFT JOIN calendar_items ci ON ci.assigned_employee_id = u.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY u.id
             ORDER BY u.name",
            $params
        );

        foreach ($rows as &$row) {
            $row['assigned_clients'] = Database::fetchAll(
                'SELECT c.id, c.company_name
                 FROM employee_client_assignments eca
                 JOIN clients c ON c.id = eca.client_id
                 WHERE eca.employee_user_id = :employee
                 ORDER BY c.company_name',
                ['employee' => $row['id']]
            );
        }

        return $rows;
    }

    public function assignmentCards(): array
    {
        return $this->employeeCards();
    }

    public function artworkLibrary(array $filters = []): array
    {
        [$scope, $params] = $this->scope('ci.client_id');
        $where = [$scope];

        if (!empty($filters['search'])) {
            $where[] = '(ci.title LIKE :search OR c.company_name LIKE :search OR f.original_name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        return Database::fetchAll(
            "SELECT f.*, ci.title, ci.status, ci.version_number, c.company_name
             FROM item_files f
             JOIN calendar_items ci ON ci.id = f.calendar_item_id
             JOIN clients c ON c.id = ci.client_id
             WHERE " . implode(' AND ', $where) . $this->softDeleteClause('ci') . "
             ORDER BY f.created_at DESC
             LIMIT 100",
            $params
        );
    }

    public function activityLog(array $filters = []): array
    {
        $rows = $this->recentActivity(50);

        if (empty($filters['search'])) {
            return $rows;
        }

        $search = strtolower($filters['search']);
        return array_values(array_filter($rows, static function (array $row) use ($search): bool {
            $haystack = strtolower(
                implode(' ', [
                    $row['name'] ?? '',
                    $row['action'] ?? '',
                    $row['item_title'] ?? '',
                    $row['company_name'] ?? '',
                    $row['status'] ?? '',
                ])
            );

            return str_contains($haystack, $search);
        }));
    }

    public function calendarMonth(array $filters): array
    {
        [$scope, $params] = $this->scope('ci.client_id');
        $month = (int) ($filters['month'] ?? date('n'));
        $year = (int) ($filters['year'] ?? date('Y'));
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));

        $where = [$scope, 'ci.scheduled_date BETWEEN :from_date AND :to_date'];
        if ($this->hasSoftDeleteColumns()) {
            $where[] = 'ci.deleted_at IS NULL';
        }
        $params['from_date'] = $from;
        $params['to_date'] = $to;

        if (!empty($filters['client_id'])) {
            $where[] = 'ci.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ci.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['platform'])) {
            $where[] = 'ci.platform = :platform';
            $params['platform'] = $filters['platform'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(ci.title LIKE :search OR c.company_name LIKE :search OR ci.campaign LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $items = Database::fetchAll(
            "SELECT ci.*, c.company_name,
                    (
                        SELECT f.id
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_file_id,
                    (
                        SELECT f.mime_type
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_mime_type
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ci.scheduled_date ASC, ci.id ASC",
            $params
        );

        return [
            'month' => $month,
            'year' => $year,
            'items' => $items,
            'total_posts' => count($items),
        ];
    }

    public function pendingActionItems(int $limit = 6): array
    {
        $user = Auth::user();
        [$scope, $params] = $this->scope('ci.client_id');
        $where = [$scope];

        if ($user['role_name'] === 'client') {
            $where[] = "ci.status = 'For Client Approval'";
        } else {
            $where[] = "ci.status IN ('For Client Approval', 'Revision Requested', 'Rejected')";
        }
        if ($this->hasSoftDeleteColumns()) {
            $where[] = 'ci.deleted_at IS NULL';
        }

        $rows = Database::fetchAll(
            "SELECT ci.*, c.company_name,
                    (
                        SELECT f.id
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_file_id,
                    (
                        SELECT f.mime_type
                        FROM item_files f
                        WHERE f.calendar_item_id = ci.id
                        ORDER BY f.version_number DESC, f.id DESC
                        LIMIT 1
                    ) AS preview_mime_type
             FROM calendar_items ci
             JOIN clients c ON c.id = ci.client_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ci.scheduled_date ASC, ci.id DESC
             LIMIT {$limit}",
            $params
        );

        foreach ($rows as &$row) {
            $row['action_label'] = $user['role_name'] === 'client'
                ? 'Review now'
                : (($row['status'] ?? '') === 'For Client Approval' ? 'Track review' : 'Revise item');
        }

        return $rows;
    }

    private function scope(string $clientColumn): array
    {
        $user = Auth::user();

        if ($user['role_name'] === 'master_admin') {
            return ['1=1', []];
        }

        if ($user['role_name'] === 'employee') {
            return [
                "{$clientColumn} IN (SELECT client_id FROM employee_client_assignments WHERE employee_user_id = :scope_user_id)",
                ['scope_user_id' => $user['id']],
            ];
        }

        return [
            "{$clientColumn} IN (SELECT id FROM clients WHERE client_user_id = :scope_user_id)",
            ['scope_user_id' => $user['id']],
        ];
    }

    private function hasSoftDeleteColumns(): bool
    {
        if (self::$hasSoftDeleteColumns !== null) {
            return self::$hasSoftDeleteColumns;
        }

        $column = Database::fetch("SHOW COLUMNS FROM calendar_items LIKE 'deleted_at'");
        self::$hasSoftDeleteColumns = $column !== null;

        return self::$hasSoftDeleteColumns;
    }

    private function softDeleteClause(string $alias): string
    {
        return $this->hasSoftDeleteColumns() ? " AND {$alias}.deleted_at IS NULL" : '';
    }
}
