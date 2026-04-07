<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Client;
use App\Services\ActivityLogger;
use App\Services\WorkspaceService;

final class EmployeeController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin']);
        $workspace = new WorkspaceService();

        $this->view('employees/index', [
            'title' => 'Employees',
            'employees' => $workspace->employeeCards(['search' => $_GET['search'] ?? '']),
            'search' => $_GET['search'] ?? '',
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['master_admin']);

        $roleId = (int) (Database::fetch("SELECT id FROM roles WHERE name = 'employee'")['id'] ?? 0);
        $employeeId = Database::insert(
            'INSERT INTO users (role_id, name, email, password, status) VALUES (:role_id, :name, :email, :password, :status)',
            [
                'role_id' => $roleId,
                'name' => trim((string) $_POST['name']),
                'email' => trim((string) $_POST['email']),
                'password' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
                'status' => $_POST['status'] ?? 'active',
            ]
        );

        ActivityLogger::log('employee_created', 'user', $employeeId, ['name' => $_POST['name']]);
        $this->flash('success', 'Employee created successfully.');
        $this->redirect('employees');
    }

    public function delete(): void
    {
        Auth::requireRole(['master_admin']);

        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $employee = $employeeId > 0
            ? Database::fetch(
                "SELECT u.id, u.name, r.name AS role_name
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE u.id = :id
                 LIMIT 1",
                ['id' => $employeeId]
            )
            : null;

        if (!$employee || ($employee['role_name'] ?? '') !== 'employee') {
            $this->flash('error', 'Employee not found.');
            $this->redirect('employees');
        }

        if ((int) (Auth::user()['id'] ?? 0) === $employeeId) {
            $this->flash('error', 'You cannot delete your own logged-in account from here.');
            $this->redirect('employees');
        }

        $dependencies = Database::fetch(
            "SELECT
                (SELECT COUNT(*) FROM clients WHERE account_owner_employee_id = :id) AS owned_clients,
                (SELECT COUNT(*) FROM calendars WHERE assigned_employee_id = :id OR created_by = :id) AS calendars_count,
                (SELECT COUNT(*) FROM calendar_items WHERE assigned_employee_id = :id OR created_by = :id) AS items_count,
                (SELECT COUNT(*) FROM item_files WHERE uploaded_by = :id) AS files_count,
                (SELECT COUNT(*) FROM item_comments WHERE user_id = :id) AS comments_count,
                (SELECT COUNT(*) FROM item_status_history WHERE changed_by = :id) AS status_history_count,
                (SELECT COUNT(*) FROM item_edit_history WHERE changed_by = :id) AS edit_history_count",
            ['id' => $employeeId]
        ) ?: [];

        if (array_sum(array_map('intval', $dependencies)) > 0) {
            $this->flash('error', 'Reassign or clear this employee\'s clients and content records before deletion.');
            $this->redirect('employees');
        }

        Database::query('DELETE FROM employee_client_assignments WHERE employee_user_id = :id', ['id' => $employeeId]);
        Database::query('DELETE FROM users WHERE id = :id', ['id' => $employeeId]);

        ActivityLogger::log('employee_deleted', 'user', $employeeId, ['name' => $employee['name']]);
        $this->flash('success', 'Employee deleted successfully.');
        $this->redirect('employees');
    }

    public function assignments(): void
    {
        Auth::requireRole(['master_admin']);
        $workspace = new WorkspaceService();

        $this->view('employees/assignments', [
            'title' => 'Assignments',
            'employees' => $workspace->assignmentCards(),
            'clients' => Client::accessible(),
        ]);
    }

    public function storeAssignment(): void
    {
        Auth::requireRole(['master_admin']);

        Database::insert(
            'INSERT IGNORE INTO employee_client_assignments (employee_user_id, client_id) VALUES (:employee, :client)',
            ['employee' => (int) $_POST['employee_user_id'], 'client' => (int) $_POST['client_id']]
        );

        ActivityLogger::log('assignment_created', 'assignment', null, [
            'employee_user_id' => (int) $_POST['employee_user_id'],
            'client_id' => (int) $_POST['client_id'],
        ]);

        $this->flash('success', 'Client assigned successfully.');
        $this->redirect('assignments');
    }

    public function removeAssignment(): void
    {
        Auth::requireRole(['master_admin']);

        Database::query(
            'DELETE FROM employee_client_assignments WHERE employee_user_id = :employee AND client_id = :client',
            ['employee' => (int) $_POST['employee_user_id'], 'client' => (int) $_POST['client_id']]
        );

        ActivityLogger::log('assignment_removed', 'assignment', null, [
            'employee_user_id' => (int) $_POST['employee_user_id'],
            'client_id' => (int) $_POST['client_id'],
        ]);

        $this->flash('success', 'Assignment removed.');
        $this->redirect('assignments');
    }
}
