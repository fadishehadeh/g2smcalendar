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
    <?php if ($items === []): ?>
        <?php
        $emptyTitle = 'No approvals are waiting right now';
        $emptyMessage = 'When artwork is ready, use the Submit for Approval wizard from a post detail page. It checks missing fields before anything reaches the client.';
        $emptyActions = [
            ['label' => 'Open Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary'],
        ];
        require dirname(__DIR__) . '/partials/empty-state.php';
        ?>
    <?php endif; ?>
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
                <span class="status-badge <?= Ui::statusClass('Pending Approval') ?>">Pending Approval</span>
            </div>
            <div class="approval-actions">
                <?php if ($canApprove): ?>
                    <a class="btn btn-primary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=approval.review&item_id=<?= (int) $item['id'] ?>">Guided Review</a>
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
