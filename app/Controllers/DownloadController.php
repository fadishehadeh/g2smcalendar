<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CalendarItem;
use App\Services\ActivityLogger;

final class DownloadController extends Controller
{
    public function preview(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);

        $fileId = (int) ($_GET['file_id'] ?? 0);
        $file = Database::fetch(
            "SELECT f.*, ci.status, ci.id AS item_id, ci.client_id
             FROM item_files f
             JOIN calendar_items ci ON ci.id = f.calendar_item_id
             WHERE f.id = :id",
            ['id' => $fileId]
        );

        if (!$file || !CalendarItem::canAccess(['client_id' => $file['client_id']])) {
            http_response_code(403);
            exit('Forbidden');
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . $file['file_size']);
        header('Content-Disposition: inline; filename="' . basename($file['original_name']) . '"');
        readfile($file['file_path']);
        exit;
    }

    public function download(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);

        $fileId = (int) ($_GET['file_id'] ?? 0);
        $file = Database::fetch(
            "SELECT f.*, ci.status, ci.id AS item_id, ci.client_id
             FROM item_files f
             JOIN calendar_items ci ON ci.id = f.calendar_item_id
             WHERE f.id = :id",
            ['id' => $fileId]
        );

        if (!$file || !CalendarItem::canAccess(['client_id' => $file['client_id']])) {
            http_response_code(403);
            exit('Forbidden');
        }

        Database::insert(
            'INSERT INTO download_logs (calendar_item_id, item_file_id, downloaded_by) VALUES (:item, :file, :user)',
            ['item' => $file['item_id'], 'file' => $fileId, 'user' => Auth::user()['id']]
        );

        Database::query("UPDATE calendar_items SET status = CASE WHEN status = 'Approved' THEN 'Downloaded' ELSE status END WHERE id = :id", ['id' => $file['item_id']]);
        ActivityLogger::log('file_downloaded', 'calendar_item', (int) $file['item_id'], ['file_id' => $fileId]);

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . $file['file_size']);
        header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
        readfile($file['file_path']);
        exit;
    }
}
