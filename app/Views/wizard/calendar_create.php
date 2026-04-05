<?php

require dirname(__DIR__) . '/partials/header.php';

$draft = $draft ?? [];
$subtitle = 'Guide your team through one monthly calendar setup with clear assumptions and recommended next steps.';
$pageActions = [
    ['label' => 'Back to Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar', 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$wizardSteps = ['Calendar', 'Creation Mode', 'Plan Assumptions', 'Review'];
$wizardCurrentStep = 1;
require dirname(__DIR__) . '/partials/wizard-stepper.php';
?>

<section class="wizard-shell" data-flow-wizard data-wizard-type="calendar-create">
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.calendar.store" class="card form-card wizard-form" data-wizard-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Calendar basics</h3>
                    <p>Choose the client and month first. This keeps the rest of the workflow focused.</p>
                </div>
            </div>
            <div class="form-grid">
                <label>
                    <span>Client</span>
                    <select name="client_id" required>
                        <option value="">Choose client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>" <?= (string) ($draft['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="muted">The calendar will only be visible to the team members assigned to this client.</small>
                </label>
                <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= htmlspecialchars((string) ($draft['month'] ?? date('n'))) ?>" required></label>
                <label><span>Year</span><input type="number" name="year" min="2024" max="2035" value="<?= htmlspecialchars((string) ($draft['year'] ?? date('Y'))) ?>" required></label>
                <label><span>Calendar Title</span><input type="text" name="title" value="<?= htmlspecialchars((string) ($draft['title'] ?? '')) ?>" placeholder="April 2026 Social Calendar" required></label>
                <label><span>Optional Campaign Link</span><input type="text" name="campaign_name" value="<?= htmlspecialchars((string) ($draft['campaign_name'] ?? '')) ?>" placeholder="Spring awareness campaign"></label>
            </div>
            <label><span>Notes</span><textarea name="notes" placeholder="Anything the team should keep in mind for this month"><?= htmlspecialchars((string) ($draft['notes'] ?? '')) ?></textarea></label>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Choose how to start</h3>
                    <p>Pick the easiest starting point instead of building everything manually.</p>
                </div>
            </div>
            <div class="wizard-choice-grid">
                <?php
                $modes = [
                    'blank' => ['label' => 'Blank calendar', 'body' => 'Start with an empty month and add items manually later.'],
                    'template' => ['label' => 'Use template', 'body' => 'Start from a common monthly structure.'],
                    'duplicate_previous' => ['label' => 'Duplicate previous month', 'body' => 'Reuse last month as a starting point.'],
                    'bulk' => ['label' => 'Create with Bulk Wizard', 'body' => 'Create many posts right after the calendar is saved.'],
                ];
                $selectedMode = (string) ($draft['creation_mode'] ?? 'blank');
                ?>
                <?php foreach ($modes as $modeValue => $mode): ?>
                    <label class="wizard-choice-card">
                        <input type="radio" name="creation_mode" value="<?= htmlspecialchars($modeValue) ?>" <?= $selectedMode === $modeValue ? 'checked' : '' ?>>
                        <strong><?= htmlspecialchars($mode['label']) ?></strong>
                        <span><?= htmlspecialchars($mode['body']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($templates !== []): ?>
                <div class="form-grid">
                    <label>
                        <span>Template</span>
                        <select name="template_key">
                            <option value="">No template selected</option>
                            <?php foreach ($templates as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= (string) ($draft['template_key'] ?? '') === (string) $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            <?php endif; ?>
        </section>

        <section class="wizard-panel" data-step-panel="3">
            <div class="card-head">
                <div>
                    <h3>Step 3. Set planning assumptions</h3>
                    <p>These defaults help your team plan faster and reduce back-and-forth later.</p>
                </div>
            </div>
            <div class="form-grid">
                <label><span>Estimated Posting Frequency</span><input type="text" name="posting_frequency" value="<?= htmlspecialchars((string) ($draft['posting_frequency'] ?? '3 posts per week')) ?>" placeholder="3 posts per week"></label>
                <label><span>Approval Timeline</span><input type="text" name="approval_timeline" value="<?= htmlspecialchars((string) ($draft['approval_timeline'] ?? '48 hours')) ?>" placeholder="48 hours"></label>
            </div>
            <div class="selection-chip-row">
                <?php foreach (\App\Models\CalendarItem::PLATFORMS as $platform): ?>
                    <label class="selection-chip">
                        <input type="checkbox" name="primary_platforms[]" value="<?= htmlspecialchars($platform) ?>" <?= in_array($platform, (array) ($draft['primary_platforms'] ?? []), true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($platform) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="4">
            <div class="card-head">
                <div>
                    <h3>Step 4. Review before create</h3>
                    <p>Check the summary, then create the calendar and move directly into the next action.</p>
                </div>
            </div>
            <div class="summary-grid" data-summary-root></div>
        </section>

        <?php $wizardSubmitLabel = 'Create Calendar'; $wizardSaveDraft = true; require dirname(__DIR__) . '/partials/wizard-footer.php'; ?>
    </form>
</section>

<script type="application/json" data-wizard-draft="calendar-create"><?= json_encode($draft, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
