<?php

require dirname(__DIR__) . '/partials/header.php';

$subtitle = 'Build multiple social content items through a step-based batch workflow.';
$pageActions = [
    ['label' => 'Back to Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary', 'icon' => 'calendar'],
];
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="wizard-shell" data-wizard>
    <div class="wizard-stepper">
        <button class="wizard-step is-active" type="button" data-step-nav="1"><span>1</span><strong>Setup</strong></button>
        <button class="wizard-step" type="button" data-step-nav="2"><span>2</span><strong>Select Dates</strong></button>
        <button class="wizard-step" type="button" data-step-nav="3"><span>3</span><strong>Channels</strong></button>
        <button class="wizard-step" type="button" data-step-nav="4"><span>4</span><strong>Details</strong></button>
        <button class="wizard-step" type="button" data-step-nav="5"><span>5</span><strong>Review</strong></button>
    </div>

    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.generate" class="card form-card wizard-form" data-wizard-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <input type="hidden" name="selected_dates" value="" data-selected-dates>

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Setup</h3>
                    <p>Select the client, planning month, and assigned employee.</p>
                </div>
            </div>
            <div class="form-grid">
                <label>
                    <span>Client</span>
                    <select name="client_id" required>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= date('n') ?>" required data-wizard-month></label>
                <label><span>Year</span><input type="number" name="year" min="2024" max="2035" value="<?= date('Y') ?>" required data-wizard-year></label>
                <label>
                    <span>Assigned Employee</span>
                    <select name="assigned_employee_id">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Select Dates</h3>
                    <p>Pick one or more dates directly from the planning calendar.</p>
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
        </section>

        <section class="wizard-panel" data-step-panel="3">
            <div class="card-head">
                <div>
                    <h3>Step 3. Per-Date Channels</h3>
                    <p>For each selected date, choose the social channels, post types, and quantities required.</p>
                </div>
            </div>
            <div class="wizard-date-cards" data-wizard-date-cards></div>
        </section>

        <section class="wizard-panel" data-step-panel="4">
            <div class="card-head">
                <div>
                    <h3>Step 4. Shared Content Details</h3>
                    <p>Apply the shared artwork type, recommended size, campaign, caption placeholder, and notes.</p>
                </div>
            </div>
            <div class="form-grid">
                <label>
                    <span>Artwork Type</span>
                    <select name="format" data-artwork-type>
                        <option value="Image">Image</option>
                        <option value="Video">Video</option>
                    </select>
                </label>
                <label>
                    <span>Recommended Size</span>
                    <select name="size" data-artwork-size></select>
                    <small class="muted">Presets follow current platform aspect-ratio guidance for Instagram/Meta, TikTok, and YouTube.</small>
                </label>
                <label><span>Campaign</span><input name="campaign" placeholder="April Product Push"></label>
                <label><span>Content Pillar</span><input name="content_pillar" placeholder="Brand Awareness"></label>
                <label><span>CTA</span><input name="cta" placeholder="Learn More"></label>
                <label><span>Caption Placeholder</span><input name="caption_placeholder" placeholder="Draft caption..."></label>
            </div>
            <label><span>Notes</span><textarea name="notes" placeholder="Internal production notes"></textarea></label>
        </section>

        <section class="wizard-panel" data-step-panel="5">
            <div class="card-head">
                <div>
                    <h3>Step 5. Review</h3>
                    <p>Confirm the selected dates and channel quantities before generation.</p>
                </div>
            </div>
            <div class="wizard-review" data-wizard-review></div>
        </section>

        <div class="wizard-footer">
            <button class="btn btn-secondary" type="button" data-step-back>Back</button>
            <button class="btn btn-primary" type="button" data-step-next>Next</button>
            <button class="btn btn-primary" type="submit" data-step-submit hidden>Generate Calendar Items</button>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
