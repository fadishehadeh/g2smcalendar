<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UploadService
{
    private const ALLOWED = [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'application/pdf',
        'video/mp4',
    ];

    public function __construct(private array $config)
    {
    }

    public function store(array $file, ?array $allowedMimes = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Artwork upload failed.');
        }

        $mime = mime_content_type($file['tmp_name']);
        $allowed = $allowedMimes ?? self::ALLOWED;
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Unsupported file type.');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = uniqid('art_', true) . '.' . strtolower((string) $extension);
        $target = $this->config['app']['uploads_path'] . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to move uploaded file.');
        }

        return [
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'file_path' => $target,
            'mime_type' => $mime,
            'file_size' => (int) $file['size'],
        ];
    }
}
