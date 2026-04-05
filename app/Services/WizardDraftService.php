<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class WizardDraftService
{
    public function load(string $wizardKey, int $userId): array
    {
        $row = Database::fetch(
            'SELECT * FROM wizard_drafts WHERE wizard_key = :wizard_key AND user_id = :user_id LIMIT 1',
            ['wizard_key' => $wizardKey, 'user_id' => $userId]
        );

        if (!$row) {
            return [];
        }

        $payload = json_decode((string) ($row['draft_payload'] ?? '{}'), true);
        return is_array($payload) ? $payload : [];
    }

    public function save(string $wizardKey, int $userId, array $payload, string $title = ''): void
    {
        Database::query(
            'INSERT INTO wizard_drafts (wizard_key, user_id, draft_title, draft_payload, status)
             VALUES (:wizard_key, :user_id, :draft_title, :draft_payload, :status)
             ON DUPLICATE KEY UPDATE draft_title = VALUES(draft_title), draft_payload = VALUES(draft_payload), status = VALUES(status), updated_at = CURRENT_TIMESTAMP',
            [
                'wizard_key' => $wizardKey,
                'user_id' => $userId,
                'draft_title' => $title !== '' ? $title : null,
                'draft_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'draft',
            ]
        );
    }

    public function clear(string $wizardKey, int $userId): void
    {
        Database::query(
            'DELETE FROM wizard_drafts WHERE wizard_key = :wizard_key AND user_id = :user_id',
            ['wizard_key' => $wizardKey, 'user_id' => $userId]
        );
    }
}
