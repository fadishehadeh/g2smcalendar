<?php
$pageActions = $pageActions ?? [];
$pageMeta = $pageMeta ?? [];
?>
<section class="page-header">
    <div>
        <h1><?= htmlspecialchars($title ?? '') ?></h1>
        <?php if (!empty($subtitle ?? '')): ?>
            <p><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
        <?php if ($pageMeta !== []): ?>
            <div class="page-meta">
                <?php foreach ($pageMeta as $meta): ?>
                    <span><?= htmlspecialchars($meta) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pageActions !== []): ?>
        <div class="page-actions">
            <?php foreach ($pageActions as $action): ?>
                <a class="btn <?= htmlspecialchars($action['class'] ?? 'btn-secondary') ?>" href="<?= htmlspecialchars($action['href']) ?>">
                    <?php if (!empty($action['icon'])): ?>
                        <span class="btn-icon"><?= \App\Core\Ui::icon($action['icon']) ?></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
