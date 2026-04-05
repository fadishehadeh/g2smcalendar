<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = ($approvalsSubtitle ?? 'Items pending client review.') . ' ' . count($items) . ' item' . (count($items) === 1 ? '' : 's') . '.';
require dirname(__DIR__) . '/partials/page-header.php';
$canApprove = in_array($authUser['role_name'] ?? '', ['master_admin', 'client'], true);

$quickItems = [];
foreach ($items as $item) {
    $quickItems[$item['id']] = [
        'id' => (int) $item['id'],
        'title' => $item['title'],
        'client' => $item['company_name'],
        'platform' => $item['platform'],
        'status' => $item['status'],
        'postType' => $item['post_type'],
        'date' => $item['scheduled_date'],
        'caption' => $item['caption_en'] ?: 'Review artwork, captions, and final delivery notes.',
        'preview' => !empty($item['preview_file_id']) ? ($config['app']['base_url'] . '/index.php?route=preview.file&file_id=' . (int) $item['preview_file_id']) : '',
        'previewKind' => Ui::mediaKind($item['preview_mime_type'] ?? ''),
        'detailsUrl' => $config['app']['base_url'] . '/index.php?route=calendar.item&item_id=' . (int) $item['id'],
        'statusClass' => Ui::statusClass($item['status']),
    ];
}
?>
<section class="approval-grid">
    <?php foreach ($items as $item): ?>
        <article class="approval-card">
            <button class="approval-preview approval-preview-image" type="button" data-item-id="<?= (int) $item['id'] ?>" data-item-source="quick">
                <?php if (!empty($item['preview_file_id'])): ?>
                    <?php if (Ui::mediaKind($item['preview_mime_type'] ?? '') === 'video'): ?>
                        <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $item['preview_file_id'] ?>" muted playsinline preload="metadata"></video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $item['preview_file_id'] ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    <?php endif; ?>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['company_name']) ?></span>
                <?php endif; ?>
            </button>
            <div class="approval-body">
                <div class="approval-meta">
                    <span><?= Ui::platformIcon($item['platform']) ?></span>
                    <small><?= htmlspecialchars($item['company_name']) ?></small>
                </div>
                <h3><?= htmlspecialchars($item['title']) ?></h3>
                <p><?= htmlspecialchars(substr((string) ($item['caption_en'] ?: 'Review artwork, captions, and final delivery notes.'), 0, 110)) ?></p>
                <span class="status-badge <?= Ui::statusClass('For Approval') ?>">For Approval</span>
            </div>
            <div class="approval-actions">
                <?php if ($canApprove): ?>
                    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.status">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="status" value="Approved">
                        <button class="btn btn-success-soft" type="submit">Approve</button>
                    </form>
                    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.status">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="status" value="Rejected">
                        <input type="hidden" name="comment" value="Needs revisions.">
                        <button class="btn btn-danger-soft" type="submit">Reject</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.item&item_id=<?= (int) $item['id'] ?>">Open Item</a>
                <?php endif; ?>
                <button class="icon-btn" type="button" data-item-id="<?= (int) $item['id'] ?>" data-item-source="quick"><?= Ui::icon('comment') ?></button>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<script type="application/json" data-item-store="quick"><?= json_encode($quickItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/item-modal.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
