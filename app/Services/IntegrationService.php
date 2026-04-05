<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class IntegrationService
{
    public function __construct(private array $config, private WorkspaceSettingsService $settings)
    {
    }

    public function providers(): array
    {
        $providers = ['openai', 'meta', 'youtube', 'tiktok', 'x'];
        $rows = [];
        foreach ($providers as $provider) {
            $enabled = $this->settings->get("integrations.{$provider}.enabled", '0') === '1';
            $apiKey = $this->settings->get("integrations.{$provider}.api_key", '');
            if ($provider === 'openai' && $apiKey === '') {
                $apiKey = (string) ($this->config['app']['openai']['api_key'] ?? '');
            }

            $rows[] = [
                'provider' => $provider,
                'enabled' => $enabled,
                'configured' => $apiKey !== '',
                'masked_key' => $apiKey !== '' ? str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4) : '',
            ];
        }

        return $rows;
    }

    public function logs(): array
    {
        return Database::fetchAll(
            "SELECT isl.*, u.name AS triggered_by_name
             FROM integration_sync_logs isl
             LEFT JOIN users u ON u.id = isl.triggered_by
             ORDER BY isl.created_at DESC, isl.id DESC
             LIMIT 40"
        );
    }

    public function saveProvider(string $provider, string $enabled, string $apiKey): void
    {
        $this->settings->setMany([
            "integrations.{$provider}.enabled" => $enabled === '1' ? '1' : '0',
            "integrations.{$provider}.api_key" => trim($apiKey),
        ]);
    }

    public function testProvider(string $provider): array
    {
        if ($provider === 'openai') {
            $service = new AiAssistService($this->config, $this->settings);
            $result = $service->generateCaptionSuggestion([
                'company_name' => 'G2',
                'platform' => 'Instagram',
                'post_type' => 'Post',
                'campaign' => 'Integration Test',
                'caption_en' => 'Testing',
                'scheduled_date' => date('Y-m-d'),
            ]);

            $status = ($result['source'] ?? '') === 'openai' ? 'success' : 'fallback';
            $message = ($result['source'] ?? '') === 'openai'
                ? 'OpenAI connection succeeded.'
                : 'OpenAI key is missing or unavailable; heuristic fallback was used.';

            $this->log($provider, 'test_connection', $status, $message);
            return ['status' => $status, 'message' => $message];
        }

        $enabled = $this->settings->get("integrations.{$provider}.enabled", '0') === '1';
        $configured = $this->settings->get("integrations.{$provider}.api_key", '') !== '';
        $status = ($enabled && $configured) ? 'configured' : 'missing_credentials';
        $message = ($enabled && $configured)
            ? ucfirst($provider) . ' credentials are saved. Live metric sync endpoints still need provider-specific tokens/scopes.'
            : ucfirst($provider) . ' is not fully configured yet.';

        $this->log($provider, 'test_connection', $status, $message);
        return ['status' => $status, 'message' => $message];
    }

    public function syncMetrics(string $provider): array
    {
        $configured = $this->settings->get("integrations.{$provider}.api_key", '') !== '';
        $message = $configured
            ? ucfirst($provider) . ' sync job queued. Replace demo sync logic with provider-specific analytics ingestion once final credentials/scopes are available.'
            : ucfirst($provider) . ' sync could not run because credentials are missing.';
        $status = $configured ? 'queued' : 'missing_credentials';
        $this->log($provider, 'sync_metrics', $status, $message);

        return ['status' => $status, 'message' => $message];
    }

    private function log(string $provider, string $action, string $status, string $message): void
    {
        Database::insert(
            'INSERT INTO integration_sync_logs (provider, action, status, message, triggered_by)
             VALUES (:provider, :action, :status, :message, :triggered_by)',
            [
                'provider' => $provider,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'triggered_by' => Auth::user()['id'] ?? null,
            ]
        );
    }
}
