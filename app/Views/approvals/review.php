<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$latestFile = $files[0] ?? null;
$subtitle = 'Review the content exactly once, then approve it or request changes in plain language.';
$pageActions = [
    ['label' => 'Back to Approvals', 'href' => $config['app']['base_url'] . '/index.php?route=approvals', 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$wizardSteps = ['Review', 'Decision'];
$wizardCurrentStep = 1;
require dirname(__DIR__) . '/partials/wizard-stepper.php';
?>

<section class="wizard-shell review-shell" data-flow-wizard data-wizard-type="client-review">
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=approval.review.submit" class="card form-card wizard-form" data-wizard-form data-client-guided-review>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Review the content</h3>
                    <p>Check the artwork, title, platform, date, and caption. If something feels off, request changes.</p>
                </div>
            </div>
            <div class="client-review-preview">
                <?php if ($latestFile): ?>
                    <div class="approval-preview approval-preview-image">
                        <?php if (Ui::mediaKind($latestFile['mime_type'] ?? '') === 'video'): ?>
                            <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestFile['id'] ?>" controls playsinline preload="metadata"></video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestFile['id'] ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="summary-grid">
                    <div><span>Title</span><strong><?= htmlspecialchars($item['title']) ?></strong></div>
                    <div><span>Platform</span><strong><?= htmlspecialchars($item['platform']) ?></strong></div>
                    <div><span>Date</span><strong><?= htmlspecialchars($item['scheduled_date']) ?></strong></div>
                    <div><span>Status</span><strong><?= htmlspecialchars($item['status']) ?></strong></div>
                    <div><span>Caption</span><strong><?= htmlspecialchars((string) ($item['caption_en'] ?: 'No caption added yet')) ?></strong></div>
                    <div><span>Client Note</span><strong><?= htmlspecialchars((string) ($item['client_notes'] ?: 'No extra note provided')) ?></strong></div>
                </div>
            </div>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Choose your decision</h3>
                    <p>Approve if it is ready. If not, request changes and leave one short note.</p>
                </div>
            </div>
            <div class="wizard-choice-grid compact">
                <label class="wizard-choice-card">
                    <input type="radio" name="review_action" value="approve" checked>
                    <strong>Approve</strong>
                    <span>The content is ready. The team can continue.</span>
                </label>
                <label class="wizard-choice-card">
                    <input type="radio" name="review_action" value="request_changes">
                    <strong>Request Changes</strong>
                    <span>Something needs to be updated before approval.</span>
                </label>
            </div>
            <div class="selection-chip-row">
                <?php foreach ($quickReasons as $reason): ?>
                    <label class="selection-chip">
                        <input type="radio" name="change_reason" value="<?= htmlspecialchars($reason) ?>">
                        <span><?= htmlspecialchars($reason) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <label>
                <span>Comment</span>
                <textarea name="comment" data-client-review-comment placeholder="Example: Please change the artwork and shorten the caption."></textarea>
                <small class="muted">Only required if you request changes.</small>
            </label>
        </section>

        <div class="wizard-footer">
            <button class="btn btn-secondary" type="button" data-step-back hidden>Back</button>
            <div class="wizard-footer-actions">
                <button class="btn btn-primary" type="button" data-step-next>Next</button>
                <button class="btn btn-success-soft" type="submit" data-step-submit hidden>Send Decision</button>
            </div>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
