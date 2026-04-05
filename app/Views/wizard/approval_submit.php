<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$draft = $draft ?? [];
$subtitle = 'Run a quick pre-flight check before a post goes to the client review queue.';
$pageActions = [
    ['label' => 'Back to Item', 'href' => $config['app']['base_url'] . '/index.php?route=calendar.item&item_id=' . (int) $item['id'], 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$wizardSteps = ['Checklist', 'Confirm', 'Preview', 'Submit'];
$wizardCurrentStep = 1;
require dirname(__DIR__) . '/partials/wizard-stepper.php';
$latestFile = $files[0] ?? null;
?>

<section class="wizard-shell" data-flow-wizard data-wizard-type="approval-submit">
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.approval.submit" class="card form-card wizard-form" data-wizard-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Check what is missing</h3>
                    <p>This screen prevents incomplete submissions from reaching the client.</p>
                </div>
            </div>
            <div class="checklist-block">
                <?php foreach ($checklist as $entry): ?>
                    <div class="checklist-row <?= $entry['done'] ? 'is-done' : 'is-missing' ?>">
                        <span><?= $entry['done'] ? 'OK' : 'Missing' ?></span>
                        <strong><?= htmlspecialchars($entry['label']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Confirm the handoff</h3>
                    <p>Make sure the latest version is the one the client should review.</p>
                </div>
            </div>
            <div class="selection-chip-row stack">
                <label class="selection-chip"><input type="checkbox" required <?= $latestFile ? 'checked' : '' ?>><span>I confirmed the latest artwork version is correct</span></label>
                <label class="selection-chip"><input type="checkbox" required <?= trim((string) ($item['caption_en'] ?? '')) !== '' ? 'checked' : '' ?>><span>I checked the title and caption text</span></label>
                <label class="selection-chip"><input type="checkbox" required <?= trim((string) ($item['client_notes'] ?? '')) !== '' ? 'checked' : '' ?>><span>I added the client-facing note if needed</span></label>
            </div>
            <label><span>Submission Note</span><textarea name="submission_note" placeholder="Optional note for the client review handoff"><?= htmlspecialchars((string) ($draft['submission_note'] ?? '')) ?></textarea></label>
        </section>

        <section class="wizard-panel" data-step-panel="3">
            <div class="card-head">
                <div>
                    <h3>Step 3. Preview what the client will see</h3>
                    <p>Review the key client-facing information before you submit.</p>
                </div>
            </div>
            <div class="client-review-preview">
                <?php if ($latestFile): ?>
                    <div class="approval-preview approval-preview-image">
                        <?php if (Ui::mediaKind($latestFile['mime_type'] ?? '') === 'video'): ?>
                            <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestFile['id'] ?>" muted playsinline preload="metadata" controls></video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestFile['id'] ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="summary-grid">
                    <div><span>Title</span><strong><?= htmlspecialchars($item['title']) ?></strong></div>
                    <div><span>Platform</span><strong><?= htmlspecialchars($item['platform']) ?></strong></div>
                    <div><span>Date</span><strong><?= htmlspecialchars($item['scheduled_date']) ?></strong></div>
                    <div><span>Version</span><strong>v<?= (int) $item['version_number'] ?></strong></div>
                    <div><span>Caption</span><strong><?= htmlspecialchars((string) ($item['caption_en'] ?: 'No caption added yet')) ?></strong></div>
                    <div><span>Client Note</span><strong><?= htmlspecialchars((string) ($item['client_notes'] ?: 'No client note added')) ?></strong></div>
                </div>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="4">
            <div class="card-head">
                <div>
                    <h3>Step 4. Submit to client review</h3>
                    <p>This will move the item to <strong>Pending Approval</strong> and notify the client.</p>
                </div>
            </div>
            <div class="summary-grid" data-summary-root></div>
        </section>

        <?php $wizardSubmitLabel = 'Submit for Approval'; $wizardSaveDraft = true; require dirname(__DIR__) . '/partials/wizard-footer.php'; ?>
    </form>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
