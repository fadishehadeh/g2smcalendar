<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$roleName = $authUser['role_name'] ?? '';
$isClient = $roleName === 'client';
$isAdmin = $roleName === 'master_admin';

$pageActions = [
    ['label' => 'View Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary', 'icon' => 'calendar'],
    ['label' => 'New Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=wizard.calendar', 'class' => 'btn-primary', 'icon' => 'plus'],
];

if ($isClient) {
    $pageActions = array_values(array_filter(
        $pageActions,
        static fn (array $action): bool => ($action['label'] ?? '') !== 'New Calendar'
    ));
}

$subtitle = 'Operational overview for calendars, approvals, downloads, and team activity.';
require dirname(__DIR__) . '/partials/page-header.php';

$kpis = [
    ['label' => 'Total Clients', 'value' => $stats['clients'], 'icon' => 'clients', 'tone' => 'red', 'href' => $config['app']['base_url'] . '/index.php?route=clients'],
    ['label' => 'Total Employees', 'value' => $stats['employees'], 'icon' => 'employees', 'tone' => 'blue', 'href' => $config['app']['base_url'] . '/index.php?route=employees'],
    ['label' => 'Total Calendars', 'value' => $stats['calendars'], 'icon' => 'calendar', 'tone' => 'amber', 'href' => $config['app']['base_url'] . '/index.php?route=calendar'],
    ['label' => 'Pending Approvals', 'value' => $stats['pending_approvals'], 'icon' => 'approvals', 'tone' => 'red', 'href' => $config['app']['base_url'] . '/index.php?route=approvals'],
    ['label' => 'Approved', 'value' => $stats['approved'], 'icon' => 'approvals', 'tone' => 'green', 'href' => $config['app']['base_url'] . '/index.php?route=posts'],
    ['label' => 'Rejected', 'value' => $stats['rejected'], 'icon' => 'approvals', 'tone' => 'rose', 'href' => $config['app']['base_url'] . '/index.php?route=posts'],
    ['label' => 'Downloads', 'value' => $stats['downloads'], 'icon' => 'download', 'tone' => 'violet', 'href' => $config['app']['base_url'] . '/index.php?route=artwork'],
    ['label' => 'Total Posts', 'value' => $stats['total_posts'], 'icon' => 'posts', 'tone' => 'slate', 'href' => $config['app']['base_url'] . '/index.php?route=posts'],
];

if (!$isAdmin) {
    $kpis = array_values(array_filter(
        $kpis,
        static fn (array $kpi): bool => !in_array($kpi['label'], ['Total Clients', 'Total Employees', 'Total Calendars'], true)
    ));
}

$quickItems = [];
foreach ($pendingItems as $item) {
    $quickItems[$item['id']] = [
        'id' => (int) $item['id'],
        'title' => $item['title'],
        'client' => $item['company_name'],
        'platform' => $item['platform'],
        'status' => $item['status'],
        'postType' => $item['post_type'],
        'date' => $item['scheduled_date'],
        'caption' => $item['caption_en'] ?: 'No caption added yet.',
        'preview' => !empty($item['preview_file_id']) ? ($config['app']['base_url'] . '/index.php?route=preview.file&file_id=' . (int) $item['preview_file_id']) : '',
        'previewKind' => Ui::mediaKind($item['preview_mime_type'] ?? ''),
        'detailsUrl' => $config['app']['base_url'] . '/index.php?route=calendar.item&item_id=' . (int) $item['id'],
        'statusClass' => Ui::statusClass($item['status']),
    ];
}
?>

<section class="kpi-grid">
    <?php foreach ($kpis as $kpi): ?>
        <a class="kpi-card is-clickable" href="<?= htmlspecialchars((string) ($kpi['href'] ?? '#')) ?>">
            <div class="kpi-top">
                <span class="icon-swatch tone-<?= htmlspecialchars($kpi['tone']) ?>"><?= Ui::icon($kpi['icon']) ?></span>
                <span class="trend-icon"><?= Ui::icon('trend') ?></span>
            </div>
            <strong><?= number_format((int) $kpi['value']) ?></strong>
            <span><?= htmlspecialchars($kpi['label']) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<?php if (!$isClient): ?>
<section class="dashboard-quick-actions" style="margin-bottom:20px;">
    <?php if ($isAdmin): ?>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.client">
        <strong>Add Client</strong>
        <p>Guided setup for contacts, ownership, and workflow preferences.</p>
    </a>
    <?php endif; ?>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.calendar">
        <strong>Create Calendar</strong>
        <p>Set up the month first, then move into post creation with recommended next steps.</p>
    </a>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard">
        <strong>Launch Bulk Wizard</strong>
        <p>Build many posts using dates, repeated weekly patterns, and shared defaults.</p>
    </a>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=approvals">
        <strong>Review Approvals</strong>
        <p>Track items waiting on client feedback or open guided review flows.</p>
    </a>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=reports">
        <strong>Generate Report</strong>
        <p>Create a report from analytics, approvals, downloads, and activity.</p>
    </a>
    <?php if ($isAdmin): ?>
    <a class="quick-action-card" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations">
        <strong>Integration Setup</strong>
        <p>Configure AI and analytics providers with guided setup screens.</p>
    </a>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="dashboard-grid">
    <article class="card">
        <div class="card-head">
            <div>
                <h3>Pending Actions</h3>
                <p>Items that currently need attention in the approval workflow.</p>
            </div>
        </div>
        <div class="action-list">
            <?php if ($pendingItems === []): ?>
                <?php
                $emptyTitle = 'No urgent actions are waiting';
                $emptyMessage = 'This area will show pending approvals and revision items. For now, the next useful step is to create a calendar or generate posts.';
                $emptyActions = [
                    ['label' => 'Create Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=wizard.calendar', 'class' => 'btn-secondary'],
                    ['label' => 'Launch Bulk Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard', 'class' => 'btn-primary'],
                ];
                require dirname(__DIR__) . '/partials/empty-state.php';
                ?>
            <?php else: ?>
                <?php foreach ($pendingItems as $item): ?>
                    <button class="action-card" type="button" data-item-id="<?= (int) $item['id'] ?>" data-item-source="quick">
                        <div class="action-card-media">
                            <?php if (!empty($item['preview_file_id'])): ?>
                                <?php if (Ui::mediaKind($item['preview_mime_type'] ?? '') === 'video'): ?>
                                    <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $item['preview_file_id'] ?>" muted playsinline preload="metadata"></video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $item['preview_file_id'] ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="action-card-copy">
                            <div class="action-card-head">
                                <strong><?= htmlspecialchars($item['title']) ?></strong>
                                <span class="status-badge <?= Ui::statusClass($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span>
                            </div>
                            <p><?= htmlspecialchars($item['company_name']) ?> - <?= htmlspecialchars($item['scheduled_date']) ?> - <?= htmlspecialchars($item['platform']) ?></p>
                            <span class="text-link"><?= htmlspecialchars($item['action_label']) ?></span>
                        </div>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <?php if ($isAdmin): ?>
        <article class="card">
            <div class="card-head">
                <div>
                    <h3>Recent Activity</h3>
                    <p>Latest team and client actions across the workspace.</p>
                </div>
            </div>
            <div class="activity-list">
                <?php foreach ($activities as $activity): ?>
                    <a class="activity-item is-clickable" href="<?= htmlspecialchars($config['app']['base_url'] . '/' . ltrim($activity['detail_url'], '/')) ?>">
                        <div class="activity-user">
                            <span class="avatar"><?= htmlspecialchars(Ui::initials($activity['name'] ?? 'System')) ?></span>
                            <div>
                                <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))) ?></strong>
                                <p><?= htmlspecialchars(($activity['name'] ?? 'System') . ' - ' . ($activity['company_name'] ?? ($activity['item_title'] ?? 'Workspace update'))) ?></p>
                            </div>
                        </div>
                        <div class="activity-meta">
                            <?php if (!empty($activity['status'])): ?>
                                <span class="status-badge <?= Ui::statusClass($activity['status']) ?>"><?= htmlspecialchars($activity['status']) ?></span>
                            <?php endif; ?>
                            <small><?= htmlspecialchars($activity['created_at']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="card-head">
            <div>
                <h3>Notifications</h3>
                <p>Recent system alerts and approval activity.</p>
            </div>
        </div>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <a class="notification-item is-clickable" href="<?= htmlspecialchars($config['app']['base_url'] . '/' . ltrim($notification['detail_url'], '/')) ?>">
                    <div>
                        <strong><?= htmlspecialchars($notification['subject']) ?></strong>
                        <p><?= htmlspecialchars(($notification['company_name'] ?? 'G2 Workspace') . ' - ' . ($notification['item_title'] ?? $notification['type'])) ?></p>
                    </div>
                    <div class="notification-meta">
                        <?php if (!(int) $notification['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                        <small><?= htmlspecialchars($notification['created_at']) ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<script type="application/json" data-item-store="quick"><?= json_encode($quickItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/item-modal.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
