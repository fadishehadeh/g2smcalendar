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
