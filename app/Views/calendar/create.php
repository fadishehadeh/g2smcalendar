<?php

require dirname(__DIR__) . '/partials/header.php';

$defaults = $defaults ?? [];
$subtitle = 'Create one standalone post and save it directly into an existing client calendar.';
$pageActions = [
    ['label' => 'Back to Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="card form-card">
    <div class="card-head">
        <div>
            <h3>New Post</h3>
            <p>This creates a single calendar item. Use Bulk Wizard only when you want many posts generated at once.</p>
        </div>
    </div>
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.save" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

        <label>
            <span>Client</span>
            <select name="client_id" required>
                <option value="">Choose client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id'] ?>" <?= (string) ($defaults['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Calendar</span>
            <select name="calendar_id" required>
                <option value="">Choose existing calendar</option>
                <?php foreach ($calendars as $calendar): ?>
                    <option value="<?= (int) $calendar['id'] ?>" <?= (string) ($defaults['calendar_id'] ?? '') === (string) $calendar['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($calendar['company_name'] . ' - ' . $calendar['title'] . ' (' . $calendar['month'] . '/' . $calendar['year'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label><span>Title</span><input type="text" name="title" required></label>

        <label>
            <span>Assigned Employee</span>
            <select name="assigned_employee_id">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Platform</span>
            <select name="platform" required>
                <?php foreach ($platforms as $platform): ?>
                    <option value="<?= htmlspecialchars($platform) ?>"><?= htmlspecialchars($platform) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label><span>Scheduled Date</span><input type="date" name="scheduled_date" value="<?= htmlspecialchars((string) ($defaults['scheduled_date'] ?? '')) ?>" required></label>
        <label><span>Scheduled Time</span><input type="time" name="scheduled_time"></label>
        <label><span>Post Type</span><input type="text" name="post_type" placeholder="Instagram Story" required></label>
        <label><span>Format</span><input type="text" name="format" placeholder="Image"></label>
        <label><span>Size</span><input type="text" name="size" placeholder="1080x1920"></label>
        <label><span>Campaign</span><input type="text" name="campaign"></label>
        <label><span>Content Pillar</span><input type="text" name="content_pillar"></label>
        <label><span>CTA</span><input type="text" name="cta"></label>
        <label>
            <span>Status</span>
            <select name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= (($defaults['status'] ?? 'Draft') === $status) ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="field-span-2"><span>Caption (EN)</span><textarea name="caption_en" placeholder="Post caption"></textarea></label>
        <label class="field-span-2"><span>Caption (AR)</span><textarea name="caption_ar" placeholder="Arabic caption"></textarea></label>
        <label class="field-span-2"><span>Hashtags</span><input type="text" name="hashtags" placeholder="#brand #campaign"></label>
        <label class="field-span-2"><span>Internal Notes</span><textarea name="internal_notes" placeholder="Production notes"></textarea></label>
        <label class="field-span-2"><span>Client Notes</span><textarea name="client_notes" placeholder="What the client should know"></textarea></label>
        <label class="field-span-2"><span>Artwork</span><input type="file" name="artwork" accept="image/*,video/*,.pdf,.svg"></label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create Post</button>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
