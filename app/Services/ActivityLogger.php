<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class ActivityLogger
{
    public static function log(string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        $user = Auth::user();

        Database::insert(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip)',
            [
                'user_id' => $user['id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }
}
