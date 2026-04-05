<?php

declare(strict_types=1);

namespace App\Core;

final class Ui
{
    public static function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'G2';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'G2';
    }

    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            'master_admin' => 'Master Admin',
            'employee' => 'Agency Employee',
            'client' => 'Client',
            default => ucwords(str_replace('_', ' ', (string) $role)),
        };
    }

    public static function statusClass(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $status = str_replace([' ', '/'], ['-', '-'], $status);
        return 'status-' . $status;
    }

    public static function platformLabel(?string $platform): string
    {
        return ucfirst(strtolower((string) $platform));
    }

    public static function icon(string $name): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 12.75h6.25V4.5H4v8.25Zm9.75 6.75H20V4.5h-6.25v15Zm-9.75 0H10.25v-3.75H4v3.75Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7.75 3v3M16.25 3v3M3.5 9.25h17M5.25 5.5h13.5A1.75 1.75 0 0 1 20.5 7.25v11.5a1.75 1.75 0 0 1-1.75 1.75H5.25A1.75 1.75 0 0 1 3.5 18.75V7.25A1.75 1.75 0 0 1 5.25 5.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24" fill="none"><path d="M16.75 20v-1.25A3.75 3.75 0 0 0 13 15h-6a3.75 3.75 0 0 0-3.75 3.75V20M18 7.5A2.5 2.5 0 1 1 18 12.5M10 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'employees' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 13.5a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5ZM4 20a7.25 7.25 0 0 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'assignments' => '<svg viewBox="0 0 24 24" fill="none"><path d="M8.5 8.5h11M8.5 15.5h11M4.5 8.5h.01M4.5 15.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="3.5" y="3.5" width="17" height="17" rx="3" stroke="currentColor" stroke-width="1.6"/></svg>',
            'posts' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            'approvals' => '<svg viewBox="0 0 24 24" fill="none"><path d="m7.75 12 2.5 2.5 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/></svg>',
            'artwork' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="4.5" width="17" height="15" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="m7.5 15.5 3-3 2.5 2.5 3.5-4 3 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8.5" cy="9" r="1.25" fill="currentColor"/></svg>',
            'notifications' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6.75 9.25a5.25 5.25 0 1 1 10.5 0c0 6 2.25 6.75 2.25 6.75H4.5s2.25-.75 2.25-6.75ZM10 19h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'activity' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 12h3l2-5 4 10 2-5h4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3.5" y="4.5" width="17" height="15" rx="3" stroke="currentColor" stroke-width="1.6"/></svg>',
            'settings' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 8.75a3.25 3.25 0 1 0 0 6.5 3.25 3.25 0 0 0 0-6.5Z" stroke="currentColor" stroke-width="1.6"/><path d="M19.25 12a7.94 7.94 0 0 0-.12-1.38l1.86-1.45-1.75-3.03-2.26.91a8.24 8.24 0 0 0-2.38-1.38l-.35-2.42H9.75L9.4 5.67a8.24 8.24 0 0 0-2.38 1.38l-2.27-.91L3 9.17l1.86 1.45a8.43 8.43 0 0 0 0 2.76L3 14.83l1.75 3.03 2.27-.91c.72.58 1.52 1.04 2.38 1.38l.35 2.42h4.5l.35-2.42a8.24 8.24 0 0 0 2.38-1.38l2.26.91L21 14.83l-1.86-1.45c.08-.45.11-.91.11-1.38Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>',
            'bell' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6.75 9.25a5.25 5.25 0 1 1 10.5 0c0 6 2.25 6.75 2.25 6.75H4.5s2.25-.75 2.25-6.75ZM10 19h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            'chevron' => '<svg viewBox="0 0 24 24" fill="none"><path d="m8 10 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'close' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'menu' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'plus' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'filter' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 6.5h15M7.5 12h9M10.5 17.5h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'left' => '<svg viewBox="0 0 24 24" fill="none"><path d="m14.5 6-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'right' => '<svg viewBox="0 0 24 24" fill="none"><path d="m9.5 6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'trend' => '<svg viewBox="0 0 24 24" fill="none"><path d="m7 15 4-4 3 3 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'comment' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 17.25V19.5l3.25-2.25H18A2.25 2.25 0 0 0 20.25 15V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v9A2.25 2.25 0 0 0 6 17.25Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
            'download' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 4.5v9M8.5 10.5 12 14l3.5-3.5M5 18.5h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$name] ?? $icons['dashboard'];
    }

    public static function platformIcon(string $platform): string
    {
        $platform = strtolower($platform);
        return match ($platform) {
            'instagram' => '<span class="platform-badge platform-instagram">IG</span>',
            'facebook' => '<span class="platform-badge platform-facebook">FB</span>',
            'tiktok' => '<span class="platform-badge platform-tiktok">TT</span>',
            'youtube' => '<span class="platform-badge platform-youtube">YT</span>',
            'x', 'twitter' => '<span class="platform-badge platform-x">X</span>',
            default => '<span class="platform-badge platform-generic">' . htmlspecialchars(strtoupper(substr($platform, 0, 2))) . '</span>',
        };
    }

    public static function mediaKind(?string $mimeType): string
    {
        $mimeType = strtolower((string) $mimeType);

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        return 'file';
    }
}
