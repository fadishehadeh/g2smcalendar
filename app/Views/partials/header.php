<?php

use App\Core\Auth;
use App\Core\Ui;

$baseUrl = $config['app']['base_url'];
$logoUrl = $baseUrl . '/public/assets/img/g2group.svg';
$user = Auth::user();
$currentRoute = $currentRoute ?? 'dashboard';
$shellData = $shellData ?? ['unread_notifications' => 0];
$cssVersion = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: time();

$navItems = [
    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'calendar', 'label' => 'Calendar', 'icon' => 'calendar', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'clients', 'label' => 'Clients', 'icon' => 'clients', 'roles' => ['master_admin', 'employee']],
    ['route' => 'employees', 'label' => 'Employees', 'icon' => 'employees', 'roles' => ['master_admin']],
    ['route' => 'assignments', 'label' => 'Assignments', 'icon' => 'assignments', 'roles' => ['master_admin']],
    ['route' => 'posts', 'label' => 'All Posts', 'icon' => 'posts', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'analytics', 'label' => 'Analytics', 'icon' => 'trend', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'reports', 'label' => 'Reports', 'icon' => 'activity', 'roles' => ['master_admin', 'employee']],
    ['route' => 'approvals', 'label' => 'Approvals', 'icon' => 'approvals', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'artwork', 'label' => 'Artwork Library', 'icon' => 'artwork', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'notifications', 'roles' => ['master_admin', 'employee', 'client']],
    ['route' => 'activity', 'label' => 'Activity Log', 'icon' => 'activity', 'roles' => ['master_admin', 'employee', 'client']],
    [
        'route' => 'settings',
        'label' => 'Settings',
        'icon' => 'settings',
        'roles' => ['master_admin'],
        'children' => [
            ['route' => 'integrations', 'label' => 'Integrations', 'roles' => ['master_admin']],
        ],
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? $config['app']['name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($logoUrl) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/public/assets/css/app.css?v=<?= (int) $cssVersion ?>">
</head>
<body class="<?= $user ? 'has-shell' : 'login-view' ?>" data-route="<?= htmlspecialchars((string) $currentRoute) ?>">
<?php if ($user): ?>
<div class="app-shell" data-shell>
    <aside class="sidebar" data-sidebar>
        <div class="sidebar-accent" aria-hidden="true"></div>
        <div class="sidebar-top">
            <div class="sidebar-brand-row">
                <a class="sidebar-brand" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=dashboard">
                    <span class="sidebar-brand-mark sidebar-brand-logo">
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="G2 Group">
                    </span>
                    <span class="sidebar-brand-copy">
                        <strong>G2 Social Calendar</strong>
                        <small>Agency Workspace</small>
                    </span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($navItems as $navItem): ?>
                    <?php if (!in_array($user['role_name'], $navItem['roles'], true)) { continue; } ?>
                    <?php
                    $isChildActive = false;
                    foreach (($navItem['children'] ?? []) as $childItem) {
                        if ($currentRoute === $childItem['route'] || str_starts_with((string) $currentRoute, $childItem['route'] . '.')) {
                            $isChildActive = true;
                            break;
                        }
                    }
                    $isNavActive = $currentRoute === $navItem['route'] || str_starts_with((string) $currentRoute, $navItem['route'] . '.');
                    ?>
                    <a class="nav-item <?= $isNavActive || $isChildActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=<?= htmlspecialchars($navItem['route']) ?>">
                        <span class="nav-icon"><?= Ui::icon($navItem['icon']) ?></span>
                        <span class="nav-label"><?= htmlspecialchars($navItem['label']) ?></span>
                    </a>
                    <?php if (!empty($navItem['children'])): ?>
                        <div class="nav-submenu">
                            <?php foreach ($navItem['children'] as $childItem): ?>
                                <?php if (!in_array($user['role_name'], $childItem['roles'], true)) { continue; } ?>
                                <a class="nav-subitem <?= $currentRoute === $childItem['route'] ? 'is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=<?= htmlspecialchars($childItem['route']) ?>">
                                    <span class="nav-subitem-label"><?= htmlspecialchars($childItem['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="sidebar-profile">
            <a class="sidebar-profile-link" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=profile">
                <div class="avatar avatar-lg"><?= htmlspecialchars(Ui::initials($user['name'])) ?></div>
                <div>
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                    <small><?= htmlspecialchars(Ui::roleLabel($user['role_name'])) ?></small>
                </div>
            </a>
            <a class="logout-link" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=logout">Logout</a>
        </div>
    </aside>

    <div class="shell-main">
        <header class="topbar">
            <div class="topbar-accent" aria-hidden="true"></div>
            <button class="icon-btn topbar-sidebar-toggle" type="button" data-sidebar-toggle aria-label="Hide sidebar">
                <?= Ui::icon('menu') ?>
            </button>
            <div class="topbar-search">
                <span class="input-icon"><?= Ui::icon('search') ?></span>
                <input type="text" placeholder="Search posts, clients, campaigns...">
            </div>

            <div class="topbar-right">
                <a class="icon-btn notification-btn" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=notifications" aria-label="Notifications">
                    <?= Ui::icon('bell') ?>
                    <?php if (($shellData['unread_notifications'] ?? 0) > 0): ?>
                        <span class="notification-count"><?= (int) $shellData['unread_notifications'] ?></span>
                    <?php endif; ?>
                </a>
                <a class="btn btn-secondary topbar-logout" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=logout">Logout</a>
                <a class="topbar-user" href="<?= htmlspecialchars($baseUrl) ?>/index.php?route=profile">
                    <div class="avatar"><?= htmlspecialchars(Ui::initials($user['name'])) ?></div>
                    <div class="topbar-user-copy">
                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                        <small><?= htmlspecialchars(Ui::roleLabel($user['role_name'])) ?></small>
                    </div>
                    <span class="topbar-chevron"><?= Ui::icon('chevron') ?></span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <?php if (!empty($flash['success'])): ?>
                <div class="flash flash-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="flash flash-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>
<?php endif; ?>
