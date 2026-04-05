<?php

$emptyTitle = $emptyTitle ?? 'Nothing here yet';
$emptyMessage = $emptyMessage ?? 'Start by creating your first record.';
$emptyActions = $emptyActions ?? [];
?>
<div class="empty-state-card">
    <div class="empty-state-icon"><?= \App\Core\Ui::icon('plus') ?></div>
    <div class="empty-state-copy">
        <h3><?= htmlspecialchars((string) $emptyTitle) ?></h3>
        <p><?= htmlspecialchars((string) $emptyMessage) ?></p>
    </div>
    <?php if ($emptyActions !== []): ?>
        <div class="page-actions">
            <?php foreach ($emptyActions as $action): ?>
                <a class="btn <?= htmlspecialchars((string) ($action['class'] ?? 'btn-secondary')) ?>" href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>">
                    <?= htmlspecialchars((string) ($action['label'] ?? 'Open')) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
