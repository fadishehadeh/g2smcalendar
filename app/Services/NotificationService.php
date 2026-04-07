<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

final class NotificationService
{
    public function __construct(private array $config)
    {
    }

    public function notify(int $userId, ?int $itemId, string $type, string $subject, string $body): void
    {
        if ($this->wasRecentlySent($userId, $itemId, $type, $subject, $body)) {
            return;
        }

        $notificationId = Database::insert(
            'INSERT INTO notifications (user_id, calendar_item_id, type, subject, body, sent_at, provider, provider_message_id, provider_message_uuid, provider_status, provider_response) VALUES (:user_id, :item_id, :type, :subject, :body, NULL, NULL, NULL, NULL, NULL, NULL)',
            [
                'user_id' => $userId,
                'item_id' => $itemId,
                'type' => $type,
                'subject' => $subject,
                'body' => $body,
            ]
        );

        $recipient = Database::fetch(
            'SELECT name, email FROM users WHERE id = :id',
            ['id' => $userId]
        );

        if (!$recipient || empty($recipient['email'])) {
            return;
        }

        $link = $this->buildItemLink($itemId, $type);
        $textBody = trim($body . ($link !== '' ? "\n\nOpen Item: " . $link : ''));
        $htmlBody = $this->buildHtml($recipient['name'] ?? '', $subject, $body, $link);

        try {
            $result = (new MailTransport($this->config))->sendWithResult(
                (string) $recipient['email'],
                (string) ($recipient['name'] ?? ''),
                $subject,
                $textBody,
                $htmlBody
            );

            Database::query(
                'UPDATE notifications
                 SET sent_at = NOW(),
                     provider = :provider,
                     provider_message_id = :message_id,
                     provider_message_uuid = :message_uuid,
                     provider_status = :provider_status,
                     provider_response = :provider_response
                 WHERE id = :id',
                [
                    'id' => $notificationId,
                    'provider' => $result['provider'] ?? null,
                    'message_id' => $result['message_id'] ?? null,
                    'message_uuid' => $result['message_uuid'] ?? null,
                    'provider_status' => $result['status'] ?? null,
                    'provider_response' => $result['response'] ?? null,
                ]
            );
        } catch (Throwable $exception) {
            Database::query(
                'UPDATE notifications
                 SET provider = :provider,
                     provider_status = :provider_status,
                     provider_response = :provider_response
                 WHERE id = :id',
                [
                    'id' => $notificationId,
                    'provider' => 'mailjet',
                    'provider_status' => 'error',
                    'provider_response' => $exception->getMessage(),
                ]
            );

            $line = sprintf("[%s] MAIL ERROR: %s\n", date('Y-m-d H:i:s'), $exception->getMessage());
            file_put_contents(dirname(__DIR__, 2) . '/storage/logs/mail.log', $line, FILE_APPEND);
        }
    }

    private function buildItemLink(?int $itemId, string $type): string
    {
        if (!$itemId) {
            return '';
        }

        $baseUrl = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
        $route = $type === 'item_submitted' ? 'approval.review' : 'calendar.item';

        return $baseUrl . '/index.php?route=' . $route . '&item_id=' . $itemId;
    }

    private function wasRecentlySent(int $userId, ?int $itemId, string $type, string $subject, string $body): bool
    {
        $recent = Database::fetch(
            'SELECT id
             FROM notifications
             WHERE user_id = :user_id
               AND ((calendar_item_id = :item_id) OR (calendar_item_id IS NULL AND :item_id IS NULL))
               AND type = :type
               AND subject = :subject
               AND body = :body
               AND sent_at IS NOT NULL
               AND sent_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
             ORDER BY id DESC
             LIMIT 1',
            [
                'user_id' => $userId,
                'item_id' => $itemId,
                'type' => $type,
                'subject' => $subject,
                'body' => $body,
            ]
        );

        return $recent !== null;
    }

    private function buildHtml(string $recipientName, string $subject, string $body, string $link): string
    {
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $safeName = htmlspecialchars($recipientName !== '' ? $recipientName : 'there', ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $button = $link !== ''
            ? '<p style="margin:24px 0 0;"><a href="' . $safeLink . '" style="display:inline-block;padding:12px 20px;border-radius:12px;background:#d92d2a;color:#ffffff;text-decoration:none;font-weight:700;">Open Item</a></p>'
            : '';

        return <<<HTML
<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f6f7fb;font-family:Segoe UI,Arial,sans-serif;color:#131722;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e6e8ef;border-radius:18px;overflow:hidden;">
    <tr>
      <td style="padding:22px 24px;background:#d92d2a;color:#ffffff;">
        <div style="font-size:12px;letter-spacing:0.12em;font-weight:800;">G2 SOCIAL CALENDAR</div>
        <div style="margin-top:8px;font-size:24px;font-weight:800;">{$safeSubject}</div>
      </td>
    </tr>
    <tr>
      <td style="padding:24px;">
        <p style="margin:0 0 14px;font-size:15px;color:#6e7687;">Hello {$safeName},</p>
        <div style="font-size:15px;line-height:1.7;color:#131722;">{$safeBody}</div>
        {$button}
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
