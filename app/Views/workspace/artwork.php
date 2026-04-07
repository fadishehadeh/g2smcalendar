<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = count($files) . ' artwork files and versions available for review or download.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="toolbar-card" data-page-skeleton>
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid" data-inline-validate>
        <input type="hidden" name="route" value="artwork">
        <div class="input-with-icon grow">
            <span class="input-icon"><?= Ui::icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Search artwork, post title, client...">
        </div>
    </form>
</section>
<section class="table-card" data-page-skeleton>
    <table class="data-table">
        <thead>
            <tr><th>Artwork</th><th>Client</th><th>File Type</th><th>Version</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
                <tr data-row-link="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.item&item_id=<?= (int) $file['calendar_item_id'] ?>">
                    <td data-label="Artwork">
                        <div class="post-cell">
                            <div class="post-thumb post-thumb-image">
                                <?php if (Ui::mediaKind($file['mime_type']) === 'video'): ?>
                                    <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $file['id'] ?>" muted playsinline preload="metadata"></video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $file['id'] ?>" alt="<?= htmlspecialchars($file['title']) ?>">
                                <?php endif; ?>
                            </div>
                            <div><strong><?= htmlspecialchars($file['title']) ?></strong><small><?= htmlspecialchars($file['original_name']) ?></small></div>
                        </div>
                    </td>
                    <td data-label="Client"><?= htmlspecialchars($file['company_name']) ?></td>
                    <td data-label="File Type"><?= htmlspecialchars($file['mime_type']) ?></td>
                    <td data-label="Version">v<?= (int) $file['version_number'] ?></td>
                    <td data-label="Status"><span class="status-badge <?= Ui::statusClass($file['status']) ?>"><?= htmlspecialchars($file['status']) ?></span></td>
                    <td data-label="Actions" class="table-actions">
                        <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $file['id'] ?>" target="_blank">View</a>
                        <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=download.file&file_id=<?= (int) $file['id'] ?>">Download</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
