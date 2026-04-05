<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$viewMode = ($monthData['view'] ?? 'monthly') === 'weekly' ? 'weekly' : 'monthly';
$rangeTitle = $viewMode === 'weekly'
    ? date('M j', strtotime($monthData['from'])) . ' - ' . date('M j, Y', strtotime($monthData['to']))
    : date('F Y', strtotime(sprintf('%04d-%02d-01', $monthData['year'], $monthData['month'])));

$title = 'Content Calendar';
$subtitle = $rangeTitle . ' - ' . $monthData['total_posts'] . ' posts';
$pageActions = [
    ['label' => 'Create Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=wizard', 'class' => 'btn-secondary', 'icon' => 'plus'],
    ['label' => 'Bulk Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard', 'class' => 'btn-primary', 'icon' => 'calendar'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$roleName = $authUser['role_name'] ?? '';
$isLockedClientFilter = $roleName !== 'master_admin' && count($clients) === 1;
$lockedClient = $isLockedClientFilter ? $clients[0] : null;
$filterStatuses = $statuses;
$attentionStatuses = ['Pending Approval', 'Rejected', 'Revision Requested'];

if ($roleName === 'client') {
    $filterStatuses = array_values(array_filter(
        $statuses,
        static fn (string $status): bool => in_array($status, [
            'Pending Approval',
            'Approved',
            'Rejected',
            'Revision Requested',
            'Ready for Download',
            'Downloaded',
        ], true)
    ));
}

$modalItems = [];
$monthMap = [];
$weekMap = [];
foreach ($items as $item) {
    $day = (int) date('j', strtotime($item['scheduled_date']));
    $monthMap[$day][] = $item;
    $weekMap[$item['scheduled_date']][] = $item;
    $modalItems[$item['id']] = [
        'id' => (int) $item['id'],
        'title' => $item['title'],
        'client' => $item['company_name'],
        'platform' => $item['platform'],
        'status' => $item['status'],
        'post_type' => $item['post_type'],
        'date' => $item['scheduled_date'],
        'caption' => $item['caption_en'] ?: 'No caption added yet.',
        'preview' => !empty($item['preview_file_id']) ? ($config['app']['base_url'] . '/index.php?route=preview.file&file_id=' . (int) $item['preview_file_id']) : '',
        'previewKind' => Ui::mediaKind($item['preview_mime_type'] ?? ''),
        'detailsUrl' => $config['app']['base_url'] . '/index.php?route=calendar.item&item_id=' . (int) $item['id'],
        'statusClass' => Ui::statusClass($item['status']),
        'platformMarkup' => Ui::platformIcon($item['platform']),
    ];
}

$month = (int) $monthData['month'];
$year = (int) $monthData['year'];
$firstDay = (int) date('N', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$daysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

$prevAnchor = $viewMode === 'weekly'
    ? date('Y-m-d', strtotime($monthData['from'] . ' -7 days'))
    : date('Y-m-d', strtotime(sprintf('%04d-%02d-01 -1 month', $year, $month)));
$nextAnchor = $viewMode === 'weekly'
    ? date('Y-m-d', strtotime($monthData['from'] . ' +7 days'))
    : date('Y-m-d', strtotime(sprintf('%04d-%02d-01 +1 month', $year, $month)));

$calendarBaseQuery = [
    'route' => 'calendar',
    'client_id' => $filters['client_id'],
    'status' => $filters['status'],
    'platform' => $filters['platform'],
    'search' => $filters['search'],
];
?>

<section class="toolbar-card filter-panel" data-collapsible-filter>
    <div class="card-head filter-panel-head">
        <div>
            <h3>Filters</h3>
            <p>Switch between monthly and weekly planning, then narrow by client, platform, and status.</p>
        </div>
        <button class="btn btn-secondary" type="button" data-filter-toggle>
            <span class="btn-icon"><?= Ui::icon('filter') ?></span>
            <span data-filter-toggle-label>Hide Filters</span>
        </button>
    </div>
    <div class="filter-panel-body" data-filter-body>
        <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid calendar-toolbar">
            <input type="hidden" name="route" value="calendar">
            <input type="hidden" name="anchor_date" value="<?= htmlspecialchars((string) ($monthData['anchor_date'] ?: sprintf('%04d-%02d-01', $month, $year))) ?>">
            <div class="input-with-icon grow">
                <span class="input-icon"><?= Ui::icon('search') ?></span>
                <input type="text" name="search" value="<?= htmlspecialchars((string) $filters['search']) ?>" placeholder="Search posts, clients, campaigns...">
            </div>
            <select name="view">
                <option value="monthly" <?= $viewMode === 'monthly' ? 'selected' : '' ?>>Monthly View</option>
                <option value="weekly" <?= $viewMode === 'weekly' ? 'selected' : '' ?>>Weekly View</option>
            </select>
            <?php if ($isLockedClientFilter && $lockedClient): ?>
                <input type="hidden" name="client_id" value="<?= (int) $lockedClient['id'] ?>">
                <select disabled>
                    <option selected><?= htmlspecialchars($lockedClient['company_name']) ?></option>
                </select>
            <?php else: ?>
                <select name="client_id">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= (string) $filters['client_id'] === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <select name="platform">
                <option value="">All Platforms</option>
                <?php foreach ($platforms as $platform): ?>
                    <option value="<?= htmlspecialchars($platform) ?>" <?= $filters['platform'] === $platform ? 'selected' : '' ?>><?= htmlspecialchars($platform) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($filterStatuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-secondary" type="submit"><span class="btn-icon"><?= Ui::icon('filter') ?></span>Apply Filters</button>
        </form>
    </div>
</section>

<section class="calendar-page calendar-page-full">
    <div class="calendar-card">
        <div class="calendar-head">
            <div class="view-toggle">
                <a class="toggle-btn <?= $viewMode === 'monthly' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?<?= htmlspecialchars(http_build_query($calendarBaseQuery + ['view' => 'monthly', 'month' => $month, 'year' => $year])) ?>">Monthly</a>
                <a class="toggle-btn <?= $viewMode === 'weekly' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?<?= htmlspecialchars(http_build_query($calendarBaseQuery + ['view' => 'weekly', 'anchor_date' => $monthData['anchor_date'] ?: date('Y-m-d')])) ?>">Weekly</a>
                <a class="toggle-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=posts">List</a>
                <a class="toggle-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=approvals">Approval</a>
            </div>
            <div class="calendar-nav">
                <a class="icon-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?<?= htmlspecialchars(http_build_query($calendarBaseQuery + ['view' => $viewMode, 'anchor_date' => $prevAnchor, 'month' => date('n', strtotime($prevAnchor)), 'year' => date('Y', strtotime($prevAnchor))])) ?>"><?= Ui::icon('left') ?></a>
                <h2><?= htmlspecialchars($rangeTitle) ?></h2>
                <a class="icon-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?<?= htmlspecialchars(http_build_query($calendarBaseQuery + ['view' => $viewMode, 'anchor_date' => $nextAnchor, 'month' => date('n', strtotime($nextAnchor)), 'year' => date('Y', strtotime($nextAnchor))])) ?>"><?= Ui::icon('right') ?></a>
            </div>
        </div>

        <?php if ($viewMode === 'monthly'): ?>
            <div class="calendar-grid calendar-grid-labels">
                <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>
            <div class="calendar-grid calendar-grid-month">
                <?php for ($blank = 1; $blank < $firstDay; $blank++): ?>
                    <div class="calendar-cell is-empty"></div>
                <?php endfor; ?>
                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php $dayItems = $monthMap[$day] ?? []; ?>
                    <?php $hasAttention = count(array_filter($dayItems, static fn (array $dayItem): bool => in_array((string) $dayItem['status'], $attentionStatuses, true))) > 0; ?>
                    <div class="calendar-cell <?= $day === (int) date('j') && $month === (int) date('n') && $year === (int) date('Y') ? 'is-today' : '' ?> <?= $hasAttention ? 'has-attention' : '' ?>">
                        <div class="calendar-cell-head">
                            <strong><?= $day ?></strong>
                        </div>
                        <div class="calendar-cell-items">
                            <?php foreach (array_slice($dayItems, 0, 4) as $dayItem): ?>
                                <button class="calendar-event-row calendar-event-button <?= in_array((string) $dayItem['status'], $attentionStatuses, true) ? 'needs-attention' : '' ?>" type="button" data-item-id="<?= (int) $dayItem['id'] ?>" data-item-source="calendar">
                                    <?= Ui::platformIcon($dayItem['platform']) ?>
                                    <span class="event-client"><?= htmlspecialchars($dayItem['company_name']) ?></span>
                                    <span class="event-dot <?= Ui::statusClass($dayItem['status']) ?>"></span>
                                </button>
                            <?php endforeach; ?>
                            <?php if (count($dayItems) > 4): ?>
                                <span class="more-link">+<?= count($dayItems) - 4 ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <div class="week-grid">
                <?php foreach ($monthData['week_dates'] as $date): ?>
                    <?php $dayItems = $weekMap[$date] ?? []; ?>
                    <?php $hasAttention = count(array_filter($dayItems, static fn (array $dayItem): bool => in_array((string) $dayItem['status'], $attentionStatuses, true))) > 0; ?>
                    <article class="week-day-card <?= $hasAttention ? 'has-attention' : '' ?>">
                        <div class="week-day-head">
                            <small><?= htmlspecialchars(date('D', strtotime($date))) ?></small>
                            <strong><?= htmlspecialchars(date('M j', strtotime($date))) ?></strong>
                        </div>
                        <div class="week-day-items">
                            <?php if ($dayItems === []): ?>
                                <p class="muted">No scheduled posts.</p>
                            <?php endif; ?>
                            <?php foreach ($dayItems as $dayItem): ?>
                                <button class="calendar-event-row calendar-event-button <?= in_array((string) $dayItem['status'], $attentionStatuses, true) ? 'needs-attention' : '' ?>" type="button" data-item-id="<?= (int) $dayItem['id'] ?>" data-item-source="calendar">
                                    <?= Ui::platformIcon($dayItem['platform']) ?>
                                    <span class="event-client"><?= htmlspecialchars($dayItem['title']) ?></span>
                                    <span class="event-dot <?= Ui::statusClass($dayItem['status']) ?>"></span>
                                </button>
                                <small class="week-day-client"><?= htmlspecialchars($dayItem['company_name']) ?></small>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script type="application/json" data-item-store="calendar"><?= json_encode($modalItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/item-modal.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
