<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\WorkspaceService;

final class ClientController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $workspace = new WorkspaceService();

        $this->view('clients/index', [
            'title' => 'Clients',
            'clients' => $workspace->clientCards(['search' => $_GET['search'] ?? '']),
            'employees' => User::employees(),
            'clientUsers' => User::clients(),
            'search' => $_GET['search'] ?? '',
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('master_admin');

        $clientId = Database::insert(
            'INSERT INTO clients (company_name, contact_name, contact_email, contact_phone, client_user_id, status) VALUES (:company_name, :contact_name, :contact_email, :contact_phone, :client_user_id, :status)',
            [
                'company_name' => trim((string) $_POST['company_name']),
                'contact_name' => trim((string) $_POST['contact_name']),
                'contact_email' => trim((string) $_POST['contact_email']),
                'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
                'client_user_id' => !empty($_POST['client_user_id']) ? (int) $_POST['client_user_id'] : null,
                'status' => $_POST['status'] ?? 'active',
            ]
        );

        foreach ((array) ($_POST['employee_ids'] ?? []) as $employeeId) {
            Database::insert(
                'INSERT IGNORE INTO employee_client_assignments (employee_user_id, client_id) VALUES (:employee, :client)',
                ['employee' => (int) $employeeId, 'client' => $clientId]
            );
        }

        ActivityLogger::log('client_created', 'client', $clientId, ['company_name' => $_POST['company_name']]);
        $this->flash('success', 'Client created successfully.');
        $this->redirect('clients');
    }
}
