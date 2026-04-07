<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = count($rows) . ' recent system events and user actions.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="toolbar-card" data-page-skeleton>
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid" data-inline-validate>
        <input type="hidden" name="route" value="activity">
        <div class="input-with-icon grow">
            <span class="input-icon"><?= Ui::icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Search activity by user, action, item...">
        </div>
    </form>
</section>
<section class="table-card" data-page-skeleton>
    <table class="data-table">
        <thead>
            <tr><th>User</th><th>Action</th><th>Item</th><th>Status</th><th>Date/Time</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr data-row-link="<?= htmlspecialchars($config['app']['base_url'] . '/' . ltrim($row['detail_url'], '/')) ?>">
                    <td data-label="User"><div class="user-inline"><span class="avatar"><?= htmlspecialchars(Ui::initials($row['name'] ?? 'System')) ?></span><strong><?= htmlspecialchars($row['name'] ?? 'System') ?></strong></div></td>
                    <td data-label="Action"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['action']))) ?></td>
                    <td data-label="Item"><?= htmlspecialchars($row['item_title'] ?? $row['company_name'] ?? 'Workspace') ?></td>
                    <td data-label="Status"><?php if (!empty($row['status'])): ?><span class="status-badge <?= Ui::statusClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span><?php endif; ?></td>
                    <td data-label="Date/Time"><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
