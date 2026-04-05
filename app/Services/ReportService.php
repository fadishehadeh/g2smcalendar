<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class ReportService
{
    public function __construct(private array $config, private WorkspaceSettingsService $settings, private WorkspaceService $workspace)
    {
    }

    public function runs(): array
    {
        return Database::fetchAll(
            "SELECT rr.*, u.name AS generated_by_name
             FROM report_runs rr
             LEFT JOIN users u ON u.id = rr.generated_by
             ORDER BY rr.created_at DESC, rr.id DESC
             LIMIT 40"
        );
    }

    public function automationSettings(): array
    {
        return [
            'weekly_enabled' => $this->settings->get('reports.weekly.enabled', '0'),
            'weekly_recipient' => $this->settings->get('reports.weekly.recipient', ''),
            'monthly_enabled' => $this->settings->get('reports.monthly.enabled', '1'),
            'monthly_recipient' => $this->settings->get('reports.monthly.recipient', ''),
        ];
    }

    public function saveAutomationSettings(array $data): void
    {
        $this->settings->setMany([
            'reports.weekly.enabled' => !empty($data['weekly_enabled']) ? '1' : '0',
            'reports.weekly.recipient' => trim((string) ($data['weekly_recipient'] ?? '')),
            'reports.monthly.enabled' => !empty($data['monthly_enabled']) ? '1' : '0',
            'reports.monthly.recipient' => trim((string) ($data['monthly_recipient'] ?? '')),
        ]);
    }

    public function generate(string $type, array $filters = [], bool $sendEmail = false): int
    {
        $period = $this->period($type, $filters);
        $month = (int) date('n', strtotime($period['from']));
        $year = (int) date('Y', strtotime($period['from']));

        $overview = $this->workspace->analyticsOverview([
            'month' => $month,
            'year' => $year,
            'client_id' => $filters['client_id'] ?? '',
            'platform' => $filters['platform'] ?? '',
        ]);
        $posts = $this->workspace->analyticsPosts([
            'month' => $month,
            'year' => $year,
            'client_id' => $filters['client_id'] ?? '',
            'platform' => $filters['platform'] ?? '',
        ]);

        $subject = strtoupper($type) . ' Report · ' . $period['label'];
        $body = $this->buildBody($subject, $overview, $posts);
        $recipient = trim((string) ($filters['recipient_email'] ?? ''));

        $reportId = Database::insert(
            'INSERT INTO report_runs (report_type, report_month, report_year, period_start, period_end, generated_by, recipient_email, status, report_subject, report_body)
             VALUES (:report_type, :report_month, :report_year, :period_start, :period_end, :generated_by, :recipient_email, :status, :report_subject, :report_body)',
            [
                'report_type' => $type,
                'report_month' => $month,
                'report_year' => $year,
                'period_start' => $period['from'],
                'period_end' => $period['to'],
                'generated_by' => Auth::user()['id'] ?? null,
                'recipient_email' => $recipient !== '' ? $recipient : null,
                'status' => $sendEmail && $recipient !== '' ? 'queued' : 'generated',
                'report_subject' => $subject,
                'report_body' => $body,
            ]
        );

        if ($sendEmail && $recipient !== '') {
            (new MailTransport($this->config))->send($recipient, $recipient, $subject, $body, nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));
            Database::query('UPDATE report_runs SET status = :status WHERE id = :id', ['status' => 'emailed', 'id' => $reportId]);
        }

        return $reportId;
    }

    public function dispatchDueReports(): int
    {
        $settings = $this->automationSettings();
        $generated = 0;

        if ($settings['weekly_enabled'] === '1' && $settings['weekly_recipient'] !== '') {
            $this->generate('weekly', ['recipient_email' => $settings['weekly_recipient']], true);
            $generated++;
        }

        if ($settings['monthly_enabled'] === '1' && $settings['monthly_recipient'] !== '') {
            $this->generate('monthly', ['recipient_email' => $settings['monthly_recipient']], true);
            $generated++;
        }

        return $generated;
    }

    private function period(string $type, array $filters): array
    {
        if ($type === 'weekly') {
            $from = date('Y-m-d', strtotime('monday this week'));
            $to = date('Y-m-d', strtotime($from . ' +6 days'));
            return ['from' => $from, 'to' => $to, 'label' => date('M j', strtotime($from)) . ' - ' . date('M j, Y', strtotime($to))];
        }

        $month = (int) ($filters['month'] ?? date('n'));
        $year = (int) ($filters['year'] ?? date('Y'));
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));
        return ['from' => $from, 'to' => $to, 'label' => date('F Y', strtotime($from))];
    }

    private function buildBody(string $subject, array $overview, array $posts): string
    {
        $totals = $overview['totals'];
        $lines = [
            $subject,
            '',
            'Posts: ' . number_format((int) ($totals['posts'] ?? 0)),
            'Reach: ' . number_format((int) ($totals['reach'] ?? 0)),
            'Engagement: ' . number_format((int) ($totals['engagement'] ?? 0)),
            'Clicks: ' . number_format((int) ($totals['clicks'] ?? 0)),
            '',
            'Top Posts:',
        ];

        foreach (array_slice($posts, 0, 5) as $post) {
            $lines[] = '- ' . $post['title'] . ' | ' . $post['platform'] . ' | Reach ' . number_format((int) $post['reach']) . ' | Engagement ' . number_format((int) $post['engagement']);
        }

        return implode("\n", $lines);
    }
}
