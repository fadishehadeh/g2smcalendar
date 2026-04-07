<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$subtitle = 'Performance metrics, platform comparisons, and per-post analytics for ' . $overview['range']['label'];
require dirname(__DIR__) . '/partials/page-header.php';

$totals = $overview['totals'];
$comparison = $overview['comparison'];
$platforms = $overview['platforms'];

$maxReach = 1;
foreach ($platforms as $platformRow) {
    $maxReach = max($maxReach, (int) $platformRow['reach']);
}
?>

<section class="toolbar-card" data-page-skeleton>
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid" data-inline-validate>
        <input type="hidden" name="route" value="analytics">
        <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= (int) $filters['month'] ?>"></label>
        <label><span>Year</span><input type="number" name="year" min="2024" max="2035" value="<?= (int) $filters['year'] ?>"></label>
        <label>
            <span>Client</span>
            <select name="client_id">
                <option value="">All Clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id'] ?>" <?= (string) $filters['client_id'] === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Platform</span>
            <select name="platform">
                <option value="">All Platforms</option>
                <?php foreach (\App\Models\CalendarItem::PLATFORMS as $platform): ?>
                    <option value="<?= htmlspecialchars($platform) ?>" <?= (string) $filters['platform'] === $platform ? 'selected' : '' ?>><?= htmlspecialchars($platform) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-primary" type="submit"><span class="btn-icon"><?= Ui::icon('filter') ?></span>Apply</button>
    </form>
</section>

<section class="kpi-grid analytics-kpis" data-page-skeleton>
    <article class="kpi-card">
        <div class="kpi-top"><span class="icon-swatch tone-red"><?= Ui::icon('trend') ?></span></div>
        <strong><?= number_format((int) ($totals['reach'] ?? 0)) ?></strong>
        <span>Total Reach</span>
    </article>
    <article class="kpi-card">
        <div class="kpi-top"><span class="icon-swatch tone-blue"><?= Ui::icon('approvals') ?></span></div>
        <strong><?= number_format((int) ($totals['engagement'] ?? 0)) ?></strong>
        <span>Total Engagement</span>
    </article>
    <article class="kpi-card">
        <div class="kpi-top"><span class="icon-swatch tone-amber"><?= Ui::icon('download') ?></span></div>
        <strong><?= number_format((int) ($totals['clicks'] ?? 0)) ?></strong>
        <span>Total Clicks</span>
    </article>
    <article class="kpi-card">
        <div class="kpi-top"><span class="icon-swatch tone-green"><?= Ui::icon('posts') ?></span></div>
        <strong><?= number_format((int) ($totals['posts'] ?? 0)) ?></strong>
        <span>Tracked Posts</span>
    </article>
</section>

<section class="dashboard-grid analytics-grid" data-page-skeleton>
    <article class="card">
        <div class="card-head">
            <div>
                <h3>Month Comparison</h3>
                <p><?= htmlspecialchars($overview['range']['label']) ?> versus <?= htmlspecialchars($overview['range']['compare_label']) ?>.</p>
            </div>
        </div>
        <div class="detail-stats">
            <div><span>Reach Delta</span><strong><?= ($comparison['reach_delta'] >= 0 ? '+' : '') . number_format((int) $comparison['reach_delta']) ?></strong></div>
            <div><span>Engagement Delta</span><strong><?= ($comparison['engagement_delta'] >= 0 ? '+' : '') . number_format((int) $comparison['engagement_delta']) ?></strong></div>
            <div><span>Clicks Delta</span><strong><?= ($comparison['clicks_delta'] >= 0 ? '+' : '') . number_format((int) $comparison['clicks_delta']) ?></strong></div>
        </div>
    </article>

    <article class="card">
        <div class="card-head">
            <div>
                <h3>Platform Performance</h3>
                <p>Reach comparison across selected channels.</p>
            </div>
        </div>
        <div class="analytics-bars">
            <?php foreach ($platforms as $platformRow): ?>
                <?php $width = max(6, (int) round(((int) $platformRow['reach'] / $maxReach) * 100)); ?>
                <div class="analytics-bar-row">
                    <div class="analytics-bar-label">
                        <span><?= Ui::platformIcon($platformRow['platform']) ?></span>
                        <strong><?= htmlspecialchars($platformRow['platform']) ?></strong>
                    </div>
                    <div class="analytics-bar-track"><span style="width: <?= $width ?>%"></span></div>
                    <small><?= number_format((int) $platformRow['reach']) ?> reach</small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="table-card" data-page-skeleton>
    <table class="data-table">
        <thead>
            <tr>
                <th>Post</th>
                <th>Client</th>
                <th>Platform</th>
                <th>Reach</th>
                <th>Engagement</th>
                <th>Clicks</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr data-row-link="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.item&item_id=<?= (int) $post['id'] ?>">
                    <td data-label="Post">
                        <strong><?= htmlspecialchars($post['title']) ?></strong>
                        <small><?= htmlspecialchars($post['scheduled_date']) ?></small>
                    </td>
                    <td data-label="Client"><?= htmlspecialchars($post['company_name']) ?></td>
                    <td data-label="Platform"><?= Ui::platformIcon($post['platform']) ?></td>
                    <td data-label="Reach"><?= number_format((int) $post['reach']) ?></td>
                    <td data-label="Engagement"><?= number_format((int) $post['engagement']) ?></td>
                    <td data-label="Clicks"><?= number_format((int) $post['clicks']) ?></td>
                    <td data-label="Status"><span class="status-badge <?= Ui::statusClass($post['status']) ?>"><?= htmlspecialchars($post['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
