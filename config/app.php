<?php

declare(strict_types=1);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseDir = rtrim(str_replace('/index.php', '', $scriptName), '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'name' => 'G2 Social Media Calendar',
    'base_url' => $baseDir === '' ? '' : $baseDir,
    'url' => rtrim((string) $env('APP_URL', $scheme . '://' . $host . ($baseDir === '' ? '' : $baseDir)), '/'),
    'uploads_path' => dirname(__DIR__) . '/storage/uploads/private',
    'mailer' => [
        'driver' => (string) $env('MAIL_DRIVER', 'log'),
        'from_email' => (string) $env('MAIL_FROM_EMAIL', 'no-reply@g2.local'),
        'from_name' => (string) $env('MAIL_FROM_NAME', 'G2 Social Media Calendar'),
        'reply_to_email' => (string) $env('MAIL_REPLY_TO_EMAIL', ''),
        'reply_to_name' => (string) $env('MAIL_REPLY_TO_NAME', ''),
        'log_only' => filter_var((string) $env('MAIL_LOG_ONLY', 'true'), FILTER_VALIDATE_BOOLEAN),
        'mailjet' => [
            'api_key' => (string) $env('MAILJET_API_KEY', ''),
            'api_secret' => (string) $env('MAILJET_API_SECRET', ''),
            'endpoint' => (string) $env('MAILJET_ENDPOINT', 'https://api.mailjet.com/v3.1/send'),
        ],
    ],
    'openai' => [
        'api_key' => (string) $env('OPENAI_API_KEY', ''),
        'model' => (string) $env('OPENAI_MODEL', 'gpt-5'),
        'endpoint' => (string) $env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/responses'),
    ],
];
