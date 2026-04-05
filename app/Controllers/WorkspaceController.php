<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CalendarItem;
use App\Services\ActivityLogger;
use App\Services\MailTransport;
use App\Services\WorkspaceService;
use Throwable;

final class WorkspaceController extends Controller
{
    public function posts(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();
        $view = ($_GET['view'] ?? 'active') === 'trash' ? 'trash' : 'active';
        $isClient = (Auth::user()['role_name'] ?? '') === 'client';

        $this->view('workspace/posts', [
            'title' => 'All Posts',
            'posts' => $workspace->allPosts(['search' => $_GET['search'] ?? '', 'view' => $view]),
            'search' => $_GET['search'] ?? '',
            'view' => $view,
            'canManagePosts' => !$isClient,
        ]);
    }

    public function bulkPosts(): void
    {
        Auth::requireRole(['master_admin', 'employee']);
        $action = (string) ($_POST['bulk_action'] ?? '');
        $itemIds = array_values(array_unique(array_map('intval', $_POST['selected_ids'] ?? [])));

        if ($itemIds === []) {
            $this->flash('error', 'Select at least one post.');
            $this->redirect('posts', ['view' => $_POST['view'] ?? 'active']);
        }

        $workspace = new WorkspaceService();
        $allowedItems = $workspace->allPosts(['view' => ($_POST['view'] ?? 'active') === 'trash' ? 'trash' : 'active']);
        $allowedMap = [];
        foreach ($allowedItems as $post) {
            $allowedMap[(int) $post['id']] = $post;
        }

        $validIds = array_values(array_filter($itemIds, static fn (int $id): bool => $id > 0 && isset($allowedMap[$id])));
        if ($validIds === []) {
            $this->flash('error', 'No valid posts selected.');
            $this->redirect('posts', ['view' => $_POST['view'] ?? 'active']);
        }

        if ($action === 'edit') {
            if (count($validIds) !== 1) {
                $this->flash('error', 'Select exactly one post to edit.');
                $this->redirect('posts', ['view' => $_POST['view'] ?? 'active']);
            }

            $this->redirect('calendar.item', ['item_id' => $validIds[0]]);
        }

        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        $userId = (int) (Auth::user()['id'] ?? 0);

        if ($action === 'trash') {
            Database::pdo()->prepare("UPDATE calendar_items SET deleted_at = NOW(), deleted_by = ? WHERE id IN ({$placeholders})")
                ->execute(array_merge([$userId], $validIds));

            foreach ($validIds as $id) {
                ActivityLogger::log('item_trashed', 'calendar_item', $id, ['view' => 'trash']);
            }

            $this->flash('success', count($validIds) . ' post(s) moved to trash.');
            $this->redirect('posts');
        }

        if ($action === 'restore') {
            Database::pdo()->prepare("UPDATE calendar_items SET deleted_at = NULL, deleted_by = NULL WHERE id IN ({$placeholders})")
                ->execute($validIds);

            foreach ($validIds as $id) {
                ActivityLogger::log('item_restored', 'calendar_item', $id, ['view' => 'active']);
            }

            $this->flash('success', count($validIds) . ' post(s) restored from trash.');
            $this->redirect('posts', ['view' => 'trash']);
        }

        $this->flash('error', 'Unsupported bulk action.');
        $this->redirect('posts', ['view' => $_POST['view'] ?? 'active']);
    }

    public function approvals(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();
        $roleName = Auth::user()['role_name'] ?? '';
        $isReviewer = in_array($roleName, ['master_admin', 'client'], true);

        $this->view('workspace/approvals', [
            'title' => $isReviewer ? 'Client Review Queue' : 'Approval Tracking',
            'items' => $workspace->approvals(),
            'approvalsSubtitle' => $isReviewer
                ? 'Items currently waiting for client or admin review.'
                : 'Items currently in client review or awaiting approval updates.',
        ]);
    }

    public function artwork(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();

        $this->view('workspace/artwork', [
            'title' => 'Artwork Library',
            'files' => $workspace->artworkLibrary(['search' => $_GET['search'] ?? '']),
            'search' => $_GET['search'] ?? '',
        ]);
    }

    public function notifications(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();

        $this->view('workspace/notifications', [
            'title' => 'Notifications',
            'notifications' => $workspace->notifications(40),
        ]);
    }

    public function activity(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();

        $this->view('workspace/activity', [
            'title' => 'Activity Log',
            'rows' => $workspace->activityLog(['search' => $_GET['search'] ?? '']),
            'search' => $_GET['search'] ?? '',
        ]);
    }

    public function settings(): void
    {
        Auth::requireRole(['master_admin']);

        $this->view('workspace/settings', [
            'title' => 'Settings',
        ]);
    }

    public function sendTestEmail(): void
    {
        Auth::requireRole(['master_admin']);
        $user = Auth::user();

        try {
            $appUrl = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
            $subject = 'Mailjet Test Email';
            $text = "This is a Mailjet delivery test from G2 Social Media Calendar.\n\nOpen App: {$appUrl}/index.php?route=dashboard";
            $html = '<!doctype html><html lang="en"><body style="margin:0;padding:24px;background:#f6f7fb;font-family:Segoe UI,Arial,sans-serif;color:#131722;">'
                . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e6e8ef;border-radius:18px;overflow:hidden;">'
                . '<tr><td style="padding:22px 24px;background:#d92d2a;color:#ffffff;"><div style="font-size:12px;letter-spacing:0.12em;font-weight:800;">G2 SOCIAL CALENDAR</div><div style="margin-top:8px;font-size:24px;font-weight:800;">Mailjet Test Email</div></td></tr>'
                . '<tr><td style="padding:24px;"><p style="margin:0 0 14px;font-size:15px;color:#6e7687;">Hello ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<div style="font-size:15px;line-height:1.7;color:#131722;">This is a Mailjet delivery test from the current workspace configuration.</div>'
                . '<p style="margin:24px 0 0;"><a href="' . htmlspecialchars($appUrl . '/index.php?route=dashboard', ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 20px;border-radius:12px;background:#d92d2a;color:#ffffff;text-decoration:none;font-weight:700;">Open Dashboard</a></p>'
                . '</td></tr></table></body></html>';

            (new MailTransport($this->config))->send((string) $user['email'], (string) $user['name'], $subject, $text, $html);
            $this->flash('success', 'Test email sent to ' . $user['email'] . '.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Test email failed: ' . $exception->getMessage());
        }

        $this->redirect('settings');
    }
}
