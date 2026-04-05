<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = count($campaigns) . ' campaign group' . (count($campaigns) === 1 ? '' : 's') . ' tracked across content, approvals, and analytics.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="toolbar-card">
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid">
        <input type="hidden" name="route" value="campaigns">
        <label>
            <span>Client</span>
            <select name="client_id">
                <option value="">All Clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id'] ?>" <?= (string) $filters['client_id'] === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Apply</button>
    </form>
</section>

<section class="entity-grid">
    <?php foreach ($campaigns as $campaign): ?>
        <article class="entity-card">
            <div class="entity-card-head">
                <div>
                    <h3><?= htmlspecialchars($campaign['campaign']) ?></h3>
                    <p><?= htmlspecialchars($campaign['company_name']) ?></p>
                </div>
                <span class="status-badge <?= Ui::statusClass(((int) $campaign['pending_count']) > 0 ? 'Pending Approval' : 'Approved') ?>">
                    <?= (int) $campaign['pending_count'] > 0 ? 'Active Review' : 'Tracked' ?>
                </span>
            </div>
            <dl class="entity-meta">
                <div><dt>Posts</dt><dd><?= number_format((int) $campaign['posts_count']) ?></dd></div>
                <div><dt>Reach</dt><dd><?= number_format((int) $campaign['reach']) ?></dd></div>
                <div><dt>Engagement</dt><dd><?= number_format((int) $campaign['engagement']) ?></dd></div>
            </dl>
            <div class="settings-list">
                <div><strong>Window</strong><span><?= htmlspecialchars((string) $campaign['first_post_date']) ?> to <?= htmlspecialchars((string) $campaign['last_post_date']) ?></span></div>
                <div><strong>Pending Approval</strong><span><?= number_format((int) $campaign['pending_count']) ?></span></div>
                <div><strong>Approved</strong><span><?= number_format((int) $campaign['approved_count']) ?></span></div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
