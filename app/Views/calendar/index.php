<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$title = 'Content Calendar';
$subtitle = date('F Y', strtotime(sprintf('%04d-%02d-01', $monthData['year'], $monthData['month']))) . ' - ' . $monthData['total_posts'] . ' posts';
$pageActions = [
    ['label' => 'Create Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=wizard', 'class' => 'btn-secondary', 'icon' => 'plus'],
    ['label' => 'Bulk Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard', 'class' => 'btn-primary', 'icon' => 'calendar'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$roleName = $authUser['role_name'] ?? '';
$isLockedClientFilter = $roleName !== 'master_admin' && count($clients) === 1;
$lockedClient = $isLockedClientFilter ? $clients[0] : null;
$filterStatuses = $statuses;
$attentionStatuses = ['For Client Approval', 'Rejected', 'Revision Requested'];

if ($roleName === 'client') {
    $filterStatuses = array_values(array_filter(
        $statuses,
        static fn (string $status): bool => in_array($status, [
            'For Client Approval',
            'Approved',
            'Rejected',
            'Revision Requested',
            'Ready for Download',
            'Downloaded',
        ], true)
    ));
}

$calendarMap = [];
$modalItems = [];
foreach ($items as $item) {
    $day = (int) date('j', strtotime($item['scheduled_date']));
    $calendarMap[$day][] = $item;
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
        'previewKind' => \App\Core\Ui::mediaKind($item['preview_mime_type'] ?? ''),
        'detailsUrl' => $config['app']['base_url'] . '/index.php?route=calendar.item&item_id=' . (int) $item['id'],
        'statusClass' => Ui::statusClass($item['status']),
        'platformMarkup' => Ui::platformIcon($item['platform']),
    ];
}

$month = (int) $monthData['month'];
$year = (int) $monthData['year'];
$firstDay = (int) date('N', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$daysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$prevMonth = date('n', strtotime(sprintf('%04d-%02d-01 -1 month', $year, $month)));
$prevYear = date('Y', strtotime(sprintf('%04d-%02d-01 -1 month', $year, $month)));
$nextMonth = date('n', strtotime(sprintf('%04d-%02d-01 +1 month', $year, $month)));
$nextYear = date('Y', strtotime(sprintf('%04d-%02d-01 +1 month', $year, $month)));
?>

<section class="toolbar-card filter-panel" data-collapsible-filter>
    <div class="card-head filter-panel-head">
        <div>
            <h3>Filters</h3>
            <p>Search and narrow the calendar view by client and status.</p>
        </div>
        <button class="btn btn-secondary" type="button" data-filter-toggle>
            <span class="btn-icon"><?= Ui::icon('filter') ?></span>
            <span data-filter-toggle-label>Hide Filters</span>
        </button>
    </div>
    <div class="filter-panel-body" data-filter-body>
        <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid calendar-toolbar">
            <input type="hidden" name="route" value="calendar">
            <div class="input-with-icon grow">
                <span class="input-icon"><?= Ui::icon('search') ?></span>
                <input type="text" name="search" value="<?= htmlspecialchars((string) $filters['search']) ?>" placeholder="Search posts, clients, campaigns...">
            </div>
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
                <button class="toggle-btn is-active" type="button">Calendar</button>
                <a class="toggle-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=posts">List</a>
                <a class="toggle-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=approvals">Approval</a>
            </div>
            <div class="calendar-nav">
                <a class="icon-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>"><?= Ui::icon('left') ?></a>
                <h2><?= htmlspecialchars(date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $month)))) ?></h2>
                <a class="icon-btn" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>"><?= Ui::icon('right') ?></a>
            </div>
        </div>

        <div class="calendar-grid calendar-grid-labels">
            <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
        </div>
        <div class="calendar-grid calendar-grid-month">
            <?php for ($blank = 1; $blank < $firstDay; $blank++): ?>
                <div class="calendar-cell is-empty"></div>
            <?php endfor; ?>
            <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                <?php $dayItems = $calendarMap[$day] ?? []; ?>
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
    </div>
</section>

<script type="application/json" data-item-store="calendar"><?= json_encode($modalItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/item-modal.php'; ?>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
