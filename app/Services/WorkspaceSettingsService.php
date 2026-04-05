<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class WorkspaceSettingsService
{
    public function all(): array
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM workspace_settings ORDER BY setting_key');
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $map;
    }

    public function get(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT setting_value FROM workspace_settings WHERE setting_key = :setting_key', ['setting_key' => $key]);
        return $row !== null ? (string) ($row['setting_value'] ?? '') : $default;
    }

    public function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO workspace_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            ['setting_key' => $key, 'setting_value' => $value]
        );
    }

    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set((string) $key, (string) $value);
        }
    }
}
