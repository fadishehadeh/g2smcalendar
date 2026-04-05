<?php

require dirname(__DIR__) . '/partials/header.php';

$draft = $draft ?? [];
$subtitle = 'Create many posts quickly with guided dates, reusable defaults, and a final preview before insert.';
$pageActions = [
    ['label' => 'Calendar Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard.calendar', 'class' => 'btn-secondary', 'icon' => 'calendar'],
    ['label' => 'Back to Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$wizardSteps = ['Setup', 'Dates', 'Channels', 'Defaults', 'Preview'];
$wizardCurrentStep = 1;
require dirname(__DIR__) . '/partials/wizard-stepper.php';
?>

<section class="wizard-shell" data-wizard data-wizard-type="bulk-posts">
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.generate" class="card form-card wizard-form" data-wizard-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <input type="hidden" name="selected_dates" value="<?= htmlspecialchars((string) ($draft['selected_dates'] ?? '')) ?>" data-selected-dates>

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Choose the calendar</h3>
                    <p>Pick the client, month, and the calendar you want these posts to land in.</p>
                </div>
            </div>
            <div class="helper-block">Recommended: choose an existing monthly calendar if it already exists, so the new posts stay grouped correctly.</div>
            <div class="form-grid">
                <label>
                    <span>Client</span>
                    <select name="client_id" required>
                        <option value="">Choose client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>" <?= (string) ($draft['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="muted">Only clients you can access are shown here.</small>
                </label>
                <label>
                    <span>Existing Calendar</span>
                    <select name="calendar_id">
                        <option value="">Create or use by month</option>
                        <?php foreach ($calendars as $calendar): ?>
                            <option value="<?= (int) $calendar['id'] ?>" <?= (string) ($draft['calendar_id'] ?? '') === (string) $calendar['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($calendar['company_name'] . ' - ' . $calendar['title'] . ' (' . $calendar['month'] . '/' . $calendar['year'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="muted">Optional. Leave blank if you want the wizard to create the month automatically.</small>
                </label>
                <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= htmlspecialchars((string) ($draft['month'] ?? date('n'))) ?>" required data-wizard-month></label>
                <label><span>Year</span><input type="number" name="year" min="2024" max="2035" value="<?= htmlspecialchars((string) ($draft['year'] ?? date('Y'))) ?>" required data-wizard-year></label>
                <label><span>Calendar Title</span><input type="text" name="calendar_title" value="<?= htmlspecialchars((string) ($draft['calendar_title'] ?? '')) ?>" placeholder="April 2026 Content Plan"></label>
                <label>
                    <span>Assigned Employee</span>
                    <select name="assigned_employee_id">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>" <?= (string) ($draft['assigned_employee_id'] ?? '') === (string) $employee['id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Pick dates</h3>
                    <p>Select exact dates, or build a repeating weekly structure for the whole month.</p>
                </div>
            </div>
            <div class="wizard-date-header">
                <h3 data-wizard-month-label></h3>
                <div class="page-actions">
                    <button class="icon-btn" type="button" data-wizard-prev><?= \App\Core\Ui::icon('left') ?></button>
                    <button class="icon-btn" type="button" data-wizard-next><?= \App\Core\Ui::icon('right') ?></button>
                </div>
            </div>
            <div class="wizard-calendar" data-wizard-calendar></div>
            <div class="pill-row" data-selected-date-pills></div>
            <div class="wizard-repeat-grid">
                <?php $weekdayLabels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun']; ?>
                <?php foreach ($weekdayLabels as $weekdayValue => $weekdayLabel): ?>
                    <label class="selection-chip">
                        <input type="checkbox" name="repeat_weekdays[]" value="<?= $weekdayValue ?>" <?= in_array((string) $weekdayValue, array_map('strval', (array) ($draft['repeat_weekdays'] ?? [])), true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($weekdayLabel) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="muted">Tip: choose the weekdays you repeat every week, then add any one-off dates manually from the calendar grid.</small>
        </section>

        <section class="wizard-panel" data-step-panel="3">
            <div class="card-head">
                <div>
                    <h3>Step 3. Choose channels and quantities</h3>
                    <p>For each selected date, decide how many pieces are needed by platform and post type.</p>
                </div>
            </div>
            <div class="wizard-date-cards" data-wizard-date-cards></div>
        </section>

        <section class="wizard-panel" data-step-panel="4">
            <div class="card-head">
                <div>
                    <h3>Step 4. Apply shared defaults</h3>
                    <p>Use one set of defaults across all generated posts so the team starts from a clean structure.</p>
                </div>
            </div>
            <div class="form-grid">
                <label>
                    <span>Artwork Type</span>
                    <select name="format" data-artwork-type>
                        <option value="Image" <?= (($draft['format'] ?? 'Image') === 'Image') ? 'selected' : '' ?>>Image</option>
                        <option value="Video" <?= (($draft['format'] ?? '') === 'Video') ? 'selected' : '' ?>>Video</option>
                    </select>
                </label>
                <label>
                    <span>Recommended Size</span>
                    <select name="size" data-artwork-size data-default-value="<?= htmlspecialchars((string) ($draft['size'] ?? '')) ?>"></select>
                    <small class="muted">The size list updates automatically based on the channels you selected.</small>
                </label>
                <label><span>Campaign</span><input name="campaign" value="<?= htmlspecialchars((string) ($draft['campaign'] ?? '')) ?>" placeholder="Summer launch"></label>
                <label><span>Content Pillar</span><input name="content_pillar" value="<?= htmlspecialchars((string) ($draft['content_pillar'] ?? '')) ?>" placeholder="Product education"></label>
                <label><span>CTA</span><input name="cta" value="<?= htmlspecialchars((string) ($draft['cta'] ?? '')) ?>" placeholder="Learn more"></label>
                <label><span>Priority</span><select name="priority"><option <?= (($draft['priority'] ?? 'Normal') === 'Low') ? 'selected' : '' ?>>Low</option><option <?= (($draft['priority'] ?? 'Normal') === 'Normal') ? 'selected' : '' ?>>Normal</option><option <?= (($draft['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High</option></select></label>
                <label><span>Approval Route</span><input name="approval_route" value="<?= htmlspecialchars((string) ($draft['approval_route'] ?? 'Client review')) ?>" placeholder="Client review"></label>
                <label><span>Posting Frequency</span><input name="posting_frequency" value="<?= htmlspecialchars((string) ($draft['posting_frequency'] ?? '3 posts per week')) ?>" placeholder="3 posts per week"></label>
                <label><span>Approval Timeline</span><input name="approval_timeline" value="<?= htmlspecialchars((string) ($draft['approval_timeline'] ?? '48 hours')) ?>" placeholder="48 hours"></label>
                <label><span>Caption Placeholder</span><input name="caption_placeholder" value="<?= htmlspecialchars((string) ($draft['caption_placeholder'] ?? '')) ?>" placeholder="Draft caption starter"></label>
            </div>
            <label><span>Notes</span><textarea name="notes" placeholder="Internal production note or artwork instruction"><?= htmlspecialchars((string) ($draft['notes'] ?? '')) ?></textarea></label>
            <div class="selection-chip-row">
                <label class="selection-chip"><input type="checkbox" name="auto_attach_demo" value="1" <?= !empty($draft['auto_attach_demo']) ? 'checked' : '' ?>><span>Attach demo artwork automatically</span></label>
                <label class="selection-chip"><input type="checkbox" name="submit_after_create" value="1" <?= !empty($draft['submit_after_create']) ? 'checked' : '' ?>><span>Send directly to Pending Approval after create (only when demo artwork is attached)</span></label>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="5">
            <div class="card-head">
                <div>
                    <h3>Step 5. Preview everything</h3>
                    <p>Review the total count and remove anything that looks wrong before the posts are created.</p>
                </div>
            </div>
            <div class="wizard-summary-bar">
                <strong data-generated-total>0 posts</strong>
                <span>Preview updates as you edit dates and channels.</span>
            </div>
            <div class="wizard-review" data-wizard-review></div>
        </section>

        <?php $wizardSubmitLabel = 'Create Posts'; $wizardSaveDraft = true; require dirname(__DIR__) . '/partials/wizard-footer.php'; ?>
    </form>
</section>

<script type="application/json" data-wizard-draft="bulk-posts"><?= json_encode($draft, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
