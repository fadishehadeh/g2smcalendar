<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CalendarItem;
use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientWelcomeMailer;
use App\Services\DemoAssetService;
use App\Services\NotificationService;
use App\Services\UploadService;
use App\Services\WizardDraftService;
use RuntimeException;
use Throwable;

final class WizardController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $draft = (new WizardDraftService())->load('bulk_posts', (int) $user['id']);
        foreach (['client_id', 'calendar_id', 'month', 'year'] as $key) {
            if (!empty($_GET[$key])) {
                $draft[$key] = $_GET[$key];
            }
        }

        $this->view('calendar/wizard', [
            'title' => 'Bulk Post Generation Wizard',
            'clients' => Client::accessible(),
            'employees' => User::employees(),
            'draft' => $draft,
            'calendars' => $this->accessibleCalendars(),
        ]);
    }

    public function calendarCreate(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();

        $this->view('wizard/calendar_create', [
            'title' => 'Calendar Creation Wizard',
            'clients' => Client::accessible(),
            'employees' => User::employees(),
            'draft' => (new WizardDraftService())->load('calendar_create', (int) $user['id']),
            'templates' => [
                'monthly_standard' => 'Monthly standard plan',
                'campaign_launch' => 'Campaign launch plan',
                'always_on' => 'Always-on content plan',
            ],
        ]);
    }

    public function calendarStore(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $drafts = new WizardDraftService();

        if (($this->intent() === 'save_draft')) {
            $drafts->save('calendar_create', (int) $user['id'], $this->payload($_POST), (string) ($_POST['title'] ?? 'Calendar draft'));
            $this->flash('success', 'Calendar draft saved. You can continue later.');
            $this->redirect('wizard.calendar');
        }

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($clientId <= 0 || $title === '') {
            $this->flash('error', 'Choose a client and add a calendar title before continuing.');
            $this->redirect('wizard.calendar');
        }

        $existing = Database::fetch(
            'SELECT id FROM calendars WHERE client_id = :client AND month = :month AND year = :year LIMIT 1',
            ['client' => $clientId, 'month' => $month, 'year' => $year]
        );

        if ($existing) {
            $this->flash('success', 'A calendar for that month already exists. Opening the existing calendar instead.');
            $this->redirect('calendar', ['calendar_id' => (int) $existing['id'], 'month' => $month, 'year' => $year]);
        }

        $assignedEmployeeId = $this->resolveCalendarOwner($clientId, (int) $user['id']);
        $calendarId = Database::insert(
            'INSERT INTO calendars (
                title, campaign_name, client_id, assigned_employee_id, month, year, status, notes, creation_mode,
                posting_frequency, primary_platforms, approval_timeline, created_by
             ) VALUES (
                :title, :campaign_name, :client_id, :assigned_employee_id, :month, :year, :status, :notes, :creation_mode,
                :posting_frequency, :primary_platforms, :approval_timeline, :created_by
             )',
            [
                'title' => $title,
                'campaign_name' => trim((string) ($_POST['campaign_name'] ?? '')),
                'client_id' => $clientId,
                'assigned_employee_id' => $assignedEmployeeId,
                'month' => $month,
                'year' => $year,
                'status' => 'active',
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'creation_mode' => trim((string) ($_POST['creation_mode'] ?? 'blank')),
                'posting_frequency' => trim((string) ($_POST['posting_frequency'] ?? '')),
                'primary_platforms' => implode(', ', array_filter((array) ($_POST['primary_platforms'] ?? []))),
                'approval_timeline' => trim((string) ($_POST['approval_timeline'] ?? '')),
                'created_by' => (int) $user['id'],
            ]
        );

        ActivityLogger::log('calendar_created_via_wizard', 'calendar', $calendarId, [
            'client_id' => $clientId,
            'creation_mode' => (string) ($_POST['creation_mode'] ?? 'blank'),
        ]);

        $drafts->clear('calendar_create', (int) $user['id']);

        $nextRoute = match ((string) ($_POST['creation_mode'] ?? 'blank')) {
            'bulk' => 'wizard',
            'duplicate_previous' => 'calendar',
            'template' => 'calendar',
            default => 'calendar',
        };
        $this->flash('success', 'Calendar created successfully. Next: add posts with the Bulk Wizard or review the month view.');
        $this->redirect($nextRoute, [
            'calendar_id' => $calendarId,
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function clientOnboarding(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();

        $this->view('wizard/client_onboarding', [
            'title' => 'Client Onboarding Wizard',
            'employees' => User::employees(),
            'clientUsers' => User::clients(),
            'draft' => (new WizardDraftService())->load('client_onboarding', (int) $user['id']),
        ]);
    }

    public function clientStore(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $drafts = new WizardDraftService();

        if ($this->intent() === 'save_draft') {
            $drafts->save('client_onboarding', (int) $user['id'], $this->payload($_POST), (string) ($_POST['company_name'] ?? 'Client draft'));
            $this->flash('success', 'Client onboarding draft saved.');
            $this->redirect('wizard.client');
        }

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $contactName = trim((string) ($_POST['contact_name'] ?? ''));
        $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));

        if ($companyName === '' || $contactName === '' || $contactEmail === '') {
            $this->flash('error', 'Company name, contact name, and email are required.');
            $this->redirect('wizard.client');
        }

        $clientUserId = !empty($_POST['client_user_id']) ? (int) $_POST['client_user_id'] : null;
        $generatedPassword = null;

        if (!empty($_POST['create_portal_access'])) {
            if ($clientUserId === null) {
                [$clientUserId, $generatedPassword] = $this->createClientUser(
                    $contactName,
                    $contactEmail,
                    (string) ($_POST['contact_phone'] ?? ''),
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

        $logoPath = null;
        if (!empty($_FILES['logo']['name'])) {
            try {
                $upload = (new UploadService($this->config))->store($_FILES['logo'], ['image/jpeg', 'image/png', 'image/svg+xml']);
                $logoPath = $upload['file_path'];
            } catch (Throwable $exception) {
                $this->flash('error', $exception->getMessage());
                $this->redirect('wizard.client');
            }
        }

        $accountOwnerId = !empty($_POST['account_owner_employee_id']) ? (int) $_POST['account_owner_employee_id'] : null;
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['employee_ids'] ?? [])))));
        if ($accountOwnerId && !in_array($accountOwnerId, $employeeIds, true)) {
            $employeeIds[] = $accountOwnerId;
        }

        $clientId = Database::insert(
            'INSERT INTO clients (
                company_name, contact_name, contact_email, contact_phone, logo_path, client_user_id, account_owner_employee_id,
                status, workflow_preferences, approval_turnaround, brand_notes, naming_conventions
             ) VALUES (
                :company_name, :contact_name, :contact_email, :contact_phone, :logo_path, :client_user_id, :account_owner_employee_id,
                :status, :workflow_preferences, :approval_turnaround, :brand_notes, :naming_conventions
             )',
            [
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'contact_email' => $contactEmail,
                'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
                'logo_path' => $logoPath,
                'client_user_id' => $clientUserId,
                'account_owner_employee_id' => $accountOwnerId,
                'status' => (string) ($_POST['status'] ?? 'active'),
                'workflow_preferences' => trim((string) ($_POST['workflow_preferences'] ?? '')),
                'approval_turnaround' => trim((string) ($_POST['approval_turnaround'] ?? '')),
                'brand_notes' => trim((string) ($_POST['brand_notes'] ?? '')),
                'naming_conventions' => trim((string) ($_POST['naming_conventions'] ?? '')),
            ]
        );

        foreach ($employeeIds as $employeeId) {
            Database::insert(
                'INSERT IGNORE INTO employee_client_assignments (employee_user_id, client_id) VALUES (:employee, :client)',
                ['employee' => $employeeId, 'client' => $clientId]
            );
        }

        ActivityLogger::log('client_created_via_wizard', 'client', $clientId, ['company_name' => $companyName]);
        $drafts->clear('client_onboarding', (int) $user['id']);

        if (!empty($_POST['send_welcome_email']) && $clientUserId !== null && $generatedPassword !== null) {
            $this->sendPortalWelcomeEmail($contactEmail, $contactName, $companyName, $generatedPassword);
        }

        $successMessage = 'Client created successfully. Next: assign the team or create the first calendar.';
        if ($generatedPassword !== null) {
            $successMessage .= " Temporary password: {$generatedPassword}";
        }

        $this->flash('success', $successMessage);
        $this->redirect('clients');
    }

    public function approvalSubmission(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $itemId = (int) ($_GET['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Item Not Found']);
            return;
        }

        $files = CalendarItem::files($itemId);
        $this->view('wizard/approval_submit', [
            'title' => 'Submit for Approval',
            'item' => $item,
            'files' => $files,
            'checklist' => $this->approvalChecklist($item, $files),
            'draft' => (new WizardDraftService())->load('approval_submit_' . $itemId, (int) Auth::user()['id']),
        ]);
    }

    public function approvalSubmit(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Item Not Found']);
            return;
        }

        $drafts = new WizardDraftService();
        $draftKey = 'approval_submit_' . $itemId;
        if ($this->intent() === 'save_draft') {
            $drafts->save($draftKey, (int) $user['id'], $this->payload($_POST), 'Approval submission');
            $this->flash('success', 'Approval submission draft saved.');
            $this->redirect('wizard.approval', ['item_id' => $itemId]);
        }

        $files = CalendarItem::files($itemId);
        $checklist = $this->approvalChecklist($item, $files);
        $missing = array_filter($checklist, static fn (array $row): bool => !$row['done']);
        if ($missing !== []) {
            $this->flash('error', 'Finish the required approval checklist before submitting.');
            $this->redirect('wizard.approval', ['item_id' => $itemId]);
        }

        $comment = trim((string) ($_POST['submission_note'] ?? ''));
        Database::query('UPDATE calendar_items SET status = :status WHERE id = :id', ['status' => 'Pending Approval', 'id' => $itemId]);
        Database::insert(
            'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
             VALUES (:item, :user, :previous, :new, :comment)',
            [
                'item' => $itemId,
                'user' => (int) $user['id'],
                'previous' => (string) ($item['status'] ?? 'Draft'),
                'new' => 'Pending Approval',
                'comment' => $comment !== '' ? $comment : 'Submitted via approval wizard.',
            ]
        );

        ActivityLogger::log('approval_submitted_via_wizard', 'calendar_item', $itemId, ['status' => 'Pending Approval']);
        $this->notifyClientForApproval($itemId);
        $drafts->clear($draftKey, (int) $user['id']);

        $this->flash('success', 'Sent to client review. Next: track feedback in the approvals queue.');
        $this->redirect('approvals');
    }

    public function generate(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $drafts = new WizardDraftService();

        if ($this->intent() === 'save_draft') {
            $drafts->save('bulk_posts', (int) $user['id'], $this->payload($_POST), 'Bulk post draft');
            $this->flash('success', 'Bulk generation draft saved.');
            $this->redirect('wizard');
        }

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));
        $assignedEmployeeId = (int) (($_POST['assigned_employee_id'] ?? '') ?: $user['id']);
        $calendarId = (int) ($_POST['calendar_id'] ?? 0);
        $dates = $this->selectedDays($month, $year, $_POST);
        $platforms = $_POST['platforms'] ?? [];
        $postTypes = $_POST['post_types'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $demoAssets = new DemoAssetService($this->config);

        if ($clientId <= 0 || $dates === []) {
            $this->flash('error', 'Choose a client and at least one date before generating posts.');
            $this->redirect('wizard');
        }

        $calendar = $calendarId > 0
            ? Database::fetch('SELECT * FROM calendars WHERE id = :id', ['id' => $calendarId])
            : Database::fetch(
                'SELECT * FROM calendars WHERE client_id = :client AND month = :month AND year = :year',
                ['client' => $clientId, 'month' => $month, 'year' => $year]
            );

        if (!$calendar) {
            $calendarId = Database::insert(
                'INSERT INTO calendars (
                    title, campaign_name, client_id, assigned_employee_id, month, year, status, notes, creation_mode, posting_frequency, primary_platforms, approval_timeline, created_by
                 ) VALUES (
                    :title, :campaign_name, :client, :employee, :month, :year, :status, :notes, :creation_mode, :posting_frequency, :primary_platforms, :approval_timeline, :created_by
                 )',
                [
                    'title' => trim((string) ($_POST['calendar_title'] ?? date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year . ' Content Plan')),
                    'campaign_name' => trim((string) ($_POST['campaign'] ?? '')),
                    'client' => $clientId,
                    'employee' => $assignedEmployeeId,
                    'month' => $month,
                    'year' => $year,
                    'status' => 'active',
                    'notes' => trim((string) ($_POST['notes'] ?? '')),
                    'creation_mode' => 'bulk',
                    'posting_frequency' => trim((string) ($_POST['posting_frequency'] ?? '')),
                    'primary_platforms' => implode(', ', array_filter((array) ($_POST['primary_platforms'] ?? []))),
                    'approval_timeline' => trim((string) ($_POST['approval_timeline'] ?? '')),
                    'created_by' => (int) $user['id'],
                ]
            );
        } else {
            $calendarId = (int) $calendar['id'];
        }

        $generated = 0;
        foreach ($dates as $date) {
            foreach ($platforms[$date] ?? [] as $index => $platform) {
                $qty = max(1, (int) ($quantities[$date][$index] ?? 1));
                $postType = $postTypes[$date][$index] ?? 'Post';

                for ($i = 1; $i <= $qty; $i++) {
                    $format = (string) ($_POST['format'] ?? 'Image');
                    $scheduledDate = $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $date, 2, '0', STR_PAD_LEFT);
                    $itemId = Database::insert(
                        "INSERT INTO calendar_items (
                            calendar_id, client_id, created_by, assigned_employee_id, title, platform, scheduled_date, post_type, format, size,
                            caption_en, campaign, content_pillar, cta, priority, approval_route, internal_notes, status
                        ) VALUES (
                            :calendar_id, :client_id, :created_by, :assigned_employee_id, :title, :platform, :scheduled_date, :post_type, :format, :size,
                            :caption_en, :campaign, :content_pillar, :cta, :priority, :approval_route, :internal_notes, :status
                        )",
                        [
                            'calendar_id' => $calendarId,
                            'client_id' => $clientId,
                            'created_by' => (int) $user['id'],
                            'assigned_employee_id' => $assignedEmployeeId,
                            'title' => $this->bulkTitle((string) ($_POST['campaign'] ?? 'Campaign'), (string) $platform, (string) $postType, $i, $scheduledDate),
                            'platform' => $platform,
                            'scheduled_date' => $scheduledDate,
                            'post_type' => $postType,
                            'format' => $format,
                            'size' => (string) ($_POST['size'] ?? '1080x1080'),
                            'caption_en' => (string) ($_POST['caption_placeholder'] ?? ''),
                            'campaign' => (string) ($_POST['campaign'] ?? ''),
                            'content_pillar' => (string) ($_POST['content_pillar'] ?? ''),
                            'cta' => (string) ($_POST['cta'] ?? ''),
                            'priority' => (string) ($_POST['priority'] ?? 'Normal'),
                            'approval_route' => (string) ($_POST['approval_route'] ?? 'Client review'),
                            'internal_notes' => trim((string) ($_POST['notes'] ?? '')),
                            'status' => 'Draft',
                        ]
                    );

                    if (!empty($_POST['auto_attach_demo'])) {
                        $demoKind = stripos($format, 'video') !== false || stripos($postType, 'video') !== false || stripos($postType, 'reel') !== false || stripos($postType, 'short') !== false
                            ? 'video'
                            : 'image';

                        try {
                            $upload = $demoAssets->provision($demoKind);
                            Database::insert(
                                'INSERT INTO item_files (calendar_item_id, version_number, original_name, stored_name, file_path, mime_type, file_size, uploaded_by)
                                 VALUES (:calendar_item_id, 1, :original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by)',
                                $upload + ['calendar_item_id' => $itemId, 'uploaded_by' => (int) $user['id']]
                            );
                            Database::query(
                                'UPDATE calendar_items SET artwork_path = :file_path, version_number = 1 WHERE id = :id',
                                ['file_path' => $upload['file_path'], 'id' => $itemId]
                            );
                        } catch (Throwable) {
                        }
                    }

                    if (!empty($_POST['submit_after_create']) && !empty($_POST['auto_attach_demo'])) {
                        Database::query('UPDATE calendar_items SET status = :status WHERE id = :id', ['status' => 'Pending Approval', 'id' => $itemId]);
                        Database::insert(
                            'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
                             VALUES (:item, :user, :previous, :new, :comment)',
                            [
                                'item' => $itemId,
                                'user' => (int) $user['id'],
                                'previous' => 'Draft',
                                'new' => 'Pending Approval',
                                'comment' => 'Generated via bulk wizard.',
                            ]
                        );
                        $this->notifyClientForApproval($itemId);
                    }

                    $generated++;
                }
            }
        }

        ActivityLogger::log('wizard_generated_items', 'calendar', $calendarId, ['generated' => $generated]);
        $drafts->clear('bulk_posts', (int) $user['id']);
        $this->flash('success', $generated . ' posts created successfully. Next: review details or submit selected items for approval.');
        $this->redirect('calendar', ['calendar_id' => $calendarId, 'month' => $month, 'year' => $year]);
    }

    private function accessibleCalendars(): array
    {
        $clients = Client::accessible();
        $clientIds = array_values(array_filter(array_map(static fn (array $client): int => (int) ($client['id'] ?? 0), $clients)));
        if ($clientIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        return Database::fetchAll(
            "SELECT cal.id, cal.title, cal.month, cal.year, c.company_name
             FROM calendars cal
             JOIN clients c ON c.id = cal.client_id
             WHERE cal.client_id IN ({$placeholders})
             ORDER BY cal.year DESC, cal.month DESC, cal.title ASC",
            $clientIds
        );
    }

    private function selectedDays(int $month, int $year, array $payload): array
    {
        $selected = array_values(array_filter(array_map('intval', explode(',', (string) ($payload['selected_dates'] ?? '')))));
        $weekdayMap = array_map('intval', (array) ($payload['repeat_weekdays'] ?? []));
        $daysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        if ($weekdayMap !== []) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $weekday = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $year, $month, $day)));
                if (in_array($weekday, $weekdayMap, true)) {
                    $selected[] = $day;
                }
            }
        }

        $selected = array_values(array_unique(array_filter($selected, static fn (int $day): bool => $day >= 1 && $day <= $daysInMonth)));
        sort($selected);
        return $selected;
    }

    private function bulkTitle(string $campaign, string $platform, string $postType, int $index, string $scheduledDate): string
    {
        $prefix = trim($campaign) !== '' ? trim($campaign) : date('M j', strtotime($scheduledDate));
        return $prefix . ' - ' . $platform . ' ' . $postType . ' #' . $index;
    }

    private function resolveCalendarOwner(int $clientId, int $fallbackUserId): int
    {
        $client = Database::fetch(
            'SELECT account_owner_employee_id FROM clients WHERE id = :id',
            ['id' => $clientId]
        );

        if (!empty($client['account_owner_employee_id'])) {
            return (int) $client['account_owner_employee_id'];
        }

        $assignment = Database::fetch(
            'SELECT employee_user_id FROM employee_client_assignments WHERE client_id = :client_id ORDER BY id ASC LIMIT 1',
            ['client_id' => $clientId]
        );

        return !empty($assignment['employee_user_id']) ? (int) $assignment['employee_user_id'] : $fallbackUserId;
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

    private function sendPortalWelcomeEmail(string $email, string $name, string $companyName, ?string $generatedPassword): void
    {
        if ($generatedPassword === null) {
            return;
        }

        try {
            (new ClientWelcomeMailer($this->config))->send($email, $name, $companyName, $email, $generatedPassword);
        } catch (Throwable) {
        }
    }

    private function approvalChecklist(array $item, array $files): array
    {
        return [
            ['label' => 'Title is filled in', 'done' => trim((string) ($item['title'] ?? '')) !== ''],
            ['label' => 'Caption is added', 'done' => trim((string) ($item['caption_en'] ?? '')) !== ''],
            ['label' => 'Client note is added', 'done' => trim((string) ($item['client_notes'] ?? '')) !== ''],
            ['label' => 'Artwork is uploaded', 'done' => $files !== []],
            ['label' => 'Version number is available', 'done' => (int) ($item['version_number'] ?? 0) > 0],
            ['label' => 'Platform and date are set', 'done' => trim((string) ($item['platform'] ?? '')) !== '' && trim((string) ($item['scheduled_date'] ?? '')) !== ''],
        ];
    }

    private function notifyClientForApproval(int $itemId): void
    {
        $item = CalendarItem::find($itemId);
        if (!$item) {
            return;
        }

        $recipient = Database::fetch('SELECT client_user_id FROM clients WHERE id = :client', ['client' => $item['client_id']]);
        if (empty($recipient['client_user_id'])) {
            return;
        }

        (new NotificationService($this->config))->notify(
            (int) $recipient['client_user_id'],
            $itemId,
            'item_submitted',
            'New post submitted for approval',
            "A new post is ready for your review in the G2 Social Media Calendar.\n\nPost title: {$item['title']}\nClient: {$item['company_name']}\nPlatform: {$item['platform']}\nScheduled date: {$item['scheduled_date']}\nStatus: Pending Approval\n\nUse the button below to open the review page and approve it or request changes."
        );
    }

    private function payload(array $payload): array
    {
        unset($payload['_csrf'], $payload['intent']);
        return $payload;
    }

    private function intent(): string
    {
        return (string) ($_POST['intent'] ?? 'submit');
    }
}
