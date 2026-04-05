<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = count($notifications) . ' recent notifications and workflow updates.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="card">
    <div class="notification-list large-list">
        <?php foreach ($notifications as $notification): ?>
            <a class="notification-item is-clickable" href="<?= htmlspecialchars($config['app']['base_url'] . '/' . ltrim($notification['detail_url'], '/')) ?>">
                <div>
                    <strong><?= htmlspecialchars($notification['subject']) ?></strong>
                    <p><?= htmlspecialchars($notification['body']) ?></p>
                </div>
                <div class="notification-meta">
                    <?php if (!(int) $notification['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                    <small><?= htmlspecialchars($notification['created_at']) ?></small>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
