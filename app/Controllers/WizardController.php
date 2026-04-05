<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DemoAssetService;

final class WizardController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin', 'employee']);

        $this->view('calendar/wizard', [
            'title' => 'Bulk Creation Wizard',
            'clients' => Client::accessible(),
            'employees' => User::employees(),
        ]);
    }

    public function generate(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();

        $clientId = (int) $_POST['client_id'];
        $month = (int) $_POST['month'];
        $year = (int) $_POST['year'];
        $assignedEmployeeId = (int) ($_POST['assigned_employee_id'] ?: $user['id']);
        $dates = array_values(array_filter(array_map('trim', explode(',', (string) $_POST['selected_dates']))));
        $platforms = $_POST['platforms'] ?? [];
        $postTypes = $_POST['post_types'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $demoAssets = new DemoAssetService($this->config);

        $calendar = Database::fetch(
            'SELECT * FROM calendars WHERE client_id = :client AND month = :month AND year = :year',
            ['client' => $clientId, 'month' => $month, 'year' => $year]
        );

        if (!$calendar) {
            $calendarId = Database::insert(
                'INSERT INTO calendars (title, client_id, assigned_employee_id, month, year, status, created_by) VALUES (:title, :client, :employee, :month, :year, :status, :created_by)',
                [
                    'title' => date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year . ' Content Plan',
                    'client' => $clientId,
                    'employee' => $assignedEmployeeId,
                    'month' => $month,
                    'year' => $year,
                    'status' => 'active',
                    'created_by' => $user['id'],
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
                    $itemId = Database::insert(
                        "INSERT INTO calendar_items (
                            calendar_id, client_id, created_by, assigned_employee_id, title, platform, scheduled_date,
                            post_type, format, size, caption_en, campaign, content_pillar, cta, internal_notes, status
                        ) VALUES (
                            :calendar_id, :client_id, :created_by, :assigned_employee_id, :title, :platform, :scheduled_date,
                            :post_type, :format, :size, :caption_en, :campaign, :content_pillar, :cta, :internal_notes, :status
                        )",
                        [
                            'calendar_id' => $calendarId,
                            'client_id' => $clientId,
                            'created_by' => $user['id'],
                            'assigned_employee_id' => $assignedEmployeeId,
                            'title' => trim((string) ($_POST['campaign'] ?? 'Campaign')) . ' - ' . $platform . ' ' . $postType . ' #' . $i,
                            'platform' => $platform,
                            'scheduled_date' => $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($date, 2, '0', STR_PAD_LEFT),
                            'post_type' => $postType,
                            'format' => $format,
                            'size' => $_POST['size'] ?? '1080x1080',
                            'caption_en' => $_POST['caption_placeholder'] ?? '',
                            'campaign' => $_POST['campaign'] ?? '',
                            'content_pillar' => $_POST['content_pillar'] ?? '',
                            'cta' => $_POST['cta'] ?? '',
                            'internal_notes' => $_POST['notes'] ?? '',
                            'status' => 'Pending Approval',
                        ]
                    );

                    $demoKind = stripos($format, 'video') !== false || stripos($postType, 'video') !== false || stripos($postType, 'reel') !== false
                        ? 'video'
                        : 'image';

                    try {
                        $upload = $demoAssets->provision($demoKind);
                        Database::insert(
                            'INSERT INTO item_files (calendar_item_id, version_number, original_name, stored_name, file_path, mime_type, file_size, uploaded_by)
                             VALUES (:calendar_item_id, 1, :original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by)',
                            $upload + ['calendar_item_id' => $itemId, 'uploaded_by' => $user['id']]
                        );
                        Database::query(
                            'UPDATE calendar_items SET artwork_path = :file_path, version_number = 1 WHERE id = :id',
                            ['file_path' => $upload['file_path'], 'id' => $itemId]
                        );
                    } catch (\Throwable) {
                        // Keep generation flowing even if dummy media provisioning fails.
                    }

                    Database::insert(
                        'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
                         VALUES (:item, :user, :previous, :new, :comment)',
                        [
                            'item' => $itemId,
                            'user' => $user['id'],
                            'previous' => 'Draft',
                            'new' => 'Pending Approval',
                            'comment' => 'Generated via bulk wizard.',
                        ]
                    );
                    $generated++;
                }
            }
        }

        ActivityLogger::log('wizard_generated_items', 'calendar', $calendarId, ['generated' => $generated]);
        $this->flash('success', $generated . ' calendar items generated.');
        $this->redirect('calendar', ['calendar_id' => $calendarId, 'month' => $month, 'year' => $year]);
    }
}
