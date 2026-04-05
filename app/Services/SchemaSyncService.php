<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SchemaSyncService
{
    public static function sync(): void
    {
        self::ensureEditHistoryTable();
        self::ensureMetricsTable();
        self::migratePendingApprovalStatus();
        self::seedMissingMetrics();
    }

    private static function ensureEditHistoryTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS item_edit_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                calendar_item_id INT UNSIGNED NOT NULL,
                changed_by INT UNSIGNED NOT NULL,
                field_name VARCHAR(80) NOT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_item_edit_history_item (calendar_item_id),
                INDEX idx_item_edit_history_created (created_at),
                CONSTRAINT fk_item_edit_history_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
                CONSTRAINT fk_item_edit_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureMetricsTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS post_metrics (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                calendar_item_id INT UNSIGNED NOT NULL,
                metric_date DATE NOT NULL,
                reach INT UNSIGNED NOT NULL DEFAULT 0,
                engagement INT UNSIGNED NOT NULL DEFAULT 0,
                clicks INT UNSIGNED NOT NULL DEFAULT 0,
                impressions INT UNSIGNED NOT NULL DEFAULT 0,
                saves INT UNSIGNED NOT NULL DEFAULT 0,
                shares INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_post_metrics_item_date (calendar_item_id, metric_date),
                INDEX idx_post_metrics_date (metric_date),
                CONSTRAINT fk_post_metrics_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function migratePendingApprovalStatus(): void
    {
        $tables = [
            ['table' => 'calendar_items', 'column' => 'status'],
            ['table' => 'item_status_history', 'column' => 'previous_status'],
            ['table' => 'item_status_history', 'column' => 'new_status'],
        ];

        foreach ($tables as $target) {
            Database::query(
                "UPDATE {$target['table']} SET {$target['column']} = 'Pending Approval' WHERE {$target['column']} = 'For Client Approval'"
            );
        }

        Database::query(
            "UPDATE notifications
             SET subject = REPLACE(subject, 'For Client Approval', 'Pending Approval'),
                 body = REPLACE(body, 'For Client Approval', 'Pending Approval')
             WHERE subject LIKE '%For Client Approval%' OR body LIKE '%For Client Approval%'"
        );

        Database::query(
            "UPDATE activity_logs
             SET details = REPLACE(details, 'For Client Approval', 'Pending Approval')
             WHERE details LIKE '%For Client Approval%'"
        );
    }

    private static function seedMissingMetrics(): void
    {
        $items = Database::fetchAll(
            "SELECT ci.id, ci.scheduled_date, ci.platform, ci.status
             FROM calendar_items ci
             LEFT JOIN post_metrics pm ON pm.calendar_item_id = ci.id
             WHERE pm.id IS NULL"
        );

        foreach ($items as $item) {
            $base = match (strtolower((string) $item['platform'])) {
                'instagram' => 1800,
                'facebook' => 1300,
                'youtube' => 2600,
                'tiktok' => 2400,
                'x', 'twitter' => 900,
                default => 1100,
            };

            $statusBoost = match ((string) $item['status']) {
                'Published' => 1.25,
                'Approved' => 1.1,
                'Pending Approval' => 0.85,
                default => 1.0,
            };

            $seed = ((int) $item['id'] * 37) + (int) date('j', strtotime((string) $item['scheduled_date']));
            $reach = (int) round(($base + ($seed % 700)) * $statusBoost);
            $engagement = (int) round($reach * (0.04 + (($seed % 6) / 100)));
            $clicks = (int) round($reach * (0.012 + (($seed % 4) / 1000)));
            $impressions = (int) round($reach * 1.55);
            $saves = max(0, (int) round($engagement * 0.18));
            $shares = max(0, (int) round($engagement * 0.11));

            Database::insert(
                'INSERT INTO post_metrics (calendar_item_id, metric_date, reach, engagement, clicks, impressions, saves, shares)
                 VALUES (:item, :metric_date, :reach, :engagement, :clicks, :impressions, :saves, :shares)',
                [
                    'item' => $item['id'],
                    'metric_date' => $item['scheduled_date'],
                    'reach' => $reach,
                    'engagement' => $engagement,
                    'clicks' => $clicks,
                    'impressions' => $impressions,
                    'saves' => $saves,
                    'shares' => $shares,
                ]
            );
        }
    }
}
