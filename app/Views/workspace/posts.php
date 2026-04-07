<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$isTrashView = ($view ?? 'active') === 'trash';
$canManagePosts = in_array($authUser['role_name'] ?? '', ['master_admin', 'employee'], true);
$subtitle = count($posts) . ($isTrashView
    ? ' post' . (count($posts) === 1 ? '' : 's') . ' currently stored in trash.'
    : ' posts across all accessible calendars.');
$pageActions = [
    ['label' => 'Active Posts', 'href' => $config['app']['base_url'] . '/index.php?route=posts', 'class' => $isTrashView ? 'btn-secondary' : 'btn-primary', 'icon' => 'posts'],
    ['label' => 'Trash', 'href' => $config['app']['base_url'] . '/index.php?route=posts&view=trash', 'class' => $isTrashView ? 'btn-primary' : 'btn-secondary', 'icon' => 'activity'],
];
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="toolbar-card" data-page-skeleton>
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid" data-inline-validate>
        <input type="hidden" name="route" value="posts">
        <input type="hidden" name="view" value="<?= htmlspecialchars((string) ($view ?? 'active')) ?>">
        <div class="input-with-icon grow">
            <span class="input-icon"><?= Ui::icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Search post title, client, campaign...">
        </div>
    </form>
</section>
<section class="table-card" data-page-skeleton>
    <?php if ($canManagePosts): ?>
        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=posts.bulk" class="bulk-toolbar" data-post-bulk-form>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="view" value="<?= htmlspecialchars((string) ($view ?? 'active')) ?>">
            <div class="page-actions">
                <button class="btn btn-secondary" type="submit" name="bulk_action" value="edit" data-bulk-edit>Edit Selected</button>
                <?php if ($isTrashView): ?>
                    <button class="btn btn-primary" type="submit" name="bulk_action" value="restore" data-bulk-action>Restore from Trash</button>
                <?php else: ?>
                    <button class="btn btn-danger-soft" type="submit" name="bulk_action" value="trash" data-bulk-action>Move to Trash</button>
                <?php endif; ?>
                <span class="muted" data-selection-count>0 selected</span>
            </div>
            <table class="data-table">
        <?php else: ?>
            <table class="data-table">
        <?php endif; ?>
        <thead>
            <tr>
                <?php if ($canManagePosts): ?>
                    <th class="check-col"><input type="checkbox" data-select-all></th>
                <?php endif; ?>
                <th>Post</th><th>Client</th><th>Platform</th><th>Date</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr data-row-link="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.item&item_id=<?= (int) $post['id'] ?>">
                    <?php if ($canManagePosts): ?>
                        <td class="check-col" data-label="Select"><input type="checkbox" name="selected_ids[]" value="<?= (int) $post['id'] ?>" data-select-row></td>
                    <?php endif; ?>
                    <td data-label="Post">
                        <a class="post-link" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.item&item_id=<?= (int) $post['id'] ?>">
                            <div class="post-cell">
                                <div class="post-thumb post-thumb-image">
                                    <?php if (!empty($post['preview_file_id'])): ?>
                                        <?php if (Ui::mediaKind($post['preview_mime_type'] ?? '') === 'video'): ?>
                                            <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $post['preview_file_id'] ?>" muted playsinline preload="metadata"></video>
                                        <?php else: ?>
                                            <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $post['preview_file_id'] ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars(Ui::initials($post['platform'])) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($post['title']) ?></strong>
                                    <small><?= htmlspecialchars($post['post_type']) ?></small>
                                </div>
                            </div>
                        </a>
                    </td>
                    <td data-label="Client"><?= htmlspecialchars($post['company_name']) ?></td>
                    <td data-label="Platform"><?= Ui::platformIcon($post['platform']) ?></td>
                    <td data-label="Date"><?= htmlspecialchars($post['scheduled_date']) ?></td>
                    <td data-label="Status"><span class="status-badge <?= Ui::statusClass($post['status']) ?>"><?= htmlspecialchars($post['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($canManagePosts): ?>
        </form>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
