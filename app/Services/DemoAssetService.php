<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class DemoAssetService
{
    public function __construct(private array $config)
    {
    }

    public function provision(string $kind): array
    {
        $kind = strtolower($kind);

        return match ($kind) {
            'video' => $this->ensureVideoAsset(),
            default => $this->ensureImageAsset(),
        };
    }

    private function ensureImageAsset(): array
    {
        $target = $this->config['app']['uploads_path'] . '/demo-approval-image.svg';

        if (!is_file($target)) {
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="1200" viewBox="0 0 1200 1200">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#d92d2a" />
      <stop offset="100%" stop-color="#8b1e1e" />
    </linearGradient>
  </defs>
  <rect width="1200" height="1200" fill="url(#g)" />
  <circle cx="930" cy="240" r="190" fill="rgba(255,255,255,0.12)" />
  <circle cx="240" cy="930" r="220" fill="rgba(255,255,255,0.08)" />
  <rect x="88" y="110" width="1024" height="980" rx="42" fill="rgba(255,255,255,0.12)" />
  <text x="128" y="250" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="60" font-weight="700">Dukhan Bank</text>
  <text x="128" y="340" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="86" font-weight="800">Approval Draft</text>
  <text x="128" y="418" fill="#ffe4e4" font-family="Segoe UI, Arial, sans-serif" font-size="34">G2 dummy artwork for testing image approvals</text>
  <rect x="128" y="860" width="326" height="112" rx="28" fill="rgba(255,255,255,0.16)" />
  <text x="174" y="930" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="34" font-weight="700">Preview Ready</text>
</svg>
SVG;

            file_put_contents($target, $svg);
        }

        return [
            'original_name' => 'demo-approval-image.svg',
            'stored_name' => 'demo-approval-image.svg',
            'file_path' => $target,
            'mime_type' => 'image/svg+xml',
            'file_size' => max(1, filesize($target)),
        ];
    }

    private function ensureVideoAsset(): array
    {
        $target = $this->config['app']['uploads_path'] . '/demo-approval-video.mp4';

        if (!is_file($target) || filesize($target) < 1024) {
            $sources = [
                'https://samplelib.com/lib/preview/mp4/sample-5s.mp4',
                'https://filesamples.com/samples/video/mp4/sample_640x360.mp4',
            ];

            foreach ($sources as $source) {
                $context = stream_context_create([
                    'http' => ['timeout' => 12],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);

                $binary = @file_get_contents($source, false, $context);
                if ($binary !== false && strlen($binary) > 1024) {
                    file_put_contents($target, $binary);
                    break;
                }
            }
        }

        if (!is_file($target) || filesize($target) < 1024) {
            throw new RuntimeException('Unable to provision dummy video asset.');
        }

        return [
            'original_name' => 'demo-approval-video.mp4',
            'stored_name' => 'demo-approval-video.mp4',
            'file_path' => $target,
            'mime_type' => 'video/mp4',
            'file_size' => max(1, filesize($target)),
        ];
    }
}
