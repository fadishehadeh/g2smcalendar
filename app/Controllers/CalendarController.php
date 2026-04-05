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
use App\Services\AiAssistService;
use App\Services\DemoAssetService;
use App\Services\NotificationService;
use App\Services\UploadService;
use App\Services\WorkspaceSettingsService;
use App\Services\WorkspaceService;
use Throwable;

final class CalendarController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $user = Auth::user();
        $accessibleClients = Client::accessible();

        $filters = [
            'client_id' => $_GET['client_id'] ?? '',
            'month' => $_GET['month'] ?? date('n'),
            'year' => $_GET['year'] ?? date('Y'),
            'view' => $_GET['view'] ?? 'monthly',
            'anchor_date' => $_GET['anchor_date'] ?? '',
            'status' => $_GET['status'] ?? '',
            'platform' => $_GET['platform'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        if (($user['role_name'] ?? '') !== 'master_admin' && count($accessibleClients) === 1) {
            $filters['client_id'] = (string) $accessibleClients[0]['id'];
        }

        $workspace = new WorkspaceService();
        $monthData = $workspace->calendarMonth($filters);
        $items = $monthData['items'];
        $this->view('calendar/index', [
            'title' => 'Calendar',
            'clients' => $accessibleClients,
            'employees' => User::employees(),
            'items' => $items,
            'filters' => $filters,
            'statuses' => CalendarItem::STATUSES,
            'platforms' => CalendarItem::PLATFORMS,
            'monthData' => $monthData,
        ]);
    }

    public function show(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);

        $itemId = (int) ($_GET['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Item Not Found']);
            return;
        }

        $comments = CalendarItem::comments($itemId, Auth::user()['role_name']);
        $files = CalendarItem::files($itemId);
        $history = CalendarItem::history($itemId);
        $activity = CalendarItem::activity($itemId);
        $editHistory = CalendarItem::editHistory($itemId);

        $this->view('calendar/show', [
            'title' => $item['title'],
            'item' => $item,
            'comments' => $comments,
            'files' => $files,
            'history' => $history,
            'activity' => $activity,
            'editHistory' => $editHistory,
            'aiSuggestion' => $_SESSION['ai_suggestions'][$itemId] ?? null,
            'statuses' => CalendarItem::STATUSES,
        ]);
    }

    public function saveItem(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $user = Auth::user();
        $previousItem = null;

        $data = [
            'calendar_id' => (int) $_POST['calendar_id'],
            'client_id' => (int) $_POST['client_id'],
            'assigned_employee_id' => (int) ($_POST['assigned_employee_id'] ?: $user['id']),
            'title' => trim((string) $_POST['title']),
            'platform' => trim((string) $_POST['platform']),
            'scheduled_date' => $_POST['scheduled_date'],
            'scheduled_time' => $_POST['scheduled_time'] ?: null,
            'post_type' => trim((string) $_POST['post_type']),
            'format' => trim((string) ($_POST['format'] ?? '')),
            'size' => trim((string) ($_POST['size'] ?? '')),
            'caption_en' => trim((string) ($_POST['caption_en'] ?? '')),
            'caption_ar' => trim((string) ($_POST['caption_ar'] ?? '')),
            'hashtags' => trim((string) ($_POST['hashtags'] ?? '')),
            'campaign' => trim((string) ($_POST['campaign'] ?? '')),
            'content_pillar' => trim((string) ($_POST['content_pillar'] ?? '')),
            'cta' => trim((string) ($_POST['cta'] ?? '')),
            'internal_notes' => trim((string) ($_POST['internal_notes'] ?? '')),
            'client_notes' => trim((string) ($_POST['client_notes'] ?? '')),
            'status' => $_POST['status'] ?? 'Draft',
        ];

        $itemId = !empty($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

        if ($itemId > 0) {
            $previousItem = CalendarItem::find($itemId);
            Database::query(
                "UPDATE calendar_items SET
                    title = :title,
                    platform = :platform,
                    scheduled_date = :scheduled_date,
                    scheduled_time = :scheduled_time,
                    post_type = :post_type,
                    format = :format,
                    size = :size,
                    caption_en = :caption_en,
                    caption_ar = :caption_ar,
                    hashtags = :hashtags,
                    campaign = :campaign,
                    content_pillar = :content_pillar,
                    cta = :cta,
                    internal_notes = :internal_notes,
                    client_notes = :client_notes,
                    assigned_employee_id = :assigned_employee_id,
                    status = :status
                 WHERE id = :id",
                $data + ['id' => $itemId]
            );
            $this->recordEditHistory($itemId, $user['id'], $previousItem, $data);
        } else {
            $itemId = Database::insert(
                "INSERT INTO calendar_items (
                    calendar_id, client_id, created_by, assigned_employee_id, title, platform, scheduled_date, scheduled_time,
                    post_type, format, size, caption_en, caption_ar, hashtags, campaign, content_pillar, cta,
                    internal_notes, client_notes, status
                ) VALUES (
                    :calendar_id, :client_id, :created_by, :assigned_employee_id, :title, :platform, :scheduled_date, :scheduled_time,
                    :post_type, :format, :size, :caption_en, :caption_ar, :hashtags, :campaign, :content_pillar, :cta,
                    :internal_notes, :client_notes, :status
                )",
                $data + ['created_by' => $user['id']]
            );
        }

        if (!empty($_FILES['artwork']['name'])) {
            try {
                $upload = (new UploadService($this->config))->store($_FILES['artwork']);
                $version = (int) (Database::fetch('SELECT COALESCE(MAX(version_number), 0) AS version FROM item_files WHERE calendar_item_id = :item', ['item' => $itemId])['version'] ?? 0) + 1;

                Database::insert(
                    'INSERT INTO item_files (calendar_item_id, version_number, original_name, stored_name, file_path, mime_type, file_size, uploaded_by) VALUES (:calendar_item_id, :version_number, :original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by)',
                    $upload + ['calendar_item_id' => $itemId, 'version_number' => $version, 'uploaded_by' => $user['id']]
                );

                Database::query(
                    'UPDATE calendar_items SET artwork_path = :path, version_number = :version WHERE id = :id',
                    ['path' => $upload['file_path'], 'version' => $version, 'id' => $itemId]
                );
            } catch (Throwable $exception) {
                $this->flash('error', $exception->getMessage());
            }
        }

        ActivityLogger::log('item_saved', 'calendar_item', $itemId, ['title' => $data['title'], 'status' => $data['status']]);

        if ($data['status'] === 'Pending Approval' && (($previousItem['status'] ?? null) !== 'Pending Approval')) {
            $this->notifyClientForApproval($itemId);
        }

        $this->flash('success', 'Calendar item saved.');
        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    public function attachArtwork(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $user = Auth::user();
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(403);
            exit('Forbidden');
        }

        try {
            $upload = null;
            $isClient = ($user['role_name'] ?? '') === 'client';

            if (!empty($_FILES['artwork']['name'])) {
                $allowedMimes = $isClient
                    ? ['image/jpeg', 'image/png', 'image/svg+xml']
                    : null;
                $upload = (new UploadService($this->config))->store($_FILES['artwork'], $allowedMimes);
            } elseif (!$isClient && !empty($_POST['demo_kind'])) {
                $upload = (new DemoAssetService($this->config))->provision((string) $_POST['demo_kind']);
            }

            if (!$upload) {
                throw new \RuntimeException($isClient
                    ? 'Choose an image file to share with the employee.'
                    : 'Choose an image/video file or attach a dummy asset.');
            }

            $version = (int) (Database::fetch(
                'SELECT COALESCE(MAX(version_number), 0) AS version FROM item_files WHERE calendar_item_id = :item',
                ['item' => $itemId]
            )['version'] ?? 0) + 1;

            Database::insert(
                'INSERT INTO item_files (calendar_item_id, version_number, original_name, stored_name, file_path, mime_type, file_size, uploaded_by)
                 VALUES (:calendar_item_id, :version_number, :original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by)',
                $upload + ['calendar_item_id' => $itemId, 'version_number' => $version, 'uploaded_by' => $user['id']]
            );

            Database::query(
                "UPDATE calendar_items
                 SET artwork_path = CASE WHEN :is_client = 1 THEN artwork_path ELSE :path END,
                     version_number = :version,
                     status = CASE
                        WHEN :is_client = 1 THEN status
                        WHEN status IN ('Draft', 'In Progress', 'Rejected', 'Revision Requested') THEN 'Pending Approval'
                        ELSE status
                     END
                 WHERE id = :id",
                [
                    'path' => $upload['file_path'],
                    'version' => $version,
                    'id' => $itemId,
                    'is_client' => $isClient ? 1 : 0,
                ]
            );

            if (!$isClient) {
                Database::insert(
                    'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
                     VALUES (:item, :user, :previous, :new, :comment)',
                    [
                        'item' => $itemId,
                        'user' => $user['id'],
                        'previous' => $item['status'],
                        'new' => in_array($item['status'], ['Draft', 'In Progress', 'Rejected', 'Revision Requested'], true) ? 'Pending Approval' : $item['status'],
                        'comment' => 'Artwork uploaded and routed for review.',
                    ]
                );
            }

            ActivityLogger::log($isClient ? 'client_reference_uploaded' : 'artwork_uploaded', 'calendar_item', $itemId, ['version' => $version, 'mime_type' => $upload['mime_type']]);

            if (!$isClient && in_array($item['status'], ['Draft', 'In Progress', 'Rejected', 'Revision Requested'], true)) {
                $this->notifyClientForApproval($itemId);
            }

            if ($isClient) {
                (new NotificationService($this->config))->notify(
                    (int) $item['assigned_employee_id'],
                    $itemId,
                    'client_upload',
                    'Client uploaded a reference image',
                    "Post: {$item['title']}\nClient: {$item['company_name']}\nStatus: {$item['status']}\nLink: index.php?route=calendar.item&item_id={$itemId}"
                );
                $this->flash('success', 'Reference image uploaded for the employee.');
            } else {
                $this->flash('success', 'Artwork attached and item is ready for approval.');
            }
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }

        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    public function updateStatus(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $user = Auth::user();
        $itemId = (int) $_POST['item_id'];
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $newStatus = (string) $_POST['status'];
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $previousStatus = $item['status'];

        if ($user['role_name'] === 'client') {
            if ($newStatus === 'Rejected' && $comment === '') {
                $this->flash('error', 'Client rejection requires a comment.');
                $this->redirect('calendar.item', ['item_id' => $itemId]);
            }
            if (!in_array($newStatus, ['Approved', 'Rejected'], true)) {
                $this->flash('error', 'Client can only approve or reject.');
                $this->redirect('calendar.item', ['item_id' => $itemId]);
            }
        }

        if ($user['role_name'] === 'employee' && in_array($newStatus, ['Approved', 'Rejected'], true)) {
            $this->flash('error', 'Only admin or client can approve or reject items.');
            $this->redirect('calendar.item', ['item_id' => $itemId]);
        }

        Database::query('UPDATE calendar_items SET status = :status WHERE id = :id', ['status' => $newStatus, 'id' => $itemId]);
        Database::insert(
            'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment) VALUES (:item, :user, :previous, :new, :comment)',
            ['item' => $itemId, 'user' => $user['id'], 'previous' => $previousStatus, 'new' => $newStatus, 'comment' => $comment ?: null]
        );

        ActivityLogger::log('status_changed', 'calendar_item', $itemId, ['previous' => $previousStatus, 'new' => $newStatus]);

        $notificationService = new NotificationService($this->config);
        if ($newStatus === 'Pending Approval') {
            $this->notifyClientForApproval($itemId);
        }

        if (in_array($newStatus, ['Approved', 'Rejected'], true)) {
            $notificationService->notify((int) $item['assigned_employee_id'], $itemId, 'client_review', "Client {$newStatus}", "Post: {$item['title']}\nClient: {$item['company_name']}\nStatus: {$newStatus}\nLink: index.php?route=calendar.item&item_id={$itemId}");
        }

        $this->flash('success', 'Status updated.');
        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    public function addComment(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $user = Auth::user();
        $itemId = (int) $_POST['item_id'];
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $visibility = $user['role_name'] === 'client' ? 'shared' : ($_POST['visibility'] ?? 'shared');
        $comment = trim((string) $_POST['comment']);

        if ($comment === '') {
            $this->flash('error', 'Comment cannot be empty.');
            $this->redirect('calendar.item', ['item_id' => $itemId]);
        }

        Database::insert(
            'INSERT INTO item_comments (calendar_item_id, user_id, visibility, comment) VALUES (:item, :user, :visibility, :comment)',
            ['item' => $itemId, 'user' => $user['id'], 'visibility' => $visibility, 'comment' => $comment]
        );

        ActivityLogger::log('comment_added', 'calendar_item', $itemId, ['visibility' => $visibility]);

        if ($user['role_name'] === 'client') {
            (new NotificationService($this->config))->notify((int) $item['assigned_employee_id'], $itemId, 'client_comment', 'Client added feedback', "Post: {$item['title']}\nClient: {$item['company_name']}\nComment: {$comment}\nLink: index.php?route=calendar.item&item_id={$itemId}");
        }

        $this->flash('success', 'Comment added.');
        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    public function generateAiSuggestion(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $service = new AiAssistService($this->config, new WorkspaceSettingsService());
        $suggestion = $service->generateCaptionSuggestion($item);
        $_SESSION['ai_suggestions'][$itemId] = $suggestion;

        ActivityLogger::log('ai_suggestion_generated', 'calendar_item', $itemId, ['source' => $suggestion['source'] ?? 'unknown']);
        $this->flash('success', 'AI suggestion generated using ' . ($suggestion['source'] ?? 'assistant') . '.');
        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    public function applyAiSuggestion(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(403);
            exit('Forbidden');
        }

        $suggestion = $_SESSION['ai_suggestions'][$itemId] ?? null;
        if (!is_array($suggestion)) {
            $this->flash('error', 'No AI suggestion is available to apply.');
            $this->redirect('calendar.item', ['item_id' => $itemId]);
        }

        $oldCaption = trim((string) ($item['caption_en'] ?? ''));
        $newCaption = trim((string) ($suggestion['caption'] ?? ''));
        $newTime = trim((string) ($suggestion['suggested_time'] ?? ''));

        Database::query(
            'UPDATE calendar_items SET caption_en = :caption_en, scheduled_time = :scheduled_time WHERE id = :id',
            [
                'caption_en' => $newCaption,
                'scheduled_time' => $newTime !== '' ? $newTime : null,
                'id' => $itemId,
            ]
        );

        if ($oldCaption !== $newCaption) {
            Database::insert(
                'INSERT INTO item_edit_history (calendar_item_id, changed_by, field_name, old_value, new_value)
                 VALUES (:item, :user, :field_name, :old_value, :new_value)',
                [
                    'item' => $itemId,
                    'user' => Auth::user()['id'],
                    'field_name' => 'Caption (EN)',
                    'old_value' => $oldCaption !== '' ? $oldCaption : null,
                    'new_value' => $newCaption !== '' ? $newCaption : null,
                ]
            );
        }

        Database::insert(
            'INSERT INTO item_edit_history (calendar_item_id, changed_by, field_name, old_value, new_value)
             VALUES (:item, :user, :field_name, :old_value, :new_value)',
            [
                'item' => $itemId,
                'user' => Auth::user()['id'],
                'field_name' => 'Suggested Publish Time',
                'old_value' => (string) ($item['scheduled_time'] ?? '') !== '' ? (string) $item['scheduled_time'] : null,
                'new_value' => $newTime !== '' ? $newTime : null,
            ]
        );

        ActivityLogger::log('ai_suggestion_applied', 'calendar_item', $itemId, ['source' => $suggestion['source'] ?? 'unknown']);
        unset($_SESSION['ai_suggestions'][$itemId]);
        $this->flash('success', 'AI suggestion applied to caption and schedule time.');
        $this->redirect('calendar.item', ['item_id' => $itemId]);
    }

    private function notifyClientForApproval(int $itemId): void
    {
        $item = CalendarItem::find($itemId);
        if (!$item) {
            return;
        }

        $recipient = Database::fetch(
            'SELECT client_user_id FROM clients WHERE id = :client',
            ['client' => $item['client_id']]
        );

        if (empty($recipient['client_user_id'])) {
            return;
        }

        (new NotificationService($this->config))->notify(
            (int) $recipient['client_user_id'],
            $itemId,
            'item_submitted',
            'New post submitted for approval',
            "Post: {$item['title']}\nClient: {$item['company_name']}\nStatus: Pending Approval\nLink: index.php?route=calendar.item&item_id={$itemId}"
        );
    }

    private function recordEditHistory(int $itemId, int $userId, array $previousItem, array $data): void
    {
        $fields = [
            'title' => 'Title',
            'platform' => 'Platform',
            'scheduled_date' => 'Scheduled Date',
            'scheduled_time' => 'Scheduled Time',
            'post_type' => 'Post Type',
            'format' => 'Format',
            'size' => 'Size',
            'caption_en' => 'Caption (EN)',
            'caption_ar' => 'Caption (AR)',
            'hashtags' => 'Hashtags',
            'campaign' => 'Campaign',
            'content_pillar' => 'Content Pillar',
            'cta' => 'CTA',
            'internal_notes' => 'Internal Notes',
            'client_notes' => 'Client Notes',
            'status' => 'Status',
        ];

        foreach ($fields as $field => $label) {
            $oldValue = trim((string) ($previousItem[$field] ?? ''));
            $newValue = trim((string) ($data[$field] ?? ''));

            if ($oldValue === $newValue) {
                continue;
            }

            Database::insert(
                'INSERT INTO item_edit_history (calendar_item_id, changed_by, field_name, old_value, new_value)
                 VALUES (:item, :user, :field_name, :old_value, :new_value)',
                [
                    'item' => $itemId,
                    'user' => $userId,
                    'field_name' => $label,
                    'old_value' => $oldValue !== '' ? $oldValue : null,
                    'new_value' => $newValue !== '' ? $newValue : null,
                ]
            );
        }
    }
}
