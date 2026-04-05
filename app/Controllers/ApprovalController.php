<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CalendarItem;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

final class ApprovalController extends Controller
{
    public function review(): void
    {
        Auth::requireRole(['client', 'master_admin']);
        $itemId = (int) ($_GET['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Item Not Found']);
            return;
        }

        $this->view('approvals/review', [
            'title' => 'Review Content',
            'item' => $item,
            'files' => CalendarItem::files($itemId),
            'quickReasons' => ['Change caption', 'Update artwork', 'Change size', 'Wrong date', 'Other'],
        ]);
    }

    public function submitReview(): void
    {
        Auth::requireRole(['client', 'master_admin']);
        $user = Auth::user();
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = CalendarItem::find($itemId);

        if (!$item || !CalendarItem::canAccess($item)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Item Not Found']);
            return;
        }

        $action = (string) ($_POST['review_action'] ?? '');
        $reason = trim((string) ($_POST['change_reason'] ?? ''));
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $note = trim($reason . ($comment !== '' ? ($reason !== '' ? ': ' : '') . $comment : ''));

        if ($action === 'request_changes' && $note === '') {
            $this->flash('error', 'Add a short note so the team knows what to revise.');
            $this->redirect('approval.review', ['item_id' => $itemId]);
        }

        $newStatus = $action === 'approve' ? 'Approved' : 'Revision Requested';
        Database::query('UPDATE calendar_items SET status = :status WHERE id = :id', ['status' => $newStatus, 'id' => $itemId]);
        Database::insert(
            'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
             VALUES (:item, :user, :previous, :new, :comment)',
            [
                'item' => $itemId,
                'user' => (int) $user['id'],
                'previous' => (string) ($item['status'] ?? 'Pending Approval'),
                'new' => $newStatus,
                'comment' => $note !== '' ? $note : ($newStatus === 'Approved' ? 'Approved in guided review.' : 'Changes requested in guided review.'),
            ]
        );

        if ($note !== '') {
            Database::insert(
                'INSERT INTO item_comments (calendar_item_id, user_id, visibility, comment) VALUES (:item, :user, :visibility, :comment)',
                [
                    'item' => $itemId,
                    'user' => (int) $user['id'],
                    'visibility' => 'shared',
                    'comment' => $note,
                ]
            );
        }

        ActivityLogger::log($newStatus === 'Approved' ? 'client_approved_guided' : 'client_requested_changes_guided', 'calendar_item', $itemId, ['status' => $newStatus]);

        (new NotificationService($this->config))->notify(
            (int) $item['assigned_employee_id'],
            $itemId,
            'client_review',
            $newStatus === 'Approved' ? 'Client approved the content' : 'Client requested changes',
            "Post: {$item['title']}\nClient: {$item['company_name']}\nStatus: {$newStatus}\nFeedback: " . ($note !== '' ? $note : 'No additional note')
        );

        $this->flash('success', $newStatus === 'Approved'
            ? 'Approved successfully. The G2 team has been notified.'
            : 'Changes requested. The G2 team has been notified.');
        $this->redirect('approvals');
    }
}
