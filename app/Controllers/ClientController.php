<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientWelcomeMailer;
use App\Services\WorkspaceService;
use RuntimeException;

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

    public function show(): void
    {
        Auth::requireRole(['master_admin', 'employee']);

        $clientId = (int) ($_GET['client_id'] ?? 0);
        $client = $this->findAccessibleClient($clientId);

        if (!$client) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Client Not Found']);
            return;
        }

        $assignedEmployees = Database::fetchAll(
            'SELECT u.id, u.name, u.email
             FROM employee_client_assignments eca
             JOIN users u ON u.id = eca.employee_user_id
             WHERE eca.client_id = :client
             ORDER BY u.name',
            ['client' => $clientId]
        );

        $recentCalendars = Database::fetchAll(
            'SELECT id, title, month, year, status
             FROM calendars
             WHERE client_id = :client
             ORDER BY year DESC, month DESC, id DESC
             LIMIT 6',
            ['client' => $clientId]
        );

        $this->view('clients/show', [
            'title' => $client['company_name'],
            'client' => $client,
            'assignedEmployees' => $assignedEmployees,
            'recentCalendars' => $recentCalendars,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $contactName = trim((string) $_POST['contact_name']);
        $contactEmail = trim((string) $_POST['contact_email']);
        $contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
        $clientUserId = !empty($_POST['client_user_id']) ? (int) $_POST['client_user_id'] : null;
        $generatedPassword = null;

        try {
            if (!empty($_POST['create_portal_access'])) {
                if ($clientUserId === null) {
                    [$clientUserId, $generatedPassword] = $this->createClientUser(
                        $contactName,
                        $contactEmail,
                        $contactPhone,
                        (string) ($_POST['password_mode'] ?? 'auto'),
                        (string) ($_POST['client_password'] ?? '')
                    );
                } else {
                    $generatedPassword = $this->issuePasswordForUser(
                        $clientUserId,
                        (string) ($_POST['password_mode'] ?? 'auto'),
                        (string) ($_POST['client_password'] ?? '')
                    );
                }
            }
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('clients');
        }

        $accountOwnerId = !empty($_POST['account_owner_employee_id']) ? (int) $_POST['account_owner_employee_id'] : null;
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['employee_ids'] ?? [])))));

        if ($accountOwnerId && !in_array($accountOwnerId, $employeeIds, true)) {
            $employeeIds[] = $accountOwnerId;
        }

        $clientId = Database::insert(
            'INSERT INTO clients (company_name, contact_name, contact_email, contact_phone, client_user_id, account_owner_employee_id, status) VALUES (:company_name, :contact_name, :contact_email, :contact_phone, :client_user_id, :account_owner_employee_id, :status)',
            [
                'company_name' => trim((string) $_POST['company_name']),
                'contact_name' => $contactName,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone,
                'client_user_id' => $clientUserId,
                'account_owner_employee_id' => $accountOwnerId,
                'status' => $_POST['status'] ?? 'active',
            ]
        );

        foreach ($employeeIds as $employeeId) {
            if ($employeeId <= 0) {
                continue;
            }

            Database::insert(
                'INSERT IGNORE INTO employee_client_assignments (employee_user_id, client_id) VALUES (:employee, :client)',
                ['employee' => (int) $employeeId, 'client' => $clientId]
            );
        }

        ActivityLogger::log('client_created', 'client', $clientId, ['company_name' => $_POST['company_name']]);

        if (!empty($_POST['create_portal_access']) && $clientUserId !== null && $generatedPassword !== null) {
            try {
                (new ClientWelcomeMailer($this->config))->send(
                    $contactEmail,
                    $contactName,
                    trim((string) $_POST['company_name']),
                    $contactEmail,
                    $generatedPassword
                );
            } catch (RuntimeException $exception) {
                $this->flash('error', 'Client created, but the welcome email failed to send: ' . $exception->getMessage());
            }
        }

        $successMessage = 'Client created successfully.';
        if ($generatedPassword !== null) {
            $successMessage .= " Temporary password: {$generatedPassword}";
        }

        $this->flash('success', $successMessage);
        $this->redirect('clients');
    }

    public function delete(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $client = null;

        if ($clientId > 0) {
            if (($user['role_name'] ?? '') === 'employee') {
                $client = Database::fetch(
                    'SELECT c.id, c.company_name
                     FROM clients c
                     JOIN employee_client_assignments eca ON eca.client_id = c.id
                     WHERE c.id = :id AND eca.employee_user_id = :employee
                     LIMIT 1',
                    ['id' => $clientId, 'employee' => (int) $user['id']]
                );
            } else {
                $client = Database::fetch(
                    'SELECT id, company_name FROM clients WHERE id = :id LIMIT 1',
                    ['id' => $clientId]
                );
            }
        }

        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('clients');
        }

        Database::query('DELETE FROM clients WHERE id = :id', ['id' => $clientId]);
        ActivityLogger::log('client_deleted', 'client', $clientId, ['company_name' => $client['company_name']]);

        $this->flash('success', 'Client deleted successfully.');
        $this->redirect('clients');
    }

    public function resendWelcomeEmail(): void
    {
        Auth::requireRole(['master_admin', 'employee']);

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $client = $this->findAccessibleClient($clientId);

        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('clients');
        }

        if (empty($client['client_user_id']) || empty($client['client_user_email'])) {
            $this->flash('error', 'This client does not have a linked login yet.');
            $this->redirect('clients.show', ['client_id' => $clientId]);
        }

        $password = 'G2-' . substr(bin2hex(random_bytes(6)), 0, 10);
        Database::query(
            'UPDATE users SET password = :password WHERE id = :id',
            ['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $client['client_user_id']]
        );

        try {
            (new ClientWelcomeMailer($this->config))->send(
                (string) $client['client_user_email'],
                (string) ($client['client_user_name'] ?: $client['contact_name']),
                (string) $client['company_name'],
                (string) $client['client_user_email'],
                $password
            );
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('clients.show', ['client_id' => $clientId]);
        }

        $this->flash('success', 'Welcome email resent with a fresh temporary password.');
        $this->redirect('clients.show', ['client_id' => $clientId]);
    }

    public function sendPasswordResetEmail(): void
    {
        Auth::requireRole(['master_admin', 'employee']);

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $client = $this->findAccessibleClient($clientId);

        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('clients');
        }

        if (empty($client['client_user_id']) || empty($client['client_user_email'])) {
            $this->flash('error', 'This client does not have a linked login yet.');
            $this->redirect('clients.show', ['client_id' => $clientId]);
        }

        $password = 'G2-' . substr(bin2hex(random_bytes(6)), 0, 10);
        Database::query(
            'UPDATE users SET password = :password WHERE id = :id',
            ['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $client['client_user_id']]
        );

        try {
            (new ClientWelcomeMailer($this->config))->sendPasswordReset(
                (string) $client['client_user_email'],
                (string) ($client['client_user_name'] ?: $client['contact_name']),
                (string) $client['company_name'],
                (string) $client['client_user_email'],
                $password
            );
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('clients.show', ['client_id' => $clientId]);
        }

        $this->flash('success', 'Password reset email sent with a fresh temporary password.');
        $this->redirect('clients.show', ['client_id' => $clientId]);
    }

    private function findAccessibleClient(int $clientId): ?array
    {
        if ($clientId <= 0) {
            return null;
        }

        $user = Auth::user();
        $params = ['id' => $clientId];
        $scopeJoin = '';
        $scopeWhere = '';

        if (($user['role_name'] ?? '') === 'employee') {
            $scopeJoin = ' JOIN employee_client_assignments access_eca ON access_eca.client_id = c.id ';
            $scopeWhere = ' AND access_eca.employee_user_id = :employee ';
            $params['employee'] = (int) $user['id'];
        }

        return Database::fetch(
            "SELECT c.*,
                    cu.name AS client_user_name,
                    cu.email AS client_user_email,
                    owner.name AS account_owner_name,
                    owner.email AS account_owner_email,
                    COUNT(DISTINCT cal.id) AS calendars_count,
                    COUNT(DISTINCT eca.employee_user_id) AS employees_count
             FROM clients c
             LEFT JOIN users cu ON cu.id = c.client_user_id
             LEFT JOIN users owner ON owner.id = c.account_owner_employee_id
             LEFT JOIN calendars cal ON cal.client_id = c.id
             LEFT JOIN employee_client_assignments eca ON eca.client_id = c.id
             {$scopeJoin}
             WHERE c.id = :id {$scopeWhere}
             GROUP BY c.id
             LIMIT 1",
            $params
        );
    }

    private function createClientUser(string $name, string $email, string $phone, string $passwordMode = 'auto', string $manualPassword = ''): array
    {
        $existing = Database::fetch(
            "SELECT u.id, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1",
            ['email' => $email]
        );
        if ($existing) {
            if (($existing['role_name'] ?? '') !== 'client') {
                throw new RuntimeException('That email already belongs to a non-client account. Link an existing client login or use another email.');
            }
            return [(int) $existing['id'], null];
        }

        $roleId = (int) (Database::fetch("SELECT id FROM roles WHERE name = 'client'")['id'] ?? 0);
        if ($roleId <= 0) {
            throw new RuntimeException('Client role is missing from the database.');
        }

        $password = $passwordMode === 'manual'
            ? trim($manualPassword)
            : 'G2-' . substr(bin2hex(random_bytes(6)), 0, 10);

        if ($passwordMode === 'manual' && $password === '') {
            throw new RuntimeException('Manual password is required when manual password mode is selected.');
        }

        $userId = Database::insert(
            'INSERT INTO users (role_id, name, email, password, status, phone) VALUES (:role_id, :name, :email, :password, :status, :phone)',
            [
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'status' => 'active',
                'phone' => $phone !== '' ? $phone : null,
            ]
        );

        return [$userId, $password];
    }

    private function issuePasswordForUser(int $userId, string $passwordMode = 'auto', string $manualPassword = ''): string
    {
        $password = $passwordMode === 'manual'
            ? trim($manualPassword)
            : 'G2-' . substr(bin2hex(random_bytes(6)), 0, 10);

        if ($passwordMode === 'manual' && $password === '') {
            throw new RuntimeException('Manual password is required when manual password mode is selected.');
        }

        Database::query(
            'UPDATE users SET password = :password WHERE id = :id',
            ['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $userId]
        );

        return $password;
    }
}
