<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class MailTransport
{
    public function __construct(private array $config)
    {
    }

    public function send(string $toEmail, string $toName, string $subject, string $textBody, string $htmlBody): bool
    {
        $this->sendWithResult($toEmail, $toName, $subject, $textBody, $htmlBody);
        return true;
    }

    public function sendWithResult(string $toEmail, string $toName, string $subject, string $textBody, string $htmlBody): array
    {
        $mailer = $this->config['mailer'] ?? ($this->config['app']['mailer'] ?? []);
        $driver = strtolower((string) ($mailer['driver'] ?? 'log'));
        $logOnly = (bool) ($mailer['log_only'] ?? true);

        if ($driver !== 'mailjet' || $logOnly) {
            $this->log($subject, $textBody, $toEmail);
            return [
                'provider' => $driver !== '' ? $driver : 'log',
                'status' => 'logged',
                'message_id' => null,
                'message_uuid' => null,
                'response' => null,
            ];
        }

        $mailjet = $mailer['mailjet'] ?? [];
        $apiKey = (string) ($mailjet['api_key'] ?? '');
        $apiSecret = (string) ($mailjet['api_secret'] ?? '');
        $endpoint = (string) ($mailjet['endpoint'] ?? 'https://api.mailjet.com/v3.1/send');

        if ($apiKey === '' || $apiSecret === '') {
            throw new RuntimeException('Mailjet is enabled, but the API credentials are missing.');
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for Mailjet email delivery.');
        }

        $message = [
            'From' => [
                'Email' => (string) ($mailer['from_email'] ?? 'no-reply@g2.local'),
                'Name' => (string) ($mailer['from_name'] ?? 'G2 Social Media Calendar'),
            ],
            'To' => [[
                'Email' => $toEmail,
                'Name' => $toName !== '' ? $toName : $toEmail,
            ]],
            'Subject' => $subject,
            'TextPart' => $textBody,
            'HTMLPart' => $htmlBody,
        ];

        if (!empty($mailer['reply_to_email'])) {
            $message['ReplyTo'] = [
                'Email' => (string) $mailer['reply_to_email'],
                'Name' => (string) ($mailer['reply_to_name'] ?: $mailer['from_name']),
            ];
        }

        $payload = json_encode(['Messages' => [$message]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            throw new RuntimeException('Failed to encode Mailjet payload.');
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $apiKey . ':' . $apiSecret,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Mailjet request failed: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Mailjet request failed with HTTP ' . $httpCode . ': ' . $response);
        }

        $decoded = json_decode($response, true);
        $status = $decoded['Messages'][0]['Status'] ?? null;
        if ($status !== null && strtolower((string) $status) !== 'success') {
            throw new RuntimeException('Mailjet rejected the message: ' . $response);
        }

        $this->log($subject, $textBody, $toEmail);
        return [
            'provider' => 'mailjet',
            'status' => (string) ($decoded['Messages'][0]['Status'] ?? 'success'),
            'message_id' => isset($decoded['Messages'][0]['To'][0]['MessageID']) ? (string) $decoded['Messages'][0]['To'][0]['MessageID'] : null,
            'message_uuid' => isset($decoded['Messages'][0]['To'][0]['MessageUUID']) ? (string) $decoded['Messages'][0]['To'][0]['MessageUUID'] : null,
            'response' => $response,
        ];
    }

    private function log(string $subject, string $textBody, string $toEmail): void
    {
        $logLine = sprintf("[%s] To: %s | %s\n%s\n\n", date('Y-m-d H:i:s'), $toEmail, $subject, $textBody);
        file_put_contents(dirname(__DIR__, 2) . '/storage/logs/mail.log', $logLine, FILE_APPEND);
    }
}
