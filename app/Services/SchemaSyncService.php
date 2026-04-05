<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SchemaSyncService
{
    public static function sync(): void
    {
        self::ensureClientWizardColumns();
        self::ensureCalendarWizardColumns();
        self::ensureItemWizardColumns();
        self::ensureEditHistoryTable();
        self::ensureMetricsTable();
        self::ensureWorkspaceSettingsTable();
        self::ensureReportRunsTable();
        self::ensureIntegrationSyncLogTable();
        self::ensureWizardDraftsTable();
        self::migratePendingApprovalStatus();
        self::seedMissingMetrics();
        self::seedDefaultSettings();
    }

    private static function ensureClientWizardColumns(): void
    {
        $columns = [
            'account_owner_employee_id' => "ALTER TABLE clients ADD COLUMN account_owner_employee_id INT UNSIGNED NULL AFTER client_user_id",
            'workflow_preferences' => "ALTER TABLE clients ADD COLUMN workflow_preferences TEXT NULL AFTER status",
            'approval_turnaround' => "ALTER TABLE clients ADD COLUMN approval_turnaround VARCHAR(120) NULL AFTER workflow_preferences",
            'brand_notes' => "ALTER TABLE clients ADD COLUMN brand_notes TEXT NULL AFTER approval_turnaround",
            'naming_conventions' => "ALTER TABLE clients ADD COLUMN naming_conventions TEXT NULL AFTER brand_notes",
        ];

        foreach ($columns as $name => $sql) {
            if (!Database::fetch("SHOW COLUMNS FROM clients LIKE '{$name}'")) {
                Database::query($sql);
            }
        }

        if (!Database::fetch("SHOW INDEX FROM clients WHERE Key_name = 'idx_clients_account_owner'")) {
            Database::query('ALTER TABLE clients ADD INDEX idx_clients_account_owner (account_owner_employee_id)');
        }

        if (!Database::fetch("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'account_owner_employee_id' AND REFERENCED_TABLE_NAME = 'users'")) {
            Database::query('ALTER TABLE clients ADD CONSTRAINT fk_clients_account_owner FOREIGN KEY (account_owner_employee_id) REFERENCES users(id) ON DELETE SET NULL');
        }
    }

    private static function ensureCalendarWizardColumns(): void
    {
        $columns = [
            'campaign_name' => "ALTER TABLE calendars ADD COLUMN campaign_name VARCHAR(120) NULL AFTER title",
            'notes' => "ALTER TABLE calendars ADD COLUMN notes TEXT NULL AFTER status",
            'creation_mode' => "ALTER TABLE calendars ADD COLUMN creation_mode VARCHAR(60) NULL AFTER notes",
            'posting_frequency' => "ALTER TABLE calendars ADD COLUMN posting_frequency VARCHAR(80) NULL AFTER creation_mode",
            'primary_platforms' => "ALTER TABLE calendars ADD COLUMN primary_platforms VARCHAR(255) NULL AFTER posting_frequency",
            'approval_timeline' => "ALTER TABLE calendars ADD COLUMN approval_timeline VARCHAR(120) NULL AFTER primary_platforms",
        ];

        foreach ($columns as $name => $sql) {
            if (!Database::fetch("SHOW COLUMNS FROM calendars LIKE '{$name}'")) {
                Database::query($sql);
            }
        }
    }

    private static function ensureItemWizardColumns(): void
    {
        $columns = [
            'priority' => "ALTER TABLE calendar_items ADD COLUMN priority VARCHAR(30) NULL AFTER cta",
            'approval_route' => "ALTER TABLE calendar_items ADD COLUMN approval_route VARCHAR(80) NULL AFTER priority",
        ];

        foreach ($columns as $name => $sql) {
            if (!Database::fetch("SHOW COLUMNS FROM calendar_items LIKE '{$name}'")) {
                Database::query($sql);
            }
        }
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

    private static function ensureWorkspaceSettingsTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS workspace_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL UNIQUE,
                setting_value TEXT NULL,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureReportRunsTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS report_runs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                report_type ENUM('weekly', 'monthly') NOT NULL,
                report_month TINYINT UNSIGNED NULL,
                report_year SMALLINT UNSIGNED NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                generated_by INT UNSIGNED NULL,
                recipient_email VARCHAR(190) NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'generated',
                report_subject VARCHAR(190) NOT NULL,
                report_body MEDIUMTEXT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_report_runs_created (created_at),
                CONSTRAINT fk_report_runs_user FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureIntegrationSyncLogTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS integration_sync_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(60) NOT NULL,
                action VARCHAR(80) NOT NULL,
                status VARCHAR(40) NOT NULL,
                message TEXT NULL,
                triggered_by INT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_integration_sync_logs_created (created_at),
                CONSTRAINT fk_integration_sync_logs_user FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureWizardDraftsTable(): void
    {
        Database::query(
            "CREATE TABLE IF NOT EXISTS wizard_drafts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                wizard_key VARCHAR(80) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                draft_title VARCHAR(150) NULL,
                draft_payload LONGTEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'draft',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_wizard_user_key (wizard_key, user_id),
                INDEX idx_wizard_drafts_updated (updated_at),
                CONSTRAINT fk_wizard_drafts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function seedDefaultSettings(): void
    {
        $defaults = [
            'reports.weekly.enabled' => '0',
            'reports.weekly.recipient' => '',
            'reports.monthly.enabled' => '1',
            'reports.monthly.recipient' => '',
            'integrations.openai.enabled' => '0',
            'integrations.openai.api_key' => '',
            'integrations.meta.enabled' => '0',
            'integrations.meta.api_key' => '',
            'integrations.youtube.enabled' => '0',
            'integrations.youtube.api_key' => '',
            'integrations.tiktok.enabled' => '0',
            'integrations.tiktok.api_key' => '',
            'integrations.x.enabled' => '0',
            'integrations.x.api_key' => '',
            'wizard.default_approval_turnaround' => '48 hours',
            'wizard.default_posting_frequency' => '3 posts per week',
        ];

        foreach ($defaults as $key => $value) {
            Database::query(
                'INSERT IGNORE INTO workspace_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)',
                ['setting_key' => $key, 'setting_value' => $value]
            );
        }
    }
}
