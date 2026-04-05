<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class AiAssistService
{
    public function __construct(private array $config, private WorkspaceSettingsService $settings)
    {
    }

    public function generateCaptionSuggestion(array $item): array
    {
        $apiKey = $this->resolveApiKey();
        if ($apiKey === '') {
            return $this->heuristicSuggestion($item, false);
        }

        if (!function_exists('curl_init')) {
            return $this->heuristicSuggestion($item, false);
        }

        try {
            $response = $this->callOpenAi($item, $apiKey);
            if ($response !== null) {
                return $response + ['source' => 'openai'];
            }
        } catch (\Throwable) {
        }

        return $this->heuristicSuggestion($item, false);
    }

    private function resolveApiKey(): string
    {
        $settingsKey = $this->settings->get('integrations.openai.api_key', '');
        if ($settingsKey !== '') {
            return $settingsKey;
        }

        return (string) (($this->config['app']['openai']['api_key'] ?? '') ?: '');
    }

    private function callOpenAi(array $item, string $apiKey): ?array
    {
        $endpoint = (string) ($this->config['app']['openai']['endpoint'] ?? 'https://api.openai.com/v1/responses');
        $model = (string) ($this->config['app']['openai']['model'] ?? 'gpt-5');

        $prompt = "You are helping a social media agency.\n"
            . "Return JSON only with keys: caption, suggested_time, rationale.\n"
            . "Client: {$item['company_name']}\n"
            . "Platform: {$item['platform']}\n"
            . "Post type: {$item['post_type']}\n"
            . "Campaign: " . ($item['campaign'] ?: 'General') . "\n"
            . "Current caption: " . ($item['caption_en'] ?: 'None') . "\n"
            . "Scheduled date: {$item['scheduled_date']}\n"
            . "Goal: provide a stronger caption and one suggested publishing time in HH:MM format.";

        $payload = json_encode([
            'model' => $model,
            'input' => $prompt,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new RuntimeException('Failed to encode OpenAI payload.');
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 25,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('OpenAI request failed: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('OpenAI request failed with HTTP ' . $httpCode . ': ' . $raw);
        }

        $decoded = json_decode($raw, true);
        $text = (string) ($decoded['output'][0]['content'][0]['text'] ?? $decoded['output_text'] ?? '');
        if ($text === '') {
            return null;
        }

        $json = $this->extractJson($text);
        if ($json === null) {
            return null;
        }

        return [
            'caption' => trim((string) ($json['caption'] ?? '')),
            'suggested_time' => trim((string) ($json['suggested_time'] ?? '10:00')),
            'rationale' => trim((string) ($json['rationale'] ?? 'Generated from current post context.')),
        ];
    }

    private function extractJson(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $decoded = json_decode($trimmed, true);
            return is_array($decoded) ? $decoded : null;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function heuristicSuggestion(array $item, bool $configured): array
    {
        $campaign = trim((string) ($item['campaign'] ?? '')) ?: 'this campaign';
        $platform = strtolower((string) $item['platform']);

        $time = match ($platform) {
            'instagram' => '18:30',
            'facebook' => '13:00',
            'youtube' => '20:00',
            'tiktok' => '19:30',
            'x' => '09:00',
            default => '12:00',
        };

        $caption = match ($platform) {
            'instagram' => "A sharper look at {$campaign}. Built for attention, clarity, and action. Discover what makes it relevant today.",
            'facebook' => "{$campaign} is built around practical value, clear communication, and outcomes that matter. Explore the full story.",
            'youtube' => "What makes {$campaign} worth watching right now? This piece breaks it down with a clear takeaway and next step.",
            'tiktok' => "{$campaign}, simplified. Fast take, clear value, and one reason to keep watching.",
            default => "A stronger update for {$campaign}, focused on clarity, relevance, and a clear next action.",
        };

        return [
            'caption' => $caption,
            'suggested_time' => $time,
            'rationale' => $configured
                ? 'Fallback heuristic was used because the AI response could not be parsed.'
                : 'Heuristic recommendation based on platform engagement patterns because no OpenAI key is configured.',
            'source' => 'heuristic',
        ];
    }
}
